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
 * Model class for the termlists_term_attributes table.
 *
 * @package	Core
 * @subpackage Models
 */
class Termlists_term_attribute_Model extends ATTR_ORM {

  protected $belongs_to = array('created_by'=>'user', 'updated_by'=>'user', 'termlist');

  protected $has_many = array(
    'termlists_term_attributes_values',
  );

  protected $has_and_belongs_to_many = array('termlists');
  
  /**
   * After saving, ensures that the join records linking the attribute to a taxon 
   * list are created or deleted.
   * @return boolean Returns true to indicate success.  
   */
  protected function postSubmit($isInsert) {
    $lists = ORM::factory('termlist')->find_all();
    foreach ($lists as $list) {
      $this->set_attribute_termlist_record($this->id, $list->id, isset($_POST['termlist_'.$list->id]));
    }
    return true;
  }

  /**
   * Internal function to ensure that an attribute is linked to a termlist or alternatively is unlinked from the list.
   * Checks the existing data and creates or deletes the join record as and when necessary.
   * @param integer $attr_id Id of the attribute.
   * @param integer $list_id ID of the termlist.
   * @param boolean $checked True if there should be a link, false if not. 
   */
  private function set_attribute_termlist_record($attr_id, $list_id, $checked)
  {
    $attributes_termlist = ORM::factory('termlists_termlists_term_attribute',
        array($this->object_name.'_id' => $attr_id, 'termlist_id' => $list_id)
    );
    if ($attributes_termlist->loaded) {
      // existing record
      if($checked == true and $attributes_termlist->deleted == 't') {
        $attributes_termlist->deleted = 'f';
        $attributes_termlist->save();
      } else if ($checked == false and $attributes_termlist->deleted == 'f')  {
        $attributes_termlist->deleted = 't';
        $attributes_termlist->save();
      }
    } else if ($checked == true) {
      $save_array = array(
        'id' => $attributes_termlist->object_name,
        'fields' => array(
          'termlists_term_attribute_id' => array('value' => $attr_id),
          'termlist_id' => array('value' => $list_id),
          'deleted' => array('value' => 'f')
        ),
        'fkFields' => array(),
        'superModels' => array()
      );
      $attributes_termlist->submission = $save_array;
      $attributes_termlist->submit();
    }
  }

}
