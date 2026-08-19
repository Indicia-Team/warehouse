<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

class Models_Cache_Builder_Test extends Indicia_DatabaseTestCase {

  protected $db;

  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  public function setUp(): void {
    parent::setUp();
    $this->db = new Database();
  }

  public function testOccurrenceUpdateFillsTaxonPathMediaAndDataCleanerInfo() {
    $this->addTaxonPath();
    $this->addOccurrenceMedia(1);
    $this->prepareDataCleanerInfo(1);

    cache_builder::update($this->db, 'occurrences', [1]);

    $functional = $this->db->query(
      'SELECT taxon_path FROM cache_occurrences_functional WHERE id=1'
    )->current();
    $nonfunctional = $this->db->query(
      'SELECT media, data_cleaner_info FROM cache_occurrences_nonfunctional WHERE id=1'
    )->current();

    $this->assertSame('{10000}', (string) $functional->taxon_path);
    $this->assertSame('cache-builder-update.jpg', (string) $nonfunctional->media);
    $this->assertSame(
      '[cache-builder-test]{Occurrence comment for unit testing}',
      (string) $nonfunctional->data_cleaner_info
    );
  }

  public function testOccurrenceInsertFillsTaxonPathMediaAndDataCleanerInfo() {
    $this->addTaxonPath();
    $this->addOccurrenceMedia(1);
    $this->prepareDataCleanerInfo(1);
    $this->db->query('DELETE FROM cache_occurrences_functional WHERE id=1');
    $this->db->query('DELETE FROM cache_occurrences_nonfunctional WHERE id=1');

    cache_builder::insert($this->db, 'occurrences', [1]);

    $functional = $this->db->query(
      'SELECT taxon_path FROM cache_occurrences_functional WHERE id=1'
    )->current();
    $nonfunctional = $this->db->query(
      'SELECT media, data_cleaner_info FROM cache_occurrences_nonfunctional WHERE id=1'
    )->current();

    $this->assertSame('{10000}', (string) $functional->taxon_path);
    $this->assertSame('cache-builder-update.jpg', (string) $nonfunctional->media);
    $this->assertSame(
      '[cache-builder-test]{Occurrence comment for unit testing}',
      (string) $nonfunctional->data_cleaner_info
    );
  }

  public function testOccurrenceUpdateFallsBackToOccurrenceTaxonListPath() {
    $taxonListId = $this->addNonMasterTaxonList();
    $this->db->query(
      'UPDATE cache_taxa_taxon_lists SET taxon_list_id=? WHERE id=1',
      [$taxonListId]
    );
    $this->db->query(
      "INSERT INTO cache_taxon_paths (taxon_meaning_id, taxon_list_id, external_key, path)
       VALUES (10000, ?, 'TESTKEY', ARRAY[10000]::integer[])",
      [$taxonListId]
    );

    cache_builder::update($this->db, 'occurrences', [1]);

    $functional = $this->db->query(
      'SELECT taxon_path FROM cache_occurrences_functional WHERE id=1'
    )->current();

    $this->assertSame('{10000}', (string) $functional->taxon_path);
  }

  public function testConfidentialOccurrenceRemovesSampleLocationFromFunctionalCache() {
    cache_builder::update($this->db, 'samples', [1]);

    $sample = $this->db->query(
      'SELECT location_id, location_name FROM cache_samples_functional WHERE id=1'
    )->current();

    $this->assertNull($sample->location_id);
    $this->assertNull($sample->location_name);
  }

  private function addTaxonPath() {
    $this->db->query(
      "INSERT INTO cache_taxon_paths (taxon_meaning_id, taxon_list_id, external_key, path)
       VALUES (10000, 1, 'TESTKEY', ARRAY[10000]::integer[])"
    );
  }

  private function addNonMasterTaxonList() {
    return (int) $this->db->query(
      "INSERT INTO taxon_lists
        (title, website_id, created_on, created_by_id, updated_on, updated_by_id)
       VALUES ('Cache builder fallback list', 1, now(), 1, now(), 1)
       RETURNING id"
    )->current()->id;
  }

  private function addOccurrenceMedia($occurrenceId) {
    $media = ORM::factory('occurrence_medium');
    $media->submission = [
      'id' => 'occurrence_medium',
      'fields' => [
        'website_id' => ['value' => 1],
        'occurrence_id' => ['value' => $occurrenceId],
        'path' => ['value' => 'cache-builder-update.jpg'],
      ],
    ];
    $media->submit();
  }

  private function prepareDataCleanerInfo($occurrenceId) {
    $this->db->query(
      'UPDATE occurrences SET last_verification_check_date=now() WHERE id=?',
      [$occurrenceId]
    );
    $this->db->query(
      "UPDATE occurrence_comments
       SET generated_by='cache-builder-test', implies_manual_check_required=true
       WHERE occurrence_id=?",
      [$occurrenceId]
    );
  }

}
