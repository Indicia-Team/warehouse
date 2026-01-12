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
 * Model class for the Sample_Attributes table.
 *
 * @link http://indicia-docs.readthedocs.io/en/latest/developing/data-model.html
 */
class Sample_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user',
    'termlist_id' => 'termlist',
    'source_id' => 'termlists_term',
    'reporting_category_id' => 'termlists_term',
  );

  protected $has_many = array(
    'sample_attributes_values',
  );

  protected $has_and_belongs_to_many = array('websites');

  public function validate(Validation $array, $save = FALSE) {
    $this->unvalidatedFields = ['applies_to_location'];
    // If changing a linked location ID attribute, clear related cache entries.
    if (isset($this->submission['fields']['system_function']) &&
        (($this->submission['fields']['system_function']['value'] === 'linked_location_id') !== ($this->system_function === 'linked_location_id'))) {
      $cache = Cache::instance();
      $cache->delete('spatial-index-linked-location-attr-ids');
    }
    return parent::validate($array, $save);
  }

  /**
   * Retrieve system functions for sample attributes.
   *
   * Get the list of known system functions for sample attributes, each with a
   * title and description of their usage.
   *
   * @return array
   *   List of the system known functions that a sample attribute can have.
   */
  public function get_system_functions() {
    return array(
      'email' => array(
        'title' => 'Email address',
        'friendly' => 'Email',
        'description' => 'A text attribute corresponding to an email address.',
      ),
      'cms_user_id' => array(
        'title' => 'CMS User ID',
        'description' => 'An integer attribute corresponding to the user ID on the client website\'s content management system.',
      ),
      'cms_username' => array(
        'title' => 'CMS Username',
        'description' => 'A text attribute corresponding to the user login name on the client website\'s content management system',
      ),
      'first_name' => array(
        'title' => 'First name',
        'description' => 'A text attribute corresponding to the recorder\'s first name.',
      ),
      'last_name' => array(
        'title' => 'Last name',
        'description' => 'A text attribute corresponding to the recorder\'s last name.',
      ),
      'full_name' => array(
        'title' => 'Full name',
        'description' => 'A text attribute corresponding to the recorder\'s full name.',
      ),
      'biotope' => array(
        'title' => 'Biotope',
        'friendly' => 'Habitat',
        'description' => 'A text or lookup attribute where the value describes the biotope (often described as the habitat) of the sample.',
      ),
      'sref_precision' => array(
        'title' => 'Spatial precision',
        'description' => 'A numeric attribute describing the precision of a map reference in metres.',
      ),
      'linked_location_id' => array(
        'title' => 'Linked location ID',
        'description' => 'ID of a location that has been manually linked to a record. This is used to force ' .
          'selection of a single location boundary when a sample\'s grid square overlaps 2 different boundaries in ' .
          'the locations being spatially indexed. For example, if a record overlaps 2 vice counties this allows the ' .
          'recorder to identify which applies to the record.',
      ),
      'sample_method' => array(
        'title' => 'Sample method',
        'description' => 'Method of sampling used, e.g. field observation, methodology name or trap type.',
      ),
    );
  }

}
