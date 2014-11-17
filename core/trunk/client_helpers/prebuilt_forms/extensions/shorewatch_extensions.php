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
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Extension class that supplies new controls to support the WDCS Shorewatch project.
 */
class extension_shorewatch_extensions {
  /*
   * On the mapping page the records table is only shown for volunteers if My Own Locality is selected.
   * As guests don't have that option, then this extension makes sures the records table is always hidden for guests.
   * Note: This extension operates in the background and is hidden from the user's view
   */
  public static function mapping_for_volunteers_guests($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('data_entry_helper'));
    data_entry_helper::$javascript="
    if ($('#dynamic-my_own_locality').is(':checked')) {
      $('#tab-records').show();
    } else {
      $('#tab-records').hide();
    }
    ";
  }

  /*
   * Button control that links to a Efforts and Sightings chart page.
   * The user must provide the following options:
   * @link - Name of the chart page.
   * @currentPageUrlParam - The name of the location id $_GET parameter on the page the button is on.
   * @chartPageUrlParam - The name of the location id $_GET parameter on the chart page the button is linking to.
   */
  public static function efforts_and_sightings_chart_link($auth, $args, $tabalias, $options, $path) {
    if (!empty($_GET[$options['currentPageUrlParam']])) {
      $urlQuery=array($options['chartPageUrlParam']=>$_GET[$options['currentPageUrlParam']]);
      $linkUrl=url($options['link'],array('query'=>$urlQuery));
    } else {
      $linkUrl=url($options['link']);
    }
    $buttonHtml =
            "<FORM><INPUT TYPE='button' VALUE='View Efforts and Sightings Chart'
      ONCLICK='window.location.href=\"".$linkUrl."\"'>
    </FORM>";
    return $buttonHtml;
  }

  /*
   * Control allows a user to click on the map to open the Site Information page.
   * User must provided the page name link) and parameter(linkPageParam) options.
   * Note: This extension operates in the background and is hidden from the user's view
   */
  public static function call_page_from_map($auth, $args, $tabalias, $options, $path) {
    data_entry_helper::$javascript .= "indiciaData.linkToPage='".url($options['link']).(variable_get('clean_url', 0) ? '?' : '&').$options['linkPageParam'].'='."';";
    drupal_add_js(iform_client_helpers_path().'prebuilt_forms/extensions/shorewatch_extensions.js');
  }

  /*
   * Control gets the name and description of the site for display on the Site Information page.
   */
  public static function site_description($auth, $args, $tabalias, $options, $path) {
    if (!empty($_GET[$options['urlParameter']])) {
      $locationCommentData = data_entry_helper::get_population_data(array(
                  'table' => 'location',
        'extraParams' => $auth['read'] + array('id' => $_GET[$options['urlParameter']], 'view'=>'detail'),
              ));
      $r = '<div><h2>'.$locationCommentData[0]['name'].'</h2><p>'.$locationCommentData[0]['comment'].'</p></div>';
      return $r;
    }
  }

  /*
   * Control takes administrators to the Create Site page
   */
  public static function create_site($auth, $args, $tabalias, $options, $path) {
    $button = '<div>';
    $button .= '  <FORM>';
      $button .= "    <INPUT TYPE=\"button\" VALUE=\"".$options['button-label']."\"
                        ONCLICK=\"window.location.href='".url($options['create-site-page-page'])."'\">";
    $button .= '  </FORM>';
    $button .= '</div>';
    return $button;
  }
}