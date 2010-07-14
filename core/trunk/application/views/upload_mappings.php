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
$filename = urlencode(basename($_SESSION['uploaded_csv']));
$extraParams = '';
// Are there any extra parameters that need to be sent along with the chunked upload?
foreach ($this->input->post() as $a => $b) {
  $extraParams .= "&$a=$b";
}
?>
<script type='text/javascript'>
$(document).ready(function(){
  var total=0;
  // handle the upload button to do a chunked upload
  $('#upload-button').click(function(e) {
    jQuery('#progress-bar').progressbar ({value: 0});
    jQuery('#progress').show();
    e.preventDefault();
    var mappings = {};
    jQuery.each($('form.cmxform select'), function(i, item) {
      mappings[item.id] = item.value;
    });
    jQuery.post("<?php echo url::site() . $controllerpath; ?>/cache_upload_mappings?uploaded_csv=<?php echo $filename; ?>",
        mappings, 
        function() {
          jQuery('#progress-text').html('0 records uploaded.');
          uploadChunk();
        }
    );
    
  });

  /**
  * Upload a single chunk of a file, by doing an AJAX get. If there is more, then on receiving the response upload the
  * next chunk.
  */
  uploadChunk = function() {
    var limit=10;
    jQuery.getJSON("<?php echo url::site() . $controllerpath; ?>/upload?offset="+total+"&limit="+limit+"&uploaded_csv=<?php echo $filename.$extraParams; ?>",
      function(response) {
        total = total + response.uploaded;
        jQuery('#progress-text').html(total + ' records uploaded.');
        $('#progress-bar').progressbar ('option', 'value', response.progress);
        if (response.uploaded>=limit) {
          uploadChunk();
        } else {
          jQuery('#progress-text').html('Upload complete.');
          window.location = "<?php echo url::site() . $controllerpath; ?>/display_upload_result/" + total + "?uploaded_csv=<?php echo $filename.$extraParams; ?>";
        }
      }
    );  
  };
});
</script>
<?php echo form::open($controllerpath.'/upload', array('class'=>'cmxform')); ?>
<p>Please map each column in the CSV file you are uploading to the associated attribute in the destination list.</p>
<br />
<table class="ui-widget ui-widget-content">
<thead class="ui-widget-header">
<tr><th>Column in CSV File</th><th>Maps to attribute</th></tr>
</thead>
<tbody>
<?php $options = html::model_field_options($model, '<please select>');
$i=0;
foreach ($columns as $col):
  echo '<tr class="';
  echo ($i % 2 == 0) ? 'evenRow">' : 'oddRow">';
  $i++;  ?>
    <td><?php echo $col; ?></td>
    <td><select <?php echo 'id="'.$col.'" name="'.$col.'">'.$options; ?></select></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>
<input type="Submit" value="Upload Data" id="upload-button" />
<br/>
<div id="progress" class="ui-widget ui-widget-content ui-corner-all" style="display: none; ">
<div id="progress-bar" style="width: 400"></div>
<div id="progress-text">Preparing to upload.</div>
</div>
<?php
// We stick these at the bottom so that all the other things will be parsed first
foreach ($this->input->post() as $a => $b) {
  print form::hidden($a, $b);
}
?>
</form>

