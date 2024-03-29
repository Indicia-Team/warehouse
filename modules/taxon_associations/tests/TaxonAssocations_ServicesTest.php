<?php

use PHPUnit\DbUnit\DataSet\YamlDataSet as DbUDataSetYamlDataSet;
use PHPUnit\DbUnit\DataSet\CompositeDataSet as DbUDataSetCompositeDataSet;

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

define('CORE_FIXTURE_TERMLIST_COUNT', 4);
define('CORE_FIXTURE_TERM_COUNT', 5);
define('CORE_FIXTURE_TERMLISTS_TERM_COUNT', 5);

class TaxonAssociations_ServicesTest extends Indicia_DatabaseTestCase {

  private $auth;

  public function getDataSet()
  {
    $ds1 = new DbUDataSetYamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    $ds2 = new Indicia_ArrayDataSet(
      [
        'meanings' => [
          [
            'id' => 20000,
          ],
        ],
        'termlists' => [
          [
            'title' => 'Taxon association types',
            'description' => 'Types of associations between taxa',
            'website_id' => 1,
            'created_on' => '2016-07-22 16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22 16:00:00',
            'updated_by_id' => 1,
            'external_key' => NULL,
          ],
        ],
        'terms' => [
          [
            'term' => 'is associated with',
            'language_id' => 1,
            'created_on' => '2016-07-22 16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22 16:00:00',
            'updated_by_id' => 1,
          ],
        ],
        'termlists_terms' => [
          [
            'termlist_id' => CORE_FIXTURE_TERMLIST_COUNT + 1,
            'term_id' => CORE_FIXTURE_TERM_COUNT + 1,
            'created_on' => '2016-07-22 16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22 16:00:00',
            'updated_by_id' => 1,
            'meaning_id' => 20000,
            'preferred' => TRUE,
            'sort_order' => 1,
          ],
        ],
      ]
    );

    $compositeDs = new DbUDataSetCompositeDataSet();
    $compositeDs->addDataSet($ds1);
    $compositeDs->addDataSet($ds2);
    return $compositeDs;
  }

  public function setup() {
    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    $this->auth['write_tokens']['persist_auth'] = true;
    parent::setup();
  }

  function testPost() {
    $array = [
      'taxon_association:from_taxon_meaning_id' => 10000,
      'taxon_association:to_taxon_meaning_id' => 10001,
      'taxon_association:association_type_id' => CORE_FIXTURE_TERMLISTS_TERM_COUNT + 1,
    ];
    $s = submission_builder::build_submission(
      $array, ['model' => 'taxon_association']
    );
    $r = data_entry_helper::forward_post_to(
      'taxon_association', $s, $this->auth['write_tokens']
    );
    Kohana::log(
      'debug',
      "Submission response to taxon_association save " . print_r($r, TRUE)
    );
    $this->assertTrue(
      isset($r['success']),
      'Submitting a taxon_association did not return success response'
    );
  }
}