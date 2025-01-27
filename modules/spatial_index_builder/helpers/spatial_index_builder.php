<?php

/**
 * @file
 * Helper functions for the spatial_index_builder module.
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
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper functions for the spatial_index_builder module.
 */
class spatial_index_builder {

  /**
   * Retrieve a list of indexed location types.
   *
   * A utility function used by the work queue task helpers. Returns the filter
   * SQL to limit indexed locations to the correct types as declared in the
   * configuration, with survey limits where appropriate.
   *
   * @param object $db
   *   Database connection object.
   *
   * @return string
   *   SQL filter clause.
   */
  public static function getLocationTypeFilters($db) {
    $cache = Cache::instance();
    $filters = $cache->get('spatial-index-location-type-filter-info');
    if (!$filters) {
      $config = kohana::config_load('spatial_index_builder');
      if (!array_key_exists('location_types', $config)) {
        throw new Exception('Spatial index builder configuration location_types missing');
      }
      $locationTypesEsc = array_map(function ($s) use ($db) {
        return pg_escape_literal($db->getLink(), $s);
      }, $config['location_types']);
      $locationTypesCsv = implode(',', $locationTypesEsc);
      $idQuery = $db->query(<<<SQL
        SELECT id, term
        FROM cache_termlists_terms
        WHERE preferred_term IN ($locationTypesCsv)
        AND termlist_title ilike 'location types'
      SQL)->result();
      $allLocationTypeIds = [];
      foreach ($idQuery as $row) {
        $allLocationTypeIds[$row->term] = $row->id;
      }
      $surveyFilters = [];
      if (array_key_exists('survey_restrictions', $config)) {
        foreach ($config['survey_restrictions'] as $type => $surveyIds) {
          $surveys = implode(', ', $surveyIds);
          warehouse::validateIntCsvListParam($surveys);
          if (!isset($allLocationTypeIds[$type])) {
            throw new exception('Configured survey restriction incorrect in spatial index builder');
          }
          $id = $allLocationTypeIds[$type];
          $surveyFilters[] = "AND (l.location_type_id<>$id OR s.survey_id IN ($surveys))\n";
        }
      }
      $filters = [
        'allLocationTypeIds' => implode(', ', $allLocationTypeIds),
        'surveyFilters' => implode("\n", $surveyFilters),
      ];
      $cache->set('spatial-index-location-type-filter-info', $filters);
    }
    return $filters;
  }

  /**
   * Retrieve a list of attribute IDs for linked locations.
   *
   * Sample attributes with a system function of linked_location_id can be used
   * to allow recorders to pick an indexed location to use when a record
   * straddles a boundary.
   *
   * @param object $db
   *   Database connection object.
   *
   * @return string
   *   CSV list of attribute IDs ready for SQL in(...) clause.
   */
  public static function getLinkedLocationAttrIds($db) {
    $cache = Cache::instance();
    $attrIds = $cache->get('spatial-index-linked-location-attr-ids');
    if (!$attrIds) {
      $qry = <<<SQL
      SELECT string_agg(id::text, ', ') as attrs
      FROM sample_attributes
      WHERE system_function='linked_location_id';
SQL;
      // Use 0 as fallback so SQL still works later.
      $attrIds = $db->query($qry)->current()->attrs ?? '0';
      $cache->set('spatial-index-linked-location-attr-ids', $attrIds);
    }
    return $attrIds;
  }

}
