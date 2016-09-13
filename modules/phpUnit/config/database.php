<?php defined('SYSPATH') OR die('No direct access allowed.');

$config['phpunit'] = array (
  'benchmark'     => TRUE,
  'persistent'    => FALSE,
  'connection'    => array
  (
    'type'     => 'pgsql',
    'user'     => 'indicia_user',
    'pass'     => 'indicia_user_pass',
    'host'     => 'localhost',
    'port'     => 5432,
    'socket'   => FALSE,
    'database' => 'indicia'
  ),
  'character_set' => 'utf8',
  'table_prefix'  => '',
  'schema'        => 'indicia',
  'object'        => TRUE,
  'cache'         => FALSE,
  'escape'        => TRUE
);
