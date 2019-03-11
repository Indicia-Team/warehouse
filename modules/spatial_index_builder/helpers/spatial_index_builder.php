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
      $surveyRestriction = '';
      if (!array_key_exists('location_types', $config)) {
        throw new Exception('Spatial index builder configuration location_types missing');
      }
      $idQuery = $db->query("select id, term from cache_termlists_terms where preferred_term in ('" .
        implode("','", $config['location_types']) . "') and termlist_title ilike 'location types'")
        ->result();
      $allLocationTypeIds = [];
      foreach ($idQuery as $row) {
        $allLocationTypeIds[$row->term] = $row->id;
      }
      $surveyFilters = [];
      if (array_key_exists('survey_restrictions', $config)) {
        foreach ($config['survey_restrictions'] as $type => $surveyIds) {
          $surveys = implode(', ', $surveyIds);
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
   * Retrieve a list of location types to be treated as hiearchical.
   *
   * A utility function used by the work queue task helpers. Returns the
   * list of location type IDs where the locations in this layer have
   * parent_ids that point to another layer which contains locations that
   * should also be indexed. For example, a Counties layer could be
   * included in this list if the location parent_ids point to locations in a
   * Countries layer. The Countries would then be inluded in the indexing data
   * without ever needing to be spatially queried.
   *
   * @param object $db
   *   Database connection object.
   *
   * @return string
   *   Comma separated list of IDs. If none, then returns '0' so can still be
   *   inserted into an SQL IN (...) filter.
   */
  public static function getLocationTypeTreeFilters($db) {
    $cache = Cache::instance();
    $locationTypeIds = $cache->get('spatial-index-hierarchical-location-types');
    if (!$locationTypeIds) {
      $config = kohana::config_load('spatial_index_builder');
      if (!array_key_exists('hierarchical_location_types', $config)) {
        // This effectively cancels the hierarchical querying.
        return '0';
      }
      $idQuery = $db->query("select id, term from cache_termlists_terms where preferred_term in ('" .
        implode("','", $config['location_types']) . "') and termlist_title ilike 'location types'")
        ->result();
      $locationTypeIds = [];
      foreach ($idQuery as $row) {
        $locationTypeIds[$row->term] = $row->id;
      }
      $locationTypeIds = implode(', ', $locationTypeIds);
      $cache->set('spatial-index-hierarchical-location-types', $locationTypeIds);
    }
    return $locationTypeIds;
  }

}
