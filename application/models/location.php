<?php defined('SYSPATH') or die('No direct script access.');

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

/**
 * Model class for the Locations table.
 */
class Location_Model extends ORM_Tree {

  public $search_field='name';

  protected $lookup_against='detail_location';
  // This needs id, name, code, external_key, website_id and location_type_id

  protected $ORM_Tree_children = "locations";
  protected $has_and_belongs_to_many = array('websites', 'groups');
  protected $has_many = array('samples', 'location_attribute_values', 'location_media');
  protected $belongs_to = array('created_by' => 'user', 'updated_by' => 'user', 'location_type' => 'termlists_term');

  // Declare that this model has child attributes, and the name of the node in the submission which contains them
  protected $has_attributes = TRUE;
  protected $attrs_submission_name = 'locAttributes';
  public $attrs_field_prefix = 'locAttr';

  // Declare additional fields required when posting via CSV.
  protected $additional_csv_fields = array(
    // extra lookup options
    'location:fk_parent:code' => 'Parent location Code',
    'location:fk_parent:external_key' => 'Parent location External key',
  );

  public $importDuplicateCheckCombinations = array(
    array(
      'description' => 'Location External Key',
      'fields' => array(
          array('fieldName' => 'website_id', 'notInMappings' => TRUE),
          array('fieldName' => 'location:location_type_id'),
          array('fieldName' => 'location:external_key'),
      ),
    ),
    array(
      'description' => 'Location Name',
      'fields' => array(
          array('fieldName' => 'website_id', 'notInMappings' => TRUE),
          array('fieldName' => 'location:location_type_id'),
          array('fieldName' => 'location:name'),
      ),
    ),
    array(
      'description' => 'Location Code',
      'fields' => array(
          array('fieldName' => 'website_id', 'notInMappings' => TRUE),
          array('fieldName' => 'location:location_type_id'),
          array('fieldName' => 'location:code'),
      ),
    ),
    array(
      'description' => 'Location Grid Ref',
      'fields' => array(
          array('fieldName' => 'website_id', 'notInMappings' => TRUE),
          array('fieldName' => 'location:location_type_id'),
          array('fieldName' => 'location:centroid_sref'),
      ),
    ),
    array(
      'description' => 'Location Parent and Code',
      'fields' => array(
          array('fieldName' => 'website_id', 'notInMappings' => TRUE),
          array('fieldName' => 'location:location_type_id'),
          array('fieldName' => 'location:parent_id'),
          array('fieldName' => 'location:code'),
      ),
    ),
  );

