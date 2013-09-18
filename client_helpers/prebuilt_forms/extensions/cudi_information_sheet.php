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
 * Extension class that supplies a new control which allows the user to click on a button to navigate to the cudi form page.
 */
class extension_cudi_information_sheet {
  /*
   * Control is not visible to user, instead it appends "dynamic-" to the front of the $_GET parameter which is then used
   * by the system "behind the scenes" to automatically load that parameter into reports on that page.
   * The parameter is currently always 'id' because if a count unit page is saved and the user is returned to the Cudi Information
   * Sheet, then the code that automatically handles that return is designed to supply 'id' as the parameter to the Cudi Information Sheet.
   */
  public function autoLoadReportParamFromGet($auth, $args, $tabalias, $options, $path) {
    $_REQUEST['dynamic-id']=$_GET['id'];
  }
  /*
   * A button link to the cudi form for the same location as being viewing on the information sheet
   */
  public function cudiFormButtonLink($auth, $args, $tabalias, $options, $path) {
    global $base_url;
    $cudiFormOptions = explode('|',$options['cudiFormOptions']);
    $cudiFormPath = $cudiFormOptions[0];
    $cudiFormParam = $cudiFormOptions[1];
    $cudi_form_url=(variable_get('clean_url', 0) ? '' : '?q=').$cudiFormPath.(variable_get('clean_url', 0) ? '?' : '&').$cudiFormParam.'='.$_GET[$options['urlParameter']];
    $cudiFormButtonLink = '<div>If you think any of this information is incorrect please submit a CUDI form</br>';
    $cudiFormButtonLink .= 
    "<FORM>
      <INPUT Type=\"BUTTON\" VALUE=\"Cudi Form\" ONCLICK=\"window.location.href='$cudi_form_url'\">
    </FORM>";
    return $cudiFormButtonLink;
  }  
}
  