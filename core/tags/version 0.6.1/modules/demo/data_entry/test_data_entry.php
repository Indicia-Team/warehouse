<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<?php
include '../../../client_helpers/data_entry_helper.php';
include '../data_entry_config.php';
?>
<title>Indicia external site data entry test page</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Indicia Data entry test</h1>
<?php
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);

// PHP to catch and submit the POST data from the form - we need to wrap
// some things manually in order to get the supermodel in.
if ($_POST)
{
  // Replace the site usage array with a comma sep list
  if (array_key_exists($config['site_usage'], $_POST))
  {
    if (is_array($_POST[$config['site_usage']]))
    {
      $_POST[$config['site_usage']] = implode(',',$_POST[$config['site_usage']]);
    }
  }

  $submission = data_entry_helper::build_sample_occurrence_submission($_POST);
  $response = data_entry_helper::forward_post_to('save', $submission);
  echo data_entry_helper::dump_errors($response);
}
else if ($_GET)
{
  if (array_key_exists('id', $_GET))
  {
    $url = 'http://localhost/indicia/index.php/services/data/occurrence/'.$_GET['id'];
    $url .= "?mode=json&view=detail&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth["nonce"];
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $entity = json_decode(curl_exec($session), true);
    $entity_to_load = $entity[0];
  }
  else
  {
    $entity = null;
  }
}

global $reload_post_data;
$reload_post_data=true;

?>
<form method="post" enctype="multipart/form-data" >

<?php
// Get authentication information
echo data_entry_helper::get_auth($config['website_id'], $config['password']);
?>
<input type='hidden' id='website_id' name='website_id' value='1' />
<input type='hidden' id='record_status' name='record_status' value='C' />
<input type='hidden' id='id' name='id' value='<?php echo data_entry_helper::check_default_value('id'); ?>' />
<?php
echo data_entry_helper::autocomplete(array(
    'label'=>'Taxon',
    'fieldname'=>'occurrence:taxa_taxon_list_id',
    'table'=>'taxa_taxon_list',
    'captionField'=>'taxon',
    'valueField'=>'id',
    'extraParams'=>$readAuth
));
echo data_entry_helper::date_picker(array(
    'label'=>'Date',
    'fieldname'=>'sample:date'
));
echo data_entry_helper::map();
echo data_entry_helper::text_input(array(
    'label'=>'Locality Description',
    'fieldname'=>'sample:location_name',
    'class'=>'wide'
));
echo data_entry_helper::select(array(
    'label'=>'Survey',
    'fieldname'=>'sample:survey_id',
    'table'=>'survey',
    'captionField'=>'title',
    'valueField'=>'id',
    'extraParams' => $readAuth
)); ?>
<br />
<label for='occurrence:determiner_id:caption'>Determiner:</label>
<?php echo data_entry_helper::autocomplete('occurrence:determiner_id', 'person', 'caption', 'id', $readAuth); ?>
<br />
<?php echo data_entry_helper::textarea(array('label'=>'Comment', 'fieldname'=>'sample:comment')); ?>
<?php echo data_entry_helper::image_upload(array(
  'label' => 'Image Upload',
  'fieldname' => 'occurrence:image'
)); ?>
<fieldset>
<legend>Occurrence attributes</legend>
<label for='<?php echo $config['dafor']; ?>'>Abundance DAFOR:</label>
<?php echo data_entry_helper::select($config['dafor'], 'termlists_term', 'term', 'id', $readAuth + array('termlist_id' => $config['dafor_termlist'])); ?>
<br />
<?php echo data_entry_helper::text_input(array('label'=>'Determination Date', 'fieldname'=>$config['det_date'])); ?>
</fieldset>
<fieldset>
<legend>Sample attributes</legend>
<?php 
echo data_entry_helper::text_input(array(
  'label' => 'Weather',
  'fieldname' => $config['weather'],
  'class' => 'control-width-6'
)); 
echo data_entry_helper::text_input(array(
  'label' => 'Temperature (Celcius)',
  'fieldname' => $config['temperature']
));
?>
<?php 
echo data_entry_helper::radio_group(array(
  'label' => 'Surroundings',
	'fieldname' => $config['surroundings'], 
	'table' => 'termlists_term', 
	'captionField' => 'term', 
	'valueField' => 'id', 
	'extraParams' => $readAuth + array('termlist_id' => $config['surroundings_termlist']), 
	'sep' => '<br />'
)); 
?>
	
<br/>
<label for='<?php echo $config['site_usage']; ?>[]'>Site Usage:</label>
<?php echo data_entry_helper::listbox($config['site_usage'].'[]', 'termlists_term', 'term', 4, true, 'id', $readAuth + array('termlist_id' => $config['site_usage_termlist'])); ?>
</fieldset>
<input type="submit" value="Save" />
</form>
</div>
</body>
<?php echo data_entry_helper::dump_javascript(); ?>
</html>
