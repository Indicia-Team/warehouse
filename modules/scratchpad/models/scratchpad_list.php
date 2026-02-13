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
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the scratchpad_list_entries table.
 */
class Scratchpad_list_Model extends ORM {

  protected $belongs_to = array(
    'website',
    'created_by' => 'user',
  );

  protected $has_many = array('scratchpad_list_entries');

  protected $has_and_belongs_to_many = [
    'groups',
    'locations',
  ];

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('entity', 'required');
    $array->add_rules('website_id', 'required');
    $array->add_rules('website_id', 'integer');
    $array->add_rules('scratchpad_type_id', 'integer');
    $this->unvalidatedFields = [
      'description',
      'expires_on',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Return the submission structure.
   *
   * This includes defining taxon and taxon_meaning as the parent (super)
   * models, and the synonyms and commonNames as metaFields which are specially
   * handled.
   *
   * @return array
   *   Submission structure for a taxa_taxon_list entry.
   */
  public function get_submission_structure() {
    return [
      'model' => $this->object_name,
      'metaFields' => array('entries')
    ];
  }

  /**
   * Handle update of scratchpad list entries.
   *
   * @param mixed $isInsert
   *   Is the submission for an insert or update?
   *
   * @return bool
   *   TRUE on success.
   */
  public function postSubmit($isInsert) {
    if (array_key_exists('metaFields', $this->submission) &&
        array_key_exists('entries', $this->submission['metaFields'])) {
      $entries = explode(';', $this->submission['metaFields']['entries']['value']);
      $entriesCsv = implode(',', $entries);
      $metadataJson = $this->submission['metaFields']['entries_metadata']['value'] ?? NULL;
      $metadata = $metadataJson ? json_decode($metadataJson, TRUE) : NULL;
      warehouse::validateIntCsvListParam($entriesCsv);
      $userId = $this->getUserId();
      if (!$isInsert) {
        $this->db->query(<<<SQL
          UPDATE scratchpad_list_entries
          SET deleted = true, updated_on = now(), updated_by_id = ?
          WHERE scratchpad_list_id=$this->id
          AND entry_id NOT IN ($entriesCsv)
        SQL, [$userId]);
      }
      foreach ($entries as $entry_id) {
        $entryMetadata = $metadata[$entry_id] ?? NULL;
        if ($this->db->query(
            "select 1 from scratchpad_list_entries where scratchpad_list_id=? and entry_id=?",
            [$this->id, $entry_id]
            )->count() === 0) {
          // Doesn't exist, so insert it.
          $this->db->query(<<<SQL
              INSERT INTO scratchpad_list_entries (scratchpad_list_id, entry_id, metadata, created_on, created_by_id, updated_on, updated_by_id)
              SELECT ?, ?, ?, now(), ?, now(), ?
            SQL,
            [$this->id, $entry_id, json_encode($entryMetadata), $userId, $userId]
          );
        }
        else {
          // Exists, so update the updated_on timestamp and metadata. This will
          // undelete an entry if it was previously deleted.
          $this->db->query(<<<SQL
              UPDATE scratchpad_list_entries
              SET updated_on=now(), updated_by_id=?, metadata=?, deleted=false
              WHERE scratchpad_list_id=? AND entry_id=?
              AND (deleted=true OR metadata::text <> ?)
            SQL,
            [$userId, json_encode($entryMetadata), $this->id, $entry_id, json_encode($entryMetadata)]
          );
        }
      }
    }
    return TRUE;
  }

}