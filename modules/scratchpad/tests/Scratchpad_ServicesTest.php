<?php

require_once 'client_helpers/data_entry_helper.php';
require_once 'client_helpers/submission_builder.php';

class Scratchpad_ServicesTest extends Indicia_DatabaseTestCase {

  private $auth;
  private $db;

  public function setup() {
    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    $this->db = new Database();
    // make the tokens re-usable
    $this->auth['write_tokens']['persist_auth']=true;
    parent::setup();
  }

  private function submitScratchpad($array, $structure = NULL) {
    if ($structure === NULL) {
      $structure = array(
        'model' => 'scratchpad_list',
        'metaFields' => array('entries'),
      );
    }
    $s = submission_builder::build_submission($array, $structure);
    return data_entry_helper::forward_post_to('scratchpad_list', $s, $this->auth['write_tokens']);
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

  /**
   * Test that a scratchpad can be linked to valid group and location records.
   */
  function testCreateWithGroupAndLocation() {
    $array = array(
      'scratchpad_list:title' => 'Scratchpad with links',
      'scratchpad_list:description' => 'Test linked records',
      'scratchpad_list:entity' => 'taxa_taxon_list',
      'website_id' => 1,
      'metaFields:entries' => '1;2',
      'groups_scratchpad_list:group_id' => 1,
      'locations_scratchpad_list:location_id' => 1,
    );
    $structure = array(
      'model' => 'scratchpad_list',
      'metaFields' => array('entries'),
      'subModels' => array(
        'groups_scratchpad_list' => array('fk' => 'scratchpad_list_id'),
        'locations_scratchpad_list' => array('fk' => 'scratchpad_list_id'),
      ),
    );

    $response = $this->submitScratchpad($array, $structure);
    $this->assertArrayHasKey('success', $response, 'Valid scratchpad links were not saved');
    $listId = $response['success'];

    $groupLink = $this->db->query(
      'SELECT group_id FROM groups_scratchpad_lists
       WHERE scratchpad_list_id=? AND deleted=false',
      [$listId]
    )->current();
    $locationLink = $this->db->query(
      'SELECT location_id FROM locations_scratchpad_lists
       WHERE scratchpad_list_id=? AND deleted=false',
      [$listId]
    )->current();
    $this->assertSame('1', (string) $groupLink->group_id);
    $this->assertSame('1', (string) $locationLink->location_id);
  }

  /**
   * Invalid child links must fail the complete parent save.
   */
  function testRejectsInvalidLocationAssociation() {
    $array = array(
      'scratchpad_list:title' => 'Invalid location scratchpad',
      'scratchpad_list:entity' => 'taxa_taxon_list',
      'website_id' => 1,
      'metaFields:entries' => '1',
      'locations_scratchpad_list:location_id' => 999999,
    );
    $structure = array(
      'model' => 'scratchpad_list',
      'metaFields' => array('entries'),
      'subModels' => array(
        'locations_scratchpad_list' => array('fk' => 'scratchpad_list_id'),
      ),
    );

    $response = $this->submitScratchpad($array, $structure);
    $this->assertArrayHasKey('error', $response, 'An invalid location link was accepted');
    $this->assertSame(
      0,
      (int) $this->db->query(
        'SELECT count(*) AS count FROM scratchpad_lists WHERE title=?',
        [$array['scratchpad_list:title']]
      )->current()->count,
      'The parent scratchpad was not rolled back after an invalid location link'
    );
  }

  /**
   * Invalid group links must also fail the complete parent save.
   */
  function testRejectsInvalidGroupAssociation() {
    $array = array(
      'scratchpad_list:title' => 'Invalid group scratchpad',
      'scratchpad_list:entity' => 'taxa_taxon_list',
      'website_id' => 1,
      'metaFields:entries' => '1',
      'groups_scratchpad_list:group_id' => 999999,
    );
    $structure = array(
      'model' => 'scratchpad_list',
      'metaFields' => array('entries'),
      'subModels' => array(
        'groups_scratchpad_list' => array('fk' => 'scratchpad_list_id'),
      ),
    );

    $response = $this->submitScratchpad($array, $structure);
    $this->assertArrayHasKey('error', $response, 'An invalid group link was accepted');
    $this->assertSame(
      0,
      (int) $this->db->query(
        'SELECT count(*) AS count FROM scratchpad_lists WHERE title=?',
        [$array['scratchpad_list:title']]
      )->current()->count,
      'The parent scratchpad was not rolled back after an invalid group link'
    );
  }

  /**
   * A submitted website must match the authenticated website context.
   */
  function testRejectsOtherWebsite() {
    $array = array(
      'scratchpad_list:title' => 'Other website scratchpad',
      'scratchpad_list:entity' => 'taxa_taxon_list',
      'website_id' => 2,
      'metaFields:entries' => '1',
    );

    $response = $this->submitScratchpad($array);
    $this->assertArrayHasKey('error', $response, 'A scratchpad for another website was accepted');
    $this->assertSame(
      0,
      (int) $this->db->query(
        'SELECT count(*) AS count FROM scratchpad_lists WHERE title=?',
        [$array['scratchpad_list:title']]
      )->current()->count
    );
  }

  /**
   * Updating with no entries soft-deletes all existing entries.
   */
  function testUpdateWithNoEntries() {
    $array = array(
      'scratchpad_list:title' => 'Empty scratchpad',
      'scratchpad_list:entity' => 'taxa_taxon_list',
      'website_id' => 1,
      'metaFields:entries' => '1;2',
    );
    $createResponse = $this->submitScratchpad($array);
    $this->assertArrayHasKey('success', $createResponse);
    $listId = $createResponse['success'];

    $array['scratchpad_list:id'] = $listId;
    $array['metaFields:entries'] = '';
    $updateResponse = $this->submitScratchpad($array);
    $this->assertArrayHasKey('success', $updateResponse, 'An empty scratchpad update failed');

    $entries = $this->db->query(
      'SELECT count(*) AS count FROM scratchpad_list_entries
       WHERE scratchpad_list_id=? AND deleted=false',
      [$listId]
    )->current();
    $this->assertSame('0', (string) $entries->count);
  }

  /**
   * Entry metadata is stored and malformed metadata rejects the update.
   */
  function testEntryMetadataValidation() {
    $array = array(
      'scratchpad_list:title' => 'Metadata scratchpad',
      'scratchpad_list:entity' => 'taxa_taxon_list',
      'website_id' => 1,
      'metaFields:entries' => '1;2',
      'metaFields:entries_metadata' => json_encode(array(
        '1' => array('source' => 'first'),
        '2' => array('source' => 'second'),
      )),
    );
    $structure = array(
      'model' => 'scratchpad_list',
      'metaFields' => array('entries', 'entries_metadata'),
    );
    $createResponse = $this->submitScratchpad($array, $structure);
    $this->assertArrayHasKey('success', $createResponse);
    $listId = $createResponse['success'];

    $metadata = $this->db->query(
      'SELECT metadata FROM scratchpad_list_entries
       WHERE scratchpad_list_id=? AND entry_id=1',
      [$listId]
    )->current()->metadata;
    $this->assertSame(array('source' => 'first'), json_decode($metadata, TRUE));

    $array['scratchpad_list:id'] = $listId;
    $array['scratchpad_list:title'] = 'Metadata scratchpad unchanged';
    $array['metaFields:entries_metadata'] = '{invalid';
    $response = $this->submitScratchpad($array, $structure);
    $this->assertArrayHasKey('error', $response, 'Malformed entry metadata was accepted');
    $this->assertSame(
      'Metadata scratchpad',
      $this->db->query(
        'SELECT title FROM scratchpad_lists WHERE id=?',
        [$listId]
      )->current()->title,
      'The scratchpad update was not rolled back after malformed metadata'
    );
  }

}