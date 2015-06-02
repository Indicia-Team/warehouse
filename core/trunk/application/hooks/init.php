<?php

class Indicia
{
    public static function init()
    {
      set_error_handler(array('Indicia', 'indicia_error_handler'));
      Event::add('system.log', array('Indicia', 'log_rotate'));
    }

    /**
     * Implements the deletion of old logs if the indicia.log_rotate config item exists
     * @throws \Kohana_Exception
     */
    public static function log_rotate() {
      $rotate_days = Kohana::config('indicia.log_rotate', FALSE, FALSE);
      if ($rotate_days) {
        $filename = Kohana::log_directory() . date('Y-m-d') . '.log' . EXT;
        if (!is_file($filename)) {
          // writing the first message today, so we can go back and delete log files over a certain age
          $files = glob(Kohana::log_directory()."*");
          $now   = time();
          foreach ($files as $file)
            if (is_file($file) && $now - filemtime($file) >= 60*60*24*$rotate_days) {
              unlink($file);
            }
        }
      }
    }

    /**
     * Convert PHP errors to exceptions so that they can be handled nicely.
     */
    public static function indicia_error_handler($errno, $errstr, $errfile, $errline)
    {
      // if error reporting has been switched off completely we don't want to convert the error to an exception
      // should really do a binary AND between error_reporting() and $errno; which would only raise exceptions for those which are
      // flagged by error_reporting(), but this may impact unknown assumptions elsewhere.
      if(error_reporting() == 0)
        return true;
 
      try {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      } catch (Exception $e) {
        error::log_error('Error converted to exception', $e);
        throw $e;
      }
    }

    /**
     * Hook to prepare the database connection. Performs 2 tasks.
     *   1) Initialises the search path, unless configured not to do this (e.g. if this is set at db level).
     *   2) If this is a report request, sets the connection to read only.
     */
    public static function prepare_connection()
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

        $query = '';
        if(!empty($_schema) && kohana::config('indicia.apply_schema')!==false)
        {
          $query = "SET search_path TO $_schema, public, pg_catalog;\n";
        }
        // Force a read only connection for reporting.
        if ($uri->segment(1)=='services' && $uri->segment(2)=='report') {
          $query .= "SET default_transaction_read_only TO true;\n";
        }
        if (!empty($query)) {
          $db = Database::instance();
          $db->query($query);
        }
    }


}

Event::add('system.ready',   array('Indicia', 'init'));
Event::add('system.routing', array('Indicia', 'prepare_connection'));

?>
