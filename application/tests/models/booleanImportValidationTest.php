<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

class Models_Boolean_Import_Validation_Test extends Indicia_DatabaseTestCase {

  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  public function testOccurrenceBooleanImportFieldsRejectBlankValues(): void {
    $model = ORM::factory('occurrence');
    $validation = new Validation([
      'sample_id' => 1,
      'website_id' => 1,
      'taxa_taxon_list_id' => 1,
      'confidential' => '',
      'deleted' => '',
      'zero_abundance' => '',
      'training' => '',
    ]);

    $model->validate($validation);

    foreach (['confidential', 'deleted', 'zero_abundance', 'training'] as $field) {
      $this->assertSame('bool', $validation->errors()[$field]);
    }
  }

  public function testSampleBooleanImportFieldsRejectBlankValues(): void {
    $model = ORM::factory('sample');
    $validation = new Validation([
      'deleted' => '',
      'training' => '',
    ]);

    $model->validate($validation);

    foreach (['deleted', 'training'] as $field) {
      $this->assertSame('bool', $validation->errors()[$field]);
    }
  }

}
