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
 * Model class for the Taxa table.
 */
class Taxon_Model extends ORM {

  public $search_field = 'taxon';

  protected $belongs_to = [
    'meaning',
    'language',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  protected $has_many = ['taxa_taxon_lists'];

  protected $has_and_belongs_to_many = ['taxon_lists'];

  /**
   * Track external key changes.
   *
   * Taxon meaning ID for group of taxon names where preferred external key
   * changing. Allows synonyms/vernaculars to be kept in sync with the accepted
   * name.
   *
   * @var array
   */
  private $prefExternalKeyChangedForTaxonMeaningIds = [];

  /**
   * Does an update change fields which are in the occurrences cache tables?
   *
   * @var bool
   */
  private $updateAffectsOccurrenceCache = FALSE;

  public function validate(Validation $array, $save = FALSE) {
    // An external key change needs to apply to other taxa with the same
    // meaning (concept).
    if (isset($this->submission['fields']['external_key'])) {
      foreach ($this->taxa_taxon_lists as $ttl) {
        if ($ttl->preferred && $this->external_key !== $this->submission['fields']['external_key']['value']) {
          $this->prefExternalKeyChangedForTaxonMeaningIds[] = $ttl->taxon_meaning_id;
        }
      }
    }
    $array->pre_filter('trim');
    $array->add_rules('taxon', 'required');
    $array->add_rules('language_id', 'required');
    $array->add_rules('taxon_group_id', 'required');

    // Explicitly add those fields for which we don't do validation.
    $this->unvalidatedFields = [
      'attribute',
      'external_key',
      'authority',
      'deleted',
      'search_code',
      'description',
      'taxon_rank_id',
      'marine_flag',
      'freshwater_flag',
      'terrestrial_flag',
      'non_native_flag',
      'organism_key',
      'scientific',
      'organism_deprecated',
      'name_deprecated',
      'name_form',
    ];
    return parent::validate($array, $save);
  }

  /**
   * Handle tasks before submission.
   *
   * * Fill in calculated scientific field value.
   * * Check if any fields are being updated that imply the occurrence cache
   *   tables will need an update.
   */
  protected function preSubmit() {
    // Call the parent preSubmit function.
    parent::preSubmit();
    // Set scientific if latin.
    $scientific = ORM::factory('language')->find($this->submission['fields']['language_id']['value'])->iso === 'lat' ? 't' : 'f';
    $this->submission['fields']['scientific'] = [
      'value' => $scientific,
    ];
    // For existing records, need to assess if the occurrences cache data needs
    // a taxonomy refresh.
    if ($this->id) {
      $keyFields = [
        'taxon',
        'taxon_group_id',
        'external_key',
        'search_code',
        'organism_key',
        'taxon_rank_id',
        'marine_flag',
        'freshwater_flag',
        'terrestrial_flag',
        'non_native_flag',
      ];
      foreach ($keyFields as $keyField) {
        if (isset($this->submission['fields'][$keyField]) && isset($this->submission['fields'][$keyField]['value'])) {
          $updatedValueToCompare = $this->submission['fields'][$keyField]['value'];
          // Allow bool values to be '0' or '1'.
          if (substr($keyField, -5) === '_flag' && in_array($updatedValueToCompare, ['0', '1'])) {
            $updatedValueToCompare = $updatedValueToCompare === '1' ? 't' : 'f';
          }
          if ($updatedValueToCompare !== (string) $this->$keyField) {
            $this->updateAffectsOccurrenceCache = TRUE;
            break;
          }
        }
      }
    }
  }

  /**
   * After submission, add work_queue tasks if occurrence cache needs update.
   */
  protected function postSubmit($isInsert) {
    if (!$isInsert && $this->updateAffectsOccurrenceCache) {
      foreach ($this->taxa_taxon_lists as $ttl) {
        // Only a preferred name update affects the other names for the taxon.
        $namesFilter = $ttl->preferred === 't' ? "taxon_meaning_id=$ttl->taxon_meaning_id" : "id=$ttl->id";
        $addWorkQueueQuery = <<<SQL
          INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
          SELECT 'task_cache_builder_taxonomy_occurrence', 'taxa_taxon_list', id, 100, 3, now()
          FROM taxa_taxon_lists
          WHERE $namesFilter
          -- Ignore other names unless pre-existing
          AND (id=$ttl->id OR created_on<'$ttl->created_on')
          AND deleted=false
          ON CONFLICT DO NOTHING;
        SQL;
        $this->db->query($addWorkQueueQuery);
      }
    }
    if (!empty($this->prefExternalKeyChangedForTaxonMeaningIds)) {
      // Apply external key change to synonyms/vernaculars.
      $userId = $this->getUserId();
      foreach ($this->prefExternalKeyChangedForTaxonMeaningIds as $taxonMeaningId) {
        $updateExtKeyQuery = <<<SQL
UPDATE taxa t
SET external_key=?, updated_on=now(), updated_by_id=?
FROM taxa_taxon_lists ttl
WHERE ttl.taxon_meaning_id=?
AND t.id=ttl.taxon_id
SQL;
        $this->db->query($updateExtKeyQuery, [$this->external_key, $userId, $taxonMeaningId]);
      }
    }
    return TRUE;
  }

  /**
   * Set default values for a new taxon entry.
   */
  public function getDefaults() {
    return [
      // Latin.
      'language_id' => 2,
    ];
  }

}
