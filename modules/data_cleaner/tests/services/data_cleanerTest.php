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

/**
 * These are required to ensure that the PDO object in the class is able to work correctly
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class Controllers_Services_Data_Cleaner_Test extends Indicia_DatabaseTestCase {
  
  protected $request;
  
  /**
   * @return PHPUnit_Extensions_Database_DataSet_IDataSet
   */
  public function getDataSet()
  {
    $ds1 =  new PHPUnit_Extensions_Database_DataSet_YamlDataSet('modules/phpUnit/config/core_fixture.yaml');
   
    // Create a rule to test against
    $ds2 = new Indicia_ArrayDataSet(
      array(
        'verification_rules' => array(
          array(
            'title' => 'Test PeriodWithinYear rule',
            'description' => 'Test rule for unit testing',
            'test_type' => 'PeriodWithinYear',
            'error_message' => 'PeriodWithinYear test failed',
            'source_url' => null,
            'source_filename' => null,
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
            'reverse_rule' => 'F',
          ),
        ),
        'verification_rule_metadata' => array(
          array(
            'verification_rule_id' => '1',
            'key' => 'Tvk',
            'value' => 'TESTKEY',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
          ),
          array(
            'verification_rule_id' => '1',
            'key' => 'StartDate',
            'value' => '0801',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
          ),
          array(
            'verification_rule_id' => '1',
            'key' => 'EndDate',
            'value' => '0831',
            'created_on' => '2016-07-22:16:00:00',
            'created_by_id' => 1,
            'updated_on' => '2016-07-22:16:00:00',
            'updated_by_id' => 1,
          ),
        ),
      )  
    );
    
    $compositeDs = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet();
    $compositeDs->addDataSet($ds1);
    $compositeDs->addDataSet($ds2); 
    return $compositeDs;
  }

  public function setUp() {
    // Calling parent::setUp() will build the database fixture.
    parent::setUp();
    
    $auth = data_entry_helper::get_read_auth(1, 'password');
    $token = $auth['auth_token'];
    $nonce = $auth['nonce'];
    $this->request = data_entry_helper::$base_url .
            "index.php/services/data_cleaner/verify?auth_token=$token&nonce=$nonce";
        
    $cache = Cache::instance();
    $cache->delete('data-cleaner-rules');
  }
  
  /**
   * A quick check that the functionality to report errors if the parameters are
   * incomplete or wrong works.
   */
  public function testIncorrectParams() {
    $response = data_entry_helper::http_post($this->request, null);
    $this->assertEquals($response['output'], 'Invalid parameters');
  }

  /** 
   * PeriodWithinYear Rule.
   * Check that a date out of range is identified as an error.
   * data_cleaner_period_within_year module must be enabled.
   */
  public function testPeriodWithinYearFail() {
    $response = data_entry_helper::http_post($this->request, array(
      'sample'=>json_encode(array(
        'sample:survey_id'=>1,
        'sample:date'=>'12/09/2012', 
        'sample:entered_sref'=>'SU1234',
        'sample:entered_sref_system'=>'osgb'
      )),
      'occurrences'=>json_encode(array(
        array(
          'occurrence:taxa_taxon_list_id'=>1
        ))
      ),
      'rule_types'=>json_encode(array('PeriodWithinYear'))
    ));
    $errors = json_decode($response['output'], true);
    
    $this->assertTrue($response['result'], 'Invalid response');
    $this->assertInternalType('array', $errors, 'Errors list not returned');
    $this->assertEquals(1, count($errors), 'Errors list empty. Is the data_cleaner_period_within_year module installed?');
    $this->assertArrayHasKey('taxa_taxon_list_id', $errors[0], 'Errors list missing taxa_taxon_list_id');
    $this->assertEquals('1', $errors[0]['taxa_taxon_list_id'], 'Incorrect taxa_taxon_list_id returned');
    $this->assertArrayHasKey('message', $errors[0], 'Errors list missing message');
    $this->assertEquals('PeriodWithinYear test failed', $errors[0]['message'], 'Incorrect message returned');
  }
  
  /** 
   * PeriodWithinYear Rule.
   * Check that a date in range is identified as okay.
   * data_cleaner_period_within_year module must be enabled.
   */
  public function testPeriodWithinYearPass() {
    $response = data_entry_helper::http_post($this->request, array(
      'sample'=>json_encode(array(
        'sample:survey_id'=>1,
        'sample:date'=>'12/08/2012', 
        'sample:entered_sref'=>'SU1234',
        'sample:entered_sref_system'=>'osgb'
      )),
      'occurrences'=>json_encode(array(
        array(
          'occurrence:taxa_taxon_list_id'=>1
        ))
      ),
      'rule_types'=>json_encode(array('PeriodWithinYear'))
    ));
    $errors = json_decode($response['output'], true);
    
    $this->assertTrue($response['result'], 'Invalid response');
    $this->assertInternalType('array', $errors, 'Errors list not returned');
    $this->assertCount(0, $errors, 'Errors contanied in list');
  }
  
}