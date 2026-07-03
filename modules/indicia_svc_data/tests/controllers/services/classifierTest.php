<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';


class Controllers_Services_Classifier_Test extends Indicia_DatabaseTestCase {

  private static $db;

  private static $auth;

  public function getDataSet() {
    $ds1 =  new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  /**
   * Create additional data used by tests.
   *
   * We can't create users and people in the main fixture, since they are
   * mutually dependent. So create test data here.
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    self::$auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    self::$auth['write_tokens']['persist_auth'] = TRUE;
    self::$db = new Database();
  }

  /**
   * Create a classification event and result row for test setup.
   *
   * @return array
   *   Contains event_id and result_id.
   */
  private function createClassificationResultContext() {
    $termId = (int) self::$db->query('select min(id) as id from termlists_terms')->current()->id;
    $event = ORM::factory('classification_event');
    $event->created_by_id = 1;
    $event->set_metadata();
    $event->save();
    $result = ORM::factory('classification_result');
    $result->classification_event_id = $event->id;
    $result->classifier_id = $termId;
    $result->classifier_version = '1.0';
    $result->set_metadata();
    $result->save();
    return [
      'event_id' => (int) $event->id,
      'result_id' => (int) $result->id,
      'term_id' => $termId,
    ];
  }

  public function testCreateClassifiedOccurrence() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateClassifiedOccurrence");
    // First, build a basic sample -> occurrrence -> occurrence_medium submission.
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
      'occurrence_medium:path' => 'test_classified_file.jpg',
      'occurrence:machine_involvement' => 4,
    ];
    $structure = [
      'model' => 'sample',
      'subModels' => [
        'occurrence' => [
          'fk' => 'sample_id',
          'subModels' => [
            'occurrence_medium' => [
              'fk' => 'occurrence_id',
            ],
          ],
        ],
      ],
    ];
    $s = submission_builder::build_submission($array, $structure);

