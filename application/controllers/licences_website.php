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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Controller providing CRUD access to the list of licences available to each website.
 *
 * @package Core
 * @subpackage Controllers
 */
class Licences_website_Controller extends Gridview_Base_Controller {

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct('licences_website');

    $this->columns = array(
        'id'            => 'ID',
        'licence_title' => 'Licence',
        'licence_code'  => ''
    );

    $this->pagetitle = "Licences for website";
    $this->set_website_access('admin');
  }

  /**
   * Override the default index functionality to filter by website.
   */
  public function index() {
    $website_id = $this->uri->argument(1);
    $this->base_filter['website_id'] = $website_id;
    parent::index();
  }

  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(FALSE) == 'create') {
      // Website id is passed as first argument in URL when creating.
      $r['licences_website:website_id'] = $this->uri->argument(1);
    }
    return $r;
  }

  /**
   * Additional information for the edit view.
   *
   * Returns some addition information required by the edit view, which is not associated with
   * a particular record.
   */
  protected function prepareOtherViewData($values) {
    $licenses = $this->db
      ->select('id, title')
      ->from('licences')
      ->get();
    $arr = [];
    foreach ($licenses as $licence) {
      $arr[$licence->id] = $licence->title;
    }
    return array(
      'licences' => $arr
    );
  }

  /**
   * Licences_websites only editable by core admin or admin of website.
   */
  public function record_authorised($id) {
    if ($this->auth->logged_in('CoreAdmin')) {
      return TRUE;
    }
    else {
      if (!is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
        $lw = new Licences_Website_Model($id);
        return (in_array($lw->website_id, $this->auth_filter['values']));
      }
    }
    // Should not get here as auth_filter populated if not core admin.
    return FALSE;
  }

  /**
   * Core admin or website admins can see the list of website agreements.
   */
  public function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }

  /**
   * Define non-standard behaviour for the breadcrumbs, since this is accessed via a website list.
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('website', 'Websites');
    if ($this->model->id) {
      // Editing an existing item, so our argument is the website_id.
      $websiteId = $this->model->website_id;
    }
    else {
      // Creating a new one so our argument is the website id.
      $websiteId = $this->uri->argument(1);
    }
    $websiteTitle = ORM::Factory('website', $websiteId)->title;
    $this->page_breadcrumbs[] = html::anchor('website/edit/' . $websiteId . '?tab=Licences', $websiteTitle);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

  /**
   * Return to correct page after save.
   *
   * Override the default return page behaviour so that after saving a licence for a website you
   * are returned to the list of licences on the sub-tab of the website.
   */
  protected function get_return_page() {
    if (array_key_exists('licences_website:website_id', $_POST)) {
      // After saving a record, the website id to return to is in the POST data.
      // User may select to continue adding new terms.
      if (isset($_POST['what-next'])) {
        if ($_POST['what-next'] === 'add') {
          return 'licences_website/create/' . $_POST['licences_website:website_id'];
        }
      }
      // Or, just return to the website page.
      return "website/edit/" . $_POST['licences_website:website_id'] . "?tab=Licences";
    }
    elseif (array_key_exists('licences_website:website_id', $_GET)) {
      // After uploading records, the website id is in the URL get parameters.
      return "website/edit/" . $_GET['licences_website:website_id'] . "?tab=Licences";
    }
    else {
      // Last resort if we don't know the list, just show the whole list of licences.
      return $this->model->object_name;
    }
  }

}
