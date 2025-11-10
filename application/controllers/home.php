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

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller class for the home page.
 */
class Home_Controller extends Indicia_Controller {

  public function index() {
    $view = new View('home');
    $this->template->title = 'Welcome to the Indicia Warehouse!';
    $system = new System_Model();
    $view->db_version = $system->getVersion();
    $view->app_version = kohana::config('version.version');
    $this->set_website_access('admin');
    $view->configProblems = [];
    $view->gettingStartedTips = [];
    $view->statusWarnings = [];
    if ($view->db_version === $view->app_version) {
      $view->configProblems = config_test::check_config(TRUE, TRUE);
      $view->gettingStartedTips = serverStatus::getGettingStartedTips($this->db, $this->auth_filter);
      $view->statusWarnings = serverStatus::getStatusWarnings($this->db, $this->auth_filter);
    }
    elseif (version_compare($view->db_version, $view->app_version, '>')) {
      $view->gettingStartedTips[] = [
        'title' => 'Inconsistent database state ' . $view->db_version,
        'description' => 'The database upgrade version is higher than the application code version. Inconsistent results may occur.',
        'severity' => 'danger',
      ];
    }
    else {
      $view->gettingStartedTips[] = [
        'title' => 'Upgrade required',
        'description' => 'Database requires upgrade as there are schema changes that have not been applied yet.',
      ];
    }
    $this->template->content = $view;
  }

  /**
   * Action called when an formal upgrade is required.
   */
  public function upgrade()
  {
    $upgrader = new Upgrade_Model();
    try {
      $view = new View('upgrade');
      $this->template->title='Indicia Upgrade';
      $upgrader->run();
    } catch (Exception $e) {
      $view->error = $e->getMessage();
    }
    $system = new System_Model;
    $view->db_version=$system->getVersion();
    $view->app_version=kohana::config('version.version');
    $view->pgUserScriptsToBeApplied = $upgrader->pgUserScriptsToBeApplied;
    $view->slowScriptsToBeApplied = $upgrader->slowScriptsToBeApplied;
    $this->template->content=$view;
  }

}