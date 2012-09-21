<?php defined('SYSPATH') OR die('No direct access allowed.');

$config['phpunit'] = array
(
	'benchmark'     => FALSE,
	'persistent'    => FALSE,
	'connection'    => array
	(
		'type'      => 'mysql',
		'user'      => 'kohana_phpunit',
		'pass'      => 'kohana_phpunit',
		'host'      => 'localhost',
		'port'      => FALSE,
		'socket'    => FALSE,
		'database'  => 'kohana_phpunit',
		'params'    => NULL,
	),
	'character_set' => 'utf8',
	'table_prefix'  => '',
	'object'        => TRUE,
	'cache'         => FALSE,
	'escape'        => TRUE,
);
