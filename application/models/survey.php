<?php

/**
 * @file
 * Model for the survey entity.
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
 * Model class for the Surveys table.
 */
class Survey_Model extends ORM_Tree {

  protected $ORM_Tree_children = "surveys";

  protected $has_many = [
    'sample_media',
  ];

  protected $belongs_to = [
    'owner' => 'person',
    'website',
    'created_by' => 'user',
    'updated_by' => 'user',
  ];

  // Declare that this model has child attributes, and the name of the node
  // in the submission which contains them.
  protected $has_attributes = TRUE;
  protected $attrs_submission_name = 'srvAttributes';
  public $attrs_field_prefix = 'srvAttr';

  public function validate(Validation $array, $save = FALSE) {
    $array->pre_filter('trim');
    $array->add_rules('title', 'required');
    $array->add_rules('website_id', 'required');
    $this->unvalidatedFields = [
      'description',
      'deleted',
      'parent_id',
      'owner_id',
      'auto_accept',
      'auto_accept_max_difficulty',
      'auto_accept_taxa_filters',
      'core_validation_rules',
    ];
    return parent::validate($array, $save);
  }

  protected function preSubmit() {
    if (!empty($_POST['has-taxon-restriction-data'])) {
      $ttlIds = [];
      $tmIds = [];
      foreach ($_POST as $key => $value) {
        if (substr($key, -8) === ':present' && $value !== '0') {
          $ttlIds[] = $value;
        }
      }
      $tmIdRecs = $this->db
        ->select('id, taxon_meaning_id')
        ->from('cache_taxa_taxon_lists')
        ->in('id', $ttlIds)
        ->get()->result();

      foreach ($tmIdRecs as $tmIdRec) {
        $tmIds[] = intVal($tmIdRec->taxon_meaning_id);
      }
      $this->submission['fields']['auto_accept_taxa_filters'] = ['value' => $tmIds];
    }
    return parent::presubmit();
  }

  /**
   * Define a form that is used to capture a set of predetermined values that
   * apply to every record during an import.
   */
  public function fixedValuesForm($options = []) {
    $retval = [
      'website_id' => [
        'display' => 'Website',
        'description' => 'Select the website to import records into.',
        'datatype' => 'lookup',
        'population_call' => 'direct:website:id:title',
      ],
    ];
    return $retval;
  }

}
