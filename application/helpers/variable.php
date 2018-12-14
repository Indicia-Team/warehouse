<?php

/**
 * @file
 * Helper class for persistant variables.
 *
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
 * @link https://github.com/indicia-team/warehouse/
 */

defined('SYSPATH') or die('No direct script access.');

class variable {

  /**
   * Set a variable value for later retrieval.
   *
   * @param string $name
   *   Name of the variable to set.
   * @param mixed $value
   *   Value to set.
   * @param bool $caching
   *   Set to true to enable use of the Kohana cache.
   */
  public static function set($name, $value, $caching = TRUE) {
    $db = new Database();
    $query = $db->update('variables', array('value' => json_encode(array($value))), array('name' => $name));
    if ($query->count() === 0) {
      // Insert record if nothing to update.
      $db->insert('variables', array('name' => $name, 'value' => json_encode(array($value))));
    }
    if ($caching) {
      $cache = Cache::instance();
      $cache->set("variable-$name", $value, array('variables'));
    }
  }

  /**
   * Retrieve a value from the variables.
   *
   * @param string $name
   *   Variable name.
   * @param mixed $default
   *   Return value if no variable exists with this name.
   * @param bool $caching
   *   Use the Kohana cache to retrieve the value?
   */
  public static function get($name, $default = FALSE, $caching = TRUE) {
    $value = NULL;
    if ($caching) {
      $cache = Cache::instance();
      // Get returns null if no value.
      $value = $cache->get("variable-$name");
    }
    if ($value === NULL) {
      $db = new Database();
      $r = $db->select('value')
        ->from('variables')
        ->where('name', $name)
        ->get()->result_array(FALSE);
      if (count($r)) {
        $array = json_decode($r[0]['value']);
        $value = $array[0];
        if ($caching) {
          $cache->set("variable-$name", $value, array('variables'));
        }
      }

    }
    if ($value !== NULL) {
      return $value;
    }
    else {
      return $default;
    }
  }

  /**
   * Delete a named variable value.
   *
   * @param string $name
   *   Name of the variable to delete.
   */
  public static function delete($name) {
    $db = new Database();
    $db->delete('variables', array('name' => $name));
    $cache = Cache::instance();
    $cache->delete("variable-$name");
  }

}
