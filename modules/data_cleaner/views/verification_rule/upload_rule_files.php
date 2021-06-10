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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */
?>
<script type="text/javascript">
// <![CDATA[

$(document).ready(function() {

var totaldone=0,
  totalerrors=0,
  uploadId='<?php echo $uploadId; ?>',
  requiresFetch=<?php echo $requiresFetch; ?>;

function dumpErrors(response) {
  if (typeof response.errors!=="undefined") {
    $.each(response.errors, function(idx, error) {
      $('#messages').append('<div class="error">' + error + '</div>');
    });
  }
}

function fetchFileChunk() {
  $('#progress-text').text('Please wait - fetching rule files from the server...');
  $.ajax({
    url: '<?php echo url::base(); ?>index.php/verification_rule/fetch_file_chunk?uploadId='+uploadId,
    dataType: 'json',
    success: function(response) {
      $('#progress-bar').val(response.progress);
      dumpErrors(response);
      // can't go on if we fail to even load a file
      if (typeof response.errors==="undefined") {
        if (response.progress===100) {
          $('#progress-bar').val(0);
          $('#progress-text').text('Please wait - processing rule files...');
          uploadChunk();
        } else {
          fetchFileChunk();
        }
      }
    }
  });
}

/**
* Upload a single chunk of files, by doing an AJAX get. If there is more, then on receiving the response upload the
* next chunk.
*/
uploadChunk = function() {
  $.ajax({
    url: '<?php echo url::base(); ?>index.php/verification_rule/upload_rule_file?uploadId='+uploadId+'&totaldone='+totaldone,
    dataType: 'json',
    success: function(response) {
      dumpErrors(response);
      totalerrors += response.errors;
      if (totalerrors>0) {
        $('#errors-notice span').html(totalerrors);
        $('#errors-notice').show();
      }
      $.each(response.files, function(idx, file) {
        $('#messages').append('<div class="ok">' + file + ' done</div>');
      });
      $('#messages').scrollTop(999999);
      $('#progress-bar').val(response.progress);
      totaldone=response.totaldone;
      if (typeof response.complete!=="undefined") {
        $('#progress-text').html('Upload complete.');
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

$('#progress-bar').val(0);
if (requiresFetch) {
  fetchFileChunk();
} else {
  uploadChunk();
}

});
// ]]>
</script>
<p>The selected Record Cleaner rule files are being imported. As some of the rule files can be quite large, it may take a few seconds to import each one so please be patient.</p>
<div class="error" style="display: none" id="errors-notice"><span>0</span> error(s) have been reported.</div>
<label id="progress-text">Please wait....</label>
<progress id="progress-bar" class="progress" value="0" max="100"></progress>
<div id="link" style="display: none">Import Complete<br/><a href="<?php echo url::base(); ?>index.php/verification_rule">Return to the Verification Rules list</a></div>
<br/>
<label>Output:</label>
<div id="messages" style="height: 400px; width: 100%; border: solid silver 1px; overflow: auto"></div>
