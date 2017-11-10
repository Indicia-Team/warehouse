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
 * @package    Modules
 * @subpackage Verification_templates
 * @author     Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/Indicia-Team/
 */

/**
 * Model class for the verification_template table.
 */
class Verification_template_Model extends ORM {
  public $search_field='id';

  protected $belongs_to = array('website',
                                'created_by' => 'user',
                                'updated_by' => 'user');
  protected $has_and_belongs_to_many = array();

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('website_id', 'required');
    $array->add_rules('template', 'required');
    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
        'restrict_to_external_keys',
        'restrict_to_family_external_keys',
        'restrict_to_website_id',
        'deleted',
      );
    return parent::validate($array, $save);
  }
  
  /**
   * Handle the where the 2 external key lists are handed in as a textarea, joined by "\n\r"
   */
  protected function preSubmit()
  {
    if (!empty($this->submission['fields']['restrict_to_external_keys_list']['value'])) {
      $keyList = str_replace("\r\n", "\n", $this->submission['fields']['restrict_to_external_keys_list']['value']);
      $keyList = str_replace("\r", "\n", $keyList);
      $keyList = explode("\n", trim($keyList));
      $this->submission['fields']['restrict_to_external_keys'] = array('value' => $keyList);
      unset($this->submission['fields']['restrict_to_external_keys_list']);
    } elseif (isset($this->submission['fields']['restrict_to_external_keys_list'])) {
      $this->submission['fields']['restrict_to_external_keys'] = array('value' => null);
    }

    if (!empty($this->submission['fields']['restrict_to_family_external_keys_list']['value'])) {
      $keyList = str_replace("\r\n", "\n", $this->submission['fields']['restrict_to_family_external_keys_list']['value']);
      $keyList = str_replace("\r", "\n", $keyList);
      $keyList = explode("\n", trim($keyList));
      $this->submission['fields']['restrict_to_family_external_keys'] = array('value' => $keyList);
      unset($this->submission['fields']['restrict_to_family_external_keys_list']);
    } elseif (isset($this->submission['fields']['restrict_to_family_external_keys_list'])) {
      $this->submission['fields']['restrict_to_family_external_keys'] = array('value' => null);
    }

    return parent::presubmit();
  }

}
