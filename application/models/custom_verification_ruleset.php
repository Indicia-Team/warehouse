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
 * Model class for the custom_verification_rulesets table.
 */
class Custom_verification_ruleset_Model extends ORM {

  public $search_field = 'title';

  protected $has_many = [
    'custom_verification_rules',
  ];

  protected $belongs_to = [
    'website' => 'website',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('fail_icon', 'required');
    $array->add_rules('fail_message', 'required');
    $array->add_rules('website_id', 'required', 'integer');
    $this->unvalidatedFields = [
      'description',
      'limit_to_stages',
      'limit_to_geography',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Handle field value conversion.
   *
   * E.g. form may submit stages from a text area so convert to array.
   */
  protected function preSubmit() {
    if (isset($this->submission['fields']['limit_to_stages_list'])) {
      if (!empty($this->submission['fields']['limit_to_stages_list']['value'])) {
        $stageList = str_replace("\r\n", "\n", $this->submission['fields']['limit_to_stages_list']['value']);
        $stageList = str_replace("\r", "\n", $stageList);
        $stageList = explode("\n", trim($stageList));
        $this->submission['fields']['limit_to_stages'] = ['value' => $stageList];
        unset($this->submission['fields']['limit_to_stages_list']);
      }
      else {
        // Specified but empty, so null it out.
        $this->submission['fields']['limit_to_stages'] = ['value' => NULL];
      }
    }
  }

}
