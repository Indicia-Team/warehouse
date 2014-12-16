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
 * @package	Core
 * @subpackage Config
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct access allowed.');

/**
 * Sets the default route to the home page
 */
$config['_default'] = 'home';
// redirect page/1 to the index/1 page for filtered versions of lists.
$config['([a-z|_]+)/([0-9]+)'] = '$1/index/$2';
$config['report'] = 'report_viewer';
$config['report/local/(.+)'] = 'report_viewer/local/$1';
$config['report/resume/([a-z0-9]+)'] = 'report_viewer/resume/$1';

// for the REST api the following routes will map versioned calls to the normal resource endpoint
// supplying the version as the last argument. Plus any resource names with a hyphen are converted
// to underscores in the method names.
$config['services/rest/v([0-9]+)\.([0-9]+)/([a-z0-9]+)-(.+)'] = 'services/rest/$3_$4/v$1.$2';
$config['services/rest/v([0-9]+)\.([0-9]+)/(.+)'] = 'services/rest/$3/v$1.$2';
$config['services/rest/([a-z0-9]+)-(.+)'] = 'services/rest/$1_$2';
