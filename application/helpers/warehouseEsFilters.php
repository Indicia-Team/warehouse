<?php

/**
 * @file
 * A helper class for direct access to DB when converting filter to ES format.
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
 * A helper class for direct access to DB when converting filter to ES format.
 */
class warehouseEsFilters {

  /**
   * Warehouse database connection if running on warehouse.
   *
   * @var object
   */
  private static $db;

  /**
   * Get the combined boundary of several locations.
   *
   * @param string $locationIds
   *   Comma separated location ID list.
   *
   * @return string
   *   Well-known text for the polygon in WKT GPS lat long (EPSG:4326).
   */
  public static function getCombinedBoundaryData($locationIds) {
    if (!isset(self::$db)) {
      self::$db = new Database();
    }
    warehouse::validateIntCsvListParam($locationIds);
    $query = <<<SQL
      SELECT ST_AsText(ST_Transform(ST_Union(COALESCE(l.boundary_geom, l.centroid_geom)), 4326)) as polygon
      FROM locations l
      LEFT JOIN locations_websites lw on lw.location_id=l.id AND lw.deleted=false
      WHERE l.deleted=false
      AND l.id IN ($locationIds);
SQL;
    return self::$db->query($query)->current()['polygon'];
  }

  /**
   * Convert taxonomy ID list to external keys.
   *
   * @param string $filterField
   *   Field to filter against in cache_taxa_taxon_lists (id or
   *   taxon_meaning_id).
   * @param string $filterValues
   *   Comma-separated list of IDs.
   *
   * @return array
   *   Array containing data loaded with an external key property.
   */
  public static function taxonIdsToExternalKeys($filterField, $filterValues) {
    // Direct access as on warehouse.
    if (!isset(self::$db)) {
      self::$db = new Database();
    }
    $masterChecklistId = warehouse::getMasterTaxonListId();
    $field = pg_escape_identifier(self::$db->getLink(), $filterField);
    warehouse::validateIntCsvListParam($filterValues);
    $query = <<<SQL
      SELECT DISTINCT cttlout.external_key
      FROM cache_taxa_taxon_lists cttlin
      JOIN cache_taxa_taxon_lists cttlout ON cttlout.search_code=cttlin.external_key AND cttlout.taxon_list_id=$masterChecklistId
      WHERE 1=1
      AND cttlin.$field in ($filterValues);
SQL;
    return self::$db->query($query)->result_array(FALSE);
  }

  /**
   * Retrieve a list of terms from a list of IDs.
   *
   * @param string $ids
   *   Comma-separated list of termlist_term_ids.
   *
   * @return array
   *   Database rows each containing a term.
   */
  public static function getTermsFromIds($ids) {
    if (!isset(self::$db)) {
      self::$db = new Database();
    }
    warehouse::validateIntCsvListParam($ids);
    return self::$db->query("select term from cache_termlists_terms where id in ($ids)")->result_array(FALSE);
  }

  /**
   * Retrieve a list of all license codes.
   *
   * Includes whether they are considered a broadly open licence.
   *
   * @return array
   *   Database rows each containing a code and open flag.
   */
  public static function getLicences() {
    if (!isset(self::$db)) {
      self::$db = new Database();
    }
    return self::$db->query('select code, open from licences where deleted=false')->result_array(FALSE);
  }

  /**
   * Get the list of external keys for a taxonomy scratchpad.
   *
   * @param int $id
   *   Scratchpad list ID.
   *
   * @return array
   *   Database rows each containing an external key.
   */
  public static function getExternalKeysForTaxonScratchpad(int $id) {
    if (!isset(self::$db)) {
      self::$db = new Database();
    }
    $query = <<<SQL
      SELECT DISTINCT cttl.external_key
      FROM scratchpad_list_entries sle
      JOIN cache_taxa_taxon_lists cttl ON cttl.id=sle.entry_id
      WHERE sle.scratchpad_list_id=$id
    SQL;
    return self::$db->query($query)->result_array(FALSE);
  }

}
