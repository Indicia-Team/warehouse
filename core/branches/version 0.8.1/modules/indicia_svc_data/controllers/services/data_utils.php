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
      $count=0;
      foreach ($data['content']['records'] as $record) {
        if ($record['record_status']!=='V' && (!empty($record['pass'])||$_POST['ignore']==='true')) {
          $ids[] = $record['occurrence_id'];
          $db->insert('occurrence_comments', array(
              'occurrence_id'=>$record['occurrence_id'],
              'comment'=>'This record is assumed to be correct',
              'created_by_id'=>$this->user_id,
              'created_on'=>date('Y-m-d H:i:s'),
              'updated_by_id'=>$this->user_id,
              'updated_on'=>date('Y-m-d H:i:s')
          ));
          $count++;
        }
      }
      $db->from('occurrences')->set(array('record_status'=>'V'))->in('id', $ids)->update();
      echo $count;
    } catch (Exception $e) {
      echo $e->getMessage();
      error::log_error('Exception during bulk verify', $e);
    }
  }

}
 
 
 