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

require_once('helper_config.php');
require_once('phpFlickr\phpFlickr.php');

/**
 * Static helper class for interaction with the Flickr photo storage website.
 */
class flickr_helper extends helper_config {

  /**
   * Authenticate onto Flickr unless already done. This redirects the user to the Flickr
   * authentication page asking them to confirm access.
   *
   * @param string $permissions Specify 'read' or 'write' to determine the level of permissions required.
   */
  public static function auth($permissions) {
    $f = new phpFlickr(parent::$flickr_api_key, parent::$flickr_api_secret);

    $f->auth($permissions);
  }

  public static function flickr_selector($id='flickr') {
    global $javascript;

    $javascript .= "(function($) {
        $(document).ready(function(){
          $('div#$id').indiciaFlickr();
        });
      })(jQuery);\n";
    return '<div id="flickr"></div>';
  }

}

?>