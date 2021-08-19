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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Controller providing CRUD access to the licences list.
 */
class Licence_Controller extends Gridview_Base_Controller {

  /**
   * Constructor - sets up the index grid view.
   */
  public function __construct() {
    parent::__construct('licence');
    $this->columns = [
      'id' => '',
      'title' => '',
      'code' => '',
      'version' => '',
    ];
    $this->pagetitle = "Licences";
    $this->session = Session::instance();
  }

  /**
   * Check if page access authorised.
   *
   * You can only access the list of licences if at least an editor of one
   * website.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('editor');
  }

  /**
   * Returns model values ready to load onto a form.
   *
   * Returns an array of all values from this model and its super models ready
   * to be loaded into a form. For this controller, we need to also need to
   * flash a warning about editing existing licence records.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    if (!empty($r['licence:id'])) {
      $this->session->set_flash('flash_error', 'Warning! Editing an existing licence will ' .
        'affect the licence associated with records that link to this. Do not edit this if ' .
        'it changes the nature of the licence.');
    }
    return $r;
  }

}
