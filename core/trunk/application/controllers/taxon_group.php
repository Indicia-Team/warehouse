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
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller providing CRUD access to the taxon groups list.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Taxon_Group_Controller extends Gridview_Base_Controller {
  public function __construct() {
    parent::__construct('taxon_group', 'taxon_group', 'taxon_group/index');
    $this->columns = array(
      'title'=>'');
    $this->pagetitle = "Taxon Groups";
    $this->session = Session::instance();
    $this->model = ORM::factory('taxon_group');
  }

  /**
   * Action for taxon_group/create page/
   * Displays a page allowing entry of a new taxon group.
   */
  public function create() {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else
    {
        $this->setView('taxon_group/taxon_group_edit', 'Taxon Group');
    }
  }

  public function edit($id = null) {
    if ($id == null)
        {
         $this->setError('Invocation error: missing argument', 'You cannot call edit a taxon group without an ID');
        }
        else
        {
            $this->model = new Taxon_Group_Model($id);
            $this->setView('taxon_group/taxon_group_edit', 'Taxon Group');
        }
  }

}

?>
