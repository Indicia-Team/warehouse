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
 * Base class for the models which represent a name.
 *
 * E.g term or taxon on a list, i.e. Taxa_Taxon_Lists and termlists_terms.
 */
class Base_Name_Model extends ORM_Tree {

  // Set this in the subclass to the field holding the list id.
  protected $list_id_field;

  /**
   * Parse a list of names from the warehouse UI.
   *
   * Parses either common names or synonyms from the warehouse UI form into a
   * list of tokens.
   *
   * @param string $value
   *   Submitted value.
   * @param string $parser
   *   Name of the function to process each tokenised line.
   *
   * @return array
   *   Array of tokenised names.
   */
  protected function parseRelatedNames($value, $parser) {
    $arrLine = explode("\n", trim($value ?? ''));
    $arrNames = [];
    foreach ($arrLine as $line) {
      if (trim($line) == '') {
        break;
      }
      $c = [];
      $b = preg_split("/(?<!\\\\ )\|/", $line);
      foreach ($b as $d) {
        $c[] = trim($d);
      }
      call_user_func_array([$this, $parser], [$c, &$arrNames]);
    }
    return $arrNames;
  }

  /**
   * Retrieve the list of synonyms using a meaning id.
   *
   * @param string $meaning_field
   *   Name of the meaning field, either taxon_meaning_id or meaning_id.
   * @param int $meaning_id
   *   Id value of the meaning to search for.
   * @param bool $within_list
   *   Search within the current list only (true=default) or across all lists
   *   (false).
   *
   * @return ORM_Iterator
   *   List of synonyms.
   */
  public function getSynonomy($meaning_field, $meaning_id, $within_list = TRUE) {
    $filters = [
      'preferred' => 'f',
      'deleted' => 'f',
      $meaning_field => $meaning_id,
    ];
    if ($within_list) {
      $list_id_field = $this->list_id_field;
      $filters[$list_id_field] = $this->$list_id_field;
    }
    return ORM::factory(inflector::singular($this->table_name))->where(
      $filters)->find_all();
  }

}
