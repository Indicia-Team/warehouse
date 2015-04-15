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
   * User can also provide a value in braces to replace with the Drupal field for current user e.g. {field_indicia_user_id}</li>
   * <li><b>onlyShowWhenLoggedInStatus</b><br/>
   * If 1, then only show button for logged in users. If 2, only show link for users who are not logged in.
   * </ul>
   */
  public static function button_link($auth, $args, $tabalias, $options, $path) {
    global $user;
    //Check if we should only show button for logged in/or none logged in users
    if ((!empty($options['onlyShowWhenLoggedInStatus'])&&
           (($options['onlyShowWhenLoggedInStatus']==1 && $user->uid!=0)||
           ($options['onlyShowWhenLoggedInStatus']==2 && $user->uid===0)||
           ($options['onlyShowWhenLoggedInStatus']==false)))||
        empty($options['onlyShowWhenLoggedInStatus'])) {
      //Only display a button if the administrator has specified both a label and a link path for the button.
      if (!empty($options['buttonLabel'])&&!empty($options['buttonLinkPath'])) {
        if (!empty($options['paramNameToPass']) && !empty($options['paramValueToPass'])) {
          //If the param value to pass is in braces, then collect the Drupal field where the name is between the braces e.g. field_indicia_user_id
          if (substr($options['paramValueToPass'], 0, 1)==='{'&&substr($options['paramValueToPass'], -1)==='}') {
            //Chop of the {}
            $options['paramValueToPass']=substr($options['paramValueToPass'], 1, -1);
            //hostsite_get_user_field doesn't want field or profile at the front.
            $prefix = 'profile_';
            if (substr($options['paramValueToPass'], 0, strlen($prefix)) == $prefix) {
              $options['paramValueToPass'] = substr($options['paramValueToPass'], strlen($prefix));
            } 
            $prefix = 'field_';
            if (substr($options['paramValueToPass'], 0, strlen($prefix)) == $prefix) {
              $options['paramValueToPass'] = substr($options['paramValueToPass'], strlen($prefix));
            } 
            $paramValueFromUserField=hostsite_get_user_field($options['paramValueToPass']);
            //If we have collected the user field from the profile, then overwrite the existing value.
            if (!empty($paramValueFromUserField))
              $options['paramValueToPass']=$paramValueFromUserField;
          }
          $paramToPass=array($options['paramNameToPass']=>$options['paramValueToPass']);
        }
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
    } else
      return '';
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
   * The value of the static parameter to pass to the receiving page. e.g. passing a static location_type_id. Optional but requires paramNameToPass when in use.
   * User can also provide a value in braces to replace with the Drupal field for current user e.g. {field_indicia_user_id}</li>
   * <li><b>onlyShowWhenLoggedInStatus</b><br/>
   * If 1, then only show link for logged in users. If 2, only show link for users who are not logged in.
   * <li><b>anchorId</b><br/>
   * Optional id for anchor link. This might be useful, for example, if you want to reference the anchor with jQuery to set the path in real-time.
   * </ul>
   */
  public static function text_link($auth, $args, $tabalias, $options, $path) {
    global $user;
    //Check if we should only show link for logged in/or none logged in users
    if ((!empty($options['onlyShowWhenLoggedInStatus'])&&
           (($options['onlyShowWhenLoggedInStatus']==1 && $user->uid!=0)||
           ($options['onlyShowWhenLoggedInStatus']==2 && $user->uid===0)||
           ($options['onlyShowWhenLoggedInStatus']==false)))||
        empty($options['onlyShowWhenLoggedInStatus'])) {
      //Only display a link if the administrator has specified both a label and a link.
      if (!empty($options['label'])&&!empty($options['linkPath'])) {
        if (!empty($options['paramNameToPass']) && !empty($options['paramValueToPass'])) {
          //If the param value to pass is in braces, then collect the Drupal field where the name is between the braces e.g. field_indicia_user_id
          if (substr($options['paramValueToPass'], 0, 1)==='{'&&substr($options['paramValueToPass'], -1)==='}') {
            //Chop of the {}
            $options['paramValueToPass']=substr($options['paramValueToPass'], 1, -1);
            //hostsite_get_user_field doesn't want field or profile at the front.
            $prefix = 'profile_';
            if (substr($options['paramValueToPass'], 0, strlen($prefix)) == $prefix) {
              $options['paramValueToPass'] = substr($options['paramValueToPass'], strlen($prefix));
            } 
            $prefix = 'field_';
            if (substr($options['paramValueToPass'], 0, strlen($prefix)) == $prefix) {
              $options['paramValueToPass'] = substr($options['paramValueToPass'], strlen($prefix));
            } 
            $paramValueFromUserField=hostsite_get_user_field($options['paramValueToPass']);
            //If we have collected the user field from the profile, then overwrite the existing value.
            if (!empty($paramValueFromUserField))
              $options['paramValueToPass']=$paramValueFromUserField;
          }
          $paramToPass=array($options['paramNameToPass']=>$options['paramValueToPass']);
        }
        $button = '<div>';
        //If an id option for the anchor is supplied then set the anchor id.
        //This might be useful, for example, if you want to reference the anchor with jQuery to set the path in real-time.
        if (!empty($options['anchorId']))
          $button .= "  <a id=\"".$options['anchorId']."\" ";
        else 
          $button .= "  <a  ";
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
    } else
      return '';
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
    data_entry_helper::$javascript .= "indiciaData.permissions['$options[permissionName]']=$val;\n";
    return '';
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
    data_entry_helper::$javascript .= "indiciaData.userFields['$options[fieldName]']=$val;\n";
    return '';
  }
  
  public static function data_entry_helper_control($auth, $args, $tabalias, $options, $path) {
    $ctrl = $options['control'];
    if (isset($options['extraParams']))
      $options['extraParams'] = $auth['read'] + $options['extraParams'];
    return data_entry_helper::$ctrl($options);
  }
  
  /**
   * Adds a Drupal breadcrumb to the page.
   * Pass a parameter called @options, containing an associative array of paths and captions.
   * The paths can contain replacements wrapped in # characters which will be replaced by the $_GET
   * parameter of the same name.
   */
  public static function breadcrumb($auth, $args, $tabalias, $options, $path) {
    if (!isset($options['path']))
      return 'Please set an array of entries in the @path option';
    $breadcrumb[] = l('Home', '<front>');
    foreach ($options['path'] as $path => $caption) {
      $parts = explode('?', $path, 2);
      $options = array();
      if (count($parts)>1) {
        foreach ($_REQUEST as $key=>$value) {
          // GET parameters can be used as replacements.
          $parts[1] = str_replace("#$key#", $value, $parts[1]);
        }
        $query = array();
        parse_str($parts[1], $query);
        $options['query'] = $query;
      }
      $path = $parts[0];
      // don't use Drupal l function as a it messes with query params
      $caption = lang::get($caption);
      $breadcrumb[] = l($caption, $path, $options);
    }
    $breadcrumb[] = drupal_get_title();
    drupal_set_breadcrumb($breadcrumb);
    return '';
  }
  
  /*
   * Simply add this extension to your form's form structure to make the page read only. Might need expanding to 
   * take into account different scenarios
   */
  public static function read_only_input_form($auth, $args, $tabalias, $options, $path) {
    data_entry_helper::$javascript .= "
    $('#entry_form').find('input, textarea, text, button').attr('readonly', true);
    $('#entry_form').find('select,:checkbox').attr('disabled', true);\n 
    $('.indicia-button').hide();\n"; 
  }

  /**
   * Sets the page title according to an option. The title can refer to the URL query
   * string parameters as tokens.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options
   * @param $path
   * @return string
   */
  public static function set_page_title($auth, $args, $tabalias, $options, $path) {
    if (!isset($options['title']))
      return 'Please set the template for the title in the @title parameter';
    foreach($_GET as $key => $value)
      $options['title'] = str_replace("#$key#", $value, $options['title']);
    hostsite_set_page_title($options['title']);
    return '';
  }
}