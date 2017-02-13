<?php

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Scratchpad_ServicesTest extends Indicia_DatabaseTestCase {

  private $auth;

  public function setup() {
    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    // make the tokens re-usable
    $this->auth['write_tokens']['persist_auth']=true;
    parent::setup();
  }

  private function doTestCreate($array) {
    $s = submission_builder::build_submission($array, array(
      'model' => 'scratchpad_list', 'metaFields' => array('entries')
    ));
    $c = data_entry_helper::forward_post_to('scratchpad_list', $s, $this->auth['write_tokens']);
    Kohana::log('debug', "Submission response to scratchpad_list save " . print_r($c, TRUE));
    $this->assertTrue(isset($c['success']), 'Submitting a scratchpad_list did not return success response');
    return isset($c['success']) ? $c['success'] : false;
  }

  private function doTestRead($array, $listId) {
    $r = data_entry_helper::get_population_data(array(
      'table' => 'scratchpad_list',
      'extraParams' => $this->auth['read'] + array('id' => $listId)
    ));
    Kohana::log('debug', "Response to scratchpad_list read " . print_r($r, TRUE));
    $this->assertFalse(isset($r['error']), 'Error returned when reading saved scratchpad list');
    $this->assertEquals(count($r), 1, 'Failed to read single saved scratchpad list');
    $this->assertEquals($array['scratchpad_list:title'], $r[0]['title'], 'Scratchpad read response wrong');
    $this->assertEquals($array['scratchpad_list:description'], $r[0]['description'], 'Scratchpad read response wrong');
    $this->assertEquals($array['scratchpad_list:entity'], $r[0]['entity'], 'Scratchpad read response wrong');
    $r = data_entry_helper::get_population_data(array(
      'table' => 'scratchpad_list_entry',
      'extraParams' => $this->auth['read'] + array('scratchpad_list_id' => $listId, 'orderby' => 'entry_id')
    ));
    Kohana::log('debug', "Response to scratchpad_list_entry read " . print_r($r, TRUE));
    $this->assertFalse(isset($r['error']), 'Error returned when reading saved scratchpad list entries');
    $this->assertEquals(count($r), 2, 'Failed to read 2 saved scratchpad list entries');
    $this->assertEquals($r[0]['entry_id'], 1, 'Scratchpad list entry 1 wrong');
    $this->assertEquals($r[0]['entity'], 'taxa_taxon_list', 'Scratchpad list entry 1 wrong');
    $this->assertEquals($r[1]['entry_id'], 2, 'Scratchpad list entry 2 wrong');
    $this->assertEquals($r[1]['entity'], 'taxa_taxon_list', 'Scratchpad list entry 2 wrong');
  }

  private function doTestUpdate($array, $listId) {
    $array['scratchpad_list:id'] = $listId;
    $array['scratchpad_list:title'] = $array['scratchpad_list:title'] . ' UPDATED';
    $array['metaFields:entries'] = '1;3';
    $s = submission_builder::build_submission($array, array(
      'model' => 'scratchpad_list', 'metaFields' => array('entries')
    ));
    $c = data_entry_helper::forward_post_to('scratchpad_list', $s, $this->auth['write_tokens']);
    Kohana::log('debug', "Submission response to scratchpad_list update " . print_r($c, TRUE));
    $this->assertTrue(isset($c['success']), 'Update a scratchpad_list did not return success response');
    $r = data_entry_helper::get_population_data(array(
      'table' => 'scratchpad_list',
      'extraParams' => $this->auth['read'] + array('id' => $listId)
    ));
    $this->assertFalse(isset($r['error']), 'Error returned when reading updated scratchpad list');
    $this->assertEquals(count($r), 1, 'Failed to read single update scratchpad list');
    $this->assertEquals($array['scratchpad_list:title'], $r[0]['title'], 'Updated scratchpad read title wrong');
    $r = data_entry_helper::get_population_data(array(
      'table' => 'scratchpad_list_entry',
      'extraParams' => $this->auth['read'] + array('scratchpad_list_id' => $listId, 'orderby' => 'entry_id')
    ));
    Kohana::log('debug', "Response to scratchpad_list_entry read " . print_r($r, TRUE));
    $this->assertFalse(isset($r['error']), 'Error returned when reading updated scratchpad list entries');
    $this->assertEquals(count($r), 2, 'Failed to read 2 update scratchpad list entries');
    $this->assertEquals($r[0]['entry_id'], 1, 'Updated scratchpad list entry 1 wrong');
    $this->assertEquals($r[0]['entity'], 'taxa_taxon_list', 'Scratchpad list entry 1 wrong');
    $this->assertEquals($r[1]['entry_id'], 3, 'Updated scratchpad list entry 3 wrong');
    $this->assertEquals($r[1]['entity'], 'taxa_taxon_list', 'Scratchpad list entry 3 wrong');
  }

  private function doTestDelete($array, $listId) {
    $s = submission_builder::build_submission(array('id' => $listId, 'deleted' => 't'), array(
      'model' => 'scratchpad_list', 'metaFields' => array('entries')
    ));
    $d = data_entry_helper::forward_post_to('scratchpad_list', $s, $this->auth['write_tokens']);
    Kohana::log('debug', "Submission response to scratchpad_list delete " . print_r($d, TRUE));
    print_r($d);
    $this->assertTrue(isset($d['success']), 'Delete a scratchpad_list did not return success response');
    $r = data_entry_helper::get_population_data(array(
      'table' => 'scratchpad_list',
      'extraParams' => $this->auth['read'] + array('id' => $listId)
    ));
    $this->assertFalse(isset($r['error']), 'Error returned when reading deleted scratchpad list');
    $this->assertEquals(count($r), 0, 'Failed to read zero scratchpad lists after delete');
    $r = data_entry_helper::get_population_data(array(
      'table' => 'scratchpad_list_entry',
      'extraParams' => $this->auth['read'] + array('scratchpad_list_id' => $listId)
    ));
    $this->assertFalse(isset($r['error']), 'Error returned when reading deleted scratchpad list entries');
    $this->assertEquals(count($r), 0, 'Failed to read zero scratchpad list entries after delete');
  }

  /**
   * Test Create, Read, Update and Delete
   */
  function testCRUD() {
    $array = array(
      'scratchpad_list:title' => 'Test scratchpad title',
      'scratchpad_list:description' => 'Test scratchpad description',
      'scratchpad_list:entity' => 'taxa_taxon_list',
      'website_id' => 1,
      'metaFields:entries' => '1;2'
    );
    // wrap C, R, U and D tests into one test method as they are interdependent
    $listId = $this->doTestCreate($array);
    if (!$listId)
      return; // no point continuing
    $this->doTestRead($array, $listId);
    $this->doTestUpdate($array, $listId);
    $this->doTestDelete($array, $listId);
  }

}