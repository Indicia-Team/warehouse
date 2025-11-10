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
 * Model class for the Survey_Comments table.
 */
class Survey_comment_model extends ORM {
  public $search_field = 'comment';

  protected $belongs_to = [
    'created_by' => 'user',
    'updated_by' => 'user',
    'survey'
  ];

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace before validation.
    $array->pre_filter('trim');
    $array->add_rules('comment', 'required');
    $array->add_rules('survey_id', 'required', 'integer');
    $array->add_rules('reply_to_id', 'integer');

    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'email_address',
      'person_name',
      'deleted',
      'external_key',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Returns an abbreviated version of the comment to act as a caption.
   */
  public function caption() {
    if (strlen($this->comment) > 30) {
      return substr($this->comment, 0, 30) . '...';
    }
    else {
      return $this->comment;
    }
  }

}
