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
 * @subpackage REST API Sync
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */

/**
 *
 */
class Rest_Api_Sync_Controller extends Controller {

  /**
   *
   */
  public function index() {
    $this->db = Database::instance();
    rest_api_sync::$client_system_id = Kohana::config('rest_api_sync.system_id');
    $servers = Kohana::config('rest_api_sync.servers');
    foreach ($servers as $serverId => $server) {
      echo "<h3>$serverId</h3>";
      $nextPageOfProjectsUrl = rest_api_sync::get_server_projects_url($server['url']);
      while ($nextPageOfProjectsUrl) {
        $data = rest_api_sync::get_server_projects($nextPageOfProjectsUrl);
        $projects = $data['data'];
        foreach ($projects as $project) {
          $survey_id = $this->get_survey_id($server, $project);
          $this->sync_from_project($server, $project, $survey_id);
        }
        $nextPageOfProjectsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : false;
      }
    }
  }

  public function sync_from_project($server, $observation, $survey_id) {
    // @todo Last Sync date handling
    $fromDate = new DateTime('2 months ago');
    $taxon_list_id = Kohana::config('rest_api_sync.taxon_list_id');
    $nextPageOfTaxonObservationsUrl = rest_api_sync::get_server_taxon_observations_url(
      $server['url'], $observation['id'], $fromDate->format('Y-m-d'));
    while ($nextPageOfTaxonObservationsUrl) {
      $data = rest_api_sync::get_server_taxon_observations($nextPageOfTaxonObservationsUrl);
      $observations = $data['data'];
      foreach ($observations as $observation) {
        $taxa = $this->db->select('id')
          ->from('cache_taxa_taxon_lists')
          ->where(array(
            'taxon_list_id'=>$taxon_list_id,
            'external_key' => $observation['taxonversionkey'],
            'preferred' => 't'
          ))->get()->result_array(false);
        if (count($taxa)!==1) {
          // @todo Error handling
          throw new exception('Could not find taxon for '.$observation['taxonversionkey']);
        }
        // @todo Reuse the last sample if it matches
        $values = array(
          'website_id' => $server['website_id'],
          'sample:survey_id' => $survey_id,
          'sample:date_start'     => $observation['startdate'],
          'sample:date_end'       => $observation['enddate'],
          'sample:date_type'      => $observation['datetype'],
          'sample:recorder_names' => $observation['recorder'],
          'occurrence:taxa_taxon_list_id' => $taxa[0]['id'],
          'occurrence:external_key' => $observation['id']
        );
        if ($observation['projection']==='OSGB' || $observation['projection']==='OSI') {
          $values['sample:entered_sref'] = strtoupper(str_replace(' ', '', $observation['gridreference']));
          $values['sample:entered_sref_system'] = $observation['projection']==='OSGB' ? 'OSGB' : 'OSIE';
        }
        elseif ($observation['projection']==='4326') {
          $values['sample:entered_sref'] = $this->format_lat_long($observation['north'], $observation['east']);
          $values['sample:entered_sref_system'] = 4326;
        }
        // @todo Lookup matching record and overwrite if it exists
        $existing = $this->db->select('o.id, o.sample_id')
          ->from('occurrences o')
          ->join('samples as s', 'o.sample_id', 's.id')
          ->where(array(
            'o.deleted' => 'f',
            's.deleted' => 'f',
            'o.external_key' => $observation['id'],
            's.survey_id' => $survey_id
          ))->get()->result_array(false);
        if (count($existing)) {
          $values['occurrence:id'] = $existing[0]['id'];
          $values['sample:id'] = $existing[0]['sample_id'];
        }
        $obs = ORM::factory('occurrence');
        $obs->set_submission_data($values);
        $obs->submit();
        echo $obs->id;
        var_export($obs->getAllErrors());
        echo '<br/>';
      }
      $nextPageOfTaxonObservationsUrl = isset($data['paging']['next']) ? $data['paging']['next'] : false;
    }
  }

  /**
   * Returns a formatted decimal latitude and longitude string
   * @param $east
   * @param $north
   * @return string
   */
  private function format_lat_long($lat, $long) {
    $ns = $lat >= 0 ? 'N' : 'S';
    $ew = $long >= 0 ? 'E' : 'W';
    return "$lat$ns $long$ew";
  }

  private function get_survey_id($server, $project) {
    $projects = $this->db->select('id')
      ->from('surveys')
      ->where(array(
        'website_id' => $server['website_id'],
        'title' => "$project[id]:$project[title]",
        'deleted' => 'f'
      ))->get()->result_array(false);
    if (count($projects))
      return $projects[0]['id'];
    else {
      // Survey dataset does not exist yet so create it
      $values = array(
        'survey:title' => "$project[id]:$project[title]",
        'survey:description' => 'Survey containing records synchronised from another server.',
        'survey:website_id' => $server['website_id']
      );
      $survey = ORM::factory('survey');
      $survey->set_submission_data($values);
      $survey->submit();
      // @todo Error handling on submission.
      return $survey->id;
    }
  }

}