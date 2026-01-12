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
 * Model class for the groups_locations table.
 */
class Groups_location_Model extends ORM {

  protected $has_one = array('group','location');

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('group_id', 'required');
    $array->add_rules('location_id', 'required');
    $this->unvalidatedFields = array('deleted');
    return parent::validate($array, $save);
  }

  /**
   * Defines a submission structure for groups_locations.
   *
   * Lets locations be submitted at the same time, e.g. during CSV upload.
   *
   * @return array
   *   Submission structure.
   */
  public function get_submission_structure() {
    return [
      'model' => $this->object_name,
      'superModels' => [
        'location' => ['fk' => 'location_id'],
      ],
      'metaFields' => [
        'location_website_id'
      ],
    ];
  }

  /**
   * Insert locations_website.
   *
   * Need to insert a locations_websites record for the groups_location where
   * it has been specified in a mapping.
   *
   * @param bool $isInsert
   *   Unused - indication if is an insert.
   *
   * @return bool
   *   Always TRUE to indicate task completion
   */
  public function postSubmit($isInsert) {
    if (!empty($this->submission['metaFields']) && !empty($this->submission['metaFields']['location_website_id'])) {
      $websiteId = $this->submission['metaFields']['location_website_id']['value'];
      if (!empty($websiteId) && $this->location_id) {
        $selectLocationWebsite = <<<SQL
          SELECT id
          FROM locations_websites
          WHERE location_id=?
          AND website_id=?
          AND deleted = FALSE;
        SQL;
        $rows = $this->db->query($selectLocationWebsite, [$this->location_id, $websiteId])->current();
        // Only add the locations_websites record if it doesn't already exist.
        if (empty($rows)) {
          $insertLocationWebsite = <<<SQL
            INSERT INTO locations_websites (location_id, website_id, created_on, created_by_id, updated_on, updated_by_id)
            VALUES (?, ?, now(), 1, now(), 1);
          SQL;
          $this->db->query($insertLocationWebsite, [$this->location_id, $websiteId]);
        }
      }
    }
    return TRUE;
  }
}