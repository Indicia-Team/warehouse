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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Extension class for the kohana core validation class.
 *
 * Provides additional Indicia specific validation methods.
 */
class Valid extends valid_Core {

  /**
   * Validate a spatial reference system is recognised.
   *
   * System can be either an EPSG code or a notation code.
   *
   * @param string $system
   *   Spatial system code.
   *
   * @return bool
   *   True if valid.
   *
   * @todo Should we consider caching this?
   */
  public static function sref_system($system) {
    return spatial_ref::is_valid_system($system);
  }

  /**
   * Validate a spatial reference is a valid value for the system.
   *
   * @param string $sref
   *   Spatial reference.
   * @param string $system
   *   Spatial system code.
   *
   * @return bool
   *   True if valid.
   *
   * @todo Should we consider caching the system?
   */
  public static function sref($sref, $system) {
    $system = $system[0];
    return spatial_ref::is_valid($sref, $system);
  }

  /**
   * Checks that a date string can be correctly parsed into a vague date.
   *
   * @param string $sDate
   *   Date string.
   *
   * @return bool
   *   True if a valid vague date string.
   */
  public static function vague_date($sDate) {
    if (vague_date::string_to_vague_date($sDate) != FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validates that a date is not in the future.
   *
   * Note that tomorrow is allowed temporarily, until timezone is added as a
   * recognised date component, in order to allow submissions of today from
   * places which are in a timezone ahead of the warehouse.
   *
   * @param string $date
   *   Date string.
   *
   * @return bool
   *   True if date today or in past.
   */
  public static function date_in_past($date) {
    if ($date === NULL) {
      return true;
    }
    $dateTimestamp = strtotime($date);
    $tomorrowTimestamp = strtotime('+1 day', strtotime('today'));
    return $dateTimestamp <= $tomorrowTimestamp;
  }

  /**
   * Unique validation rule (case insensitive).
   *
   * Validates that a value is unique across a table column, NULLs are ignored.
   * When checking a new record, just count all records in DB. When Updating,
   * count all records excluding the one we are updating.
   *
   * @param string $column_value
   *   Column value to test.
   * @param array $args
   *   Table name, table column, ID of current record. Optionally an extra ID
   *   field name and ID value to limit the duplicate check to in the last 2
   *   parameters.
   *
   * @return bool
   *   TRUE if valid.
   */
  public static function unique($column_value, array $args) {
    $db = new Database();
    $idFilter = empty($args[2]) ? '' : 'AND id<>' . (int) $args[2];
    $value = pg_escape_literal($db->getLink(), $column_value);
    $extraFilters = '';
    if (count($args) > 3) {
      $extraFilters = 'AND ' . pg_escape_identifier($db->getLink(), $args[3]) . '=' . (int) $args[4];
    }
    $qry = <<<SQL
SELECT 1 AS hit
FROM $args[0]
WHERE deleted=false
AND LOWER($args[1]) = LOWER($value)
$idFilter
$extraFilters
LIMIT 1
SQL;
    $found = $db->query($qry)->count();
    return ($found === 0);
  }

  /**
   * Validates a term exists in the database.
   *
   * Service at URL services/validation/valid_term. Tests if a term can be
   * found on the termlist identified by the supplied id.
   */
  public static function valid_term($term, $id) {
    self::valid_term_or_taxon($term, $id, 'termlist_id', 'term', 'gv_termlists_term');
  }

  /**
   * Validates a taxon exists in the database.
   *
   * Service at URL services/validation/valid_taxon. Tests if a taxon can be
   * found on the taxon list identified by the supplied id.
   */
  public static function valid_taxon($taxon, $id) {
    self::valid_term_or_taxon($taxon, $id, 'taxon_list_id', 'taxon', 'gv_taxon_lists_taxa');
  }

  /**
   * Validate the presence of a term or taxon.
   *
   * Internal method that provides functionality for validating a term or taxon
   * against a list in the database.
   */
  protected static function valid_term_or_taxon($value, $list_id, $list_id_field, $search_field, $view_name) {
    $found = ORM::factory($view_name)
      ->where([$list_id_field => $list_id])
      ->like([$search_field => $value])
      ->find_all();
    // @todo Proper handling of output XML.
    // @todo Only accept multiple entries as valid if a single match can be determined.
    return ($found->count() > 1);
  }

  /**
   * Validates a given string against a (Perl-style) regular expression.
   */
  public static function regex($value, $regex) {
    // Kohana explodes regexes containing commas, so recreate the original regex.
    if (is_array($regex)) {
      $regex = implode(',', $regex);
    }
    return (preg_match($regex, $value) >= 1);
  }

  /**
   * Validates that JSON string or array of values matches list of options.
   *
   * The value can be provided as an array formatted as a JSON string, or a PHP
   * array. Tests that all the provided values in the array are found in a list
   * of options, provided as a | separated list.
   *
   * @param string|array $value
   *   Array of options to validate, in PHP or JSON format.
   * @param array $options
   *   Options passed to the validation rule. The first option must be a list
   *   of allowed options, separated by |.
   *
   * @return bool
   *   True if all provided values match one of the list of options.
   */
  public static function json_or_array_options($value, array $options) {
    $testArray = explode('|', $options[0]);
    if (is_string($value) && preg_match('/^\[.+\]$/', $value)) {
      $value = json_decode($value);
      if ($value === NULL) {
        return FALSE;
      }
    }
    elseif (is_string($value)) {
      // Single value in simple string form.
      $value = [$value];
    }
    return !array_diff($value, $testArray);
  }

  /**
   * Checks a value against the POST array.
   *
   * Generates an error if the field does not match one or more other fields in
   * the POST array. This is subtly different to the matches function as that
   * deals with the contents of the validation array.
   *
   * @param mixed $str
   *   Input value.
   * @param array $inputs
   *   Input names to match against.
   *
   * @return bool
   *   True if a match found.
   */
  public static function matches_post($str, array $inputs) {
    foreach ($inputs as $key) {
      if ($str !== (isset($_POST[$key]) ? $_POST[$key] : NULL)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Validate that a value is at least as high as a specified minimum value.
   *
   * @param string $value
   *   Value to validate.
   * @param array $inputs
   *   Rule inputs including maximum value accepted.
   *
   * @return bool
   *   True if equal to or above the minimum.
   */
  public static function minimum($value, array $inputs) {
    return $value >= $inputs[0];
  }

  /**
   * Validate that a value is at least as high as a specified minimum value.
   *
   * @param string $value
   *   Value to validate.
   * @param array $inputs
   *   Rule inputs including maximum value accepted.
   *
   * @return bool
   *   True if equal to or below the minimum.
   */
  public static function maximum($value, array $inputs) {
    return $value <= $inputs[0];
  }

  /**
   * Validates that a value is a list of comma separated emails.
   *
   * @param string $value
   *   Value to validate.
   *
   * @return bool
   *   True if a list of correctly formatted emails.
   */
  public static function email_list($value) {
    $emails = explode(',', $value);
    foreach ($emails as $email) {
      if (!self::email(trim($email))) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Validates that a value is a list of URLs, one per line.
   *
   * @param string $value
   *   Value to validate.
   *
   * @return bool
   *   True if a list of correctly formatted URLs.
   */
  public static function url_list($value) {
    $value = str_replace("\r\n", "\n", $value);
    $value = str_replace("\r", "\n", $value);
    $urls = explode("\n", $value);
    foreach ($urls as $url) {
      if (!self::url(trim($url))) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Checks if a string is a proper decimal format with allowance for ranges.
   *
   * The format array can be used to specify a decimal length, or a number
   * and decimal length, eg: array(2) would force each number to have 2 decimal
   * places, array(4,2) would force each number to have 4 digits and 2 decimal
   * places.
   *
   * Either a single number matching the format can be accepted or two numbers
   * separated by a hyphen (and any amount of white space).
   *
   * Takes after system/helpers/valid.php:decimal() and doesn't admit negative
   * numbers.
   *
   * @param string $str
   *   Input string.
   * @param array $format
   *   Decimal format: y or x,y.
   *
   * @return bool
   *   True if string in decimal format and within range.
   */
  public static function decimal_range($str, array $format = NULL) {
    // Create the pattern.
    $pattern = '[0-9]%s\.[0-9]%s';

    if (!empty($format)) {
      if (count($format) > 1) {
        // Use the format for number and decimal length.
        $pattern = sprintf(
          $pattern, '{' . $format[0] . '}', '{' . $format[1] . '}'
        );
      }
      elseif (count($format) > 0) {
        // Use the format as decimal length.
        $pattern = sprintf($pattern, '+', '{' . $format[0] . '}');
      }
    }
    else {
      // No format.
      $pattern = sprintf($pattern, '+', '+');
    }

    // Add allowance for range.
    $pattern = "/^{$pattern}(\s*-\s*{$pattern})?$/";

    return (bool) preg_match($pattern, (string) $str);
  }

}
