<?php

class Indicia
{
    public static function init()
    {
        set_error_handler(array('Indicia', 'indicia_error_handler'));
    }

    /**
     * Convert PHP errors to exceptions so that they can be handled nicely.
     */
    public static function indicia_error_handler($errno, $errstr, $errfile, $errline)
    {
      try {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
      } catch (Exception $e) {
        error::log_error('Error converted to exception', $e);
        throw $e;
      }
    }

    /**
     * set the database schema paths
     */
    public static function set_search_path()
    {
        $uri = URI::instance();
        // we havent to proceed futher if a setup call was made
        if($uri->segment(1) == 'setup_check')
        {
            return;
        }

        // continue to init the system
        //
        // add schema to the search path
        //
        $_schema = Kohana::config('database.default.schema');

        if(!empty($_schema))
        {
            $db = Database::instance();
            $result = $db->query('SET search_path TO ' . $_schema . ', public, pg_catalog');
        }

    }


}

Event::add('system.ready',   array('Indicia', 'init'));
Event::add('system.routing', array('Indicia', 'set_search_path'));
?>
