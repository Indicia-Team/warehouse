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
