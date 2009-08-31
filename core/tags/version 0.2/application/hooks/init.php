<?php

class Indicia
{
    public static function init()
    {
        set_error_handler(array('Indicia', 'indicia_exception_handler'));
    }

    /**
     * Convert PHP errors to exceptions so that they can be handled nicely.
     */
    public static function indicia_exception_handler($errno, $errstr, $errfile, $errline)
    {
      kohana::log('error', "Error occurred");
      kohana::log('error', $errno);
      kohana::log('error', $errstr);
      kohana::log('error', "In $errfile on line $errline");
      throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
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
