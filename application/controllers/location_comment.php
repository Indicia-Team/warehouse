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
 * Controller providing CRUD access for a location comment.
 */
class location_comment_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('location_comment');
    $this->columns = [
      'comment' => '',
      'updated_on' => 'Updated on',
    ];
    $this->pagetitle = "location Comments";
  }

  /**
   * Override the default index functionality to filter by location_id.
   */
  public function index() {
    if ($this->uri->total_arguments() > 0) {
      $this->base_filter = ['location_id' => $this->uri->argument(1)];
    }
    parent::index();
    // Pass the location id into the view, so the create button can use it to
    // autoset the location of the new comment.
    if ($this->uri->total_arguments() > 0) {
      $this->view->location_id = $this->uri->argument(1);
    }
  }

  /**
   *  Setup the default values to use for editing a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(FALSE) === 'create') {
      $r['location:id'] = $this->uri->argument(1);
      $r['location_comment:location_id'] = $this->uri->argument(1);
    }
    return $r;
  }

  /**
   * Override the default return page behaviour.
   *
   * After saving a comment you are returned to the location entry which has
   * the comment.
   *
   * @return string
   *   Page path to return to.
   */
  protected function get_return_page() {
    if (array_key_exists('location_comment:location_id', $_POST)) {
      return 'location/edit/' . $_POST['location_comment:location_id'] . '?tab=images';
    }
    else {
      return $this->model->object_name;
    }
  }

  /**
   * Define non-standard behaviour for the breadcrumbs.
   *
   * Returns to the location index page.
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('location', 'location datasets');
    if ($this->model->id) {
      // Editing an existing item, so our argument is the location_comment_id.
      $locationId = $this->model->location_id;
    }
    else {
      // Creating a new one so our argument is the location id.
      $locationId = $this->uri->argument(1);
    }
    $locationTitle = ORM::Factory('location', $locationId)->caption();
    $this->page_breadcrumbs[] = html::anchor("location/edit/$locationId?tab=Comments", $locationTitle);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

}
