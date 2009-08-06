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

defined('SYSPATH') or die('No direct script access.');

/**
 * System configuration parameters
 */
$config['system'] = array
(
    'version'      => '0.1',
    'name'         => '',
    'repository'   => 'http://indicia.googlecode.com/svn/tag/version_0_1',
    'release_date' => '2009-01-15'
);

/**
 * Key used for generation of security tokens. For maximum security, change this
 * to a unique value for each Indicia install.
 * @todo Make this randomly generated during the installation procedure.
 */
$config['private_key'] = 'Indicia';

/**
 * Life span of an authentication token for services, in seconds.
 */
$config['nonce_life'] = 1200;

/**
 * Maximum size of an upload.
 */
$config['maxUploadSize'] = '1M';

$config['defaultPersonId'] = 1;

/**
 * Directory containing reports on this server.
 */
$config['localReportDir'] = 'reports';

/**
 * Name of the theme folder in the media/themes directory used by this warehouse instance.
 */
$config['theme'] = 'default';

/**
 * For developers of indicia only!
 * In a production release this var must be set to bool false!!
 * During the development process each dev has to set this var
 *  to the version upgrade folder (string) of the setup module
 */
$config['devUpgradeFolder'] = 'upgrade_0_1_to_0_2';

?>