  public function validate(Validation $array, $save = FALSE) {
    $orig_values = $array->as_array();

    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('name', 'required');
    $this->add_sref_rules($array, 'centroid_sref', 'centroid_sref_system');

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'code',
      'parent_id',
      'deleted',
      'centroid_geom',
      'boundary_geom',
      'location_type_id',
      'comment',
      'public',
      'external_key',
    );
    return parent::validate($array, $save);
  }

  /**
   * Ensure saved boundaries won't break intersection queries.
   *
   * * Applies st_MakeValid to ensure geometries are valid.
   * * Multicollections don't support geo functions such as ST_Intersction
   *   so we either convert to a Multipolygon or Multilinestring (using)
   *   ST_CollectionHomogenize) or we apply a very small buffer so it
   *   converts to a Multipolygon.
   */
  public function __set($key, $value) {
    if (substr($key, -5) === '_geom') {
      if ($value) {
        $srid = kohana::config('sref_notations.internal_srid');
        $qry = <<<SQL
SELECT CASE
  WHEN ST_GeometryType(ST_CollectionHomogenize(ST_MakeValid(ST_GeomFromText('$value', $srid)))) = 'ST_GeometryCollection' THEN
    ST_Buffer(ST_MakeValid(ST_GeomFromText('$value', $srid)), 0.00001, 'quad_segs=2')
  ELSE
    ST_CollectionHomogenize(ST_MakeValid(ST_GeomFromText('$value', $srid)))
END AS geom
SQL;
        $row = $this->db->query($qry)->current();
        $value = $row->geom;
      }
    }
    parent::__set($key, $value);
  }

  /**
   * Override get handler to translate PostGIS internal spatial data to WKT.
   */
  public function __get($column) {
    $value = parent::__get($column);

    if (substr($column, -5) === '_geom' && !empty($value)) {
      $row = $this->db->query("SELECT ST_asText('$value') AS wkt")->current();
      $value = $row->wkt;
    }
    return $value;
  }

  /**
   * Return the submission structure, which includes defining the locations_websites table
   * is a sub-model.
   *
   * @return array
   *   Submission structure for a location entry.
   */
  public function get_submission_structure() {
    return array(
      'model' => 'location',
      'joinsTo' => array('websites'),
    );
  }

  /**
   * Handle the case where a new record is created with a centroid_sref but without the geom being pre-calculated.
   * E.g. when importing from a shape file, or when JS is disabled on the client.
   */
  protected function preSubmit() {
    // Allow a location to be submitted with a spatial ref and system but no centroid_geom. If so we
    // can work out the Geom
    if (!empty($this->submission['fields']['centroid_sref']['value']) &&
        !empty($this->submission['fields']['centroid_sref_system']['value']) &&
        empty($this->submission['fields']['centroid_geom']['value'])) {

      try {
        $this->submission['fields']['centroid_geom']['value'] = spatial_ref::sref_to_internal_wkt(
            $this->submission['fields']['centroid_sref']['value'],
            $this->submission['fields']['centroid_sref_system']['value']
        );
      }
      catch (Exception $e) {
        $this->errors['centroid_sref'] = $e->getMessage();
      }
    }
    elseif (empty($this->submission['fields']['centroid_sref']['value']) &&
        empty($this->submission['fields']['centroid_geom']['value']) &&
        !empty($this->submission['fields']['boundary_geom']['value'])) {
      kohana::log('debug', 'working out centroid from boundary');
      // if the geom is supplied for the boundary, but not the centroid sref, then calculate it.
      // First, convert the boundary geom to a centroid using any provided system, else use LatLong (EPSG:4326)
      $boundary = $this->submission['fields']['boundary_geom']['value'];
      if (!empty($this->submission['fields']['centroid_sref_system']['value']))
        $centroid = $this->calcCentroid($boundary, $this->submission['fields']['centroid_sref_system']['value']);
      else $centroid = $this->calcCentroid($boundary);
      $this->submission['fields']['centroid_geom']['value'] = $centroid['wkt'];
      $this->submission['fields']['centroid_sref']['value'] = $centroid['sref'];
      $this->submission['fields']['centroid_sref_system']['value'] = $centroid['sref_system'];
    }
    // Empty boundary geom is allowed but must be null
    if (isset($this->submission['fields']['boundary_geom']['value']) && empty($this->submission['fields']['boundary_geom']['value']))
      $this->submission['fields']['boundary_geom']['value'] = NULL;
    $this->preSubmitTidySref();
    return parent::presubmit();
  }

  /**
   * Gives sref modules the chance to tidy the format of input values, e.g. OSGB grid refs are capitalised and spaces
   * stripped.
   */
  private function preSubmitTidySref() {
    if (array_key_exists('centroid_sref', $this->submission['fields']) &&
        array_key_exists('centroid_sref_system', $this->submission['fields'])) {
      $this->submission['fields']['centroid_sref']['value'] = spatial_ref::sref_format_tidy(
          $this->submission['fields']['centroid_sref']['value'],
          $this->submission['fields']['centroid_sref_system']['value']
      );
    }
  }

  /*
   * Calculates centroid of a location from a boundary wkt
   */
  public function calcCentroid($boundary, $system = '4326') {
    $row = $this->db->query("SELECT ST_AsText(ST_Centroid(ST_GeomFromText('$boundary', " . kohana::config('sref_notations.internal_srid') . "))) AS wkt")->current();
    $result = array(
      'wkt' => $row->wkt,
      'sref' => spatial_ref::internal_wkt_to_sref($row->wkt, intval($system)),
      'sref_system' => $system
    );
    return $result;
  }

  private static function getConvertedOptionValue($first, $second) {
    return str_replace(array(',', ':'), array('&#44', '&#58'), $first) .
            ":" .
            str_replace(array(',', ':'), array('&#44', '&#58'), $second);
  }

  /**
   * Defines inputs required for values that apply to all imported records.
   *
   * Define a form that is used to capture a set of predetermined values that
   * apply to every record during an import.
   */
  public function fixedValuesForm() {
    $srefs = [];
    $systems = spatial_ref::system_metadata();
    foreach ($systems as $code => $metadata) {
      $srefs[] = self::getConvertedOptionValue($code, $metadata['title']);
    }

    $location_types = array(":Defined in file");
    $parent_location_types = array(":No filter");
    $terms = $this->db->select('id, term')->from('list_termlists_terms')->where('termlist_external_key', 'indicia:location_types')->orderby('term', 'asc')->get()->result();
    foreach ($terms as $term) {
      $location_types[] = self::getConvertedOptionValue($term->id, $term->term);
      $parent_location_types[] = self::getConvertedOptionValue($term->id, $term->term);
    }

    return array(
      'website_id' => array(
        'display' => 'Website',
        'description' => 'Select the website to import records into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:website:id:title',
      ),
      'location:centroid_sref_system' => array(
        'display' => 'Spatial Ref. System',
        'description' => 'Select the spatial reference system used in this import file. Note, if you have a file with a mix of spatial reference systems then you need a ' .
            'column in the import file which is mapped to the Location Spatial Reference System field containing the spatial reference system code.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $srefs),
      ),
      'location:location_type_id' => array(
        'display' => 'Location Type',
        'description' => 'Select the Location Type for all locations in this import file. Note, if you have a file with a mix of location type then you need a ' .
                         'column in the import file which is mapped to the Location Type field.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $location_types),
      ),
      'fkFilter:location:location_type_id' => array(
        'display' => 'Parent Location Type',
        'description' => 'If this import file includes locations which reference parent locations records, you can restrict the type of parent locations looked ' .
                         'up by setting this location type. It is not currently possible to use a column in the file to do this on a location by location basis.',
        'datatype' => 'lookup',
        'lookup_values' => implode(',', $parent_location_types),
      ),
    );
  }

}
