<?php

use PHPUnit\Framework\TestCase;

/**
 * Import recovery coverage using isolated controller and database doubles.
 *
 * Run standalone with --no-configuration; no warehouse connection is needed.
 */
class ImportRecoveryTest extends TestCase {

  private function runScenario(array $scenario): array {
    $directory = sys_get_temp_dir() . '/indicia-import-recovery-' . bin2hex(random_bytes(12));
    $this->assertTrue(mkdir($directory . '/import', 0700, TRUE));
    try {
      $process = proc_open(
        [PHP_BINARY, '-d', 'disable_functions=pg_escape_identifier', __DIR__ . '/fixtures/importRecovery.php'],
        [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
        $pipes
      );
      $this->assertIsResource($process);
      fwrite($pipes[0], json_encode($scenario + ['directory' => $directory], JSON_THROW_ON_ERROR));
      fclose($pipes[0]);
      $output = stream_get_contents($pipes[1]);
      $errors = stream_get_contents($pipes[2]);
      fclose($pipes[1]);
      fclose($pipes[2]);
      $this->assertSame(0, proc_close($process), $errors . $output);
      $this->assertSame('', $errors);
      return json_decode($output, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    finally {
      $configFile = $directory . '/import/recovery.json';
      if (file_exists($configFile)) {
        unlink($configFile);
      }
      rmdir($directory . '/import');
      rmdir($directory);
    }
  }

  public function testRecoveryDownloadIncludesImportedAndUnimportedRowsWithMatchingHeaders(): void {
    $result = $this->runScenario(['action' => 'export', 'options' => ['include-all-rows' => 'true']]);

    $this->assertSame([
      ['Comment', '[Row no.]', '[Errors]', '[Imported]'],
      ['saved', '2', '', 'yes'],
      ['pending', '3', '', 'no'],
      ['invalid', '4', '{"comment":"invalid"}', 'no'],
    ], $result['response']);
    $this->assertStringNotContainsString('WHERE', $result['queries'][0]);
  }

  public function testExistingErrorDownloadStillExcludesSuccessfulRows(): void {
    $result = $this->runScenario(['action' => 'export']);

    $this->assertSame([
      ['Comment', '[Row no.]', '[Errors]'],
      ['invalid', '4', '{"comment":"invalid"}'],
    ], $result['response']);
  }

  public function testExistingCrashDownloadStillIncludesPendingRowsAndImportStatus(): void {
    $result = $this->runScenario(['action' => 'export', 'options' => ['include-not-imported' => 'true']]);

    $this->assertSame([
      ['Comment', '[Row no.]', '[Errors]', '[Imported]'],
      ['pending', '3', '', 'no'],
      ['invalid', '4', '{"comment":"invalid"}', 'no'],
    ], $result['response']);
  }

  public function testIncompleteCompletionLogsStagingCountsAndPreservesRecoveryData(): void {
    $result = $this->runScenario(['action' => 'finish']);
    [$level, $message] = $result['logs'][0];
    $summary = json_decode(substr($message, strlen('Import chunk finished: ')), TRUE, 512, JSON_THROW_ON_ERROR);

    $this->assertSame('done', $result['response']['status']);
    $this->assertTrue($result['configRetained']);
    $this->assertSame('error', $level);
    $this->assertSame('no-parent-rows', $summary['completionReason']);
    $this->assertSame(200, $summary['rowsProcessed']);
    $this->assertSame(249, $summary['staging']['total_rows']);
    $this->assertSame(49, $summary['staging']['pending_import_rows']);
    $this->assertSame(42, $summary['activeParentBefore']);
    $this->assertSame(42, $summary['activeParentAfter']);
    $this->assertStringNotContainsString('not-for-logs', $message);
  }

  public function testOrdinaryChunksDoNotRunAdditionalStagingCountQueries(): void {
    $result = $this->runScenario(['action' => 'finish', 'parents' => [['comment' => 'sample']]]);

    $this->assertSame('importing', $result['response']['status']);
    $this->assertSame([], $result['queries']);
    $this->assertSame('info', $result['logs'][0][0]);
  }

  public function testStalledContinuationLogsPendingRowsEvenBeforeCompletion(): void {
    $result = $this->runScenario([
      'action' => 'finish',
      'parents' => [['comment' => 'sample']],
      'start' => 200,
    ]);

    $this->assertSame('importing', $result['response']['status']);
    $this->assertSame('error', $result['logs'][0][0]);
    $this->assertStringContainsString('"pending_import_rows":49', $result['logs'][0][1]);
  }

  /**
   * @dataProvider completedPhases
   */
  public function testCompletePhasesLogCountsBeforeExistingCleanup(bool $precheck): void {
    $result = $this->runScenario([
      'action' => 'finish',
      'processed' => 249,
      'precheck' => $precheck,
      'counts' => [
        'total_rows' => 249,
        'imported_rows' => $precheck ? 0 : 249,
        'checked_rows' => 249,
        'error_rows' => 0,
        'pending_import_rows' => $precheck ? 249 : 0,
        'pending_check_rows' => 0,
      ],
    ]);

    $this->assertSame('done', $result['response']['status']);
    $this->assertSame('info', $result['logs'][0][0]);
    $this->assertSame($precheck, $result['configRetained']);
    $this->assertStringContainsString('COUNT(*)', $result['queries'][0]);
    if (!$precheck) {
      $this->assertStringContainsString('DROP TABLE', $result['queries'][1]);
    }
  }

  public static function completedPhases(): array {
    return [
      'import' => [FALSE],
      'precheck' => [TRUE],
    ];
  }

}
