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
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Controller providing CRUD access to the occurrence data.
 */
class Occurrence_controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('occurrence');
    $this->pagetitle = 'Occurrences';
    $this->columns = [
      'id' => 'ID',
      'website' => 'Website',
      'survey' => 'Survey dataset',
      'taxon' => 'Taxon',
      'entered_sref' => 'Spatial Ref',
      'date_start' => 'Date',
    ];
    $this->set_website_access('editor');
  }

  /**
   * Override the index controller action to add filters for the parent sample if viewing the child occurrences.
   */
  public function index() {
    // This constructor normally has 1 argument which is the grid page. If there is a second argument
    // then it is the parent list ID.
    if ($this->uri->total_arguments() > 0) {
      $this->base_filter = array('sample_id' => $this->uri->argument(1));
    }
    parent::index();
  }

  /**
   * Return the action columns for the grid.
   */
  protected function get_action_columns() {
    return [
      [
        'caption' => 'edit',
        'url' => $this->controllerpath . "/edit/{id}"
      ],
      [
        'caption' => 'edit sample',
        'url' => 'sample/edit/{sample_id}'
      ],
    ];
  }

  /**
   * Returns an array of all values from this model and its super models ready to be
   * loaded into a form. For this controller, we need to also setup the custom attributes
   * available to display on the form.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $this->loadAttributes($r, array(
      'website_id' => array($r['occurrence:website_id']),
      'restrict_to_survey_id' => array(NULL, $r['sample:survey_id']),
    ));
    return $r;
  }

  protected function getDefaults() {
    $r = parent::getDefaults();
    // as you can't create an occurrence in the warehouse, no logic yet for which attributes
    // to display
    if ($this->uri->method(FALSE) !== 'create') {
      $sample = ORM::Factory('sample', $_POST['occurrence:sample_id']);
      $this->loadAttributes($r, array(
        'website_id' => array($_POST['occurrence:website_id']),
        'restrict_to_survey_id' => array(NULL, $sample->survey_id)
      ));
    }
    return $r;
  }

  public function save() {
    if (!empty($_POST['occurrence:record_status:combined'])) {
      $_POST['occurrence:record_status'] = substr($_POST['occurrence:record_status:combined'], 0, 1);
      $_POST['occurrence:record_substatus'] = substr($_POST['occurrence:record_status:combined'], 1, 1);
    }
    parent::save();
  }

  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return [
      [
        'controller' => 'occurrence_comment',
        'title' => 'Comments',
        'actions' => ['edit'],
      ], [
        'controller' => 'occurrence_medium',
        'title' => 'Media Files',
        'actions' => ['edit'],
      ],
      [
        'controller' => 'dna_occurrence',
        'title' => 'DNA',
        'actions' => ['edit'],
      ],
    ];
  }

  /**
   * Check access to a occurrence when editing. The occurrence's website must be in the list
   * of websites the user is authorised to administer.
   */
  protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter) && $this->auth_filter['field'] === 'website_id')
    {
      $occ = new Occurrence_Model($id);
      return (in_array($occ->website_id, $this->auth_filter['values']));
    }
    return true;
  }
}