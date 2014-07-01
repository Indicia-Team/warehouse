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
 * @package Services
 * @subpackage Data
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */
 
/**
 * Class providing miscellaneous data utility web services.
 */
class Data_utils_Controller extends Data_Service_Base_Controller {
  
  /**
   * Provides the services/data_utils/bulk_verify service. This takes a report plus params (json object) in the $_POST
   * data and verifies all the records returned by the report according to the filter. Pass ignore=true to allow this to 
   * ignore any verification check rule failures (use with care!).
   */
  public function bulk_verify() {
    $db = new Database();
    $this->authenticate('write');
    $report = $_POST['report'];
    $params = json_decode($_POST['params'], true);
    $params['sharing'] = 'verification';
    $websites = $this->website_id ? array($this->website_id) : null;
    $this->reportEngine = new ReportEngine($websites, $this->user_id);
    try {
      // Load the report used for the verification grid with the same params
      $data=$this->reportEngine->requestReport("$report.xml", 'local', 'xml', $params);
      // now get a list of all the occurrence ids
      $ids = array();
      foreach ($data['content']['records'] as $record) {
        if ($record['record_status']!=='V' && (!empty($record['pass'])||$_POST['ignore']==='true')) {
          $ids[$record['occurrence_id']] = $record['occurrence_id'];
          $db->insert('occurrence_comments', array(
              'occurrence_id'=>$record['occurrence_id'],
              'comment'=>'This record is assumed to be correct',
              'created_by_id'=>$this->user_id,
              'created_on'=>date('Y-m-d H:i:s'),
              'updated_by_id'=>$this->user_id,
              'updated_on'=>date('Y-m-d H:i:s')
          ));
        }
      }
      $db->from('occurrences')->set(array('record_status'=>'V', 'verified_by_id'=>$this->user_id, 'verified_on'=>date('Y-m-d H:i:s'),
          'updated_by_id'=>$this->user_id, 'updated_on'=>date('Y-m-d H:i:s')))->in('id', array_keys($ids))->update();
      echo count($ids);
      // since we bypass ORM here for performance, update the cache_occurrences table.
      $db->from('cache_occurrences')->set(array('record_status'=>'V', 'verified_on'=>date('Y-m-d H:i:s'), 'cache_updated_on'=>date('Y-m-d H:i:s')))->in('id', array_keys($ids))->update();
    } catch (Exception $e) {
      echo $e->getMessage();
      error::log_error('Exception during bulk verify', $e);
    }
  }
  
  /**
   * Provides the services/data_utils/single_verify service. This takes an occurrence:id, occurrence:record_status, user_id (the verifier)
   * and optional occurrence_comment:comment in the $_POST data and updates the record. This is provided as a more optimised
   * alternative to using the normal data services calls. If occurrence:taxa_taxon_list_id is supplied then a redetermination will 
   * get triggered.
   */
  public function single_verify() {
    if (empty($_POST['occurrence:id']) || !preg_match('/^\d+$/', $_POST['occurrence:id']))
      echo 'occurrence:id not supplied or invalid';
    elseif (empty($_POST['occurrence:record_status']) || ($_POST['occurrence:record_status'] !== 'V' && $_POST['occurrence:record_status'] !== 'D' && $_POST['occurrence:record_status'] !== 'R'))
      echo 'occurrence:record_status not supplied or invalid';
    else try {
      $db = new Database();
      $this->authenticate('write');
      $websites = $this->website_id ? array($this->website_id) : null;
      $delta = array('record_status'=>$_POST['occurrence:record_status'], 'verified_by_id'=>$this->user_id, 'verified_on'=>date('Y-m-d H:i:s'),
          'updated_by_id'=>$this->user_id, 'updated_on'=>date('Y-m-d H:i:s'));
      if (!empty($_POST['occurrence:taxa_taxon_list_id']))
        $delta['taxa_taxon_list_id'] = $_POST['occurrence:taxa_taxon_list_id'];
      $db->from('occurrences')
          ->set($delta)
          ->where('id', $_POST['occurrence:id'])
          ->update();
      // since we bypass ORM here for performance, update the cache_occurrences table.
      $db->from('cache_occurrences')
          ->set(array('record_status'=>$_POST['occurrence:record_status'], 'verified_on'=>date('Y-m-d H:i:s'), 'cache_updated_on'=>date('Y-m-d H:i:s')))
          ->where('id', $_POST['occurrence:id'])
          ->update();
      if (!empty($_POST['occurrence_comment:comment'])) {
        $db->insert('occurrence_comments', array(
              'occurrence_id'=>$_POST['occurrence:id'],
              'comment'=>$_POST['occurrence_comment:comment'],
              'created_by_id'=>$this->user_id,
              'created_on'=>date('Y-m-d H:i:s'),
              'updated_by_id'=>$this->user_id,
              'updated_on'=>date('Y-m-d H:i:s')
          ));
      }
      echo 'OK';
    } catch (Exception $e) {
      echo $e->getMessage();
      error::log_error('Exception during single record verify', $e);
    }
  }

}
 
 
 