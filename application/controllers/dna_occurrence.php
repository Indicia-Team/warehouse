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

/**
 * Controller providing CRUD access to the metadata for dna-derived occurrences.
 */
class Dna_occurrence_Controller extends Indicia_Controller {

  public function __construct() {
    parent::__construct();
    $this->pagetitle = "DNA-derived occurrence metadata";
  }

  /**
   * Override the default index functionality to load data for editing.
   */
  public function index() {
    $this->model = ORM::factory('dna_occurrence')
      ->where(['occurrence_id' => $this->uri->last_segment(), 'deleted' => 'f'])
      ->find();
    $view = new View('dna_occurrence/dna_occurrence_edit');
    $values = $this->getDefaults();
    $modelValues = [];
    foreach ($this->model->as_array() as $key => $value) {
      $modelValues['dna_occurrence:' . $key] = $value;
    }
    $values = array_merge($values, $modelValues);
    $values['dna_occurrence:occurrence_id'] = $this->uri->last_segment();
    $view->values = $values;
    $this->template->content = $view;
  }

}
