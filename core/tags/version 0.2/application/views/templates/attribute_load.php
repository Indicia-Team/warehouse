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

?>
<div id='attribute_load'>
<fieldset>
<legend>Reuse Attribute</legend>
<ol>
<li>
<label for="load_attr_id">Existing Attribute</label>
<select id="load_attr_id" name="load_attr_id" >
  <option value=''>&lt;Please Select&gt;</option>
<?php
  $website_list = array();
  if (!is_null($website_id)) {
    $website_attrs = ORM::factory($model->object_name.'s_website')->where('website_id',$website_id)->find_all();
    foreach ($website_attrs as $website_attr) {
      $attr = ORM::factory($model->object_name, $website_attr->__get($model->object_name.'_id'));
      echo '	<option value="'.$attr->id.'">'.$attr->caption.'</option>';
      $website_list[] = $attr->id;
    }
  }
  $public_attrs = ORM::factory($model->object_name)->where('public','t')->orderby('caption','asc')->find_all();
  foreach ($public_attrs as $attr) {
    if (!in_array($attr->id, $website_list))
      echo '	<option value="'.$attr->id.'">'.$attr->caption.' (Public)</option>';
  }
?>
</select>
<?php if ( ! empty($error_message) )
{
    echo html::error_message($error_message);
}
?>
</li>
<input type="submit" value="Reuse" name="submit" class="default"/>
</ol>
</fieldset>
</div>
