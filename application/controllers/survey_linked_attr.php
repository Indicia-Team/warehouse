
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Custom attribute controller base class for attrs that are survey linked.
 */
abstract class Survey_Linked_Attr_Controller extends Attr_Base_Controller {

  protected function prepareOtherViewData(array $values) {
    $baseData = parent::prepareOtherViewData($values);
    $qry = $this->db
      ->select([
        'w.id as website_id',
        's.id as survey_id',
        'w.title as website_title',
        's.title as survey_title',
      ])
      ->from('websites as w')
      ->join('surveys as s', [
        's.website_id' => 'w.id',
        's.deleted' => FALSE,
      ], NULL, 'LEFT')
      ->where('w.deleted', 'f');
    // Limit websites if not core admin.
    if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
      $qry->in('w.id', $this->auth_filter['values']);
    }
    // If editing, load existing data.
    if (isset($values["{$this->prefix}_attribute:id"])) {
      $qry
        ->select([
          'aw.id as website_join_id',
          '(aw.id is not null) as selected',
          '(aww.id is not null) as selected_all_surveys',
        ])
        ->join("$baseData[webrec_entity]s as aw", [
          'aw.website_id' => 'w.id',
          "aw.$baseData[webrec_key]" => (int) $values["{$this->prefix}_attribute:id"],
          'aw.restrict_to_survey_id' => 's.id',
          'aw.deleted' => FALSE,
        ], NULL, 'LEFT')
        ->join("$baseData[webrec_entity]s as aww", [
          'aww.website_id' => 'w.id',
          "aww.$baseData[webrec_key]" => (int) $values["{$this->prefix}_attribute:id"],
          'aww.restrict_to_survey_id' => NULL,
          'aww.deleted' => FALSE,
        ], NULL, 'LEFT');
    }
    $websiteSurveyLinks = $qry
      ->orderby(['w.title' => 'ASC', 's.title' => 'ASC'])
      ->get()->result_array(TRUE);
    return array_merge(
      $baseData,
      [
        'websiteSurveyLinks' => $websiteSurveyLinks,
      ]
    );
  }

}
