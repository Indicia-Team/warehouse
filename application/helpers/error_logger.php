<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * Helper class for error management.
 */
class error_logger {

  /**
   * Standardise the dumping of an exception message into the kohana log.
   *
   * For example,
   * try {
   *   ... code that throws exception ...
   * } catch (Exception $e) {
   *   error_logger::log_error('Error occurred whilst running some code', $e);
   * }
   *
   * @param string $msg
   *   A description of where the error occurred.
   * @param object $e
   *   The exception object.
   */
  public static function log_error($msg, $e) {
    kohana::log('error', get_class($e) . ' #' . $e->getCode() . ': ' . $msg . '. ' . $e->getMessage() . ' at line ' .
          $e->getLine() . ' in file ' . $e->getFile());
    // Double check the log threshold to avoid unnecessary work.
    if (kohana::config('config.log_threshold') === 4) {
      $trace = $e->getTrace();
      self::log_trace($trace);
      kohana::log_save();
    }
  }

  /**
   * Return a readable version of a stack trace.
   *
   * @param array $trace
   *   Trace from debug_backtrace().
   *
   * @return string
   *   A text representation of the trace.
   */
  public static function getTraceAsText(array $trace) {
    $output = "Stack trace:\n";
    for ($i = 0; $i < count($trace); $i++) {
      if (array_key_exists('file', $trace[$i])) {
        $file = $trace[$i]['file'];
      }
      else {
        $file = 'Unknown file';
      }
      if (array_key_exists('line', $trace[$i])) {
        $line = $trace[$i]['line'];
      }
      else {
        $line = 'Unknown';
      }
      if (array_key_exists('function', $trace[$i])) {
        $function = $trace[$i]['function'];
      }
      else {
        $function = 'Unknown function';
      }
      $output .= "\t$file - line $line - $function\n";
    }
    return $output;
  }

  /**
   * Dump a call stack trace into the kohana log.
   *
   * @param array $trace
   *   Response from debug_backtrace() to log.
   */
  public static function log_trace($trace) {
    kohana::log('debug', self::getTraceAsText($trace));
  }

}
