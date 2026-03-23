<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Models_Attribute_Encryption_Test extends Indicia_DatabaseTestCase {

  protected $auth;

  protected $db;

  protected $originalEncryptionKey;

  protected $originalEncryptionKeyId;

  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  public function setup(): void {
    parent::setUp();

    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    $this->auth['write_tokens']['persist_auth'] = TRUE;
    $this->db = new Database();
    $this->originalEncryptionKey = Kohana::config('indicia.attribute_encryption_key');
    $this->originalEncryptionKeyId = Kohana::config('indicia.attribute_encryption_key_id');
    Kohana::config_set('indicia.attribute_encryption_key', 'base64:' . base64_encode(str_repeat('a', 32)));
    Kohana::config_set('indicia.attribute_encryption_key_id', 'test-key');
  }

  public function tearDown(): void {
    Kohana::config_set('indicia.attribute_encryption_key', $this->originalEncryptionKey);
    Kohana::config_set('indicia.attribute_encryption_key_id', $this->originalEncryptionKeyId);
    parent::tearDown();
  }

  public function testCannotToggleEncryptWhenAttributeHasValues() {
    $attr = ORM::factory('location_attribute', 2);
    $attr->set_submission_data([
      'location_attribute:id' => 2,
      'location_attribute:encrypt' => '1',
    ]);
    $result = $attr->submit();

    $this->assertNull($result, 'Attribute update should fail when toggling encrypt with existing values.');
    $errors = $attr->getAllErrors();
    $this->assertArrayHasKey('location_attribute:encrypt', $errors, 'Encrypt toggle failure should be attached to the encrypt field.');
    $stored = $this->db->query('SELECT encrypt FROM location_attributes WHERE id=2')->current()->encrypt;
    $this->assertEquals('f', $stored, 'Encrypt flag should remain unchanged in database after failed update.');
  }

  public function testEncryptedAttributeValuesAreStoredEncrypted() {
    $this->db->query('UPDATE occurrence_attributes SET encrypt=true WHERE id=1');

    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
      'occAttr:1' => 'Sensitive value',
    ];
    $structure = [
      'model' => 'sample',
      'subModels' => [
        'occurrence' => ['fk' => 'sample_id'],
      ],
    ];
    $submission = submission_builder::build_submission($array, $structure);
    $response = data_entry_helper::forward_post_to('sample', $submission, $this->auth['write_tokens']);
var_export($response);
    $this->assertArrayHasKey('success', $response, 'Sample/occurrence submission did not succeed.');
    $sampleId = (int) $response['success'];
    $occurrenceId = (int) ORM::factory('occurrence', ['sample_id' => $sampleId])->id;
    $stored = $this->db
      ->query('SELECT text_value FROM occurrence_attribute_values WHERE occurrence_id=? AND occurrence_attribute_id=1', [$occurrenceId])
      ->current()->text_value;

    $this->assertNotEquals('Sensitive value', $stored, 'Stored value should not remain plaintext.');
    $this->assertStringStartsWith('enc:v1:', $stored, 'Stored value should use the versioned encrypted payload format.');
  }

}
