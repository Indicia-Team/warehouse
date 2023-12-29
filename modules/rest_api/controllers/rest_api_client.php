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
 * Controller providing CRUD access to the list of REST API clients defined in the database.
 */
class Rest_api_client_Controller extends Gridview_Base_Controller
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('rest_api_client');

    $this->columns = array(
        'id' => 'ID',
        'title' => '',
        'description' => '',
        'website_title'  => 'Website'
    );

    $this->pagetitle = "REST API clients";
    $this->set_website_access('admin');
  }

  /**
   * If trying to edit an existing website record, ensure the user has rights to this website.
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
   * Core admin or website admins can see the list of websites
   */
  public function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }

  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return [
      [
        'controller' => 'rest_api_client_connection',
        'title' => 'Connections',
        'actions' => ['edit'],
      ],
    ];
  }

  /*protected function getModelValues() {
    $r = parent::getModelValues();
    $r['secret2'] = $r['rest_api_client:secret'];
    return $r;
  }*/

  protected function prepareOtherViewData(array $values) {
    $websites = ORM::factory('website');
    if (!empty($values['rest_api_client:website_id']))
      // Can't change website for existing REST API client.
      $websites = $websites->where('id', $values['rest_api_client:website_id']);
    elseif (!$this->auth->logged_in('CoreAdmin') && $this->auth_filter['field'] === 'website_id') {
      $websites = $websites->in('id',$this->auth_filter['values']);
    }
    $arr = [];
    foreach ($websites->where('deleted','false')->orderby('title','asc')->find_all() as $website) {
      $arr[$website->id] = $website->title;
    }

    return [
      'websites' => $arr,
    ];
  }

}
