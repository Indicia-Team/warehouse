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
 * Controller providing CRUD access to the list of REST API client connections.
 */
class Rest_api_client_connection_Controller extends Gridview_Base_Controller {

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct('rest_api_client_connection');

    $this->columns = [
      'id' => 'ID',
      'title' => '',
      'username' => '',
      'proj_id' => 'Project ID',
      'description' => '',
    ];

    $this->pagetitle = "REST API client connections";
    $this->set_website_access('admin');
  }

  /**
   * Index action method.
   */
  public function index() {
    if ($this->uri->total_arguments() > 0) {
      // Apply the filter to only show connections for this client.
      $this->base_filter['rest_api_client_id'] = $this->uri->argument(1);
    }
    parent::index();
    if ($this->uri->total_arguments() > 0) {
      // Pass the client into the view, so the add button can pass through to the
      // create form.
      $this->view->rest_api_client_id = $this->uri->argument(1);
    }
  }

  /**
   * Check record authorised.
   *
   * If trying to edit an existing website record, ensure the user has rights
   * to this website.
   *
   * @param int $id
   *   Record ID to check.
   */
  public function record_authorised($id) {
    if (is_null($id)) {
      // Creating a new website registration so must be core admin.
      return $this->auth->logged_in('CoreAdmin');
    }
    elseif (!is_null($id) && !is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id') {
      // Editing a website registration - so must have rights to it.
      return (in_array($id, $this->auth_filter['values']));
    }
    return TRUE;
  }

  /**
   * Core admin or website admins can see the list of websites.
   */
  public function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }

  /**
   * Default values required by edit form on create.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(FALSE) === 'create') {
      // Link connection to parent client.
      $r['rest_api_client_connection:rest_api_client_id'] = $this->uri->argument(1);
    }
    return $r;
  }

  /**
   * Manipulate stored data ready for form controls when editing.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    // Convert the limit to reports array to a multi-line text area value.
    if (!empty($r['rest_api_client_connection:limit_to_reports'])) {
      $r['rest_api_client_connection:limit_to_reports'] = str_replace(',', "\r\n", trim($r['rest_api_client_connection:limit_to_reports'], '{}'));
    }
    // Convert the limit to data resources array to a multi-line text area
    // value.
    if (!empty($r['rest_api_client_connection:limit_to_data_resources'])) {
      $r['rest_api_client_connection:limit_to_data_resources'] = str_replace(',', "\r\n", trim($r['rest_api_client_connection:limit_to_data_resources'], '{}'));
    }
    if ($this->model->filter_id) {
      $r['filter:title'] = $this->model->filter->title;
    }
    return $r;
  }

  /**
   * Set up lookup values required by the edit form.
   */
  protected function prepareOtherViewData(array $values) {
    // Find the list of configured Elasticsearch endpoints.
    $restConfig = kohana::config('rest');
    return [
      'esEndpoints' => array_keys($restConfig['elasticsearch'] ?? []),
    ];
  }

  /**
   * Define non-standard behaviour for the breadcrumbs.
   *
   * Edit form is accessed via a client so breadcrumb should point back to the
   * client.
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('rest_api_client', 'REST API clients');
    // Client ID either in model or URL for update or create.
    $clientId = $this->model->id ? $this->model->rest_api_client_id : $this->uri->argument(1);
    $clientTitle = ORM::Factory('rest_api_client', $clientId)->title;
    $this->page_breadcrumbs[] = html::anchor('rest_api_client/edit/' . $clientId . '?tab=connections', $clientTitle);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

  /**
   * Override the default return page behaviour.
   *
   * After saving a connection you are returned to the list of connections on
   * the sub-tab of the client.
   */
  protected function get_return_page() {
    // After saving a record, the list id to return to is in the POST data.
    if (!empty($_POST['rest_api_client_connection:rest_api_client_id'])) {
      return "rest_api_client/edit/" . $_POST['rest_api_client_connection:rest_api_client_id'] . "?tab=connections";
    }
    else {
      // Last resort if we don't know the client, just show the index page.
      return 'rest_api_client';
    }
  }

}
