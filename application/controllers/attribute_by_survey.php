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
 * @package Core
 * @subpackage Controllers
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link  http://code.google.com/p/indicia/
 */

/**
 * Controller providing the ability to configure the list of attributes joined to a survey.
 *
 * @package Core
 * @subpackage Controllers
 */
class Attribute_By_Survey_Controller extends Indicia_Controller
{
  public function __construct()
  {
    parent::__construct();
    if (!is_numeric($this->uri->last_segment())) 
      throw new Exception('Page cannot be accessed without a survey filter');
    if (!isset($_GET['type'])) 
      throw new Exception('Page cannot be accessed without a type parameter');
    if ($_GET['type']!='sample' && $_GET['type']!='occurrence' && $_GET['type']!='location')
      throw new Exception('Type parameter in URL is invalid');
    $this->survey = ORM::factory('survey', $this->uri->last_segment());
    $this->base_filter=array('survey_id'=>$this->survey);
    $this->get_auth();
    $this->auth_filter = $this->gen_auth_filter;
    $this->pagetitle = 'Attributes for '.$this->survey->title;
    $this->page_breadcrumbs[] = html::anchor('survey', 'Surveys');
    $this->page_breadcrumbs[] = $this->pagetitle;
  }
  
  public function index() {
    $this->template->content=new View('Attribute_by_survey/index');
    $this->template->title=$this->pagetitle;
    $filter = $this->base_filter;
    if ($this->auth_filter) 
      $filter = array_merge($filter, $this->auth_filter);
    $top_blocks = ORM::factory('form_structure_block')->
        where('parent_id',null)->
        where('type', strtoupper(substr($_GET['type'],0,1)))->
        where($filter)->
        orderby('weight', 'ASC')->find_all();
    $this->template->content->top_blocks=$top_blocks;
    $this->template->content->filter = $filter;
    // make a copy of the filter, tweaked for control filtering
    $controlfilter = array_merge($filter);
    $controlfilter['restrict_to_survey_id']=$controlfilter['survey_id'];
    unset($controlfilter['survey_id']);
    $this->template->content->controlfilter = $controlfilter;
    // provide a list of publicly available attributes so existing ones can be added
    $attrs = ORM::factory($_GET['type'].'_attribute')->
        where(array('public'=>'t','deleted'=>'f'))->find_all();
    $this->template->content->existingAttrs=$attrs;
  }
  
  /**
   * Handle the layout_update action, which uses $_POST data to find a list of commands
   * for re-ordering the controls
   */
  public function layout_update() {
    $structure = json_decode($_POST['layout_updates'],true);
    $this->saveBlockList($structure['blocks'], null);
    $this->saveControlList($structure['controls'], null);
    $this->session->set_flash('flash_info', "The form layout changes have been saved.");
    url::redirect('attribute_by_survey/'.$this->uri->last_segment().'?type='.$_GET['type']);
  }
  
  private function saveBlockList($list, $blockId) {
    $weight = 0;
    foreach ($list as $block) {
    	kohana::log('info', kohana::debug($block));
      if (substr($block['id'], 0, 10)=='new-block-') {
        $model = ORM::factory('form_structure_block');
        $model->name = $block['name'];
        $model->survey_id=$this->uri->last_segment();
        $model->weight=$weight;
        $model->type=strtoupper(substr($_GET['type'], 0, 1));
        $model->parent_id=$blockId;
        $model->save();
      } elseif (substr($block['id'], 0, 6)=='block-') {
        $id = str_replace('block-','',$block['id']);
        $model = ORM::factory('form_structure_block', $id);
        if ($model->weight!=$weight || $model->parent_id!=$blockId) {
          $model->parent_id=$blockId;
          $model->weight = $weight;
          $model->save();
        }
      }
      if (isset($block['blocks'])) $this->saveBlockList($block['blocks'], $model->id);
      $this->saveControlList($block['controls'], $model->id);
      $weight++;
    }  
  }
  
  private function saveControlList($list, $blockId) {
    $weight = 0;
    foreach ($list as $control) {
      $changed=false;
      if (substr($control, 0, 8)=='control-') {
        $control = str_replace('control-','',$control);
        $model = ORM::factory($_GET['type'].'_attributes_website', $control);
        if ($model->weight!=$weight) {
          $model->weight = $weight;
          $changed = true;
        }
        if ($model->form_structure_block_id!=$blockId) {
          $model->form_structure_block_id=$blockId;
          $changed = true;
        }
        $weight++;
        if ($changed)
          $model->save();
      }
    }
  }
  
  /**
   * Retrieve the list of websites the user has access to. The list is then stored in
   * $this->gen_auth_filter. Also checks if the user is core admin.
   */
  protected function get_auth() {
    // If not logged in as a Core admin, restrict access to available websites.
    if(!$this->auth->logged_in('CoreAdmin')){
      $site_role = (new Site_role_Model('Admin'));
      $websites=ORM::factory('users_website')->where(
      array('user_id' => $_SESSION['auth_user']->id,
              'site_role_id' => $site_role->id))->find_all();
      $website_id_values = array();
      foreach($websites as $website)
        $website_id_values[] = $website->website_id;
      $website_id_values[] = null;
      $this->gen_auth_filter = array('field' => 'website_id', 'values' => $website_id_values);
    }
    else $this->gen_auth_filter = null;    
  }

  
}