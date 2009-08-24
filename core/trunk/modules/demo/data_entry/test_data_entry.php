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
  data_entry_helper::dump_errors($response);
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
<label for='occurrence:taxa_taxon_list_id:taxon'>Taxon:</label>
<?php echo data_entry_helper::autocomplete('occurrence:taxa_taxon_list_id', 'taxa_taxon_list', 'taxon', 'id', $readAuth); ?>
<br/>
<?php echo data_entry_helper::date_picker(array('label'=>'Date','fieldname'=>'sample:date')); ?>
<?php echo data_entry_helper::map(); ?>
<br />
<label for="sample:location_name">Locality Description:</label>
<input name="sample:location_name" class="wide" value='<?php echo data_entry_helper::check_default_value('location_name'); ?>'/><br />
<label for="sample:survey_id">Survey:</label>
<?php echo data_entry_helper::select('sample:survey_id', 'survey', 'title', 'id', $readAuth); ?>
<br />
<label for='occurrence:determiner_id:caption'>Determiner:</label>
<?php echo data_entry_helper::autocomplete('occurrence:determiner_id', 'person', 'caption', 'id', $readAuth); ?>
<br />
<?php echo data_entry_helper::textarea(array('label'=>'Comment', 'fieldname'=>'sample:comment')); ?>
<label for='occurrence_image'>Image Upload:</label>
<?php echo data_entry_helper::image_upload('occurrence:image'); ?>
<fieldset>
<legend>Occurrence attributes</legend>
<label for='<?php echo $config['dafor']; ?>'>Abundance DAFOR:</label>
<?php echo data_entry_helper::select($config['dafor'], 'termlists_term', 'term', 'id', $readAuth + array('termlist_id' => $config['dafor_termlist'])); ?>
<br />
<?php echo data_entry_helper::text_input(array('label'=>'Determination Date', 'fieldname'=>$config['det_date'])); ?>
</fieldset>
<fieldset>
<legend>Sample attributes</legend>
<label for='<?php echo $config['weather']; ?>'>Weather:</label>
<input type='text' name='<?php echo $config['weather']; ?>' class="wide" id='<?php echo $config['weather']; ?>'/><br />
<label for='<?php echo $config['temperature']; ?>'>Temperature (Celsius):</label>
<input type='text' name='<?php echo $config['temperature']; ?>' id='<?php echo $config['temperature']; ?>'/><br />
<label for='<?php echo $config['surroundings']; ?>'>Surroundings:</label>
<div style="display: inline-block"><?php echo data_entry_helper::radio_group($config['surroundings'], 'termlists_term', 'term', 'id', $readAuth + array('termlist_id' => $config['surroundings_termlist']), '<br />'); ?></div>
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
