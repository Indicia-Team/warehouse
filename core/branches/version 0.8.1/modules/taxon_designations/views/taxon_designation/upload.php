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
/**
* Upload a single chunk of a file, by doing an AJAX get. If there is more, then on receiving the response upload the
* next chunk.
*/
uploadChunk = function() {
  var limit=50;
  var filename='<?php echo $_GET['file']; ?>';
  $.ajax({
    url: '<?php echo url::base(); ?>index.php/taxon_designation/upload?offset='+total+'&limit='+limit+'&filepos='+filepos+'&uploaded_csv='+filename,
    dataType: 'json',
    success: function(response) {
      total = total + response.uploaded;
      filepos = response.filepos;
      jQuery('#progress-text').html(total + ' records uploaded.');
      $('#progress-bar').progressbar ('option', 'value', response.progress);
      if (response.uploaded>=limit) {
        uploadChunk();
      } else {
        jQuery('#progress-text').html('Upload complete.');
        window.location = '<?php echo url::base(); ?>index.php/taxon_designation/upload_complete?total='+total+'&uploaded_csv='+filename;
      }
    },
    error: function(jqXHR, textStatus, errorThrown) {
      alert('error'); 
    }
  });  
};

var total=0, filepos=0;
jQuery('#progress-bar').progressbar ({value: 0});
uploadChunk();
});
// ]]>
</script>
Uploader....
<div id="progress-bar"></div>
<div id="progress-text"></div>
