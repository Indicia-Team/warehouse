<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Models_Taxon_Test extends Indicia_DatabaseTestCase {

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

  /**
   * A test for keeping occurrence data in sync if taxon key changes.
   */
  public function testTaxonKeyChange() {
    // Add an occurrence.
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
      'occAttr:1' => 'Test recorder',
    ];
    $structure = [
      'model' => 'sample',
      'subModels' => [
        'occurrence' => ['fk' => 'sample_id'],
      ],
    ];
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Adding a sample and occurrence did not return a success response.');
    $sampleId = $r['success'];
    $occId = ORM::factory('occurrence', ['sample_id' => $sampleId])->id;
    // Check cache occurrences taxon external key.
    $currentKey = $this->db->query("SELECT taxa_taxon_list_external_key FROM cache_occurrences_functional WHERE id=$occId")->current()->taxa_taxon_list_external_key;
    $this->assertEquals('TESTKEY', $currentKey);
    // Change TVK of the recently recorded taxon.
    $array = [
      'taxa_taxon_list:id' => 1,
      'taxa_taxon_list:preferred' => 't',
      'taxon:id' => 1,
      'taxon:external_key' => 'TESTKEYUPDATED',
      'taxon:language_id' => 2,
    ];
    $s = submission_builder::build_submission($array, [
      'model' => 'taxa_taxon_list',
      'superModels' => [
        'taxon' => ['fk' => 'taxon_id'],
      ],
    ]);
    $r = data_entry_helper::forward_post_to('taxa_taxon_list', $s, $this->auth['write_tokens']);
    // Check occurrence taxonomy update work queue task created.
    $wqCount = $this->db->query("SELECT count(*) FROM work_queue WHERE task='task_cache_builder_taxonomy_occurrence' AND entity='taxa_taxon_list' AND record_id=1")->current()->count;
    $this->assertEquals(1, $wqCount, 'Should be a single work queue task to update the altered taxon key in the occurrences cache.');
    // Run work queue
    $q = new WorkQueue();
    $q->process($this->db, TRUE);
    // Work queue task should persist as the cache_taxa_taxon_lists data not updated yet.
    $wqCount = $this->db->query("SELECT count(*) FROM work_queue WHERE task='task_cache_builder_taxonomy_occurrence' AND entity='taxa_taxon_list' AND record_id=1")->current()->count;
    $this->assertEquals(1, $wqCount, 'Work queue should not process occurrence taxonomy until taxon cache updates done');
    // Run cache builder.
    cache_builder::update($this->db, 'taxa_taxon_lists', [1]);
    // Check cached taxon key updated.
    $taxonKey = $this->db->query("SELECT external_key FROM cache_taxa_taxon_lists WHERE id=1")->current()->external_key;
    $this->assertEquals('TESTKEYUPDATED', $taxonKey);
    // Run work queue.
    $q->process($this->db, TRUE);
    // Work queue task should be processed.
    $wqCount = $this->db->query("SELECT count(*) FROM work_queue WHERE task='task_cache_builder_taxonomy_occurrence' AND entity='taxa_taxon_list' AND record_id=1")->current()->count;
    $this->assertEquals(0, $wqCount, 'Work queue should process occurrence taxonomy after taxon cache updates done');
    // Check cache occurrences key updated.
    $currentKey = $this->db->query("SELECT taxa_taxon_list_external_key FROM cache_occurrences_functional WHERE id=$occId")->current()->taxa_taxon_list_external_key;
    $this->assertEquals('TESTKEYUPDATED', $currentKey, 'Occurrence cache taxon key did not update');
  }

}
