<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the host form's confirmation before it sends a Warehouse request.
 */
class ImportReversalConfirmationTest extends TestCase {

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testConfirmationIsBoundToTheWorkflowAndCanOnlyBeUsedOnce(): void {
    require_once 'client_helpers/import_helper_2.php';
    function hostsite_get_user_field($field) {
      return $GLOBALS['reversalTestUser'];
    }

    $GLOBALS['reversalTestUser'] = 7;
    $this->assertTrue(session_start(['use_cookies' => 0, 'cache_limiter' => '']));
    try {
      $issue = new ReflectionMethod(import_helper_2::class, 'issueReversalConfirmation');
      $consume = new ReflectionMethod(import_helper_2::class, 'consumeReversalConfirmation');
      $options = [
        'entity' => 'occurrence',
        'importReverse' => 'import_and_reverse_via_query',
        'initServerConfigUrl' => '/import-form/1',
        'website_id' => 1,
      ];
      $guid = 'AAAAAAAA-AAAA-4AAA-AAAA-AAAAAAAAAAAA';
      $_SERVER['REQUEST_METHOD'] = 'POST';
      $_POST = ['reverse-guid' => $guid, 'reverse-mode' => 'do_not_reverse_updated'];
      $this->assertNull($consume->invoke(NULL, $options), 'A direct POST lacks a confirmation.');

      $token = $issue->invoke(NULL, $options, $guid, FALSE);
      $_POST['reverse-confirmation'] = $token;
      $pending = $_SESSION['indicia:reversal-confirmations'];
      $_SESSION['indicia:reversal-confirmations'] = [];
      $this->assertNull($consume->invoke(NULL, $options), 'A different session cannot use the token.');
      $_SESSION['indicia:reversal-confirmations'] = $pending;

      $this->assertNull($consume->invoke(NULL, array_replace($options, ['importReverse' => 'import'])));
      $this->assertNull($consume->invoke(NULL, array_replace($options, ['entity' => 'sample'])));
      $this->assertNull($consume->invoke(NULL, array_replace($options, ['initServerConfigUrl' => '/import-form/2'])));
      $GLOBALS['reversalTestUser'] = 8;
      $this->assertNull($consume->invoke(NULL, $options));
      $GLOBALS['reversalTestUser'] = 7;

      $validPost = $_POST;
      foreach ([
        ['reverse-guid' => 'BBBBBBBB-BBBB-4BBB-BBBB-BBBBBBBBBBBB'],
        ['reverse-guid' => [$guid]],
        ['reverse-mode' => 'reverse_all'],
        ['reverse-mode' => ['do_not_reverse_updated']],
      ] as $invalid) {
        $_POST = array_replace($validPost, $invalid);
        $this->assertNull($consume->invoke(NULL, $options));
      }
      $_POST = $validPost;
      $_SERVER['REQUEST_METHOD'] = 'GET';
      $this->assertNull($consume->invoke(NULL, $options));
      $_SERVER['REQUEST_METHOD'] = 'POST';
      $this->assertSame([$guid, 'do_not_reverse_updated'], $consume->invoke(NULL, $options));
      $this->assertNull($consume->invoke(NULL, $options), 'The confirmation cannot be replayed.');

      $_POST['reverse-mode'] = 'reverse_all';
      $_POST['reverse-confirmation'] = $issue->invoke(NULL, $options, $guid, TRUE);
      $this->assertSame([$guid, 'reverse_all'], $consume->invoke(NULL, $options));

      $_POST['reverse-mode'] = 'abort_reverse';
      $_POST['reverse-confirmation'] = $issue->invoke(NULL, $options, $guid, TRUE);
      $this->assertSame([$guid, 'abort_reverse'], $consume->invoke(NULL, $options));
      $this->assertNull($consume->invoke(NULL, $options));

      $expired = $issue->invoke(NULL, $options, $guid, FALSE);
      $_SESSION['indicia:reversal-confirmations'][$expired]['expires'] = time() - 1;
      $_POST['reverse-confirmation'] = $expired;
      $this->assertNull($consume->invoke(NULL, $options));
    }
    finally {
      session_destroy();
    }
  }

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testConfirmationRendersReportFieldsAsText(): void {
    require_once 'client_helpers/import_helper_2.php';
    function hostsite_get_user_field($field) {
      return 7;
    }
    function iform_load_helpers($helpers) {
    }
    class_alias(ReversalConfirmationReportStub::class, 'report_helper');

    $this->assertTrue(session_start(['use_cookies' => 0, 'cache_limiter' => '']));
    try {
      $guid = 'AAAAAAAA-AAAA-4AAA-AAAA-AAAAAAAAAAAA';
      $render = new ReflectionMethod(import_helper_2::class, 'reversalModeWarning');
      $options = [
        'entity' => 'occurrence',
        'importReverse' => 'import_and_reverse_via_query',
        'initServerConfigUrl' => '/import-form/1',
        'website_id' => 1,
        'readAuth' => [],
      ];
      $list = new ReflectionMethod(import_helper_2::class, 'getReversibleImports');
      $list->invoke(NULL, $options);
      $html = $render->invoke(NULL, $options, $guid);
      $this->assertStringContainsString('<dd>&lt;b&gt;import&lt;/b&gt;</dd>', $html);
      $this->assertStringContainsString('<dd>&lt;i&gt;date &amp; time&lt;/i&gt;</dd>', $html);
      $this->assertStringContainsString("name=\"reverse-guid\" value=\"$guid\"", $html);
      $requests = ReversalConfirmationReportStub::$requests;
      $this->assertSame('library/imports/reversible_imports_list', $requests[0]['dataSource']);
      $this->assertSame(['currentUser' => 7, 'website_id' => 1], $requests[0]['extraParams']);
      $this->assertSame(array_replace($requests[0], [
        'extraParams' => $requests[0]['extraParams'] + ['import_guid' => $guid],
      ]), $requests[1]);
    }
    finally {
      session_destroy();
    }
  }

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testReversalOnlyImporterDoesNotOfferUploadSteps(): void {
    require_once 'client_helpers/import_helper_2.php';
    function hostsite_get_user_field($field) {
      return 7;
    }
    function iform_load_helpers($helpers) {
    }
    class_alias(ReversalConfirmationReportStub::class, 'report_helper');
    ReversalConfirmationReportStub::$imports = [];

    $options = [
      'entity' => 'occurrence',
      'importReverse' => 'reverse',
      'readAuth' => ['nonce' => 'read'],
      'writeAuth' => ['nonce' => 'write'],
      'advancedFields' => [],
    ] + array_fill_keys([
      'uploadFileUrl', 'sendFileToWarehouseUrl', 'extractFileOnWarehouseUrl',
      'initServerConfigUrl', 'loadChunkToTempTableUrl', 'preprocessUrl',
      'processLookupMatchingUrl', 'saveLookupMatchesGroupUrl', 'importChunkUrl',
      'getErrorFileUrl',
    ], '/import-form/1');
    $_GET = $_POST = [];
    $html = import_helper_2::importer($options);
    $this->assertStringContainsString('There are no previous imports available for you to reverse', $html);
    $this->assertStringNotContainsString('file-upload-form', $html);
    $_POST['next-import-step'] = 'mappingsForm';
    $html = import_helper_2::importer($options);
    $this->assertStringContainsString('This form only allows import reversal.', $html);
  }

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  public function testTasksRenderEscapedStatusAndSeparatedDownloadActions(): void {
    require_once 'client_helpers/import_helper_2.php';
    $render = new ReflectionMethod(import_helper_2::class, 'renderReversalTasks');
    $token = str_repeat('a', 64);
    import_helper_2::$base_url = 'https://warehouse.example/records&data/';
    $_GET = ['reverse_import_guid' => 'old-selection', 'q' => 'node/1'];
    $html = $render->invoke(NULL, [[
      'status' => 'queued',
      'import_guid' => '<b>import</b>',
      'queued_on' => '2026-11-20 08:00:00',
      'export_token' => $token,
    ]]);
    $this->assertStringContainsString('<td>&lt;b&gt;import&lt;/b&gt;</td>', $html);
    $this->assertStringContainsString('<td>2026-11-20 08:00:00</td>', $html);
    $this->assertStringContainsString('<td>Queued</td>', $html);
    $this->assertStringContainsString('?q=node%2F1&amp;reversal_tasks=1', $html);
    $this->assertStringNotContainsString('old-selection', $html);
    $this->assertStringContainsString('method="post" action="https://warehouse.example/records&amp;data/index.php/services/import_2/download_reversal_export"', $html);
    $this->assertStringContainsString("name=\"reversal_export_token\" value=\"$token\"", $html);
    $this->assertStringContainsString('not an audit snapshot', $html);
    $this->assertMatchesRegularExpression('/value="untouched_samples".*<\/td><td>.*value="untouched_occurrences"/s', $html);
  }

}

class ReversalConfirmationReportStub {

  public static array $requests = [];

  public static array $imports = [[
    'import_guid' => '<b>import</b>',
    'import_date_time' => '<i>date & time</i>',
    'inserted' => 1,
    'remaining' => 1,
  ]];

  public static function get_report_data(array $options): array {
    self::$requests[] = $options;
    return $options['dataSource'] === 'library/imports/reversible_imports_list' ? self::$imports : [];
  }

}
