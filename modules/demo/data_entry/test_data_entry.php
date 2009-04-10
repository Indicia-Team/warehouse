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
<p>Note that this page requires the PHP curl extension to send requests to the Indicia server.</p>
<?php
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

  // We have occurrence attributes that we have to wrap
  $occAttrs = data_entry_helper::wrap_attributes($_POST, 'occurrence');
  $smpAttrs = data_entry_helper::wrap_attributes($_POST, 'sample');

  $sampleMod = data_entry_helper::wrap($_POST, 'sample');
  $sampleMod['metaFields']['smpAttributes']['value'] = $smpAttrs;

  $occurrenceMod = data_entry_helper::wrap($_POST, 'occurrence');
  $occurrenceMod['superModels'][] = array
  (
  'fkId' => 'sample_id',
   'model' => $sampleMod
   );
   $occurrenceMod['metaFields']['occAttributes']['value'] = $occAttrs;

   // Send the image
   if ($name = data_entry_helper::handle_media('occurrence_image'))
   {
     // Add occurrence image model
     // TODO Get a caption for the image
     $oiFields = array(
     'path' => $name,
           'caption' => 'An image in need of a caption');
           $oiMod = data_entry_helper::wrap($oiFields, 'occurrence_image');
           $occurrenceMod['subModels'][] = array(
           'fkId' => 'occurrence_id',
                   'model' => $oiMod);
   }

   $submission = array('submission' => array('entries' => array(
   array ( 'model' => $occurrenceMod )
   )));
   $response = data_entry_helper::forward_post_to(
   'save', $submission);
   data_entry_helper::dump_errors($response);
}
else if ($_GET)
{
  if (array_key_exists('id', $_GET))
  {
    $url = 'http://localhost/indicia/index.php/services/data/occurrence/'.$_GET['id'];
    $url .= "?mode=json&view=detail";
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $entity = json_decode(curl_exec($session), true);
    $entity = $entity[0];
  }
  else
  {
    $entity = null;
  }
}

function field($field)
{
  global $entity;
  if ($entity != null && array_key_exists($field, $entity))
  {
    return $entity[$field];
  }
  else
  {
    return '';
  }
}

?>
<form method="post" enctype="multipart/form-data" >

<?php
// This PHP call demonstrates inserting authorisation into the form, for website ID
// 1 and password 'password'
echo data_entry_helper::get_auth(1,'password');
$readAuth = data_entry_helper::get_read_auth(1, 'password');
?>
<input type='hidden' id='website_id' name='website_id' value='1' />
<input type='hidden' id='record_status' name='record_status' value='C' />
<input type='hidden' id='id' name='id' value='<?php echo field('id'); ?>' />
<label for='actaxa_taxon_list_id'>Taxon</label>
<?php echo data_entry_helper::autocomplete('taxa_taxon_list_id', 'taxa_taxon_list', 'taxon', 'id', $readAuth, field('taxon'), field('taxa_taxon_list_id')); ?>
<br/>
<label for="date">Date:</label>
<?php echo data_entry_helper::date_picker('date'); ?>
<br />
<?php echo data_entry_helper::map('map', array('google_physical', 'virtual_earth'), true, true, null, true); ?>
<br />
<label for="location_name">Locality Description:</label>
<input name="location_name" class="wide" value='<?php echo field('location_name'); ?>'/><br />
<label for="survey_id">Survey</label>
<?php echo data_entry_helper::select('survey_id', 'survey', 'title', 'id', $readAuth, field('survey_id')); ?>
<br />
<label for='acdeterminer_id'>Determiner</label>
<?php echo data_entry_helper::autocomplete('determiner_id', 'person', 'caption', 'id', $readAuth, field('determiner'), field('determiner_id')); ?>
<br />
<label for='comment'>Comment</label>
<textarea id='comment' name='comment' class="wide"><?php echo field('comment'); ?></textarea>
<br />
<label for='occurrence_image'>Image Upload</label>
<?php echo data_entry_helper::image_upload('occurrence_image'); ?>
<fieldset>
<legend>Occurrence attributes</legend>
<label for='<?php echo $config['dafor']; ?>'>Abundance DAFOR</label>
<?php echo data_entry_helper::select($config['dafor'], 'termlists_term', 'term', 'id', $readAuth + array('termlist_id' => $config['dafor_termlist'])); ?>
<br />
<label for='<?php echo $config['det_date']; ?>'>Determination Date</label>
<input type='text' name='<?php echo $config['det_date']; ?>' id='<?php echo $config['det_date']; ?>'/><br />
</fieldset>
<fieldset>
<legend>Sample attributes</legend>
<label for='<?php echo $config['weather']; ?>'>Weather</label>
<input type='text' name='<?php echo $config['weather']; ?>' class="wide" id='<?php echo $config['weather']; ?>'/><br />
<label for='<?php echo $config['temperature']; ?>'>Temperature (Celsius)</label>
<input type='text' name='<?php echo $config['temperature']; ?>' id='<?php echo $config['temperature']; ?>'/><br />
<label for='<?php echo $config['surroundings']; ?>'>Surroundings</label>
<div style="display: inline-block"><?php echo data_entry_helper::radio_group($config['surroundings'], 'termlists_term', 'term', 'id', $readAuth + array('termlist_id' => $config['surroundings_termlist']), '<br />'); ?></div>
<br/>
<label for='<?php echo $config['site_usage']; ?>[]'>Site Usage</label>
<?php echo data_entry_helper::listbox($config['site_usage'].'[]', 'termlists_term', 'term', 4, true, 'id', $readAuth + array('termlist_id' => $config['site_usage_termlist'])); ?>
</fieldset>
<input type="submit" value="Save" />
</form>
</div>
</body>
<?php echo data_entry_helper::dump_javascript(); ?>
</html>
