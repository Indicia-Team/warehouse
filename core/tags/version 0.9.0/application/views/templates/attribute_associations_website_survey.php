<fieldset><legend><?php echo $other_data['name']; ?> Attribute
Website/Survey Allocation</legend>
<?php
if (!is_null($this->auth_filter)) {
  $websites = ORM::factory('website')->in('id',$this->auth_filter['values'])->where(array('deleted'=>'f'))->orderby('title','asc')->find_all();
  echo '<input type="hidden" name="restricted-to-websites" value="'.implode(',', $this->auth_filter['values']).'"/>';
} else
  $websites = ORM::factory('website')->where(array('deleted'=>'f'))->orderby('title','asc')->find_all();
foreach ($websites as $website) {
  $webrec = ORM::factory($other_data['webrec_entity'])->where(array($other_data['webrec_key'] => $model->id,
                            'website_id' => $website->id,
                            'restrict_to_survey_id IS' => null,
                            'deleted' => 'f'))->find();

  echo '<div class="ui-corner-all ui-widget"><div class="ui-corner-all ui-widget-header">'.$website->title.'</div><ol><li><label for="website_'.$website->id.'" class="wide" >'.$website->title.': non survey specific</label>';
  echo form::checkbox('website_'.$website->id, TRUE, $webrec->loaded, 'class="vnarrow"');
  echo "</li>";
  $surveys = ORM::factory('survey')->where(array('website_id'=>$website->id, 'deleted'=>'f'))->orderby('title','asc')->find_all();
  foreach ($surveys as $survey) {
    $webrec = ORM::factory($other_data['webrec_entity'])->where(array($other_data['webrec_key'] => $model->id,
                            'website_id' => $website->id,
                            'restrict_to_survey_id' => $survey->id,
                            'deleted'=>'f'))->find();
    echo '<li><label for="website_'.$website->id.'_'.$survey->id.'" class="wide" >'.$website->title.':'.$survey->title.'</label>';
    echo form::checkbox('website_'.$website->id.'_'.$survey->id, TRUE, $webrec->loaded, 'class="vnarrow"');
    echo "</li>";
  }
  echo '</ol></div>';

}
?>
</fieldset>