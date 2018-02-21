<?php

/**
 * @file
 * Helper class to provide generally useful Indicia warehouse functions.
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
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to provide generally useful Indicia warehouse functions.
 */
class warehouse {

  /**
   * Loads any of the client helper libraries.
   *
   * Also ensures that the correct resources are loaded when the libraries are
   * used within the context of the warehouse.
   *
   * @param array $helpers
   *   Array of helper file names without the php extension.
   */
  public static function loadHelpers(array $helpers) {
    foreach ($helpers as $helper) {
      require_once DOCROOT . "client_helpers/$helper.php";
    }
    require_once DOCROOT . 'client_helpers/templates.bootstrap-3.php';
    // No need to re-link to jQuery as included in tempalate.
    helper_base::$dumped_resources[] = 'jquery';
    helper_base::$dumped_resources[] = 'jquery_ui';
    helper_base::$dumped_resources[] = 'fancybox';
  }

}