    // Find the unknown classfier term ID.
    $classifierTerms = data_entry_helper::get_population_data([
      'table' => 'termlists_term',
      'extraParams' => self::$auth['read'] + [
        'termlist_external_key' => 'indicia:classifiers',
        'term' => 'Unknown',
      ],
    ]);
    // Modify the submission to add a classification event, containing a
    // classification result, which contains a suggestion. The event gets
    // attached as a parent (superModel) of the occurrence which is in the
    // sample's subModels array at index 0.
    // Note that the classification result contains a metaField called
    // media_paths - since you don't know the occurrence_media record's ID as
    // it doesn't exist yet, you can use this to tell the warehouse to link to
    // the media file by path instead.
    $s['subModels'][0]['model']['superModels'] = [
      [
        'fkId' => 'classification_event_id',
        'model' => [
          'id' => 'classification_event',
          'fields' => [
            // Need to supply at least 1 field value to save.
            'created_by_id' => 1,
          ],
          'subModels' => [
            [
              'fkId' => 'classification_event_id',
              'model' => [
                'id' => 'classification_result',
                'fields' => [
                  'classifier_id' => $classifierTerms[0]['id'],
                  'classifier_version' => '1.0',
                ],
                'metaFields' => [
                  'mediaPaths' => '["test_classified_file.jpg"]',
                ],
                'subModels' => [
                  [
                    'fkId' => 'classification_result_id',
                    'model' => [
                      'id' => 'classification_suggestion',
                      'fields' => [
                        'taxon_name_given' => 'A suggested name',
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    $r = data_entry_helper::forward_post_to('sample', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a sample did not return success response');
    $sampleId = $r['success'];

    // Now, test the data that were saved.
    // Query to check all the data chained together.
    $sql = <<<SQL
select s.id as sample_id,
  o.id as occurrence_id,
  o.machine_involvement,
  om.id as occurrence_medium_id,
  ce.id as classification_event_id,
  cr.id as classification_result_id,
  cs.id as classification_suggestion_id,
  crom.id as classification_results_occurrence_medium_id,
  crom.occurrence_media_id as crom_om_id
from samples s
left join occurrences o on o.sample_id=s.id and o.deleted=false
left join occurrence_media om on om.occurrence_id=o.id and om.deleted=false
left join classification_events ce on ce.id=o.classification_event_id and ce.deleted=false
left join classification_results cr on cr.classification_event_id=ce.id and cr.deleted=false
left join classification_suggestions cs on cs.classification_result_id=cr.id and cs.deleted=false
left join classification_results_occurrence_media crom on crom.classification_result_id=cr.id
where s.id=?;
SQL;
    $checkData = self::$db->query($sql, [$sampleId])->current();
    // Some assertions to check the data values.
    $this->assertTrue(!empty($checkData->occurrence_id), 'Classification submission occurrence not created.');
    $this->assertEquals(4, $checkData->machine_involvement, 'Classification submission machine_involvement saved incorrectly.');
    $this->assertTrue(!empty($checkData->occurrence_medium_id), 'Classification submission occurrence_medium not created.');
    $this->assertTrue(!empty($checkData->classification_event_id), 'Classification submission classification_event not created.');
    $this->assertTrue(!empty($checkData->classification_result_id), 'Classification submission classification_result not created.');
    $this->assertTrue(!empty($checkData->classification_suggestion_id), 'Classification submission classification_suggestion not created.');
    $this->assertTrue(!empty($checkData->classification_results_occurrence_medium_id), 'Classification submission classification_results_occurrence_medium not created.');
    $this->assertTrue(!empty($checkData->crom_om_id), 'Classification submission classification_results_occurrence_medium not linked to media file.');
    $this->assertEquals($checkData->occurrence_medium_id, $checkData->crom_om_id, 'Classification submission mediaPaths linking incorrect.');

    // If the occurrence is redetermined, the event ID in the occurrence table
    // needs to follow the identification information as it gets copied into
    // the determinations table to keep an audit trail of information.
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'occurrence:id' => $checkData->occurrence_id,
      'occurrence:taxa_taxon_list_id' => 2,
    ];
    $structure = [
      'model' => 'occurrence',
    ];
    // Post a simple redetermination.
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('occurrence', $s, self::$auth['write_tokens']);

    // Now, run a query to check the event ID has gone to the determinations table and is not left in the occurrences table.
    $sql = <<<SQL
select s.id as sample_id,
  o.id as occurrence_id,
  o.machine_involvement,
  o.classification_event_id,
  d.machine_involvement as det_machine_involvement,
  d.classification_event_id as det_classification_event_id
from samples s
left join occurrences o on o.sample_id=s.id and o.deleted=false
left join determinations d on d.occurrence_id=o.id and d.deleted=false
where s.id=?;
SQL;
    $checkPostRedetData = self::$db->query($sql, [$sampleId])->current();
    $this->assertEquals($checkData->machine_involvement, $checkPostRedetData->det_machine_involvement, 'Machine_involvement not copied to determination correctly');
    $this->assertEquals($checkData->classification_event_id, $checkPostRedetData->det_classification_event_id, 'Classification_event_id not copied to determination correctly');
    $this->assertEmpty($checkPostRedetData->machine_involvement, 'Occurrence machine_involvement not cleared after redet');
    $this->assertEmpty($checkPostRedetData->classification_event_id, 'Occurrence classification_event_id not cleared after redet');

    // Also check that these flags can be reset on a subsequent redet.
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'occurrence:id' => $checkData->occurrence_id,
      'occurrence:taxa_taxon_list_id' => 2,
      'occurrence:machine_involvement' => $checkData->machine_involvement,
      'occurrence:classification_event_id' => $checkData->classification_event_id,
    ];
    $structure = [
      'model' => 'occurrence',
    ];
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('occurrence', $s, self::$auth['write_tokens']);
    $sql = <<<SQL
select s.id as sample_id,
  o.id as occurrence_id,
  o.machine_involvement,
  o.classification_event_id,
  d.machine_involvement as det_machine_involvement,
  d.classification_event_id as det_classification_event_id
from samples s
left join occurrences o on o.sample_id=s.id and o.deleted=false
left join determinations d on d.occurrence_id=o.id and d.deleted=false
where s.id=?;
SQL;
    $checkPostRedetData = self::$db->query($sql, [$sampleId])->current();
    $this->assertEquals($checkData->machine_involvement, $checkPostRedetData->machine_involvement, 'Machine_involvement not re-saved after determination correctly');
    $this->assertEquals($checkData->classification_event_id, $checkPostRedetData->classification_event_id, 'Classification_event_id not resaved after determination correctly');
  }

  public function testCreateClassifiedSampleAttributeValue() {
    Kohana::log('debug', 'Running unit test, Controllers_Services_Classifier_Test::testCreateClassifiedSampleAttributeValue');
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU2234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'smpAttr:1' => 100,
    ];
    $s = submission_builder::build_submission($array, ['model' => 'sample']);
    $r = data_entry_helper::forward_post_to('sample', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a sample with attribute did not return success response');
    $sampleId = (int) $r['success'];

    $sampleAttrVal = self::$db->query(
      'select id from sample_attribute_values where sample_id=? and sample_attribute_id=1 and deleted=false limit 1',
      [$sampleId]
    )->current();
    $this->assertTrue(!empty($sampleAttrVal->id), 'Sample attribute value not created.');

    $ctx = $this->createClassificationResultContext();
    $suggestion = ORM::factory('classification_lookup_suggestion');
    $suggestion->classification_result_id = $ctx['result_id'];
    $suggestion->sample_attribute_id = 1;
    $suggestion->term_given = 'Classifier habitat term';
    $suggestion->termlists_term_id = $ctx['term_id'];
    $suggestion->probability_given = 0.8;
    $suggestion->set_metadata();
    $suggestion->save();
    $this->assertTrue(!empty($suggestion->id), 'Sample classification_lookup_suggestion not created.');

    $suggestionCheck = self::$db->query(
      'select sample_attribute_id, occurrence_attribute_id, location_attribute_id from classification_lookup_suggestions where id=?',
      [$suggestion->id]
    )->current();
    $this->assertEquals(1, (int) $suggestionCheck->sample_attribute_id, 'Sample classification_lookup_suggestion sample_attribute_id not saved correctly.');
    $this->assertEquals(NULL, $suggestionCheck->occurrence_attribute_id, 'Sample classification_lookup_suggestion occurrence_attribute_id should be null.');
    $this->assertEquals(NULL, $suggestionCheck->location_attribute_id, 'Sample classification_lookup_suggestion location_attribute_id should be null.');

    self::$db->query(
      'update sample_attribute_values set classification_event_id=?, machine_involvement=? where id=?',
      [$ctx['event_id'], 4, $sampleAttrVal->id]
    );

    $check = self::$db->query(
      'select classification_event_id, machine_involvement from sample_attribute_values where id=?',
      [$sampleAttrVal->id]
    )->current();
    $this->assertEquals($ctx['event_id'], (int) $check->classification_event_id, 'Sample attribute classification_event_id not saved correctly.');
    $this->assertEquals(4, (int) $check->machine_involvement, 'Sample attribute machine_involvement not saved correctly.');
  }

  public function testCreateClassifiedOccurrenceAttributeValue() {
    Kohana::log('debug', 'Running unit test, Controllers_Services_Classifier_Test::testCreateClassifiedOccurrenceAttributeValue');
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU3234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
      'occAttr:1' => 'Classifier target',
    ];
    $structure = [
      'model' => 'sample',
      'subModels' => [
        'occurrence' => ['fk' => 'sample_id'],
      ],
    ];
    $s = submission_builder::build_submission($array, $structure);
    $r = data_entry_helper::forward_post_to('sample', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a sample with occurrence attribute did not return success response');
    $occurrenceId = (int) $r['success'];

    $occAttrVal = self::$db->query(
      'select id from occurrence_attribute_values where occurrence_id=? and occurrence_attribute_id=1 and deleted=false limit 1',
      [$occurrenceId]
    )->current();
    $this->assertTrue(!empty($occAttrVal->id), 'Occurrence attribute value not created.');

    $ctx = $this->createClassificationResultContext();
    $suggestion = ORM::factory('classification_lookup_suggestion');
    $suggestion->classification_result_id = $ctx['result_id'];
    $suggestion->occurrence_attribute_id = 1;
    $suggestion->term_given = 'Classifier occurrence term';
    $suggestion->termlists_term_id = $ctx['term_id'];
    $suggestion->probability_given = 0.7;
    $suggestion->set_metadata();
    $suggestion->save();
    $this->assertTrue(!empty($suggestion->id), 'Occurrence classification_lookup_suggestion not created.');

    $suggestionCheck = self::$db->query(
      'select sample_attribute_id, occurrence_attribute_id, location_attribute_id from classification_lookup_suggestions where id=?',
      [$suggestion->id]
    )->current();
    $this->assertEquals(NULL, $suggestionCheck->sample_attribute_id, 'Occurrence classification_lookup_suggestion sample_attribute_id should be null.');
    $this->assertEquals(1, (int) $suggestionCheck->occurrence_attribute_id, 'Occurrence classification_lookup_suggestion occurrence_attribute_id not saved correctly.');
    $this->assertEquals(NULL, $suggestionCheck->location_attribute_id, 'Occurrence classification_lookup_suggestion location_attribute_id should be null.');

    self::$db->query(
      'update occurrence_attribute_values set classification_event_id=?, machine_involvement=? where id=?',
      [$ctx['event_id'], 3, $occAttrVal->id]
    );

    $check = self::$db->query(
      'select classification_event_id, machine_involvement from occurrence_attribute_values where id=?',
      [$occAttrVal->id]
    )->current();
    $this->assertEquals($ctx['event_id'], (int) $check->classification_event_id, 'Occurrence attribute classification_event_id not saved correctly.');
    $this->assertEquals(3, (int) $check->machine_involvement, 'Occurrence attribute machine_involvement not saved correctly.');
  }

  public function testCreateClassifiedLocationAttributeValue() {
    Kohana::log('debug', 'Running unit test, Controllers_Services_Classifier_Test::testCreateClassifiedLocationAttributeValue');
    $array = [
      'location:name' => 'Classifier location',
      'location:centroid_sref' => 'SU0101',
      'location:centroid_sref_system' => 'osgb',
      'locAttr:1' => 'Classifier location attribute',
    ];
    $s = submission_builder::build_submission($array, ['model' => 'location']);
    $r = data_entry_helper::forward_post_to('location', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a location with attribute did not return success response');
    $locationId = (int) $r['success'];

    $locAttrVal = self::$db->query(
      'select id from location_attribute_values where location_id=? and location_attribute_id=1 and deleted=false limit 1',
      [$locationId]
    )->current();
    $this->assertTrue(!empty($locAttrVal->id), 'Location attribute value not created.');

    $ctx = $this->createClassificationResultContext();
    $suggestion = ORM::factory('classification_lookup_suggestion');
    $suggestion->classification_result_id = $ctx['result_id'];
    $suggestion->location_attribute_id = 1;
    $suggestion->term_given = 'Classifier location term';
    $suggestion->termlists_term_id = $ctx['term_id'];
    $suggestion->probability_given = 0.9;
    $suggestion->set_metadata();
    $suggestion->save();
    $this->assertTrue(!empty($suggestion->id), 'Location classification_lookup_suggestion not created.');

    $suggestionCheck = self::$db->query(
      'select sample_attribute_id, occurrence_attribute_id, location_attribute_id from classification_lookup_suggestions where id=?',
      [$suggestion->id]
    )->current();
    $this->assertEquals(NULL, $suggestionCheck->sample_attribute_id, 'Location classification_lookup_suggestion sample_attribute_id should be null.');
    $this->assertEquals(NULL, $suggestionCheck->occurrence_attribute_id, 'Location classification_lookup_suggestion occurrence_attribute_id should be null.');
    $this->assertEquals(1, (int) $suggestionCheck->location_attribute_id, 'Location classification_lookup_suggestion location_attribute_id not saved correctly.');

    self::$db->query(
      'update location_attribute_values set classification_event_id=?, machine_involvement=? where id=?',
      [$ctx['event_id'], 5, $locAttrVal->id]
    );

    $check = self::$db->query(
      'select classification_event_id, machine_involvement from location_attribute_values where id=?',
      [$locAttrVal->id]
    )->current();
    $this->assertEquals($ctx['event_id'], (int) $check->classification_event_id, 'Location attribute classification_event_id not saved correctly.');
    $this->assertEquals(5, (int) $check->machine_involvement, 'Location attribute machine_involvement not saved correctly.');
  }

  public function testRejectClassificationLookupSuggestionWithoutSingleTargetAttribute() {
    Kohana::log('debug', 'Running unit test, Controllers_Services_Classifier_Test::testRejectClassificationLookupSuggestionWithoutSingleTargetAttribute');
    $classifierTerms = data_entry_helper::get_population_data([
      'table' => 'termlists_term',
      'extraParams' => self::$auth['read'] + [
        'termlist_external_key' => 'indicia:classifiers',
        'term' => 'Unknown',
      ],
    ]);

    // Case 1: no target attribute ID provided.
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU4234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
    ];
    $structure = [
      'model' => 'sample',
      'subModels' => [
        'occurrence' => ['fk' => 'sample_id'],
      ],
    ];
    $s = submission_builder::build_submission($array, $structure);
    $s['subModels'][0]['model']['superModels'] = [
      [
        'fkId' => 'classification_event_id',
        'model' => [
          'id' => 'classification_event',
          'fields' => [
            'created_by_id' => 1,
          ],
          'subModels' => [
            [
              'fkId' => 'classification_event_id',
              'model' => [
                'id' => 'classification_result',
                'fields' => [
                  'classifier_id' => $classifierTerms[0]['id'],
                  'classifier_version' => '1.0',
                ],
                'subModels' => [
                  [
                    'fkId' => 'classification_result_id',
                    'model' => [
                      'id' => 'classification_lookup_suggestion',
                      'fields' => [
                        'term_given' => 'No target term',
                        'probability_given' => 0.5,
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    $r = data_entry_helper::forward_post_to('sample', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['error']), 'Submitting lookup suggestion without target attribute should fail.');

    // Case 2: multiple target attribute IDs provided.
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU5234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
    ];
    $s = submission_builder::build_submission($array, $structure);
    $s['subModels'][0]['model']['superModels'] = [
      [
        'fkId' => 'classification_event_id',
        'model' => [
          'id' => 'classification_event',
          'fields' => [
            'created_by_id' => 1,
          ],
          'subModels' => [
            [
              'fkId' => 'classification_event_id',
              'model' => [
                'id' => 'classification_result',
                'fields' => [
                  'classifier_id' => $classifierTerms[0]['id'],
                  'classifier_version' => '1.0',
                ],
                'subModels' => [
                  [
                    'fkId' => 'classification_result_id',
                    'model' => [
                      'id' => 'classification_lookup_suggestion',
                      'fields' => [
                        'sample_attribute_id' => 1,
                        'occurrence_attribute_id' => 1,
                        'term_given' => 'Multi target term',
                        'probability_given' => 0.5,
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    $r = data_entry_helper::forward_post_to('sample', $s, self::$auth['write_tokens']);
    $this->assertTrue(isset($r['error']), 'Submitting lookup suggestion with multiple target attributes should fail.');

    $insertedCount = (int) self::$db->query(
      "select count(*) as count from classification_lookup_suggestions where term_given in ('No target term', 'Multi target term')"
    )->current()->count;
    $this->assertEquals(0, $insertedCount, 'Invalid lookup suggestions should not be inserted.');
  }

}
