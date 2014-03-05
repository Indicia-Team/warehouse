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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */
 
/**
 * A simple script to return the parameters form for an input prebuilt form name.
 */
 
// Use iform to load the helpers, so it can set the configuration variables if running in Drupal
require_once "form_helper.php";
// Let params forms internationalise.
require_once "lang.php";

// set the path to JS and CSS files. This script runs standalone, so has to do this itself.
$link = form_helper::get_reload_link_parts();
$path = dirname(dirname($link['path'])) . '/media';
form_helper::$js_path = "$path/js/";
form_helper::$css_path = "$path/css/";

form_helper::$is_ajax = true;

form_helper::$base_url = $_POST['base_url'];
$readAuth = form_helper::get_read_auth($_POST['website_id'], $_POST['password']);

echo form_helper::prebuilt_form_params_form(array(
  'form' => $_POST['form'],
  'readAuth' => $readAuth,
  'expandFirst' => true
));
echo form_helper::dump_javascript(true);

?>