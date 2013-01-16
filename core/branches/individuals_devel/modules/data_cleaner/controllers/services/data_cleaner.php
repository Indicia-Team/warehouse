<?php defined('SYSPATH') or die('No direct script access.');
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
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * Class to provide webservice functions to support verifying records before they are
 * submitted
 *
 * @author Indicia Team
 * @package Services
 * @subpackage Data Cleaner
 */
class Data_cleaner_Controller extends Service_Base_Controller {
  
  /**
   * Function to support the services/data_cleaner/verify web-service. 
   * Receives a list of proposed records and applies verification rules to them, then
   * returns a list of verification notices.
   * Input is provided in the $_GET or $_POST data sent to the method as follows:
   * auth_token - read authorisation token
   * nonce - read nonce
   * sample - Provides details of the sample being verified. If verifying a list
   * of records from different places or dates then the sample entry can be ommitted or only partially
   * filled-in with the missing information provided on a record by record bases. A JSON formatted
   * object with entries for sample:survey_id, sample:date, sample:entered_sref and sample:entered_sref_system, plus 
   * optional sample:geom (WKT format).
   * occurrences - JSON format, provide an array of the occurrence record to verify. Each record is an object
   * with occurrence:taxa_taxon_list_id, an optional stage plus any of the values for the sample which need to be 
   * specified on a record by record bases. I.e. provide sample:date if the sample information sent
   * does not include a date, or a date is included but this record is for a different date.
   * rule_types - JSON formatted array of the rule types to run. If not provided, then all rule types are run.
   * E.g. ["WithoutPolygon","PeriodWithinYear"] to run just without polygon and period within year checks.
   * @return JSON A JSON array containing a list of notifications. Each notification is a JSON
   * object, with taxa_taxon_list_id and message properties.
   */
  public function verify() {
    // authenticate requesting website for this service
    $this->authenticate('read');
    if (isset($_REQUEST['sample']))
      $sample = json_decode($_REQUEST['sample'], true);
    if (isset($_REQUEST['occurrences']))
      $occurrences = json_decode($_REQUEST['occurrences'], true);
    if (empty($sample) || empty($occurrences) ) 
      $this->response='Invalid parameters';      
    else {
      $db = new Database();
      // Create an empty template table
      $db->query("select * into temporary occlist from cache_occurrences limit 0;");
      try {
        $this->prepareOcclist($db, $sample, $occurrences);
        $r = $this->runRules($db);
        $db->query('drop table occlist');
        $this->content_type = 'Content-Type: application/json';
        $this->response = json_encode($r);
      } catch (Exception $e) {
        $db->query('drop table occlist');
        $this->response = "Query failed";
        error::log_error('Error occurred calling verification rule service', $e);
      }
    }
    $this->send_response();
  }
  
  /**
   * Fills the temporary table called occlist, which contains details of each proposed record to 
   * verify.
   */
  private function prepareOccList($db, $sample, $occurrences) {
    $website_id=$this->website_id;
    $srid=kohana::config('sref_notations.internal_srid');
    $last_sref='';
    $last_sref_system='';
    foreach($occurrences as $occurrence) {
      $record = array_merge($sample, $occurrence);
      $survey_id=$record['sample:survey_id'];
      $sref=$record['sample:entered_sref'];
      $sref_system=$record['sample:entered_sref_system'];
      $taxa_taxon_list_id=$record['occurrence:taxa_taxon_list_id'];
      if (isset($record['sample:geom']))
        $geom=$record['sample:geom'];
      else {
        // avoid recalculating the geom if we don't have to as this is relatively expensive
        if ($sref!==$last_sref || $sref_system!==$last_sref_system)
          $geom=spatial_ref::sref_to_internal_wkt($sref, $sref_system);
        $last_sref = $sref;
        $last_sref_system = $sref_system;
      }
      $date=$record['sample:date'];
      $vd=vague_date::string_to_vague_date($date);      
      $date_start=$vd[0];
      $date_end=$vd[1];
      $date_type=$vd[2];
      $db->query("insert into occlist (website_id, survey_id, date_start, date_end, date_type, public_entered_sref, entered_sref_system, public_geom, taxa_taxon_list_id)
          values ($website_id, $survey_id, '$date_start', '$date_end', '$date_type', '$sref', '$sref_system', st_geomfromtext('$geom', $srid), $taxa_taxon_list_id);");
    }
    // patch in some extra details about the taxon required for each cache entry
    $db->query("update occlist o set taxon_meaning_id=ttl.taxon_meaning_id, taxon=ttl.taxon, taxa_taxon_list_external_key=ttl.external_key ".
        "from list_taxa_taxon_lists ttl where ttl.id=o.taxa_taxon_list_id");
  }
  
  /**
   * Performs the task of running the rules against the temporary
   */
  private function runRules($db) {
    $rules = data_cleaner::get_rules();
    if (!empty($_REQUEST['rule_types']))
      $ruleTypes = json_decode(strtoupper($_REQUEST['rule_types']), true);
    $r = array();      
    foreach ($rules as $rule) {
      // skip rule types if only running certain ones
      if (isset($ruleTypes) && !in_array(strtoupper($rule['testType']), $ruleTypes))         
        continue;
      if (isset($rule['errorMsgField'])) 
        // rules are able to specify a different field (e.g. from the verification rule data) to provide the error message.
        $errorField = $rule['errorMsgField'];
      else
        $errorField = 'error_message';
      foreach ($rule['queries'] as $query) {
        // queries can override the error message field.
        $ruleErrorField = isset($query['errorMsgField']) ? $query['errorMsgField'] : $errorField;
        $errorMsgSuffix = isset($rule['errorMsgSuffix']) ? $rule['errorMsgSuffix'] : '';
        $sql = 'select distinct co.taxa_taxon_list_id, '.$ruleErrorField.$errorMsgSuffix.' as message from occlist co';
        if (isset($query['joins']))
          $sql .= "\n" . $query['joins'];
        if (isset($query['where']))
          $sql .= "\nwhere " . $query['where'];
        // we now have the query ready to run which will return a list of the occurrence ids that fail the check.
        $messages = $db->query($sql)->result_array(false);
        $r = $r + $messages;
      }
    }
    return $r;
  }
  
}

?>
