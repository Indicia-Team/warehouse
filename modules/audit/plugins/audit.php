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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

// No scheduled tasks

function audit_alter_menu($menu, $auth) {
	if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin'))
		$menu['Admin']['Auditing']='logged_action';
	return $menu;
}

/**
 * Adds the logged_action entity to the list available via data services.
 * @return array List of additional entities to expose via the data services.
 */
function audit_extend_data_services() {
	return [
		'logged_actions' => [],
	];
}
