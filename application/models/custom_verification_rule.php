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
 * Model class for the custom_verification_rules table.
 */
class Custom_verification_rule_Model extends ORM {

  protected $belongs_to = [
    'custom_verification_ruleset' => 'custom_verification_ruleset',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules('custom_verification_ruleset_id', 'integer', 'required');
    $array->add_rules('taxon_external_key', 'required');
    $array->add_rules('rule_type', 'required');
    $array->add_rules('definition', 'required');

    $this->unvalidatedFields = [
      'fail_icon',
      'fail_message',
      'limit_to_stages',
      'limit_to_geography',
      'reverse_rule',
    ];
    return parent::validate($array, $save);
  }

}
