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
class Taxa_Taxon_List_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = array(
    'created_by' => 'user',
    'updated_by' => 'user',
    'termlist_id' => 'termlist',
    'source_id' => 'termlists_term',
    'reporting_category_id' => 'termlists_term',
  );

  protected $has_many = array(
    'taxa_taxon_list_attribute_values',
  );

  protected $has_and_belongs_to_many = array('websites');

  /**
   * Post submit handler.
   *
   * After saving, ensures that the join records linking the attribute to a
   * taxon list are created or deleted.
   *
   * @return bool
   *   Returns true to indicate success.
   */
  protected function postSubmit($isInsert) {
    // This is only needed when run on the warehouse.
    global $remoteUserId;
    if (empty($remoteUserId)) {
      // Loop all the lists, link or unlink depending on the form POST data.
      $lists = ORM::factory('taxon_list')->find_all();
      foreach ($lists as $list) {
        $this->set_attribute_taxon_list_record($this->id, $list->id, isset($_POST["taxon_list_$list->id"]));
      }
    }
    return TRUE;
  }

  /**
   * Link or unlink the attribute to a taxon list.
   *
   * Internal function to ensure that an attribute is linked to a taxon list
   * or alternatively is unlinked from the list. Checks the existing data and
   * creates or deletes the join record as and when necessary.
   *
   * @param int $attr_id
   *   Id of the attribute.
   * @param int $list_id
   *   ID of the taxon list.
   * @param bool $checked
   *   True if there should be a link, false if not.
   */
  private function set_attribute_taxon_list_record($attr_id, $list_id, $checked) {
    $attributes_taxon_list = ORM::factory(
      'taxon_lists_taxa_taxon_list_attribute',
      [$this->object_name . '_id' => $attr_id, 'taxon_list_id' => $list_id]
    );
    if ($attributes_taxon_list->loaded) {
      // Existing record.
      if ($checked == TRUE and $attributes_taxon_list->deleted == 't') {
        $attributes_taxon_list->__set('deleted', 'f');
        $attributes_taxon_list->save();
      }
      elseif ($checked == FALSE and $attributes_taxon_list->deleted == 'f') {
        $attributes_taxon_list->__set('deleted', 't');
        $attributes_taxon_list->save();
      }
    }
    elseif ($checked == TRUE) {
      $saveArray = [
        'id' => $attributes_taxon_list->object_name,
        'fields' => [
          'taxa_taxon_list_attribute_id' => array('value' => $attr_id),
          'taxon_list_id' => array('value' => $list_id),
          'deleted' => ['value' => 'f'],
        ],
        'fkFields' => [],
        'superModels' => [],
      ];
      $attributes_taxon_list->submission = $saveArray;
      $attributes_taxon_list->submit();
    }
  }

}
