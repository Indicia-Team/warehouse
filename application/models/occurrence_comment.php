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
 * Model class for the Occurrence_Comments table.
 */
class Occurrence_comment_model extends ORM {
  public $search_field = 'comment';

  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user',
    'occurrence',
  ];

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace before validation.
    $array->pre_filter('trim');
    $array->add_rules('comment', 'required');
    $array->add_rules('occurrence_id', 'required', 'integer');
    $array->add_rules('reply_to_id', 'integer');
    $array->add_rules('redet_taxa_taxon_list_id', 'integer');
    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'email_address',
      'person_name',
      'deleted',
      'auto_generated',
      'generated_by',
      'implies_manual_check_required',
      'query',
      'record_status',
      'record_substatus',
      'external_key',
      'correspondence_data',
      'reference',
      'confidential',
    ];
    return parent::validate($array, $save);

  }

  /**
   * Returns an abbreviated version of the comment to act as a caption
   */
  public function caption()
  {
    if (strlen($this->comment)>30)
      return substr($this->comment, 0, 30).'...';
    else
      return $this->comment;
  }

  /**
   * Implement an instant update of the cache occurrences queried field.
   *
   * This allows the verification UI to report on the correct status as soon as
   * changes are made.
   *
   * @param bool $isInsert
   *   True if action is insert, false if update. This only fires on inserts,
   *
   * @return bool
   *   Always returns true to indicate success.
   */
  public function postSubmit($isInsert) {
    if ($isInsert && $this->auto_generated!=='t' and $this->query==='t') {
      $sql = <<<SQL
        UPDATE cache_occurrences_functional SET query='Q'
        WHERE id=?
        AND (query<>'Q' OR query IS NULL)
      SQL;
      $this->db->query($sql, [$this->occurrence_id]);
    }
    // Answers don't need to be instant, just queries.
    return TRUE;
  }

}
