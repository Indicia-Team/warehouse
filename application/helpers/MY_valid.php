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
 * Extension class for the kohana core validation class. Provides additional Indicia specific
 * validation methods.
 */

class Valid extends valid_Core {

  /**
   * Validate a spatial reference system is recognised, either an EPSG code or a notation.
   *
   * @param   string   system
   * @return  boolean
   * $todo Should we consider caching this?
   */
  public static function sref_system($system)
  {
    return spatial_ref::is_valid_system($system);
  }

  /**
   * Validate a spatial reference is a valid value for the system
   *
   * @param   string   sref
   * @param   string   system
   * @return  boolean
   * $todo Should we consider caching the system?
   */
  public static function sref($sref, $system)
  {
    $system = $system[0];
    return spatial_ref::is_valid($sref, $system);
  }

  /**
   * Validates that a specific date string can be correctly parsed into a vague date.
   *
   * @param	string	SDate
   */
  public static function vague_date($sDate){
    if (vague_date::string_to_vague_date($sDate) != FALSE){
      return TRUE;
    }
    return FALSE;
  }

  /**
  * Validates that a date is not in the future.
  */
  public static function date_in_past($date) {
    return ($date == NULL || strtotime($date) <= time());
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
   *   Table name, table column, ID of current record.
   *
   * @return bool
   *   TRUE if valid.
   */
  public static function unique($column_value, array $args) {
    $db = new Database();
    $idFilter = empty($args[2]) ? '' : "AND id<>$args[2]";
    $qry = <<<SQL
SELECT 1 AS hit
FROM $args[0]
WHERE deleted=false
AND LOWER($args[1]) = LOWER('$column_value')
$idFilter
LIMIT 1
SQL;
    $found = $db->query($qry)->count();
    return ($found === 0);
  }

  /**
   * Service at URL services/validation/valid_term. Tests if a term can be found
   * on the termlist identified by the supplied id in $_GET.
   */
  public static function valid_term($term, $id)
  {
    self::valid_term_or_taxon($term, $id,'termlist_id', 'term', 'gv_termlists_term');
  }

  /**
   * Service at URL services/validation/valid_taxon. Tests if a taxon can be found
   * on the taxon list identified by the supplied id in $_GET.
   */
  public static function valid_taxon($taxon, $id)
  {
    self::valid_term_or_taxon($taxon, $id, 'taxon_list_id', 'taxon', 'gv_taxon_lists_taxa');
  }

  /**
   * Internal method that provides functionality for validating a term or taxon
   * against a list.
   */
  protected static function valid_term_or_taxon($value, $list_id, $list_id_field, $search_field, $view_name)
  {
    $found=	ORM::factory($view_name)
        ->where(array($list_id_field=>$list_id))
        ->like(array($search_field=>$value))
        ->find_all();
    // TODO - proper handling of output XML.
    // TODO - Only accept multiple entries as valid if a single match can be determined.
    return ($found->count()>1);
  }

  /**
   * Validates a given string against a (Perl-style) regular expression.
   */
  public static function regex($value, $regex){
    // Kohana explodes regexes containing commas, so recreate the original regex
    if (is_array($regex)) {
      $regex = implode(',', $regex);
    }
    return (preg_match($regex, $value) >= 1);
  }

  /**
   * Rule: matches_post.
   * Generates an error if the field does not match one or more other fields in the POST array.
   * This is subtly different to the matches function as that deals with the contents of the validation
   * array.
   *
   * @param   mixed   input value
   * @param   array   input names to match against
   * @return  bool
   */
  public static function matches_post($str, array $inputs)
  {
    foreach ($inputs as $key)
    {
      if ($str !== (isset($_POST[$key]) ? $_POST[$key] : NULL))
        return FALSE;
    }

    return TRUE;
  }

  /**
   * Validate that a value is at least as high as a specified minimum value.
   *
   * @param string $value Value to validate
   * @param int $min Minimum value accepted
   * @return  bool
   */
  public static function minimum($value, $min)
  {
    return $value >= $min[0];
  }

  /**
   * Validate that a value is at least as high as a specified minimum value.
   *
   * @param string $value Value to validate
   * @param int $min Maximum value accepted
   * @return  bool
   */
  public static function maximum($value, $max)
  {
    return $value <= $max[0];
  }

  /**
   * Validates that a value is a list of comma separated emails.
   * @param string $value Value to validate
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
   * @param   string   input string
   * @param   array    decimal format: y or x,y
   * @return  boolean
   */
  public static function decimal_range($str, $format = NULL) {
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
    $pattern = "/^${pattern}(\s*-\s*${pattern})?$/";

    return (bool) preg_match($pattern, (string) $str);
  }

}