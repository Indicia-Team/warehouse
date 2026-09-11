<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for species alert model validation.
 *
 * These tests intentionally validate the model directly. The registration
 * service must use this validation before saving an alert.
 */
class SpeciesAlertTest extends TestCase {

  /**
   * A complete alert with a valid external key passes validation.
   */
  public function testValidAlertPassesValidation() {
    $alert = ORM::factory('species_alert');
    $validation = Validation::factory([
      'user_id' => 1,
      'website_id' => 1,
      'external_key' => 'TESTKEY',
      'location_id' => NULL,
      'survey_id' => NULL,
      'taxon_meaning_id' => NULL,
      'taxon_list_id' => NULL,
    ]);

    $this->assertTrue($alert->validate($validation));
  }

  /**
   * Required identity fields and the external-key length limit are enforced.
   */
  public function testInvalidAlertFailsValidation() {
    $alert = ORM::factory('species_alert');
    $validation = Validation::factory([
      'user_id' => NULL,
      'website_id' => NULL,
      'external_key' => str_repeat('x', 51),
      'location_id' => 'not-an-integer',
      'survey_id' => 'not-an-integer',
      'taxon_meaning_id' => 'not-an-integer',
      'taxon_list_id' => 'not-an-integer',
    ]);

    $this->assertFalse($alert->validate($validation));
    $this->assertArrayHasKey('user_id', $validation->errors());
    $this->assertArrayHasKey('website_id', $validation->errors());
    $this->assertArrayHasKey('external_key', $validation->errors());
  }

}
