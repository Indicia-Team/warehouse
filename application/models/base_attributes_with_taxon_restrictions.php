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
 * Base class for the models which represent a link between an attribute and a website.
 */
class Base_Attributes_With_Taxon_Restrictions_Model extends Valid_ORM {

  /**
   * Ensure taxon restrictions in the posted form are saved.
   *
   * @param bool $isInsert
   *   True if the post is an insert, false for update.
   * @param string $type
   *   Attribute type - occurrence or sample.
   */
  protected function postSubmitSaveTaxonRestrictions($isInsert, $type) {
    $typeAbbr = self::getTypeAbbr($type);
    if (!empty($_POST['has-taxon-restriction-data'])) {
      $userId = (int) $_SESSION['auth_user']->id;
      $restrictions = [];
      $ttlIds = [];
      // Loop the post data to look for rows in the restrictions species
      // checklist grid.
      foreach ($_POST as $key => $value) {
        if (substr($key, -8) === ':present' && $value !== '0') {
          // Found a row. Find the part of the fieldname shared with other
          // attribute controls in the same row.
          $rowId = preg_replace('/:present$/', '', $key);
          // Find any posted sex stage attribute keys in the same row.
          $sexStageKeys = preg_grep("/^$rowId:{$typeAbbr}Attr:\d+$/", array_keys($_POST));
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
UPDATE {$type}_attribute_taxon_restrictions
SET deleted=true, updated_on=now(), updated_by_id=$userId
WHERE {$type}_attributes_website_id=$this->id

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
UPDATE {$type}_attribute_taxon_restrictions
  SET restrict_to_stage_term_meaning_id=$restriction[stage_term_meaning_id],
    updated_on=now(),
    updated_by_id=$userId
WHERE {$type}_attributes_website_id=$this->id
AND restrict_to_taxon_meaning_id=$restriction[taxon_meaning_id]
AND deleted=false;

INSERT INTO {$type}_attribute_taxon_restrictions(
  {$type}_attributes_website_id,
  restrict_to_taxon_meaning_id,
  restrict_to_stage_term_meaning_id,
  created_on,
  created_by_id,
  updated_on,
  updated_by_id
)
SELECT $this->id, $restriction[taxon_meaning_id], $restriction[stage_term_meaning_id], now(), $userId, now(), $userId
WHERE NOT EXISTS(
  SELECT 1 FROM {$type}_attribute_taxon_restrictions
  WHERE {$type}_attributes_website_id=$this->id
  AND restrict_to_taxon_meaning_id=$restriction[taxon_meaning_id]
  AND deleted=false
);
SQL;
        $this->db->query($qry);
      }
    }
  }

  /**
   * Converts an attribute type name to a 3 letter abbreviation.
   *
   * @param string $type
   *   Attribute type name, e.g. occurrence or sample.
   *
   * @return string
   *   3 letter abbreviation, e.g. occ or smp.
   */
  private function getTypeAbbr($type) {
    switch ($type) {
      case 'occurrence':
        return 'occ';

      case 'sample':
        return 'smp';

      case 'taxa_taxon_list':
        return 'ttl';

      default:
        throw new exception("Unrecognised attribute type: $type");
    }
  }

}
