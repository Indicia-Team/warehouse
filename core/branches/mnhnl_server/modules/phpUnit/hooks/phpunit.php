<?php defined('SYSPATH') OR die('No direct access allowed.');

if (class_exists('PHPUnit_Util_Filter'))
{
	// Hand control of errors and exceptions to PHPUnit
	if (defined('Kohana::VERSION'))
	{
		Kohana_Exception::disable();
		Kohana_PHP_Exception::disable();
	}
	else
	{
		restore_exception_handler();
		restore_error_handler();
	}


	Event::clear('system.ready');
	Event::clear('system.routing');
	Event::clear('system.execute');
}