<?php

/**
 * @file
 * Controller for the list of UKSI operations.
 *
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
 * @link https://github.com/indicia-team/warehouse/
 */

class Taxa_search_Controller extends Indicia_Controller {

  public function index() {
    $view = new View('taxa_search/index');
    $this->template->title = 'UKSI taxon search';
    $view->listId = kohana::config('uksi_operations.taxon_list_id', FALSE, FALSE);
    if (!$view->listId) {
      $view = 'Incomplete configuration. Please set up the uksi_operations module\'s configuration file.';
    }
    $this->template->content = $view;
  }

  /**
   * As the UKSI list is global, need to be admin to change it.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->logged_in('UKSIAdmin');
  }

}
