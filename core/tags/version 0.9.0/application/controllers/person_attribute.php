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
 * @package Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @link http://code.google.com/p/indicia/
 * @license http://www.gnu.org/licenses/gpl.html GPL
 */

/**
 * Controller providing CRUD access to the person attributes.
 *
 * @package Core
 * @subpackage Controllers
 */
class Person_attribute_Controller extends Attr_Gridview_Base_Controller {

  public function __construct()
  {
    $this->prefix = 'person';
    parent::__construct();
  }
  
  /** 
   * Override saave to store the synchronisable field. 
   */
  public function save() {
    if ($_POST['metaFields:disabled_input'] == 'NO') {
      // Make sure checkboxes have a value.
      // @todo: If we use Indicia client helper controls for the attribute edit page, this becomes unnecessary
      if (!array_key_exists($this->model->object_name.':synchronisable', $_POST)) $_POST[$this->model->object_name.':synchronisable'] = '0';
    }
    parent::save();
  }
  
  /**
   * Returns the shared view for all custom attribute edits.
   */
  protected function editViewName() {
    $this->associationsView=new View('templates/attribute_associations_website');
    return 'custom_attribute/custom_attribute_edit';
  }
  
  /**
   * Returns some addition information required by the edit view, which is not associated with
   * a particular record.
   */
  protected function prepareOtherViewData($values)
  {
    return array_merge(
      (array)parent::prepareOtherViewData($values),
      array('publicFieldName' => 'Public (available for all people on this warehouse)')
    );
  }

}
?>
