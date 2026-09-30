<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the work queue load policy without depending on the host's current load.
 */
class Helper_Work_Queue_Load_Test extends TestCase {

  /**
   * Work queue instance used for private policy methods.
   *
   * @var WorkQueue
   */
  private WorkQueue $queue;

  /**
   * Reflection handle for WorkQueue private methods.
   *
   * @var ReflectionClass
   */
  private ReflectionClass $reflectionClass;

  public function setUp(): void {
    if (!class_exists('WorkQueue', FALSE)) {
      require_once 'application/libraries/WorkQueue.php';
    }
    $this->reflectionClass = new ReflectionClass('WorkQueue');
    $this->queue = $this->reflectionClass->newInstanceWithoutConstructor();
  }

  public function testCostLimitsAreUnrestrictedBelowHalfLoad() {
    $this->assertSame([1 => 100, 2 => 100, 3 => 100], $this->costLimits(0));
    $this->assertSame([1 => 100, 2 => 100, 3 => 100], $this->costLimits(50));
  }

  public function testCostLimitsReduceAsLoadRises() {
    $this->assertSame([1 => 68, 2 => 52, 3 => 42], $this->costLimits(66));
    $limits = $this->costLimits(80);
    $this->assertSame([1 => 40, 2 => 19, 3 => 11], $limits);
    $this->assertLessThan($this->costLimits(66)[1], $limits[1]);
    $this->assertLessThan($this->costLimits(66)[2], $limits[2]);
    $this->assertLessThan($this->costLimits(66)[3], $limits[3]);
  }

  public function testPriorityOneRetainsCheapWorkAtSaturation() {
    $this->assertSame([1 => 10, 2 => 0, 3 => 0], $this->costLimits(95));
    $this->assertSame([1 => 10, 2 => 0, 3 => 0], $this->costLimits(100));
  }

  public function testUtilisationIsClamped() {
    $this->assertSame($this->costLimits(0), $this->costLimits(-10));
    $this->assertSame($this->costLimits(100), $this->costLimits(150));
  }

  public function testPriorityOrderingIsPreserved() {
    foreach ([0, 66, 80, 95] as $utilisation) {
      $limits = $this->costLimits($utilisation);
      $this->assertGreaterThanOrEqual($limits[2], $limits[1]);
      $this->assertGreaterThanOrEqual($limits[3], $limits[2]);
    }
  }

  public function testPolicyIsBoundedAndMonotonicAcrossTheRange() {
    $previous = $this->costLimits(0);
    for ($utilisation = 0; $utilisation <= 100; $utilisation++) {
      $limits = $this->costLimits($utilisation);
      foreach ($limits as $limit) {
        $this->assertGreaterThanOrEqual(0, $limit);
        $this->assertLessThanOrEqual(100, $limit);
      }
      if ($utilisation > 0) {
        $this->assertLessThanOrEqual($previous[1], $limits[1]);
        $this->assertLessThanOrEqual($previous[2], $limits[2]);
        $this->assertLessThanOrEqual($previous[3], $limits[3]);
      }
      $previous = $limits;
    }
  }

  public function testRequestLimitsCanOnlyRestrictCosts() {
    $method = $this->reflectionClass->getMethod('applyRequestLimits');
    $method->setAccessible(TRUE);
    $base = [1 => 80, 2 => 60, 3 => 40];
    $this->assertSame([1 => 25, 2 => 25, 3 => 25], $method->invoke($this->queue, $base, ['max-cost' => '25']));
    $this->assertSame([1 => 80, 2 => 60, 3 => 0], $method->invoke($this->queue, $base, ['max-priority' => '2']));
    $this->assertSame([1 => 0, 2 => 0, 3 => 40], $method->invoke($this->queue, $base, ['min-priority' => '3']));
    $this->assertSame([1 => 0, 2 => 25, 3 => 0], $method->invoke($this->queue, $base, [
      'max-cost' => '25',
      'max-priority' => '2',
      'min-priority' => '2',
    ]));
  }

