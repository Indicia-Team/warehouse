<?php

class Test_Date_Parse_Controller extends Controller {

	public function Index() {
		$start = '';
		$end = '';
		$type = '';
		$err = '';
		$log = '';
		if (array_key_exists('date', $_POST)){
			$str = $_POST['date'];
			$log .= "Have string in postdata<br />";
			$log .= "Parsing for string ".$str."<br />";
			$arr = vague_date::string_to_vague_date($str);
			if ($arr != false){
				$log .= "Parsed correctly<br />";
				$start = $arr['start'];
				$end = $arr['end'];
				$type = $arr['type'];
			} else {
				$err = 'Unable to parse date';
				$log .= $err;
			}
		} else {
			$log .= "No postdata<br />";
		}
		?>
<form method='post'><input name='date' id='date' value=''/><br /><input type='submit' value='Parse' /></form>
<?php echo $err; ?>
<ul>
<li>Start: <?php echo $start; ?><li>
<li>End: <?php echo $end; ?><li>
<li>Type: <?php echo $type; ?></li>
</ul>
<?php echo $log; ?>

		<?php
			
	}
}
?>