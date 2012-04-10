<fieldset><legend><?php echo $other_data['name']; ?> Attribute
Website Allocation</legend>
<p>Please tick the boxes for the websites that this attribute is available for.</li>
<?php
if (!is_null($this->auth_filter))
  $websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->where(array('deleted'=>'f'))->orderby('title','asc')->find_all();
else
  $websites = ORM::factory('website')->where(array('deleted'=>'f'))->orderby('title','asc')->find_all();
echo '<ol>';
foreach ($websites as $website) {
  $webrec = ORM::factory($other_data['webrec_entity'])->where(array($other_data['webrec_key'] => $model->id,
                            'website_id' => $website->id,
                            'deleted' => 'f'))->find();

  echo '<li><label for="website_'.$website->id.'" class="wide" >'.$website->title.'</label>';
  echo form::checkbox('website_'.$website->id, TRUE, $webrec->loaded, 'class="vnarrow"');
  echo "</li>";
}
echo '</ol>';
?>
</fieldset>