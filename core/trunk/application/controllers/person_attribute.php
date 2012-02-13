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
