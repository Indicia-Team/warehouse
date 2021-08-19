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
 * @link https://github.com/indicia-team/warehouse/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to provide library functions for the data_cleaner module.
 */
class data_cleaner {

  /**
   * Retrieves the rules that are exposed by enabled data cleaner rule modules.
   *
   * @return array
   *   List of rules
   */
  public static function getRules() {
    $cacheId = 'data-cleaner-rules';
    $cache = Cache::instance();
    // Use cached rules if available.
    if (!($rules = $cache->get($cacheId))) {
      // Need to build the set of rules from plugin modules.
      $rules = [];
      foreach (Kohana::config('config.modules') as $path) {
        $plugin = basename($path);
        if (file_exists("$path/plugins/$plugin.php")) {
          require_once "$path/plugins/$plugin.php";
          if (function_exists($plugin . '_data_cleaner_rules')) {
            $pluginRules = call_user_func($plugin . '_data_cleaner_rules');
            // Mark each rule with the plugin name that generated it.
            $pluginRules['plugin'] = $plugin;
            $rules[] = $pluginRules;
          }
        }
      }
      $cache->set($cacheId, $rules);
    }
    return $rules;
  }

  /**
   * Retrieve the definition of a single rule.
   *
   * @param string $type
   *   Rule type name.
   *
   * @return array
   *   Rule definition.
   */
  public static function getRule($type) {
    $rules = data_cleaner::getRules();
    foreach ($rules as $rule) {
      if (strcasecmp($rule['testType'], $type) === 0) {
        if (!array_key_exists('required', $rule)) {
          $rule['required'] = [];
        }
        if (!array_key_exists('optional', $rule)) {
          $rule['optional'] = [];
        }
        return $rule;
      }
    }
    // If we got this far then the rule type is not found.
    throw new exception("Test type $type not found");
  }

  /**
   * Adds a value to the data collected for the current section.
   *
   * For metadata, the data are collected in a simple key/value list with the
   * key's lowercased. For metadata and other sections, the key/value list is
   * grouped allowing multiple sets to be collected.
   *
   * @param string $currentSection
   *   Title of the current section.
   * @param array $currentSectionData
   *   List the key/value pair will be added to.
   * @param int $dataGroup
   *   Index of the grouped set of key/value pairs.
   * @param string $key
   *   Key name.
   * @param string $value
   *   Value to store.
   */
  private static function addDataValue($currentSection, array &$currentSectionData, $dataGroup, $key, $value) {
    if ($currentSection === 'metadata') {
      $currentSectionData[trim(strtolower($key))] = trim($value);
    }
    else {
      // Other sections have groups of related keys (e.g. date ranges for a
      // stage term).
      if (!isset($currentSectionData[$dataGroup])) {
        $currentSectionData[$dataGroup] = [];
      }
      $currentSectionData[$dataGroup][trim(strtolower($key))] = trim($value);
    }
  }

  /**
   * Parse a verification rule test file.
   *
   * Parses a data cleaner verification rule test file into an array of
   * sections, each contining an array of key value pairs. Very similar to
   * PHP's parse_ini_string but a bit more tolerant, e.g of comments used.
   *
   * @param string $content
   *   Content of the verification rule test file.
   *
   * @return array
   *   File structure array.
   */
  public static function parseTestFile($content) {
    // Break into lines, tolerating different line ending forms.
    $lines = helper_base::explode_lines($content);
    $currentSection = '';
    $currentSectionData = [];
    $dataGroup = 0;
    $r = [];
    foreach ($lines as $line) {
      $line = trim($line);
      // Skip comments plus the end of the metadata section.
      if (substr($line, 1) === ';' || $line === '[EndMetadata]') {
        continue;
      }
      if (preg_match('/^\[(?P<section>.+)\]$/', $line, $matches)) {
        // Found a [Section] heading.
        if (!empty($currentSectionData)) {
          $r[$currentSection] = $currentSectionData;
        }
        // Reset for the next section.
        $currentSection = trim(strtolower($matches['section']));
        $currentSectionData = [];
        $dataGroup = 0;
      }
      elseif (preg_match('/^([^=\r\n]+)=([^\r\n]*)$/', $line, $matches)) {
        // Found a key=value.
        self::addDataValue($currentSection, $currentSectionData, $dataGroup, $matches[1], $matches[2]);
      }
      elseif (preg_match('/^(?P<key>[^,\r\n]+),(?P<value>[^\r\n]*)$/', $line, $matches)) {
        // Found a key,value as used in ancillary species data.
        self::addDataValue($currentSection, $currentSectionData, $dataGroup, $matches['key'], $matches['value']);
      }
      elseif (preg_match('/^(?P<key>.+)$/', $line, $matches)) {
        // Found a key with no value.
        self::addDataValue($currentSection, $currentSectionData, $dataGroup, $matches['key'], '-');
      }
      elseif (empty($line) && $currentSection !== 'metadata') {
        // Found a blank line indicating end of a data group.
        $dataGroup++;
      }
    }
    // Set the final section content.
    if (!empty($currentSectionData)) {
      $r[$currentSection] = $currentSectionData;
    }
    return $r;
  }

}
