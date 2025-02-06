<?php

/**
 * @file
 * Workflow event controller class.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/warehouse
 */

/**
 * Controller providing CRUD access to the surveys list.
 */
class Workflow_event_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('workflow_event');
    $this->columns = array(
      'id' => 'ID',
      'group_code' => 'Workflow group',
      'entity' => 'Entity',
      'event_type' => 'Type',
      'key' => 'Key',
      'key_value' => 'Key value',
      'label' => 'Label',
      'values' => 'Changed values',
    );
    $this->pagetitle = 'Workflow module event definition';
  }

  protected function get_action_columns() {
    return array(
      array(
        'caption' => 'Edit',
        'url' => 'workflow_event/edit/{id}',
      ),
    );
  }

  /**
   * Convert location_ids_filter to suitable default for the sub_list control.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    $r['location_ids_filter_array'] = [];
    if (isset($r['workflow_event:location_ids_filter'])) {
      if (preg_match('/^{(?<list>\d+(,\d+)*)}$/', $r['workflow_event:location_ids_filter'], $matches)) {
        $ids = explode(',', $matches['list']);
        foreach ($ids as $id) {
          $location = ORM::factory('location', $id);
          $r['location_ids_filter_array'][] = [
            'caption' => $location->name,
            'fieldname' => 'workflow_event:location_ids_filter[]',
            'default' => $id,
          ];
        }
      }
    }
    return $r;
  }

  /**
   * Prepares any additional data required by the edit view.
   *
   * @param array $values
   *   Data values to show on the edit form.
   *
   * @return array
   *   Associative array of any additional data to expose to the edit view.
   */
  protected function prepareOtherViewData(array $values) {
    $config = kohana::config('workflow');
    $entitySelectItems = array();
    $jsonMapping = array();
    $entities = $config['entities'];

    foreach ($entities as $entity => $entityDef) {
      $entitySelectItems[$entity] = $entityDef['title'];
      foreach ($entityDef['setableColumns'] as $column => $values) {
        $jsonMapping[$column] = [
          'type' => 'str',
          'desc' => "Set the $entity.$column field to the chosen value when this event occurs.",
          'enum' => $values,
        ];
      }
    }
    // Load workflow groups from configuration file.
    $config = kohana::config('workflow_groups', FALSE, FALSE);
    $groups = [];
    if ($config) {
      foreach ($config['groups'] as $group => $groupDef) {
        if ($this->auth->logged_in('CoreAdmin') || $this->auth->has_website_access('admin', $groupDef['owner_website_id'])) {
          $groups[$group] = $group;
        }
      }
    }
    return array(
      'entities' => $entities,
      'groupSelectItems' => $groups,
      'entitySelectItems' => $entitySelectItems,
      'jsonSchema' => json_encode([
        'type' => 'map',
        'title' => 'Columns to set',
        'mapping' => $jsonMapping,
        'desc' => 'List of columns and the values they are to be set to, when event is triggered.',
      ]),
    );
  }

  /**
   * Apply page access permissions.
   *
   * You can only access the list of workflow metadata if CoreAdmin or
   * SiteAdmin for a website that owns one of the workflow groups.
   *
   * @return bool
   *   True if acecss granted.
   */
  protected function page_authorised() {
    return workflow::allowWorkflowConfigAccess($this->auth);
  }

  protected function show_submit_fail() {
    $allErrors = $this->model->getAllErrors();
    if (count($allErrors) !== 0) {
      $this->session->set_flash('flash_error', implode('<br/>', $allErrors));
    }
    else {
      $this->session->set_flash('flash_error', 'The record could not be saved.');
    }
    $values = $this->getDefaults();
    $values = array_merge($values, $_POST);
    $this->showEditPage($values);
  }

}
