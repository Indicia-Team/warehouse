<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for scheduled task notification escalation helper logic.
 */
class Helper_Scheduled_Tasks_Priority_Test extends TestCase {

  /**
   * Reflection class for Scheduled_Tasks_Controller.
   *
   * @var ReflectionClass
   */
  private ReflectionClass $reflectionClass;

  /**
   * Reflection object for Scheduled_Tasks_Controller instance methods.
   *
   * @var object
   */
  private object $controllerObject;

  public function setUp(): void {
    if (!class_exists('Scheduled_Tasks_Controller', FALSE)) {
      require_once 'application/controllers/scheduled_tasks.php';
    }
    $this->reflectionClass = new ReflectionClass('Scheduled_Tasks_Controller');
    // Avoid constructor and base class runtime dependencies.
    $this->controllerObject = $this->reflectionClass->newInstanceWithoutConstructor();
  }

  public function testNormaliseEscalatePriorityReturnsNullForEmpty() {
    $method = $this->reflectionClass->getMethod('normaliseEscalatePriority');
    $method->setAccessible(TRUE);
    $this->assertNull($method->invoke($this->controllerObject, ''));
  }

  public function testNormaliseEscalatePriorityBoundsToOneOrTwo() {
    $method = $this->reflectionClass->getMethod('normaliseEscalatePriority');
    $method->setAccessible(TRUE);
    $this->assertSame(1, $method->invoke($this->controllerObject, '1'));
    $this->assertSame(2, $method->invoke($this->controllerObject, '2'));
    $this->assertSame(2, $method->invoke($this->controllerObject, '9'));
  }

  public function testGetEscalatePriorityFromDataFindsMaximum() {
    $method = $this->reflectionClass->getMethod('getEscalatePriorityFromData');
    $method->setAccessible(TRUE);

    $headings = ['event', 'escalate_email_priority', 'other'];
    $allowedData = [
      1 => [
        ['a', '1', 'x'],
        ['b', '2', 'y'],
      ],
      2 => [
        ['c', '1', 'z'],
      ],
    ];

    $this->assertSame(2, $method->invoke($this->controllerObject, $allowedData, $headings));
  }

  public function testRemoveEscalatePriorityColumnRemovesHeaderAndValues() {
    $method = $this->reflectionClass->getMethod('removeEscalatePriorityColumn');
    $method->setAccessible(TRUE);

    $headings = ['event', 'escalate_email_priority', 'other'];
    $websiteRecordData = [
      1 => [
        ['event-1', '2', 'other-1'],
      ],
    ];

    [$cleanHeadings, $cleanData] = $method->invoke($this->controllerObject, $headings, $websiteRecordData);

    $this->assertSame(['event', 'other'], array_values($cleanHeadings));
    $this->assertSame(['event-1', 'other-1'], array_values($cleanData[1][0]));
  }

}
