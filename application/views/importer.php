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
 * @package    Core
 * @subpackage Libraries
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link     http://code.google.com/p/indicia/
 */
 
/**
 * This is a view which outputs a parameters entry form to capture values that will apply to every row during an import, such as the website id.
 */
 
require_once(DOCROOT.'client_helpers/import_helper.php');
$auth = import_helper::get_read_write_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));

echo import_helper::importer(array(
  'model' => $this->controllerpath,
  'auth' => $auth  
));

echo import_helper::dump_javascript();

?>


