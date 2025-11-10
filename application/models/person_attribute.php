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
 * Model class for the Person_Attributes table.
 */
class Person_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user',
    'termlist_id' => 'termlist',
    'source_id' => 'termlists_term',
    'reporting_category_id' => 'termlists_term',
  );

  // The person attributes are defined per website, not per survey
  protected $hasSurveyRestriction = false;

  protected $has_many = array(
    'person_attributes_values',
  );

  protected $has_and_belongs_to_many = array('websites');

  public function validate(Validation $array, $save = FALSE) {
    $this->unvalidatedFields = array('synchronisable');
    return parent::validate($array, $save);
  }

  /**
   * After saving, ensures that the join records linking the attribute to a website are created or deleted.
   * @return boolean Returns true to indicate success.
   */
  protected function postSubmit($isInsert) {
    // Record has saved correctly or is being reused
    $websites = ORM::factory('website')->find_all();
    foreach ($websites as $website) {
      // Check for website checkbox ticked
      $this->setAttributeWebsiteRecord($this->id, $website->id, null, isset($_POST['website_'.$website->id]));
    }
    return true;
  }

  /**
   * Get the list of known system functions for person attributes, each with a title and description
   * of their usage.
   * @return array List of the system known functions that a person attribute can have.
   */
  public function get_system_functions() {
    return array(
      'linked_location_id' => array(
        'title'=>'Linked location',
        'description'=>'A multi-value integer attribute which links the person to a site they regularly record at.'
      )
    );
  }
}
