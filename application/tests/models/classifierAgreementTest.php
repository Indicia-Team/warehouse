<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

class Models_Classifier_Agreement_Test extends Indicia_DatabaseTestCase {

  protected $db;

  public function getDataSet() {
    return new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
  }

  public function setUp(): void {
    parent::setUp();
    $this->db = new Database();
  }

  public function testClassifierAgreementUpdateCombinesDefaultsAndSuggestions() {
    $mediaIds = [];
    $resultIds = [];
    foreach ([1, 2, 3] as $occurrenceId) {
      $mediaIds[$occurrenceId] = $this->createMediaRecord($occurrenceId);
      $resultIds[$occurrenceId] = $this->createClassificationResult();
      $link = ORM::factory('classification_results_occurrence_medium');
      $link->classification_result_id = $resultIds[$occurrenceId];
      $link->occurrence_media_id = $mediaIds[$occurrenceId];
      $link->set_metadata();
      $link->save();
    }

    $this->createSuggestion($resultIds[1], 1, TRUE);
    $this->createSuggestion($resultIds[2], 2, TRUE);

    $this->runClassifierAgreementUpdate([1, 2, 3]);

    $agreement = $this->db->query(
      'SELECT id, classifier_agreement FROM cache_occurrences_functional WHERE id IN (1, 2, 3) ORDER BY id'
    )->result();

    $this->assertTrue($agreement[0]->classifier_agreement === 't', 'A matching chosen suggestion should set agreement true.');
    $this->assertTrue($agreement[1]->classifier_agreement === 'f', 'A nonmatching chosen suggestion should retain disagreement.');
    $this->assertTrue($agreement[2]->classifier_agreement === 'f', 'Classifier results without suggestions should default to disagreement.');
  }

  private function runClassifierAgreementUpdate(array $occurrenceIds) {
    $query = kohana::config('cache_builder.occurrences.update.functional_classification');
    $idList = implode(',', $occurrenceIds);
    $query = str_replace(
      ['#join_needs_update#', '#occurrence_ids#'],
      ['', "o.id IN ($idList)"],
      $query
    );
    $this->db->query($query);
  }

  private function createMediaRecord($occurrenceId) {
    $media = ORM::factory('occurrence_medium');
    $media->submission = [
      'id' => 'occurrence_medium',
      'fields' => [
        'website_id' => ['value' => 1],
        'occurrence_id' => ['value' => $occurrenceId],
        'path' => ['value' => "classifier-agreement-$occurrenceId.jpg"],
      ],
    ];
    return (int) $media->submit();
  }

  private function createClassificationResult() {
    $classifierId = (int) $this->db->query('SELECT min(id) AS id FROM termlists_terms')->current()->id;
    $event = ORM::factory('classification_event');
    $event->created_by_id = 1;
    $event->set_metadata();
    $event->save();

    $result = ORM::factory('classification_result');
    $result->classification_event_id = $event->id;
    $result->classifier_id = $classifierId;
    $result->classifier_version = '1.0';
    $result->set_metadata();
    $result->save();
    return (int) $result->id;
  }

  private function createSuggestion($resultId, $taxaTaxonListId, $classifierChosen) {
    $suggestion = ORM::factory('classification_suggestion');
    $suggestion->classification_result_id = $resultId;
    $suggestion->taxon_name_given = 'Classifier suggestion';
    $suggestion->taxa_taxon_list_id = $taxaTaxonListId;
    $suggestion->probability_given = 0.8;
    $suggestion->classifier_chosen = $classifierChosen;
    $suggestion->set_metadata();
    $suggestion->save();
  }

}