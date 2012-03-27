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
 * @package	Taxon Designations
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
?>
<script type="text/javascript">
// <![CDATA[

$(document).ready(function() {
  
var total=0;

/**
* Upload a single chunk of files, by doing an AJAX get. If there is more, then on receiving the response upload the
* next chunk.
*/
uploadChunk = function() {
  $.ajax({
    url: '<?php echo url::base(); ?>index.php/verification_rule/upload_rule_file?uploadId=<?php echo $uploadId; ?>&totaldone='+total,
    dataType: 'json',
    success: function(response) {
      if (typeof response.error!=='undefined') {
        $('#messages div').append(response.error + '<br/>');
      } else {
        $('#messages div').append('File '+response.lastfile+' done.<br/>');
      }
      $('#progress-bar').progressbar ('option', 'value', (total*10) % 100);
      total++;
      if (typeof response.complete!=="undefined") {
        jQuery('#progress-text').html('Upload complete.');
        $('#progress').hide();
        $('#link').show();
      } else {
        uploadChunk();
      }
    },
    error: function(jqXHR, textStatus, errorThrown) {
      alert('Error occurred, please check the warehouse logs.'); 
    }
  });
};

jQuery('#progress-bar').progressbar ({value: 0});
uploadChunk();
});
// ]]>
</script>
<label id="progress">Uploader....
<div id="progress-bar"></div></label>
<div id="link" style="display: none">Import Complete<br/><a href="<?php echo url::base(); ?>index.php/verification_rule">Return to the Verification Rules list</a></div>
<br/>
<label id="messages">Output:
<div style="height: 400px; width: 100%; border: solid silver 1px; overflow: auto"></div>
</label>
