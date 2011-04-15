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

// Output links for jquery ui datepicker.
echo html::stylesheet(array(
  'media/css/jquery-ui.custom.css'
), false);
echo html::script(array(
  'media/js/jquery.js',
  'media/js/jquery-ui.custom.min.js'
), false);

$request=$report['content'];
?>
<script type='text/javascript'>
(function($) {
  $(document).ready(function() {
    $('.date').datepicker({dateFormat : '<?php echo kohana::lang('dates.format_js'); ?>', constrainInput: true});
  });
})(jQuery);
</script>
<div>
<p><?php echo $report['description']['description']; ?></p>
<form class='cmxform widelabels' action='<?php echo url::site().'report/resume/'.$request['uid']; ?>' method='post'>
<fieldset>
<legend>Parameters required:</legend>
<ol>
<?php
foreach ($request['parameterRequest'] as $name => $det)
{
  list($datatype, $display, $description) = array($det['datatype'], $det['display'], $det['description']);
  $label = $display ? $display : $name;
  echo "<li><label for='$name'>$label</label>";
  switch($datatype)
  {
    case 'date':
      echo "<input class='date' type='text' name='$name' id='$name' title='$description'/>";
      break;
    case 'lookup':
      echo "<select class='lookup' name='$name' id='$name' title='$description'>";
      // output the possible values for this lookup
      $values = $det['lookup_values'];

      foreach ($values as $row=>$data) {
        echo '<option value="'.$data->id.'">'.$data->caption.'</option>"';
      }
      echo "</select>";
      break;
    default:
      echo "<input type='text' name='$name' id='$name' title='$description'/>";
  }
  echo "</li>";
}
?>
</ol>
</fieldset>
<input type='submit' value='Submit' />
</form>
</div>