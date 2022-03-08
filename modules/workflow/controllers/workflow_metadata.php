<?php

/**
 * @file
 * Controller class for workflow metadata.
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
 * @package Modules
 * @subpackage Workflow
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/warehouse
 */

/**
 * Controller providing CRUD access to the surveys list.
 */
class Workflow_metadata_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('workflow_metadata');
    $this->columns = array(
      'id' => 'ID',
      'group_code' => 'Workflow group',
      'entity' => 'Entity',
      'key' => 'Key',
      'key_value' => 'Key value',
      'label' => 'Label',
    );
    $this->pagetitle = 'Workflow module metadata specification';
  }

  protected function get_action_columns() {
    return array(
      array(
        'caption' => 'Edit',
        'url' => 'workflow_metadata/edit/{id}',
      )
    );
  }

  protected function prepareOtherViewData(array $values) {
    $config = kohana::config('workflow');
    $entitySelectItems = array();
    foreach ($config['entities'] as $entity => $entityDef) {
      $entitySelectItems[$entity] = $entityDef['title'];
    }
    // Load workflow groups from configuration file.
    $groupConfig = kohana::config('workflow_groups', FALSE, FALSE);
    $groups = [];
    if ($groupConfig) {
      foreach ($groupConfig['groups'] as $group => $groupDef) {
        if ($this->auth->logged_in('CoreAdmin') || $this->auth->has_website_access('admin', $groupDef['owner_website_id'])) {
          $groups[$group] = $group;
        }
      }
    }
    return array(
      'entities' => $config['entities'],
      'entitySelectItems' => $entitySelectItems,
      'groupSelectItems' => $groups,
    );
  }

  /**
   * Apply page access permissions.
   *
   * You can only access the list of workflow metadata if CoreAdmin or SiteAdmin for a website that owns one of the
   * workflow groups.
   *
   * @return bool
   *   True if acecss granted.
   */
  protected function page_authorised() {
    return workflow::allowWorkflowConfigAccess($this->auth);
  }

  protected function show_submit_fail() {
    $all_errors = $this->model->getAllErrors();
    if (count($all_errors) !== 0) {
      $this->session->set_flash('flash_error', implode('<br/>', $all_errors));
    }
    else {
      $this->session->set_flash('flash_error', 'The record could not be saved.');
    }
    $values = $this->getDefaults();
    $values = array_merge($values, $_POST);
    $this->showEditPage($values);
  }

}
