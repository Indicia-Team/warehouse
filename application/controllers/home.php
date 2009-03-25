<?php

class Home_Controller extends Indicia_Controller {
	public function index()
	{
		$view = new View('home');
		$this->template->title='Indicia';
		$this->template->content=$view;
	}
}

?>
