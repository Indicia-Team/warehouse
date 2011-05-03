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
 * @package	Taxon Designations
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller class for the taxon designations plugin module.
 */
class Taxon_designation_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('taxon_designation', 'taxon_designation/index');
    $this->columns = array(
      'id'          => '',
      'title'       => '',
      'category'    => ''
    );
    $this->pagetitle = "Taxon Designations";
    $this->model = ORM::factory('taxon_designation');
  }

  /**
   * Get the list of terms ready for the location types list.
   */
  protected function prepareOtherViewData($values)
  {
    return array(
      'category_terms' => $this->get_termlist_terms('indicia:taxon_designation_categories')
    );
  }
  
  /**
   * As the designations list is global, need to be admin to change it.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin');
  }

}

?>