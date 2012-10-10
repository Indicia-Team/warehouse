<?php

	class Test_Vague_Date_Controller extends Controller {

		public function Index() {
			$time = time();
			echo '<ul>';
			echo '<li>Today: '.vague_date::vague_date_to_string(array(date_create('2008-11-01'), date_create('2008-11-01'), 'D')).'</li>';
			echo '<li>Day range for 1 week: '.vague_date::vague_date_to_string(array(date_create('2008-11-01'), date_create('2008-11-08'), 'DD')).'</li>';
			echo '<li>Month in Year: '.vague_date::vague_date_to_string(array(date_create('2008-11-01'), date_create('2008-11-30'), 'O')).'</li>';
			echo '<li>Month in Year range: '.vague_date::vague_date_to_string(array(date_create('2008-10-01'), date_create('2008-11-30'), 'OO')).'</li>';
			echo '<li>Season in year: '.vague_date::vague_date_to_string(array(date_create('2008-09-01'), date_create('2008-11-30'), 'P')).'</li>';
			echo '<li>Year: '.vague_date::vague_date_to_string(array(date_create('2008-01-01'), date_create('2008-12-31'), 'Y')).'</li>';
			echo '<li>Years: '.vague_date::vague_date_to_string(array(date_create('2006-01-01'), date_create('2008-12-31'), 'YY')).'</li>';
			echo '<li>From Year: '.vague_date::vague_date_to_string(array(date_create('2006-01-01'), null, 'Y-')).'</li>';
			echo '<li>To Year: '.vague_date::vague_date_to_string(array(null, date_create('2008-12-31'), '-Y')).'</li>';
			echo '<li>Month: '.vague_date::vague_date_to_string(array(date_create('2006-01-01'), date_create('2006-01-31'), 'M')).'</li>';
			echo '<li>Season: '.vague_date::vague_date_to_string(array(date_create('2008-03-01'), date_create('2008-05-31'), 'S')).'</li>';
			echo '<li>Unknown: '.vague_date::vague_date_to_string(array(null, null, 'U')).'</li>';
			echo '<li>Century: '.vague_date::vague_date_to_string(array(date_create('1801-01-01'), date_create('1900-12-31'), 'C')).'</li>';
			echo '<li>Centuries: '.vague_date::vague_date_to_string(array(date_create('1701-01-01'), date_create('1900-12-31'), 'CC')).'</li>';
			echo '<li>Century From: '.vague_date::vague_date_to_string(array(date_create('1701-01-01'), null, 'C-')).'</li>';
			echo '<li>Century To: '.vague_date::vague_date_to_string(array(null, date_create('1800-12-31'), '-C')).'</li>';

			echo '</ul';
		}
	}
?>
