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
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
 require_once(DOCROOT.'client_helpers/data_entry_helper.php');
 
 $readAuth = data_entry_helper::get_read_auth(0-$_SESSION['auth_user']->id, kohana::config('indicia.private_key'));
 ?>
<form class="iform" action="<?php echo url::site().'trigger/save'; ?>" method="post">
<fieldset>
<legend>Enter the parameters for the trigger</legend>
<?php
  // dump out the previous page form's values
 foreach ($_POST as $key=>$value) { 
   if ($key!='submit' && substr($key, 0, 6)!='param-')
     echo "<input type=\"hidden\" name=\"$key\" value=\"$value\"/>\n";
 }
 // Ask the report grid code to build us a parameters form.
 echo data_entry_helper::report_grid(array(
   'id' => 'params',
   'paramsOnly' => true,   
   'dataSource' => $_POST['trigger:trigger_template_file'],
   'readAuth' => $readAuth,
   'ignoreParams' => array('date'),
   'completeParamsForm' => false,
   'paramDefaults' => $other_data['defaults']
 ));
 ?>
 </fieldset>
 <?php echo html::form_buttons(true); ?>
 </form>