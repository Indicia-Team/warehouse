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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the Survey_Attributes table.
 */
class Survey_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user',
    'termlist_id' => 'termlist',
    'source_id' => 'termlists_term',
    'reporting_category_id' => 'termlists_term',
  ];

  protected $has_many = [
    'survey_attributes_values',
  ];

  protected $has_and_belongs_to_many = ['websites'];

  // The survey attributes are defined per website, not per survey.
  protected $hasSurveyRestriction = FALSE;

  /**
   * After saving, ensures that the join records linking the attribute to a
   * website are created or deleted.
   * @return boolean Returns true to indicate success.
   */
  protected function postSubmit($isInsert) {
    // Record has saved correctly or is being reused.
    $websites = ORM::factory('website')->find_all();
    foreach ($websites as $website) {
      // Check for website checkbox ticked.
      $this->setAttributeWebsiteRecord(
        $this->id, $website->id, NULL, isset($_POST['website_' . $website->id])
      );
    }
    return TRUE;
  }

}
