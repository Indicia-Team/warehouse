<?php defined('SYSPATH') or die('No direct script access.');

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
 * @package	Core
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the Taxa_Taxon_List_Attributes table.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Taxa_Taxon_List_Attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

  protected $has_many = array(
    'taxa_taxon_list_attributes_values',
  );

  protected $has_and_belongs_to_many = array('websites');
  
  /**
   * After saving, ensures that the join records linking the attribute to a taxon 
   * list are created or deleted.
   * @return boolean Returns true to indicate success.  
   */
  protected function postSubmit() {
    $lists = ORM::factory('taxon_list')->find_all();
    foreach ($lists as $list) {
      // First check for non survey specific checkbox
      $this->set_attribute_taxon_list_record($this->id, $list->id, isset($_POST['taxon_list_'.$list->id]));
    }
    return true;
  }

  /**
   * Internal function to ensure that an attribute is linked to a taxon list
   * or alternatively is unlinked from the list. Checks the existing data and 
   * creates or deletes the join record as and when necessary.
   * @param integer $attr_id Id of the attribute.
   * @param integer $list_id ID of the taxon list.
   * @param boolean $checked True if there should be a link, false if not. 
   */
  private function set_attribute_taxon_list_record($attr_id, $list_id, $checked)
  {
    $attributes_taxon_list = ORM::factory('taxon_lists_taxa_taxon_list_attribute',
            array($this->object_name.'_id' => $attr_id
                , 'taxon_list_id' => $list_id));
    if($attributes_taxon_list->loaded) {
      // existing record
      if($checked == true and $attributes_taxon_list->deleted == 't') {
        $attributes_taxon_list->__set('deleted', 'f');
        $attributes_taxon_list->save();
      } else if ($checked == false and $attributes_taxon_list->deleted == 'f')  {
        $attributes_taxon_list->__set('deleted', 't');
        $attributes_taxon_list->save();
      }
    } else if ($checked == true) {
           $save_array = array(
                'id' => $attributes_taxon_list->object_name
                ,'fields' => array('taxa_taxon_list_attribute_id' => array('value' => $attr_id)
                          ,'taxon_list_id' => array('value' => $list_id)
                          ,'deleted' => array('value' => 'f'))
                ,'fkFields' => array()
                ,'superModels' => array());
      $attributes_taxon_list->submission = $save_array;
      $attributes_taxon_list->submit();
    }
  }

}
