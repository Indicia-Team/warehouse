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
 * @package Services
 * @subpackage REST API
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */

class Spatial_index_builder_Controller extends Controller {

  /**
   * Controller method which outputs scripts required to add any missing location_id_* columns
   * for each indexed location type to the cache_occurrences_functional and cache_samples_functional
   * tables. The script includes saving a flag in the variables table to ensure that the same
   * column is not added twice.
   */
  function generate_scripts() {
    $db = new Database();
    $script = '';
    $config=kohana::config_load('spatial_index_builder', false);
    if (in_array(MODPATH.'cache_builder', kohana::config('config.modules'))
        && array_key_exists('location_types', $config) && array_key_exists('unique', $config)) {
      // Need to ensure the columns exist in the cache. Use the variables table to
      // keep track.
      $done = variable::get('spatial_index_builder_cache_columns', false, false);
      if (!$done) {
        // tracking variable doesn't exist yet so create it to ensure script is simple
        variable::set('spatial_index_builder_cache_columns', array());
        $done = array();
      }
      $adding = array();
      foreach ($config['location_types'] as $typeName) {
        // Only the location types that allow one per sample max are added as columns to the cache
        // tables.
        if (!in_array($typeName, $config['unique']))
          continue;
        if (!in_array($typeName, $done)) {
          $term = $db->query("select id from cache_termlists_terms where termlist_title='Location types' and term='$typeName'")
            ->result_array(false);
          if (!count($term))
            // missing location type, so skip it
            continue;
          $locTypeId = $term[0]['id'];
          $column = 'location_id_' . preg_replace('/[^\da-z]/', '_', strtolower($typeName));
          $script .= "ALTER TABLE cache_occurrences_functional ADD COLUMN $column integer;\n";
          $script .= "ALTER TABLE cache_samples_functional ADD COLUMN $column integer;\n";
          $done[] = $typeName;
          $adding[$locTypeId] = $column;
        }
      }
      // Now we've added some columns, we need to populate them
      if (count($adding)) {
        $joins = '';
        $sets = '';
        foreach($adding as $locTypeId => $column) {
          $sets[] = "$column = ils$locTypeId.location_id";
          $joins[] = "LEFT JOIN index_locations_samples ils$locTypeId on ils$locTypeId.sample_id=s.id " .
              "and ils$locTypeId.location_type_id=$locTypeId and ils$locTypeId.contains = true";
        }
        $joins = implode("\n", $joins);
        $sets = implode(",\n", $sets);
        $script .= "\nUPDATE cache_occurrences_functional u
SET $sets
FROM samples s
$joins
WHERE s.id=u.sample_id;\n";
        $script .= "\nUPDATE cache_samples_functional u
SET $sets
FROM samples s
$joins
WHERE s.id=u.id;\n";
        // Create an index on this field
        foreach($adding as $locTypeId => $column) {
          $script .= "\nCREATE INDEX ix_cache_occurrences_functional_$column
  ON cache_occurrences_functional
  USING btree
  ($column);\n";
          $script .= "\nCREATE INDEX ix_cache_samples_functional_$column
  ON cache_samples_functional
  USING btree
  ($column);\n";
        }
        // remember which columns we've done
        $script .= "\nUPDATE variables SET value='[" . json_encode($done) . "]' where name='spatial_index_builder_cache_columns';\n";
      }
    }
    if (empty($script))
      echo "<p>There are no location types defined for spatial indexing which need to be added as columns to the cache tables.</p>";
    else
      echo "<pre>$script</pre>";
  }
}