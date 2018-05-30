<?php

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Attribute_setTest extends Indicia_DatabaseTestCase {

  protected $auth;

  /**
   * Retrieve the data set fixture.
   *
   * @return obj
   *   Dataset YAML object.
   */
  public function getDataSet() {
    $ds1 =  new PHPUnit_Extensions_Database_DataSet_YamlDataSet('modules/phpUnit/config/core_fixture.yaml');
    return $ds1;
  }

  /**
   * Set up the test suite.
   */
  public function setup() {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();

    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    // Make the tokens re-usable.
    $this->auth['write_tokens']['persist_auth'] = TRUE;
  }

  /**
   * Utility method to get a response from data services.
   *
   * @param string $url
   *   Web service URL.
   * @param bool $decodeJson
   *   Should the response be decoded if JSON?
   *
   * @return array
   *   Response data.
   */
  private function getResponse($url, $decodeJson = TRUE) {
    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($session);
    // Valid json response will decode.
    if ($decodeJson) {
      $response = json_decode($response, TRUE);
    }
    Kohana::log('debug', "Received response " . print_r($response, TRUE));
    return $response;
  }

  /**
   * Test which runs through a complete attribute set setup scenario.
   *
   * @return void
   */
  public function testCreateAttributeSet() {
    Kohana::log('debug', "Running unit test, Attribute_setTest::testCreateAttributeSet");
    $db = new Database();

    // First create an attribute set.
    $array = array(
      'attribute_set:title' => 'Test attribute set',
      'attribute_set:description' => 'Test attribute set description',
      'attribute_set:website_id' => 1,
    );
    $s = submission_builder::build_submission($array, array('model' => 'attribute_set'));
    $r = data_entry_helper::forward_post_to('attribute_set', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting an attribute_set did not return success response');
    $attributeSetId = $r['success'];
    // Double check it saved OK.
    $as = ORM::Factory('attribute_set', $attributeSetId);
    $this->assertEquals($as->title, 'Test attribute set', 'Attribute set details did not save correctly');

    // Now, create a taxa taxon list attribute to add to the set.
    $array = array(
      'taxa_taxon_list_attribute:caption' => 'Body length',
      'taxa_taxon_list_attribute:data_type' => 'I',
      'taxa_taxon_list_attribute:allow_ranges' => 't',
    );
    $s = submission_builder::build_submission($array, array('model' => 'taxa_taxon_list_attribute'));
    $r = data_entry_helper::forward_post_to('taxa_taxon_list_attribute', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting a taxa_taxon_list_attribute did not return success response');
    $ttlAttributeId = $r['success'];
    $as = ORM::Factory('taxa_taxon_list_attribute', $ttlAttributeId);
    $this->assertEquals($as->allow_ranges, 't', 'Attribute details did not save allow_ranges correctly');

    // Now, join the attribute to the attribute set.
    $array = array(
      'attribute_set_id' => $attributeSetId,
      'taxa_taxon_list_attribute_id' => $ttlAttributeId,
    );
    $s = submission_builder::build_submission($array, array('model' => 'attribute_sets_taxa_taxon_list_attribute'));
    $r = data_entry_helper::forward_post_to('attribute_sets_taxa_taxon_list_attribute', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting an attribute_sets_taxa_taxon_list_attribute did not return success response');

    // Link the attribute set to the test website.
    $array = array(
      'attribute_set_id' => $attributeSetId,
      'survey_id' => 1,
    );
    $s = submission_builder::build_submission($array, array('model' => 'attribute_sets_survey'));
    $r = data_entry_helper::forward_post_to('attribute_sets_survey', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting an attribute_sets_survey did not return success response');
    // Keep so we can delete it later.
    $attributeSetsSurveyId = $r['success'];

    // And create a taxon restriction.
    $array = array(
      'attribute_sets_survey_id' => $r['success'],
      'restrict_to_taxon_meaning_id' => 10001,
    );
    $s = submission_builder::build_submission($array, array('model' => 'attribute_sets_taxon_restriction'));
    $r = data_entry_helper::forward_post_to('attribute_sets_taxon_restriction', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting an attribute_sets_taxon_restriction did not return success response');

    // Now create an occurrence_attributes_taxa_taxon_list_attributes record
    // without filling in the occurrence_attribute_id. A trigger should auto
    // create the occurrence attribute using the taxon attribute as a template
    // then link it to the website/survey/taxon as specified by the attribute
    // set.
    $array = array(
      'taxa_taxon_list_attribute_id' => $ttlAttributeId,
      'restrict_occurrence_attribute_to_single_value' => 't',
    );
    $s = submission_builder::build_submission($array, array('model' => 'occurrence_attributes_taxa_taxon_list_attribute'));
    $r = data_entry_helper::forward_post_to('occurrence_attributes_taxa_taxon_list_attribute', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Submitting an occurrence_attributes_taxa_taxon_list_attribute did not return success response');
    // Retrieve the occurrence_attributes_taxa_taxon_list_attribute that we just posted.
    $oattla = ORM::Factory('occurrence_attributes_taxa_taxon_list_attribute', $r['success']);
    // This should automagically created occurrence attribute.
    $this->assertTrue(!empty($oattla->occurrence_attribute_id), 'Inserting an occurrence_attribute_taxa_taxon_list_attribute ' .
      'did not trigger creation of occurrence_attribute.');
    $this->assertTrue($oattla->restrict_occurrence_attribute_to_single_value === 't',
      'Occurrence_attribute_taxa_taxon_list_attribute insert did not save restrict_occurrence_attribute_to_single_value value correctly.');
    $this->assertTrue($oattla->occurrence_attribute->allow_ranges === 'f',
      'Occurrence_attribute_taxa_taxon_list_attribute insert created occurrence attribute with wrong allow_ranges setting.');

    // Check the occurrence attribute auto-linked to the website because it
    // belongs to an attribute set which links to the website.
    $links = $db->select('count(*)')
      ->from('occurrence_attributes_websites')
      ->where([
        'occurrence_attribute_id' => $oattla->occurrence_attribute_id,
        'deleted' => 'f',
        'website_id' => 1,
        'restrict_to_survey_id' => 1,
      ])
      ->get()->current();
    $this->assertTrue($links->count > 0, 'Insert of occurrence_attributes_taxa_taxon_list_attribute did not cause attribute to auto-link to website/survey');

    // Check the previously created taxon restriction is copied to the
    // occurrence attribute.
    $restrictions = $db->select('count(atr.*)')
      ->from('occurrence_attribute_taxon_restrictions as atr')
      ->join('occurrence_attributes_websites as aw', [
        'aw.id' => 'atr.occurrence_attributes_website_id',
        'aw.deleted' => FALSE,
        'aw.occurrence_attribute_id' => $oattla->occurrence_attribute_id,
      ])
      ->where([
        'atr.deleted' => 'f',
        'website_id' => 1,
        'restrict_to_survey_id' => 1,
      ])
      ->get()->current();
    $this->assertTrue($restrictions->count > 0, 'Insert of occurrence_attributes_taxa_taxon_list_attribute did not cause auto-creation of taxon restriction');

    // Remove the join from the attribute set to the website and check the
    // attribute auto updates.
    $array = array(
      'id' => $attributeSetsSurveyId,
      'deleted' => 't',
    );
    $s = submission_builder::build_submission($array, array('model' => 'attribute_sets_survey'));
    $r = data_entry_helper::forward_post_to('attribute_sets_survey', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Deleting an attribute_sets_survey did not return success response');
    $links = $db->select('count(*)')
      ->from('occurrence_attributes_websites')
      ->where([
        'occurrence_attribute_id' => $oattla->occurrence_attribute_id,
        'deleted' => 'f',
        'website_id' => 1,
        'restrict_to_survey_id' => 1,
      ])
      ->get()->current();
    $this->assertTrue((integer) $links->count === 0, 'Remove the join from the attribute set to the website did not remove the auto-link to website/survey');

    // Check the previously created taxon restriction has also been removed.
    $restrictions = $db->select('count(atr.*)')
      ->from('occurrence_attribute_taxon_restrictions as atr')
      ->join('occurrence_attributes_websites as aw', [
        'aw.id' => 'atr.occurrence_attributes_website_id',
        'aw.deleted' => FALSE,
        'aw.occurrence_attribute_id' => $oattla->occurrence_attribute_id,
      ])
      ->where([
        'atr.deleted' => 'f',
        'website_id' => 1,
        'restrict_to_survey_id' => 1,
      ])
      ->get()->current();
    $this->assertTrue((integer) $restrictions->count === 0, 'Remove the join from the attribute set to the website did not remove the associated taxon restriction');

    // Re-add the attribute set survey link and make sure the occurrence
    // attribute link reappears.
    $array = array(
      'attribute_set_id' => $attributeSetId,
      'survey_id' => 1,
    );
    $s = submission_builder::build_submission($array, array('model' => 'attribute_sets_survey'));
    $r = data_entry_helper::forward_post_to('attribute_sets_survey', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Re-submitting an attribute_sets_survey did not return success response');
    $attributeSetsSurveyId = $r['success'];
    $links = $db->select('count(*)')
      ->from('occurrence_attributes_websites')
      ->where([
        'occurrence_attribute_id' => $oattla->occurrence_attribute_id,
        'deleted' => 'f',
        'website_id' => 1,
        'restrict_to_survey_id' => 1,
      ])
      ->get()->current();
    $this->assertTrue($links->count > 0, 'Undeleting an attribute_sets_survey did not re-instate attribute auto-link to website/survey');

    // If we delete the attribute set, then the associated occurrence attribute
    // links should disappear.
    $array = array(
      'id' => $attributeSetId,
      'deleted' => 't',
    );
    $s = submission_builder::build_submission($array, array('model' => 'attribute_set'));
    $r = data_entry_helper::forward_post_to('attribute_set', $s, $this->auth['write_tokens']);
    $this->assertTrue(isset($r['success']), 'Deleting an attribute_sets_survey did not return success response');
    $links = $db->select('count(*)')
      ->from('occurrence_attributes_websites')
      ->where([
        'occurrence_attribute_id' => $oattla->occurrence_attribute_id,
        'deleted' => 'f',
        'website_id' => 1,
        'restrict_to_survey_id' => 1,
      ])
      ->get()->current();
    $this->assertTrue((integer) $links->count === 0, 'Deleting an attribute_set did not remove the attribute auto-link to website/survey');
  }

}
