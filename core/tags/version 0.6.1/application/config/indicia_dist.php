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
 * Default language code for new common names, unless specified.
 */
$config['default_lang'] = 'eng';

/**
 * Default centre for the maps, using SRID 900913.
 */
$config['default_map_x']=-500000;
$config['default_map_y']=7300000;

/**
 * Default zoom for the maps, using the OpenLayers zoom scale..
 */
$config['default_map_zoom']=4;

/**
 * Does the init hook need to point the user at the schema? Set to false if this has been done at the user
 * level in the db.
 */
$config['apply_schema']=true;

/**
 * Declare the different image files that will be created when an image is uploaded.
 * Contains an array of image file sizes. The array keys are the prefixes given to the filenames,
 * except for default, which will match the value stored in the path attribute in the images table.
 * Inside each image entry is another array, containing the width and/or height. If both are specified,
 * then the image is scaled and cropped to fit the image size. If only width or only height are
 * specified, then the file scaled to this dimension but the other dimension is set automatically
 * to preserve the aspect ratio. If neither are specified, then the image file is left in its
 * original dimensions.
 * Please note, the image file sizes thumb and med have special meaning and should be left in the array
 * although they can be reconfigured. This is because they are used by the Indicia interface.
 */
$config['image_handling']=array(
  'thumb' => array(
    'width'  => 100,
    'height' => 100,
    'crop' => true
  ),
  'med' => array(
    'width'  => 500
  ),
  'default' => array(
    'width'  => 1024
  )
);

?>
