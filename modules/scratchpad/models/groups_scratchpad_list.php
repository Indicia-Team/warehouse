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
 * Model class for the groups_scratchpad_lists table.
 */
class Groups_scratchpad_list_Model extends ORM {

  protected $has_one = [
    'group',
    'scratchpad_list',
  ];

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user'
  );

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('group_id', 'integer', 'required');
    $array->add_rules('scratchpad_list_id', 'integer', 'required');
    $websiteId = $this->identifiers['website_id'];
    // A child association must not connect a website's list to another
    // website's or a deleted group's record.
    if ($websiteId !== NULL && isset($array['scratchpad_list_id']) &&
        $this->db->query(
          'SELECT 1
           FROM scratchpad_lists sl
           JOIN groups g ON g.id=? AND g.website_id=sl.website_id AND g.deleted=false
           WHERE sl.id=? AND sl.website_id=? AND sl.deleted=false',
          [$array['group_id'], $array['scratchpad_list_id'], $websiteId]
        )->count() === 0) {
      $array->add_error('group_id', 'default');
    }
    $this->unvalidatedFields = [
      'deleted',
    ];
    return parent::validate($array, $save);
  }

}
