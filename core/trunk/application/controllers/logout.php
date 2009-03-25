<?php

/*
*  class:       Logout_Controller
*  description: Provides application support for logging users out
*/
class Logout_Controller extends Indicia_Controller {

	/*
	*  description:  Logs the current user out of the application. Destroys the current session
	*  parameters:   None expected.
	*/
	public function index()
	{
		$this->auth->logout(TRUE);
		url::redirect();
	}

}