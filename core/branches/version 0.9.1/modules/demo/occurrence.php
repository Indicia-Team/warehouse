<?php
require '../../client_helpers/data_entry_helper.php';
require '../../client_helpers/map_helper.php';
require 'data_entry_config.php';
?>
<html>
<head>
<title>Record details demonstration</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<style type="text/css">
  td {
    padding: 0 0.5em;
  }
  #addCommentToggle, #commentForm, .comment {
    margin: 1em 0;
  }
  .comment-metadata {
    float: left;
    clear: left;
    width: 35%;
    background-color: #eee;
    padding: 1%;
    font-size: 9pt;
  }
  .comment-comment {
    float: right;
    width: 60%;
    padding: 1%;
  }
</style>
</head>
<body>
<div id="wrap">
  <h1>Record details</h1>
<?php
if ($_POST) {
  $response = data_entry_helper::forward_post_to('occurrence_comment');
  echo data_entry_helper::dump_errors($response);
}

if (empty($_GET['id'])) {
  echo 'This form requires an occurrence_id parameter in the URL.';
  return;
}
data_entry_helper::link_default_stylesheet();
// Get authorisation tokens to update and read from the Warehouse.
$auth = data_entry_helper::get_read_write_auth($config['website_id'], $config['password']);
data_entry_helper::load_existing_record($auth['read'], 'occurrence', $_GET['id']);
data_entry_helper::load_existing_record($auth['read'], 'sample', data_entry_helper::$entity_to_load['occurrence:sample_id']);
$r .= "<div id=\"controls\">\n";
$r .= "<table>\n";
$r .= "<tr><td><strong>".lang::get('Species')."</strong></td><td>".data_entry_helper::$entity_to_load['occurrence:taxon']."</td></tr>\n";
$r .= "<tr><td><strong>Date</strong></td><td>".data_entry_helper::$entity_to_load['sample:date']."</td></tr>\n";
$r .= "<tr><td><strong>Grid Reference</strong></td><td>".data_entry_helper::$entity_to_load['sample:entered_sref']."</td></tr>\n";
$siteLabels = array();
if (!empty(data_entry_helper::$entity_to_load['sample:location'])) $siteLabels[] = data_entry_helper::$entity_to_load['sample:location'];
if (!empty(data_entry_helper::$entity_to_load['sample:location_name'])) $siteLabels[] = data_entry_helper::$entity_to_load['sample:location_name'];
$r .= "<tr><td><strong>Site</strong></td><td>".implode(' | ', $siteLabels)."</td></tr>\n";
$smpAttrs = data_entry_helper::getAttributes(array(
    'id' => data_entry_helper::$entity_to_load['sample:id'],
    'valuetable'=>'sample_attribute_value',
    'attrtable'=>'sample_attribute',
    'key'=>'sample_id',
    'extraParams'=>$auth['read'],
    'survey_id'=>data_entry_helper::$entity_to_load['occurrence:survey_id']
));
$occAttrs = data_entry_helper::getAttributes(array(
    'id' => $_GET['occurrence_id'],
    'valuetable'=>'occurrence_attribute_value',
    'attrtable'=>'occurrence_attribute',
    'key'=>'occurrence_id',
    'extraParams'=>$auth['read'],
    'survey_id'=>data_entry_helper::$entity_to_load['occurrence:survey_id']
));
$attributes = array_merge($smpAttrs, $occAttrs);
foreach($attributes as $attr) {
  $r .= "<tr><td><strong>".lang::get($attr['caption'])."</strong></td><td>".$attr['displayValue']."</td></tr>\n";
}
$r .= "</table>\n";
$r .= "</div>\n";
$options = array(
  'readAuth' => $auth['read'],
  'presetLayers' => array('google_satellite'),
  'editLayer' => true,
  'layers' => array(),
  'initial_lat'=>52,
  'initial_long'=>-2,
  'initial_zoom'=>6,
  'width'=>400,
  'height'=>400,
  'standardControls'=>array('layerSwitcher','panZoom')
);
$options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['occurrence:wkt'];
$r .= map_helper::map_panel($options);
echo $r;
?>
<div id="addCommentToggle" class="indicia-button ui-widget ui-widget-content ui-state-default ui-corner-all">Add Comment</div>
<form method="post" id="commentForm">
<?php echo $auth['write']; ?>
<input type='hidden' name='occurrence_comment:occurrence_id' value='<?php echo $_GET['id']; ?>' />
<fieldset>
<legend>Add New Comment.</legend>
<?php
echo data_entry_helper::text_input(array(
  'label'=>'Email',
  'fieldname' => 'occurrence_comment:email_address'
));
echo data_entry_helper::text_input(array(
  'label' => 'Name',
  'fieldname'=>'occurrence_comment:person_name'
));
echo data_entry_helper::textarea(array(
  'label'=>'Comment',
  'fieldname'=>'occurrence_comment:comment'
));
data_entry_helper::add_resource('jquery_ui');
// leave comment form open if there are validation error messages
if (!isset($response['errors']))
  data_entry_helper::$javascript .= "$('#commentForm').hide();\n";
else
  data_entry_helper::$javascript .= "$('#addCommentToggle').hide();\n";
data_entry_helper::$javascript .= "$('#addCommentToggle').click(function() {
  $('#commentForm').show();
  $('#addCommentToggle').hide();
});
$('#cancelComment').click(function() {
  $('#commentForm').hide();
  $('#addCommentToggle').show();
});\n";
?>
</fieldset>
<input type='submit' id='submitComment' value='Save comment' />
<input type='button' id='cancelComment' value='Cancel' />
</form>
<?php
$comments = data_entry_helper::get_population_data(array(
  'table'=>'occurrence_comment',
  'extraParams' => array('nonce'=>$auth['read']['nonce'], 'auth_token'=>$auth['read']['auth_token'], 'occurrence_id'=>$_GET['id'],
      'orderby'=>'updated_on', 'sortdir'=>'desc')
));
foreach($comments as $comment) {
  echo '<div class="comment ui-widget ui-widget-content ui-corner-all">'.
      '<div class="comment-comment">'.$comment['comment'].'</div>'.
      '<div class="comment-metadata"><div class="comment-date">'.$comment['updated_on'].'</div>'.
      '<div class="comment-by">'.(empty($comment['person']) ? $comment['username'] : $comment['person']).'</div></div>'.
      '<div class="ui-helper-clearfix"></div>'.
      '</div>';
}
echo data_entry_helper::dump_javascript();
?>
</div>
</body>
</html>