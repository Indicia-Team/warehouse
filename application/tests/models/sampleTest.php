<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Models_Sample_Test extends Indicia_DatabaseTestCase {

  protected $auth;

  protected $db;

  public function getDataSet() {
    $ds1 = new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  public function setup(): void {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();

    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    $this->auth['write_tokens']['persist_auth'] = TRUE;

    $this->db = new Database();
  }

  public function testSampleParentChildTraining() {
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'smpAttr:1' => 45,
      'training' => 't',
    ];
    $structure = [
      'model' => 'sample',
    ];
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);

    $this->assertTrue(isset($r['success']), 'Adding a sample did not return a success response.');
    $parentSampleId = $r['success'];
    unset($array['training']);
    $array['parent_id'] = $parentSampleId;
    // Test attaching a child sample.
    $array['smpAttr:1'] = 50;
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Adding a child sample did not return a success response.');
    $parentIsTraining = $this->db->query("SELECT training FROM samples WHERE id=$parentSampleId")->current()->training === 't';
    $childSample = $this->db->query("SELECT id, training FROM samples WHERE parent_id=$parentSampleId")->current()->training === 't';
    $this->assertTrue($parentIsTraining, 'Parent sample is not marked as training.');
    $this->assertEquals('t', $childSample->training, 'Child sample is not marked as training.');
    // Test attribute attachment, so we can be sure that triggers did not mess with LASTVAL().
    $parentAltitude = $this->db->query("SELECT int_value FROM sample_attribute_values WHERE sample_id=$parentSampleId AND attribute_id=1")->current()->int_value;
    $childAltitude = $this->db->query("SELECT int_value FROM sample_attribute_values WHERE sample_id=$childSample->id AND attribute_id=1")->current()->int_value;
    $this->assertEquals(45, $parentAltitude, 'Parent sample attribute not saved correctly.');
    $this->assertEquals(50, $childAltitude, 'Child sample attribute not saved correctly.');
  }

}
