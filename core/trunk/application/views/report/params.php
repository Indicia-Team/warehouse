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
?>
<script type='text/javascript'>
(function($) {
  $(document).ready(function() {
    $('.date').datepicker({dateFormat : 'yy-mm-dd', constrainInput: true});
  });
})(jQuery);
</script>
<div>
<form class='cmxform' action='<?php echo url::site().'report/resume/'.$request['uid']; ?>' method='post'>
<fieldset>
<legend>Parameters</legend>
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
       echo "<input class='date' type='text' name='$name' id='name' />";
       break;
    default:
      echo "<input type='text' name='$name' id='name' />";
  }
  echo "</li>";
}
?>
</ol>
</fieldset>
<input type='submit' value='Submit' />
</form>
</div>