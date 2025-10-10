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
 * Model class for the Websites table.
 */
class Websites_Website_Agreement_Model extends ORM {

  protected $belongs_to = [
    'website',
    'website_agreement',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  /**
   * Validate and save the data.
   */
  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $array->add_rules('website_id', 'required');
    $array->add_rules('website_agreement_id', 'required');
    $array->add_rules('provide_for_reporting', 'required');
    $array->add_rules('receive_for_reporting', 'required');
    $array->add_rules('provide_for_peer_review', 'required');
    $array->add_rules('receive_for_peer_review', 'required');
    $array->add_rules('provide_for_verification', 'required');
    $array->add_rules('receive_for_verification', 'required');
    $array->add_rules('provide_for_data_flow', 'required');
    $array->add_rules('receive_for_data_flow', 'required');
    $array->add_rules('provide_for_moderation', 'required');
    $array->add_rules('receive_for_moderation', 'required');
    $array->add_rules('provide_for_editing', 'required');
    $array->add_rules('receive_for_editing', 'required');
    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'deleted',
    ];

    return parent::validate($array, $save);
  }

  public function caption() {
    if ($this->id) {
      return 'agreement for ' . $this->website->title . ' with ' . $this->website_agreement->title;
    }
    else {
      return $this->getNewItemCaption();
    }
  }

  /**
   * Refresh index_websites_website_agreementse on save.
   *
   * Override the save method to additionally refresh
   * index_websites_website_agreement with the latest information about website
   * agreements.
   */
  public function save() {
    $v = parent::save();
    $this->db->query('SELECT refresh_index_websites_website_agreements();');
    return $v;
  }

}
