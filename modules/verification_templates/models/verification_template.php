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
 * @link https://github.com/Indicia-Team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the verification_template table.
 */
class Verification_template_Model extends ORM {

  public $search_field = 'id';

  protected $belongs_to = [
    'website',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  protected $has_and_belongs_to_many = [];

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules('website_id', 'required');
    $array->add_rules('title', 'required');
    $array->add_rules('template_statuses', 'required');
    $array->add_rules('template', 'required');
    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'restrict_to_external_keys',
      'restrict_to_family_external_keys',
      'restrict_to_website_id',
      'deleted',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Pre-submission handler.
   *
   * Handle the situation where the 2 external key lists are handed in as a
   * textarea, joined by "\n\r".
   */
  protected function preSubmit() {
    if (!empty($this->submission['fields']['restrict_to_external_keys_list']['value'])) {
      $keyList = str_replace("\r\n", "\n", $this->submission['fields']['restrict_to_external_keys_list']['value']);
      $keyList = str_replace("\r", "\n", $keyList);
      $keyList = explode("\n", trim($keyList));
      $this->submission['fields']['restrict_to_external_keys'] = ['value' => $keyList];
      unset($this->submission['fields']['restrict_to_external_keys_list']);
    }
    elseif (isset($this->submission['fields']['restrict_to_external_keys_list'])) {
      $this->submission['fields']['restrict_to_external_keys'] = ['value' => NULL];
    }

    if (!empty($this->submission['fields']['restrict_to_family_external_keys_list']['value'])) {
      $keyList = str_replace("\r\n", "\n", $this->submission['fields']['restrict_to_family_external_keys_list']['value']);
      $keyList = str_replace("\r", "\n", $keyList);
      $keyList = explode("\n", trim($keyList));
      $this->submission['fields']['restrict_to_family_external_keys'] = ['value' => $keyList];
      unset($this->submission['fields']['restrict_to_family_external_keys_list']);
    }
    elseif (isset($this->submission['fields']['restrict_to_family_external_keys_list'])) {
      $this->submission['fields']['restrict_to_family_external_keys'] = ['value' => NULL];
    }

    // Although the template_statuses field is also an array, it is input
    // differently, using a set of checkboxes so there is no equivalent code.
    // However, force V1/V2 if V is set, similar for R4/R5 for R.
    if (isset($this->submission['fields']['template_statuses']) &&
        !empty($this->submission['fields']['template_statuses']['value'])) {
      if (in_array('V', $this->submission['fields']['template_statuses']['value']) &&
          !in_array('V1', $this->submission['fields']['template_statuses']['value'])) {
        $this->submission['fields']['template_statuses']['value'][] = 'V1';
      }
      if (in_array('V', $this->submission['fields']['template_statuses']['value']) &&
          !in_array('V2', $this->submission['fields']['template_statuses']['value'])) {
        $this->submission['fields']['template_statuses']['value'][] = 'V2';
      }
      if (in_array('R', $this->submission['fields']['template_statuses']['value']) &&
          !in_array('R4', $this->submission['fields']['template_statuses']['value'])) {
        $this->submission['fields']['template_statuses']['value'][] = 'R4';
      }
      if (in_array('R', $this->submission['fields']['template_statuses']['value']) &&
          !in_array('R5', $this->submission['fields']['template_statuses']['value'])) {
        $this->submission['fields']['template_statuses']['value'][] = 'R5';
      }
    }

    return parent::presubmit();
  }

}
