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
 * Link in other required php files.
 */
require_once('helper_config.php');
require_once('phpFlickr\phpFlickr.php');

/**
 * Static helper class for interaction with the Flickr photo storage website.
 * @package Client
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

  /**
   * <p>Generates a flickr linked photo selector control. This requires a call to flickr_helper::auth
   * to have been made first and the user to have followed the login process to Flickr, otherwise a
   * normal image upload box will be displayed.<p>
   * <p>In order to get the flickr_select control working, you need to first obtain a Flickr API key from 
   * http://www.flickr.com/services/api/. When you register for the key you will also be given a 
   * "secret" - a second code that you need to supply to the Indicia data entry helpers. Once you 
   * have the keys, go to your client_helpers/helper_config.php file and enter them into the $flickr_api_key 
   * and $flickr_api_secret values.</p>
   * <p>In addition to specifying the api key and secret, you also need to tell Flickr where to 
   * link to on your website after authenticating the user (the callback URL). There is a ready-made 
   * PHP script in the Indicia code which you can use - client_helpers/flickr_auth.php. So, if your 
   * code is running on a page at http://www.example.com/data_entry.php, with your client helpers 
   * in a sub-folder, you will have a callback URL of http://www.example.com/client_helpers/flickr_auth.php. 
   * You can list your API keys at http://www.flickr.com/services/api/keys/, then click the Edit key 
   * details for the key you have registered. Now enter your callback URL in the respective field and 
   * then save the key.</p>
   *
   * @param string $div_id Name and id of the div element that is generated. Defaults to Flickr.
   * @return string HTML to insert into the web-page for the Flickr control.
   */
  public static function flickr_selector($div_id='flickr') {
    data_entry_helper::add_resource('flickr');
    if (array_key_exists('phpFlickr_auth_token', $_SESSION) &&
        !empty($_SESSION['phpFlickr_auth_token'])) {
      data_entry_helper::$javascript .= "(function($) {
          $(document).ready(function(){
            $('div#$div_id').indiciaFlickr();
          });
        })(jQuery);\n";
      return '<div id="'.$div_id.'"></div>';
    } else {
      require_once('data_entry_helper.php');
      // Flickr authentication failed. Output a normal image upload box.
      return "<label for='occurrence_image'>Image Upload:</label>\n".
        data_entry_helper::image_upload('occurrence:image').'<br/>';
    }

  }

}

?>