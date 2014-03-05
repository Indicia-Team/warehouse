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
 * Controller providing CRUD access to the taxa_taxon_list attributes.
 *
 * @package Core
 * @subpackage Controllers
 */
class Taxa_taxon_list_attribute_Controller extends Attr_Gridview_Base_Controller {

  public function __construct()
  {
    $this->prefix = 'taxa_taxon_list';
    parent::__construct();
    $this->pagetitle = "Taxon Attributes";
    // override the default columns for custom attributes, as taxon attributes are attached
    // to websites not taxon lists.
    $this->columns = array
    (
      'id'=>'',
      'taxon_list'=>'Species List',
      'caption'=>'',
      'data_type'=>'Data type'
    );
  }
  
  /**
   * Returns the view specific to taxon attribute edits.
   */
  protected function editViewName() {
    return 'taxon_attribute/taxon_attribute_edit';
  }

  /**
   * Returns some addition information required by the edit view, which is not associated with
   * a particular record.
   */
  protected function prepareOtherViewData($values)
  {
    return array(
      'name' => ucfirst($this->prefix),
      'controllerpath' => $this->controllerpath
    );
  }
  
  public function save() {
    if ($_POST['metaFields:disabled_input']==='NO') {
      // Make sure checkboxes have a value as unchecked values don't appear in $_POST
      if (!array_key_exists($this->model->object_name.':for_verification_check', $_POST)) $_POST[$this->model->object_name.':for_verification_check'] = '0';
    }
    parent::save();
  }

}
?>
