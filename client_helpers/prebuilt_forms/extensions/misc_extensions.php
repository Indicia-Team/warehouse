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
 * Extension class that supplies general extension controls that can be used with any project.
 */
class extension_misc_extensions {
  /**
   * General button link control can be placed on pages to link to another page.
   * $options Options array with the following possibilities:<ul>
   * <li><b>buttonLabel</b><br/>
   * The label that appears on the button. Mandatory</li>
   * <li><b>buttonLinkPath</b><br/>
   * The page the button is linking to. Mandatory</li>
   * <li><b>paramNameToPass</b><br/>
   * The name of a static parameter to pass to the receiving page. Optional but requires paramValueToPass when in use</li>
   * <li><b>paramValueToPass</b><br/>
   * The value of the static parameter to pass to the receiving page. e.g. passing a static location_type_id. Optional but requires paramNameToPass when in use</li>
   * </ul>
   */
  public static function button_link($auth, $args, $tabalias, $options, $path) {
    //Only display a button if the administrator has specified both a label and a link path for the button.
    if (!empty($options['buttonLabel'])&&!empty($options['buttonLinkPath'])) {
      if (!empty($options['paramNameToPass']) && !empty($options['paramValueToPass']))
        $paramToPass=array($options['paramNameToPass']=>$options['paramValueToPass']);
      $button = '<div>';
      $button .= '  <FORM>';
      $button .= "    <INPUT TYPE=\"button\" VALUE=\"".$options['buttonLabel']."\"";
      //Button can still be used without a parameter to pass
      if (!empty($paramToPass)) {
        $button .= "ONCLICK=\"window.location.href='".url($options['buttonLinkPath'], array('query'=>$paramToPass))."'\">";
      } else { 
        $button .= "ONCLICK=\"window.location.href='".url($options['buttonLinkPath'])."'\">";
      }
      $button .= '  </FORM>';
      $button .= '</div><br>';
    } else {
      drupal_set_message('A link button has been specified without a link path or button label, please fill in the @buttonLinkPath and @buttonLabel options');
      $button = '';
    }   
    return $button;
  }
  
  /**
   * General text link control can be placed on pages to link to another page.
   * $options Options array with the following possibilities:<ul>
   * <li><b>label</b><br/>
   * The label that appears on the link. Mandatory</li>
   * <li><b>linkPath</b><br/>
   * The page to link to. Mandatory</li>
   * <li><b>paramNameToPass</b><br/>
   * The name of a static parameter to pass to the receiving page. Optional but requires paramValueToPass when in use</li>
   * <li><b>paramValueToPass</b><br/>
   * The value of the static parameter to pass to the receiving page. e.g. passing a static location_type_id. Optional but requires paramNameToPass when in use</li>
   * </ul>
   */
  public static function text_link($auth, $args, $tabalias, $options, $path) {
    //Only display a link if the administrator has specified both a label and a link.
    if (!empty($options['label'])&&!empty($options['linkPath'])) {
      if (!empty($options['paramNameToPass']) && !empty($options['paramValueToPass']))
        $paramToPass=array($options['paramNameToPass']=>$options['paramValueToPass']);
      $button = '<div>';
      $button .= "  <a ";
      //Button can still be used without a parameter to pass
      if (!empty($paramToPass)) {
        $button .= "href=\"".url($options['linkPath'], array('query'=>$paramToPass))."\">";
      } else { 
        $button .= "href=\"".url($options['linkPath'])."\">";
      }
      $button .= $options['label'];
      $button .= '  </a>';
      $button .= '</div><br>';
    } else {
      drupal_set_message('A text link has been specified without a link path or label, please fill in the @linkPath and @label options');
      $button = '';
    }   
    return $button;
  }
  
  /**
   * Adds JavaScript to the page allowing detection of whether the user has a certain permission.
   * Adds a setting indiciaData.permissions[permission name] = true or false.
   * Provide a setting called permissionName to identify the permission to check.
   */
  public static function js_has_permission($auth, $args, $tabalias, $options, $path) {
    static $done_js_has_permission=false;
    if (empty($options['permissionName']))
      return 'Please provide a setting @permissionName for the js_has_permission control.';
    if (!function_exists('user_access'))
      return 'Can\'t use the js_has_permission extension outside Drupal.';
    $val = user_access($options['permissionName']) ? 'true' : 'false';
    if (!$done_js_has_permission) {
      data_entry_helper::$javascript .= "if (typeof indiciaData.permissions==='undefined') {
  indiciaData.permissions={};
}\n";
      $done_js_has_permission=true;
    }
    data_entry_helper::$javascript .= "indiciaData.permissions['$options[permissionName]']=$val;\n" ;
  }
  
  /**
   * Adds JavaScript to the page to provide the value of a field in their user profile, allowing
   * JavaScript on the page to adjust behaviour depending on the value.
   * Provide an option called fieldName to specify the field to obtain the value for.
   */
  public static function js_user_field($auth, $args, $tabalias, $options, $path) {
    static $done_js_user_field=false;
    if (empty($options['fieldName']))
      return 'Please provide a setting @fieldName for the js_user_field control.';
    if (!function_exists('hostsite_get_user_field'))
      return 'Can\'t use the js_user_field extension without a hostsite_get_user_field function.';
    $val = hostsite_get_user_field($options['fieldName']);
    if ($val===true) 
      $val='true';
    elseif ($val===false) 
      $val='false';
    elseif (is_string($val)) 
      $val="'$val'";
    if (!$done_js_user_field) {
      data_entry_helper::$javascript .= "if (typeof indiciaData.userFields==='undefined') {
  indiciaData.userFields={};
}\n";
      $done_js_user_field=true;
    }
    data_entry_helper::$javascript .= "indiciaData.userFields['$options[fieldName]']=$val;\n" ;
  }
}
?>
