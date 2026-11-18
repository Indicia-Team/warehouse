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
 * Model class for the locations_scratchpad_lists table.
 */
class Locations_scratchpad_list_Model extends ORM {

  protected $has_one = [
    'location',
    'scratchpad_list',
  ];

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user'
  );

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('location_id', 'integer', 'required');
    $array->add_rules('scratchpad_list_id', 'integer', 'required');
    $websiteId = $this->identifiers['website_id'];
    // Locations are available to a website when public or explicitly linked
    // to it; enforce that visibility while checking the list ownership too.
    if ($websiteId !== NULL && isset($array['scratchpad_list_id']) &&
        $this->db->query(
          'SELECT 1
           FROM scratchpad_lists sl
           JOIN locations l ON l.id=? AND l.deleted=false
           WHERE sl.id=? AND sl.website_id=? AND sl.deleted=false
           AND (COALESCE(l.public, false) OR EXISTS (
             SELECT 1 FROM locations_websites lw
             WHERE lw.location_id=l.id AND lw.website_id=? AND lw.deleted=false
           ))',
          [$array['location_id'], $array['scratchpad_list_id'], $websiteId, $websiteId]
        )->count() === 0) {
      $array->add_error('location_id', 'default');
    }
    $this->unvalidatedFields = [
      'deleted',
    ];
    return parent::validate($array, $save);
  }

}
