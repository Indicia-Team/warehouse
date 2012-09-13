<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package  Services
 * @subpackage Data
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

/**
 * Test class for the data cleaner's verification web service.
 *
 * @package Services
 * @subpackage Data Cleaner
 */

require_once 'client_helpers/data_entry_helper.php';

class Controllers_Services_Data_Cleaner_Test extends PHPUnit_Framework_TestCase {
  protected $auth;
  protected $request;
  protected $ttl;
  protected $db;
  
  
  public function setup() {
    $this->auth = data_entry_helper::get_read_write_auth(1, 'password');
    $token=$this->auth['read']['auth_token'];
    $nonce=$this->auth['read']['nonce'];
    $this->request = data_entry_helper::$base_url."index.php/services/data_cleaner/verify?auth_token=$token&nonce=$nonce";
    $this->db = new Database();
    $qry=$this->db->query('select id from taxon_groups limit 1')->result_array(false);
    $taxon_group_id=$qry[0]['id'];
    $species = array(
      'taxon:taxon'=>'test species',
      'taxon:language_id'=>2,
      'taxon:taxon_group_id'=>$taxon_group_id,
      'taxon:external_key'=>'TESTKEY',
      'taxa_taxon_list:taxon_list_id'=>1,
      'taxa_taxon_list:preferred'=>'t'
    );
    $this->ttl = ORM::Factory('taxa_taxon_list');
    $this->ttl->set_submission_data($species, false);
    if (!$this->ttl->submit()) {
      echo kohana::debug($this->ttl->getAllErrors());
      throw new exception('Failed to create species');
    }
    $cache= Cache::instance();
    $cache->delete('data-cleaner-rules');
  }
  
  public function tearDown() {
    // clean up the species we created to test against
    $taxon=$this->ttl->taxon;
    $taxon_meaning=$this->ttl->taxon_meaning;
    $this->ttl->delete();    
    $taxon->delete();
    $taxon_meaning->delete();
  }
  
  /**
   * A quick check that the functionality to report errors if the parameters are incomplete or wrong works.
   */
  public function testIncorrectParams() {
    $token=$this->auth['read']['auth_token'];
    $nonce=$this->auth['read']['nonce'];
    $request = data_entry_helper::$base_url."index.php/services/data_cleaner/verify?auth_token=$token&nonce=$nonce";
    $response = data_entry_helper::http_post($this->request, null);
    $this->assertEquals($response['output'], 'Invalid parameters');
  }
  
  /** 
   * Test that a basic date out of range test works.
   */
  public function testDateOutOfRange() {
    // Need a test rule we can use to check it works
    $ruleArr=array(
      'verification_rule:title'=>'test',
      'verification_rule:test_type'=>'PeriodWithinYear',
      'verification_rule:error_message'=>'test error',
      'metaFields:metadata'=>"Tvk=TESTKEY\nStartDate=0801\nEndDate=0831",
      'metaFields:data'=>""
    );
    $rule = ORM::Factory('verification_rule');
    $rule->set_submission_data($ruleArr, false);
    if (!$rule->submit()) {
      echo kohana::debug($rule->getAllErrors());
      throw new exception('Failed to create test rule');
    }
    try {
      $response = data_entry_helper::http_post($this->request, array(
        'sample'=>json_encode(array(
          'sample:survey_id'=>1,
          'sample:date'=>'12/09/2012', 
          'sample:entered_sref'=>'SU1234',
          'sample:entered_sref_system'=>'osgb'
        )),
        'occurrences'=>json_encode(array(
          array(
            'occurrence:taxa_taxon_list_id'=>$this->ttl->id
          ))
        ),
        'rule_types'=>json_encode(array('PeriodWithinYear'))
      ));
      $errors = json_decode($response['output'], true);
      $this->assertTrue(is_array($errors), 'Errors list not returned');
      $this->assertTrue(isset($errors[0]['taxa_taxon_list_id']) && $errors[0]['taxa_taxon_list_id']===$this->ttl->id, 'Incorrect taxa_taxon_list_id returned');
      $this->assertTrue(isset($errors[0]['message']) && $errors[0]['message']==='test error', 'Incorrect message returned');
      foreach($rule->verification_rule_metadata as $m)
        $m->delete();
      $rule->delete();
    } catch (Exception $e) {
      foreach($rule->verification_rule_metadata as $m)
        $m->delete();
      $rule->delete();
    }
  }
}