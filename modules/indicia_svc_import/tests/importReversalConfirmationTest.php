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

}
