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

defined('SYSPATH') or die('No direct script access.');

/**
 * Model class for the groups table.
 */
class Group_Model extends ORM {

  protected $has_one = ['filter'];

  protected $has_and_belongs_to_many = ['users', 'locations'];

  protected $has_many = ['group_invitations', 'group_pages'];

  /**
   * Release status needs updating?
   *
   * Flag indicating if the group's private record status is changing,
   * indicating we need to update the release status of records.
   *
   * @var bool
   */
  protected $wantToUpdateReleaseStatus = FALSE;

  protected $updatingTitle = FALSE;

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('group_type_id', 'required');
    $array->add_rules('website_id', 'required');
    $array->add_rules('code', 'length[1,20]');
    $array->add_rules('post_blog_permission', 'regex[/^(A|M)$/]');
    $array->add_rules('contained_by_group_id', 'integer');
    $this->unvalidatedFields = [
      'code',
      'description',
      'from_date',
      'to_date',
      'private_records',
      'filter_id',
      'joining_method',
      'deleted',
      'implicit_record_inclusion',
      'view_full_precision',
      'logo_path',
      'licence_id',
      'container',
    ];
    // Has the private records flag changed?
    $this->wantToUpdateReleaseStatus = isset($this->submission['fields']['private_records']) &&
        ($this->submission['fields']['private_records']['value'] === '1' || $this->submission['fields']['private_records']['value'] === 't') !== ($this->private_records === 't');
    if (isset($this->submission['fields']['id']) && isset($this->submission['fields']['title']) && $this->submission['fields']['title']['value'] !== $this->title) {
      $this->updatingTitle = TRUE;
    }
    return parent::validate($array, $save);
  }

  /**
   * Override save method to handle group deletion.
   *
   * @return ORM
   *   Object for chaining.
   */
  public function save() {
    if ($this->deleted === 't') {
      // Create a task to delete the group from the cache.
      $wq = new WorkQueue();
      $wq->enqueue($this->db, [
        'task' => 'task_group_delete',
        'entity' => 'group',
        'record_id' => $this->id,
        'cost_estimate' => 100,
        'priority' => 3,
      ]);
    }
    return parent::save();
  }

  /**
   * If changing the private records setting, then must update the group's records release_status.
   */
  public function postSubmit($isInsert) {
    if (!$isInsert && $this->wantToUpdateReleaseStatus) {
      $status = $this->private_records === '1' ? 'U' : 'R';
      $sql = "update #table# o
set release_status='$status'
from samples s
where s.deleted=false and s.id=o.sample_id and s.group_id=$this->id";
      $this->db->query(str_replace('#table#', 'occurrences', $sql));
      $this->db->query(str_replace('#table#', 'cache_occurrences_functional', $sql));
    }
    $this->processIndexGroupsLocations();
    $this->processIndexGroupsTaxonGroups();
    if ($this->updatingTitle) {
      $wq = new WorkQueue();
      $wq->enqueue($this->db, [
        'task' => 'task_group_update_title',
        'entity' => 'group',
        'record_id' => $this->id,
        'cost_estimate' => 100,
        'priority' => 3,
      ]);
    }
    return TRUE;
  }

  /**
   * Method to populate the indexed locations that this group intersects with.
   *
   * Makes it easy to do things like suggest groups based on geographic region.
   */
  private function processIndexGroupsLocations() {
    $filter = json_decode($this->filter->definition, TRUE);
    $existingIndexedLocations = $this->db->select('id', 'location_id')
      ->from('index_groups_locations')
      ->where('group_id', $this->id)
      ->get();
    // Does the filter have any location_ids, indexed or otherwise? Allow for legacy filters.
    $filterLocationIds = [];
    if (!empty($filter['indexed_location_list'])) {
      $filterLocationIds[] = $filter['indexed_location_list'];
    }
    if (!empty($filter['indexed_location_id'])) {
      $filterLocationIds[] = $filter['indexed_location_id'];
    }
    if (!empty($filter['location_list'])) {
      $filterLocationIds[] = $filter['location_list'];
    }
    if (!empty($filter['location_id'])) {
      $filterLocationIds[] = $filter['location_id'];
    }
    $updatedIndexedLocations = [];
    // Location IDs or searchArea can be used to find indexed locations.
    if (!empty($filterLocationIds) || !empty($filter['searchArea'])) {
      $config = kohana::config_load('spatial_index_builder', FALSE);
      // Either use the location types specific to group indexing, or the
      // generic list of types.
      $locationTypes = $config['group_location_types'] ?? $config['location_types'] ?? [];
      if (!empty($locationTypes)) {
        $locationTypeNames = warehouse::stringArrayToSqlInList($this->db, $locationTypes);
        $locationTypeRows = $this->db->query(
          "select id from cache_termlists_terms where termlist_title='Location types' and term in ($locationTypeNames)"
          )->result();
        $locationTypeIds = [];
        foreach ($locationTypeRows as $type) {
          $locationTypeIds[] = (int) $type->id;
        }
        $types = implode(',', $locationTypeIds);
        if (!empty($filterLocationIds)) {
          $filterLocationIdsCsv = implode(',', $filterLocationIds);
          warehouse::validateIntCsvListParam($filterLocationIdsCsv);
          $qry = <<<SQL
            SELECT l.id, l.location_type_id
            FROM locations l
            JOIN locations search ON
                st_intersects(search.boundary_geom, l.boundary_geom)
                AND NOT st_touches(search.boundary_geom, l.boundary_geom)
            WHERE search.id IN ($filterLocationIdsCsv)
            AND l.location_type_id in ($types);
SQL;
        }
        else {
          $srid = (int) kohana::config('sref_notations.internal_srid');
          $searchArea = pg_escape_literal($this->db->getLink(), $filter['searchArea']);
          $qry = <<<SQL
            SELECT l.id, l.location_type_id
            FROM locations l
            WHERE st_intersects(st_geomfromtext($searchArea, $srid), l.boundary_geom)
            AND NOT st_touches(st_geomfromtext($searchArea, $srid), l.boundary_geom)
            AND l.location_type_id in ($types);
          SQL;
        }
        $rows = $this->db->query($qry)->result();
        foreach ($rows as $row) {
          $updatedIndexedLocations[$row->id] = $row->location_type_id;
        }
      }
    }
    $foundExistingLocationIds = [];
    // Go through the existing index entries for this group. Remove any that
    // are not needed now.
    foreach ($existingIndexedLocations as $record) {
      if (isset($updatedIndexedLocations[$record->location_id])) {
        // Got a correct one already. Remove the location ID from the list we
        // want to add later.
        unset($updatedIndexedLocations[$record->location_id]);
        if (in_array($record->location_id, $foundExistingLocationIds)) {
          // This one must exist twice in the index so clean it up.
          $this->db->delete('index_groups_locations', ['id' => $record->id]);
        }
        else {
          $foundExistingLocationIds[] = $record->location_id;
        }
      }
      else {
        // Got one we didn't actually want.
        $this->db->delete('index_groups_locations', ['id' => $record->id]);
      }
    }
    // Any remaining in our list now need to be added.
    foreach ($updatedIndexedLocations as $locationId => $locationTypeId) {
      $this->db->insert('index_groups_locations', [
        'group_id' => $this->id,
        'location_id' => $locationId,
        'location_type_id' => $locationTypeId,
      ]);
    }
  }

  /**
   * Process taxon groups for a recording group.
   *
   * Method to populate the indexed taxon groups that this group intersects
   * with. Makes it easy to do things like suggest groups based on species
   * being recorded.
   */
  private function processIndexGroupsTaxonGroups() {
    $filter = json_decode($this->filter->definition, TRUE);
    $exist = $this->db->select('id', 'taxon_group_id')
      ->from('index_groups_taxon_groups')
      ->where('group_id', $this->id)
      ->get();

    $taxon_group_ids = [];

    if (!empty($filter['taxon_group_list'])) {
      // Got a list of taxon groups linked to the group's filter, so these can
      // be used to define the context of the group.
      $taxon_group_ids = explode(',', $filter['taxon_group_list']);
    }
    elseif (!empty($filter['taxa_taxon_list_list']) || !empty($filter['higher_taxa_taxon_list_list']) || !empty($filter['taxon_meaning_list'])) {
      // Handle other types of species based filter, e.g. higher or lower
      // taxa taxon_list id.
      $groups = $this->db->select('DISTINCT taxon_group_id')
        ->from('cache_taxa_taxon_lists');
      if (!empty($filter['taxa_taxon_list_list'])) {
        $groups->in('id', explode(',', $filter['taxa_taxon_list_list']));
      }
      if (!empty($filter['higher_taxa_taxon_list_list'])) {
        $groups->in('id', explode(',', $filter['higher_taxa_taxon_list_list']));
      }
      if (!empty($filter['taxon_meaning_list'])) {
        $groups->in('taxon_meaning_id', explode(',', $filter['taxon_meaning_list']));
      }
      $groups = $groups->get();
      foreach ($groups as $record) {
        $taxon_group_ids[] = $record->taxon_group_id;
      }
    }
    // Go through the existing index entries for this group. Remove any that
    // are not needed now.
    foreach ($exist as $record) {
      if (in_array($record->taxon_group_id, $taxon_group_ids)) {
        // Got a correct one already. Remove the taxon group ID from the list we
        // want to add later.
        if (($key = array_search($record->taxon_group_id, $taxon_group_ids)) !== FALSE) {
          unset($taxon_group_ids[$key]);
        }
      }
      else {
        // Got one we didn't actually want.
        $this->db->delete('index_groups_taxon_groups', ['id' => $record->id]);
      }
    }
    // Any remaining in our list now need to be added.
    foreach ($taxon_group_ids as $taxon_group_id) {
      $this->db->insert('index_groups_taxon_groups', [
        'group_id' => $this->id,
        'taxon_group_id' => $taxon_group_id,
      ]);
    }
  }

}
