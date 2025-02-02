<?php

class Indicia {

  public static function init() {
    set_error_handler(['Indicia', 'indicia_error_handler']);
    Event::add('system.log', ['Indicia', 'log_rotate']);
  }

  /**
   * Deletion of old log files.
   *
   * Implements the deletion of old logs if the indicia.log_rotate config item exists.
   */
  public static function log_rotate() {
    $rotate_days = Kohana::config('indicia.log_rotate', FALSE, FALSE);
    if ($rotate_days) {
      $filename = Kohana::log_directory() . date('Y-m-d') . '.log' . EXT;
      if (!is_file($filename)) {
        // writing the first message today, so we can go back and delete log files over a certain age
        $files = glob(Kohana::log_directory() . "*");
        $now   = time();
        foreach ($files as $file) {
          if (is_file($file) && $now - filemtime($file) >= 60 * 60 * 24 * $rotate_days) {
            unlink($file);
          }
        }
      }
    }
  }

  /**
   * Convert PHP errors to exceptions so that they can be handled nicely.
   */
  public static function indicia_error_handler($errno, $errstr, $errfile, $errline) {
    // If error reporting has been switched off completely we don't want to
    // convert the error to an exception. Also ignores errors suppressed by
    // @.
    if (!(error_reporting() & $errno)) {
      return TRUE;
    }

    try {
      throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
    catch (Exception $e) {
      error_logger::log_error('Error converted to exception', $e);
      throw $e;
    }
  }

  /**
   * Hook to prepare the database connection. Performs 2 tasks.
   *   1) Initialises the search path, unless configured not to do this (e.g. if this is set at db level).
   *   2) If this is a report request, sets the connection to read only.
   */
  public static function prepare_connection() {
    $uri = URI::instance();
    // We havent to proceed futher if a setup call was made.
    if ($uri->segment(1) == 'setup_check') {
      return;
    }

    // continue to init the system
    //
    // add schema to the search path
    //
    $_schema = Kohana::config('database.default.schema');
    $applySchema = !empty($_schema) && kohana::config('indicia.apply_schema') !== FALSE;
    $runningReport = $uri->segment(1) == 'services' && $uri->segment(2) == 'report';
    if ($applySchema || $runningReport) {
      $db = Database::instance();
      $query = '';
      if ($applySchema) {
        $_schema = pg_escape_identifier($db->getLink(), $_schema);
        $query = "SET search_path TO $_schema, public, pg_catalog;\n";
      }
      // Force a read only connection for reporting.
      if ($runningReport) {
        $query .= "SET default_transaction_read_only TO true;\n";
      }
      $db->query($query);
    }
  }

}

Event::add('system.ready', ['Indicia', 'init']);
Event::add('system.routing', ['Indicia', 'prepare_connection']);
