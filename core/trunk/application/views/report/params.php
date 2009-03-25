<?php
echo html::stylesheet(array
(
'media/css/ui.datepicker.css'
), false);
echo html::script(array
(
'media/js/jquery.js',
'media/js/ui.datepicker.js'
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