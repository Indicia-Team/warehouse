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

  public function testCreateClassifiedOccurrence() {
    Kohana::log('debug', "Running unit test, Controllers_Services_Data_Test::testCreateClassifiedOccurrence");
    $array = [
      'website_id' => 1,
      'survey_id' => 1,
      'sample:entered_sref' => 'SU1234',
      'sample:entered_sref_system' => 'osgb',
      'sample:date' => '02/09/2017',
      'occurrence:taxa_taxon_list_id' => 1,
      'occurrence_medium:path' => 'test_classified_file.jpg',
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

    $classifierTerms = data_entry_helper::get_population_data([
      'table' => 'termlists_term',
      'extraParams' => self::$auth['read'] + [
        'termlist_external_key' => 'indicia:classifiers',
        'term' => 'Unknown',
      ],
    ]);
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
    // Query to check all the data chained together.
    $sql = <<<SQL
select s.id as sample_id,
  o.id as occurrence_id,
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
where s.id=$r[success];
SQL;
    $checkData = self::$db->query($sql)->current();
    $this->assertTrue(!empty($checkData->occurrence_id), 'Classification submission occurrence not created.');
    $this->assertTrue(!empty($checkData->occurrence_medium_id), 'Classification submission occurrence_medium not created.');
    $this->assertTrue(!empty($checkData->classification_event_id), 'Classification submission classification_event not created.');
    $this->assertTrue(!empty($checkData->classification_result_id), 'Classification submission classification_result not created.');
    $this->assertTrue(!empty($checkData->classification_suggestion_id), 'Classification submission classification_suggestion not created.');
    $this->assertTrue(!empty($checkData->classification_results_occurrence_medium_id), 'Classification submission classification_results_occurrence_medium not created.');
    $this->assertTrue(!empty($checkData->crom_om_id), 'Classification submission classification_results_occurrence_medium not linked to media file.');
    $this->assertEquals($checkData->occurrence_medium_id, $checkData->crom_om_id, 'Classification submission mediaPaths linking incorrect.');
    // @todo check determination gets event ID if redetermined.
  }

}