  public function testRequestLimitValidationRejectsInvalidValues() {
    $method = $this->reflectionClass->getMethod('applyRequestLimits');
    $method->setAccessible(TRUE);
    $this->expectException(Exception::class);
    $method->invoke($this->queue, [1 => 100, 2 => 100, 3 => 100], ['max-cost' => '101']);
  }

  public function testLinuxCounterParserRejectsMalformedInput() {
    $method = $this->reflectionClass->getMethod('parseLinuxCpuCounters');
    $method->setAccessible(TRUE);
    $this->assertSame([
      'total' => 900,
      'idle' => 700,
    ], $method->invoke($this->queue, "cpu 100 100 100 600\n"));
    $this->assertNull($method->invoke($this->queue, 'not cpu data'));
  }

  public function testCgroupUsageParserSupportsV1AndV2AndRejectsInvalidData() {
    $method = $this->reflectionClass->getMethod('readLinuxCgroupUsage');
    $method->setAccessible(TRUE);
    $this->assertSame(123000.0, $method->invoke($this->queue, '123', 0.001, NULL));
    $this->assertSame(456.0, $method->invoke($this->queue, "usage_usec 456\nuser_usec 12\n", 1, 'usage_usec'));
    $this->assertNull($method->invoke($this->queue, 'not numeric', 0.001, NULL));
    $this->assertNull($method->invoke($this->queue, 'user_usec 12', 1, 'usage_usec'));
  }

  public function testCgroupArithmeticHandlesQuotaAndInvalidDeltas() {
    $method = $this->reflectionClass->getMethod('calculateCgroupCpuUtilisation');
    $method->setAccessible(TRUE);
    $this->assertSame(50.0, $method->invoke($this->queue, 1000000, 2000000, 4, 0.5));
    $this->assertSame(50.0, $method->invoke($this->queue, 1000000, 2000000, 2, 2, 1));
    $this->assertNull($method->invoke($this->queue, 2000000, 1000000, 2, 1));
    $this->assertNull($method->invoke($this->queue, 1000000, 2000000, 0, 1));
    $this->assertNull($method->invoke($this->queue, 1000000, 2000000, 2, 0));
  }

  public function testInvalidMetricValuesFallBackToZeroAndStayBounded() {
    $method = $this->reflectionClass->getMethod('normaliseServerLoad');
    $method->setAccessible(TRUE);
    $this->assertSame(0.0, $method->invoke($this->queue, 'invalid'));
    $this->assertSame(0.0, $method->invoke($this->queue, -1));
    $this->assertSame(100.0, $method->invoke($this->queue, 101));
  }

  public function testMacAndWindowsParsersValidateOutput() {
    $macMethod = $this->reflectionClass->getMethod('parseMacCpuIdle');
    $macMethod->setAccessible(TRUE);
    $windowsMethod = $this->reflectionClass->getMethod('parseWindowsCpuLoad');
    $windowsMethod->setAccessible(TRUE);
    $this->assertSame(66.0, $macMethod->invoke($this->queue, ['CPU usage: 20% user, 14% sys, 66% idle']));
    $this->assertNull($macMethod->invoke($this->queue, ['invalid output']));
    $this->assertSame(34.0, $windowsMethod->invoke($this->queue, ['34'], 0));
    $this->assertSame(0.0, $windowsMethod->invoke($this->queue, [], 1));
  }

  public function testCpuSetParserCountsRanges() {
    $method = $this->reflectionClass->getMethod('parseCpuSetCount');
    $method->setAccessible(TRUE);
    $this->assertSame(5, $method->invoke($this->queue, '0-3,6'));
    $this->assertNull($method->invoke($this->queue, '3-1'));
    $this->assertNull($method->invoke($this->queue, ''));
  }

  private function costLimits($utilisation) {
    $method = $this->reflectionClass->getMethod('calculateMaxCostByPriority');
    $method->setAccessible(TRUE);
    return $method->invoke($this->queue, $utilisation);
  }

}