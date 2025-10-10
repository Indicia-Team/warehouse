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
 * Model class for the Taxa_Taxon_List_Attributes table.
 */
class Taxon_lists_taxa_taxon_list_attribute_Model extends Valid_ORM {
  protected $has_one = array(
    'taxa_taxon_list_attribute',
    'taxon_list',
  );

  protected $belongs_to = array(
    'created_by' => 'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    // Uses PHP trim() to remove whitespace from beginning and end of all
    // fields before validation.
    $array->pre_filter('trim');
    $this->unvalidatedFields = array(
      'taxa_taxon_list_attribute_id',
      'taxon_list_id',
      'default_text_value',
      'default_float_value',
      'default_int_value',
      'default_upper_value',
      'default_date_start_value',
      'default_date_end_value',
      'default_date_type_value',
      'control_type_id',
    );
    return parent::validate($array, $save);
  }

  /**
   * Return a displayable caption for the item.
   */
  public function caption() {
    if ($this->id) {
      return ($this->taxa_taxon_list_attribute != NULL ? $this->taxa_taxon_list_attribute->caption : '');
    }
    else {
      return 'Taxon Attribute';
    }
  }

  /**
   * Map a default_value virtual field  onto the relevant default value fields.
   *
   * Mapping depends on the data type.
   */
  protected function preSubmit() {
    if (isset($this->submission['fields']['default_value']['value'])) {
      $attr = ORM::factory('taxa_taxon_list_attribute', $this->submission['fields']['taxa_taxon_list_attribute_id']['value']);
      $this->setSubmissionAttrValue($this->submission['fields']['default_value']['value'], $attr->data_type, 'default_');
    }
    return parent::presubmit();
  }

  /**
   * Handle saving any taxon restrictions.
   *
   * After saving, if the posting form was the warehouse attributes_in_survey
   * edit form then it may have information about restrictions for this
   * attribute's use according to the chosen taxa. Ensure this is persisted to
   * the database.
   *
   * @param bool $isInsert
   *   True if the post is an insert, false for update.
   *
   * @return bool
   *   Return TRUE allowing the transaction to commit.
   */
  protected function postSubmit($isInsert) {
    if (!empty($_POST['has-taxon-restriction-data'])) {
      $restrictions = [];
      $ttlIds = [];
      $userId = (int) $_SESSION['auth_user']->id;
      // Loop the post data to look for rows in the restrictions species
      // checklist grid.
      foreach ($_POST as $key => $value) {
        if (substr($key, -8) === ':present' && $value !== '0') {
          // Found a row. Find the part of the fieldname shared with other
          // attribute controls in the same row.
          $rowId = preg_replace('/:present$/', '', $key);
          // Find any posted sex stage attribute keys in the same row.
          // NB - there won't be any at the moment as the UI doesn't support
          // stage terms.
          $sexStageKeys = preg_grep("/^$rowId:ttlAttr:\d+$/", array_keys($_POST));
          $sexStageVal = 'NULL';
          // If we have any keys, map the posted term ID to a meaning Id.
          if (!empty($sexStageKeys) && !empty($_POST[array_values($sexStageKeys)[0]])) {
            $meaningId = $this->db
              ->query('SELECT meaning_id FROM cache_termlists_terms WHERE id=?', [$_POST[array_values($sexStageKeys)[0]]])
              ->current();
            $sexStageVal = $meaningId->meaning_id;
          }
          $restrictions[$value] = [
            'taxa_taxon_list_id' => $value,
            'stage_term_meaning_id' => $sexStageVal,
          ];
          $ttlIds[] = $value;
        }
      }
      $tmIdList = [];
      if (count($ttlIds)) {
        $tmIds = $this->db
          ->select('id, taxon_meaning_id')
          ->from('taxa_taxon_lists')
          ->in('id', $ttlIds)
          ->get()->result();
        foreach ($tmIds as $row) {
          $restrictions[$row->id]['taxon_meaning_id'] = $row->taxon_meaning_id;
          $tmIdList[] = $row->taxon_meaning_id;
        }
      }
      if (!$isInsert) {
        // Delete any old restrictions that are not in the list.
        $tmIdCommaList = implode(',', $tmIdList);
        $qry = <<<SQL
UPDATE taxa_taxon_list_attribute_taxon_restrictions
SET deleted=true, updated_on=now(), updated_by_id=$userId
WHERE taxon_lists_taxa_taxon_list_attribute_id=$this->id

SQL;
        if (!empty($tmIdCommaList)) {
          $qry .= <<<SQL
AND restrict_to_taxon_meaning_id NOT IN ($tmIdCommaList);
SQL;
        }
        $this->db->query($qry);
      }
      foreach ($restrictions as $restriction) {
        $qry = <<<SQL
UPDATE taxa_taxon_list_attribute_taxon_restrictions
  SET restrict_to_stage_term_meaning_id=$restriction[stage_term_meaning_id],
    updated_on=now(),
    updated_by_id=$userId
WHERE taxon_lists_taxa_taxon_list_attribute_id=$this->id
AND restrict_to_taxon_meaning_id=$restriction[taxon_meaning_id]
AND deleted=false;

INSERT INTO taxa_taxon_list_attribute_taxon_restrictions(
  taxon_lists_taxa_taxon_list_attribute_id,
  restrict_to_taxon_meaning_id,
  restrict_to_stage_term_meaning_id,
  created_on,
  created_by_id,
  updated_on,
  updated_by_id
)
SELECT $this->id, $restriction[taxon_meaning_id], $restriction[stage_term_meaning_id], now(), $userId, now(), $userId
WHERE NOT EXISTS(
  SELECT 1 FROM taxa_taxon_list_attribute_taxon_restrictions
  WHERE taxon_lists_taxa_taxon_list_attribute_id=$this->id
  AND restrict_to_taxon_meaning_id=$restriction[taxon_meaning_id]
  AND deleted=false
);
SQL;
        $this->db->query($qry);
      }
    }
    return TRUE;
  }

  /**
   * Create a virtual field called default_value from the relevant default value fields, depending on the data type.
   */
  public function __get($column) {
    if ($column === 'default_value') {
      $attr = ORM::factory('taxa_taxon_list_attribute', $this->taxa_taxon_list_attribute_id);
      switch ($attr->data_type) {
        case 'T':
          return parent::__get('default_text_value');

        case 'F':
          return parent::__get('default_float_value');

        case 'I':
        case 'L':
          return parent::__get('default_int_value');

        case 'D':
        case 'V':
          $vagueDate = array(
            parent::__get('default_date_start_value'),
            parent::__get('default_date_end_value'),
            parent::__get('default_date_type_value')
          );
          return vague_date::vague_date_to_string($vagueDate);
      }
    }
    else {
      return parent::__get($column);
    }
  }

}
