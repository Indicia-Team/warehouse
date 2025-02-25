<?php

/**
 * @file
 * Plugin file for the verification templates module.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/
 */

/**
 * Implements the alter_menu hook.
 *
 * Determines the extra items to be added to the main Indicia menu.
 *
 * @return array
 *   List of menu items exposed by this plugin.
 */
function verification_templates_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('editor')) {
    $menu['Admin']['Verification templates'] = 'verification_template';
  }
  return $menu;
}

/**
 * Implements the extend_data_services hook.
 *
 * Determines the data entities which should be added to those available via data services.
 *
 * @return array
 *   List of database entities exposed by this plugin with configuration.
 */
function verification_templates_extend_data_services() {
  return array('verification_templates' => array());
}
