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
 * @package    Modules
 * @subpackage Workflow
 * @author     Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/Indicia-Team/
 */

/**
 * Controller providing CRUD access to the surveys list.
 */
class Workflow_event_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('workflow_event');
    $this->columns = array('id'=>'ID',
                           'entity'=>'Entity',
                           'event_type'=>'Type',
                           'key'=>'Key',
                           'key_value'=>'Key value');
    $this->pagetitle = 'Workflow module event definition';
  }

  protected function get_action_columns() {
    return array(
      array('caption'=>'Edit',
            'url'=>'workflow_event/edit/{id}'));
  }

  protected function prepareOtherViewData($values)
  {
    $config = kohana::config('workflow');
    $entitySelectItems = array();
    $jsonMapping = array();

    foreach($config['entities'] as $entity => $entityDef){
      $entitySelectItems[$entity] = $entityDef['title'];
      foreach($entityDef['setableColumns'] as $column)
          $jsonMapping[] = '"'.$column.'": {"type":"str","desc":"'.$entity.' '.$column.'"}';
    }
    return array('entities' => $config['entities'],
                 'entitySelectItems' => $entitySelectItems,
                 'jsonSchema' => '{"type":"map", "title":"Columns to set", "mapping": {'.implode(',', $jsonMapping).
                                 '},"desc":"List of columns and the values they are to be set to, when event is triggered."}'
    );
  }

  /**
   * You can only access the list of workflow events if CoreAdmin.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin');
  }

}
