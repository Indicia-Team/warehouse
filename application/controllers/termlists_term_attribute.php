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
 * Controller providing CRUD access to the termlists_term attributes.
 *
 * @package Core
 * @subpackage Controllers
 */
class Termlists_term_attribute_Controller extends Attr_Base_Controller {

  public function __construct()
  {
    $this->prefix = 'termlists_term';
    parent::__construct();
    $this->pagetitle = "Term attributes";
    // override the default columns for custom attributes, as termlists_term attributes are attached to termlists.
    $this->columns = array
    (
      'id'=>'',
      'termlist'=>'Term list',
      'caption'=>'',
      'data_type'=>'Data type'
    );
  }

  /**
   * Returns the view specific to termlists_term attribute edits.
   */
  protected function editViewName() {
    return 'termlists_term_attribute/termlists_term_attribute_edit';
  }

  /**
   * Returns some addition information required by the edit view, which is not associated with
   * a particular record.
   */
  protected function prepareOtherViewData(array $values) {
    return array(
      'name' => ucfirst($this->prefix),
      'controllerpath' => $this->controllerpath
    );
  }

}
