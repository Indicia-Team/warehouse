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
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
* Base class for controllers which provide CRUD access to the lists of custom attributes
* associated with locations, occurrences or sample entities.
*
* @package	Core
* @subpackage Controllers
* @subpackage Controllers
*/

abstract class Attr_Gridview_Base_Controller extends Indicia_Controller {

  /* Constructor. $modelname = name of the model for the grid.
   * $viewname = name of the view which contains the grid.
   * $controllerpath = path the controller from the controllers folder
   * $viewname and $controllerpath can be ommitted if the names are all the same.
   */
  public function __construct($modelname, $name=NULL, $websitemodelname=NULL, $gridmodelname=NULL, $viewname=NULL, $controllerpath=NULL) {
    $this->name = is_null($name) ? $modelname : $name;
    $this->model=ORM::factory($modelname);
    $this->websitemodelname = is_null($websitemodelname) ? $modelname.'s_website' : $websitemodelname;
    $this->gridmodelname=is_null($gridmodelname) ? $modelname : $gridmodelname;
    $this->viewname=is_null($viewname) ? $modelname : $viewname;
    $this->controllerpath=is_null($controllerpath) ? $modelname : $controllerpath;
    $this->createpath=$this->controllerpath."/create";
    $this->processpath=$this->controllerpath."/process";
    $this->createbutton="New $name Attribute";
    $this->gridmodel = ORM::factory($this->gridmodelname);
    $this->pageNoUriSegment = 3;
    $this->base_filter = array();
    $this->auth_filter = null;
    $this->gen_auth_filter = null;
    $this->columns = $this->gridmodel->table_columns;

    $this->pagetitle = "Abstract Attribute gridview class - override this title!";
    $this->view = new View($this->viewname);
    parent::__construct();

    $filter_type = $this->input->get('filter_type',null);
    $filter_website = $this->input->get('website_id',null);
    $filter_survey = $this->input->get('survey_id',null);

    $this->actionColumns = array(
      'edit' => $this->controllerpath."/edit/£id£".'?filter_type='.$filter_type.'&website_id='.$filter_website.'&survey_id='.$filter_survey
    );

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

  }

  /**
   * Action for custom attribute/create page.
   * Displays a page allowing entry of a new custom attribute.
   */
  // Create function is called with the website_id and optional survey id. These are used to generate the
  // *_attribute_website record after the *_attribute
  public function create() {
    $filter_type = $this->input->post('filter_type', null);
    $website = $this->input->post('website_id', null);
    $survey = $this->input->post('survey_id', null);
    $attribute_load = new View('templates/attribute_load', array('website_id'=>$website, 'model' => $this->model));
        $this->setView('custom_attribute/custom_attribute_edit',
                  $this->model->object_name,
                  array('filter_type'=>$filter_type,
                      'website_id'=>$website,
                      'survey_id'=>$survey,
                      'enabled'=>'',
                      'disabled_input'=>'NO',
                      'attribute_load' => $attribute_load,
                      'name' => $this->name,
                      'processpath' => $this->processpath,
                      'webrec_entity' => $this->websitemodelname,
                      'webrec_key' => $this->model->object_name.'_id'));
  }

  public function edit($id = null) {
    $filter_type = $this->input->get('filter_type',null);
    $filter_website = $this->input->get('website_id',null);
    $filter_survey = $this->input->get('survey_id',null);
    if ($id == null)
        {
         $this->setError('Invocation error: missing argument', 'You cannot call edit '.$this->model->object_name.' without an ID');
        }
        else if (!$this->record_authorised($id))
    {
      $this->access_denied('record with ID='.$id);
    }
        else
    {
      $this->model = ORM::factory($this->model->object_name, $id);
      $count = ORM::factory($this->websitemodelname)->where($this->model->object_name.'_id',$id)->find_all()->count();
      if ($count <= 1)
        $this->setView('custom_attribute/custom_attribute_edit',
                $this->pagetitle,
                array('filter_type'=>$filter_type,
                        'website_id'=>$filter_website,
                        'survey_id'=>$filter_survey,
                        'enabled'=>'',
                    'disabled_input'=>'NO',
                    'attribute_load' => '',
                        'name' => $this->name,
                        'processpath' => $this->processpath,
                        'webrec_entity' => $this->websitemodelname,
                        'webrec_key' => $this->model->object_name.'_id'));
      else
        $this->setView('custom_attribute/custom_attribute_edit',
                $this->pagetitle,
                array('filter_type'=>$filter_type,
                        'website_id'=>$filter_website,
                        'survey_id'=>$filter_survey,
                        'enabled'=>'disabled="disabled"',
                    'disabled_input'=>'YES',
                    'attribute_load' => '',
                        'name' => $this->name,
                        'processpath' => $this->processpath,
                        'webrec_entity' => $this->websitemodelname,
                        'webrec_key' => $this->model->object_name.'_id'));
      $this->model->populate_validation_rules();
    }
  }

  public function process() {
    $filter_type = $this->input->post('filter_type', null);
    $website = $this->input->post('website_id', null);
    $survey = $this->input->post('survey_id', null);
    if ($_POST['submit']=='Save' )
      parent::save();
    else if ($_POST['submit']=='Reuse' ) {
      // _POST[load_attr_id] points to id of *_attributes record.
      if (is_numeric($_POST['load_attr_id'])){
        $this->model = ORM::factory($this->model->object_name, $_POST['load_attr_id']);
             $this->setView('custom_attribute/custom_attribute_edit',
                  $this->model->object_name,
                  array('filter_type'=>$filter_type,
                      'website_id'=>$website,
                      'survey_id'=>$survey,
                      'enabled'=>'disabled="disabled"',
                      'disabled_input'=>'YES',
                      'attribute_load' => '',
                      'name' => $this->name,
                      'processpath' => $this->processpath,
                      'webrec_entity' => $this->websitemodelname,
                      'webrec_key' => $this->model->object_name.'_id'));
        $this->model->populate_validation_rules();
      } else {
            $attribute_load = new View('templates/attribute_load',
                  array('website_id'=>$website,
                      'model' => $this->model,
                      'error_message' => 'The attribute must be selected before the Reuse button is pressed'));
            $this->setView('custom_attribute/custom_attribute_edit',
                  $this->model->object_name,
                  array('filter_type'=>$filter_type,
                      'website_id'=>$website,
                      'survey_id'=>$survey,
                      'enabled'=>'',
                      'disabled_input'=>'NO',
                      'attribute_load' => $attribute_load,
                      'name' => $this->name,
                      'processpath' => $this->processpath,
                      'webrec_entity' => $this->websitemodelname,
                      'webrec_key' => $this->model->object_name.'_id'));
      }
    } else
         $this->setError('Invocation error: Invalid Submit', 'Value of Posted submit variable is invalid');
  }

  protected function page_authorised()
  {
    return $this->auth->logged_in();
  }

  public function page($page_no, $limit) {
    if ($this->page_authorised() == false) {
      $this->access_denied();
      return;
    }
    $grid =	Attr_Gridview_Controller::factory($this->gridmodel,
      $page_no,
      $limit,
      $this->pageNoUriSegment,
      $this->createpath,
      $this->createbutton);
    $grid->base_filter = $this->base_filter;
    $grid->auth_filter = $this->auth_filter;
    $grid->columns = array_intersect_key($grid->columns, $this->columns);
    $grid->actionColumns = $this->actionColumns;

    // Add table to view
    $this->view->table = $grid->display();

    // Templating
    $this->template->title = $this->GetEditPageTitle($this->gridmodel, $this->pagetitle);
    $this->template->content = $this->view;
  }

  public function page_gv($page_no, $limit) {
    $this->auto_render = false;
    $grid =	Attr_Gridview_Controller::factory($this->gridmodel,
      $page_no,
      $limit,
      $this->pageNoUriSegment,
      $this->createpath,
      $this->createbutton);
    $grid->base_filter = $this->base_filter;
    $grid->auth_filter = $this->auth_filter;
    $grid->columns = array_intersect_key($grid->columns, $this->columns);
    $grid->actionColumns = $this->actionColumns;
    return $grid->display();
  }

  protected function submit($submission){
    // If the disabled_input field is set to YES, the data is being reused and no changes can have been made to the main record.
    // In this case submit only the *_websites records.
        $this->model->submission = $submission;
    if ($submission['fields']['disabled_input']['value'] == 'YES') {
      $id = $submission['fields']['id']['value'];
    } else {
      $id = $this->model->submit();
    }
      if ($id != null) {
            // Record has saved correctly or is being reused
      if(!is_null($this->gen_auth_filter))
        $websites = ORM::factory('website')->in('id', $this->gen_auth_filter['values'])->find_all();
      else
        $websites = ORM::factory('website')->find_all();
         foreach ($websites as $website) {
        // First check for non survey specific checkbox
        $this->set_attribute_website_record($id, $website->id, null, isset($submission['fields']['website_'.$website->id]));
        $surveys = ORM::factory('survey')->where('website_id', $website->id)->find_all();
           foreach ($surveys as $survey) {
          $this->set_attribute_website_record($id, $website->id, $survey->id, isset($submission['fields']['website_'.$website->id.'_'.$survey->id]));
        }
      }
           $this->submit_succ($id);
        } else {
            // Record has errors - now embedded in model
            $this->submit_fail();
        }
    }

  private function set_attribute_website_record($attr_id, $website_id, $survey_id, $checked)
  {
       $attributes_website = ORM::factory($this->websitemodelname,
            array($this->model->object_name.'_id' => $attr_id
                , 'website_id' => $website_id
                , 'restrict_to_survey_id' => $survey_id));
    if($attributes_website->loaded) {
      // existing record
      if($checked == true and $attributes_website->deleted == 't') {
        $attributes_website->__set('deleted', 'f');
        $attributes_website->save();
      } else if ($checked == false and $attributes_website->deleted == 'f')  {
        $attributes_website->__set('deleted', 't');
        $attributes_website->save();
      }
    } else if ($checked == true) {
           $save_array = array(
                'id' => $attributes_website->object_name
                ,'fields' => array($this->model->object_name.'_id' => array('value' => $attr_id)
                          ,'website_id' => array('value' => $website_id)
                           ,'restrict_to_survey_id' => array('value' => $survey_id)
                          ,'deleted' => array('value' => 'f'))
                ,'fkFields' => array()
                ,'superModels' => array());
      $attributes_website->submission = $save_array;
      $attributes_website->submit();
    }
  }

  /**
     * Returns to the index view for this controller.
     */
    protected function submit_succ($id) {
        Kohana::log("info", "Submitted record ".$id." successfully.");
        url::redirect($this->model->object_name.'?filter_type='.$_POST['filter_type'].'&website_id='.$_POST['website_id'].'&survey_id='.$_POST['survey_id']);
    }

    /**
     * Returns to the edit page to correct errors - now embedded in the model
     */
    protected function submit_fail() {
        $mn = $this->model->object_name;
        $this->setView("custom_attribute/custom_attribute_edit",
                ucfirst($mn),
                array('filter_type'=>$_POST['filter_type'],
                    'website_id'=>$_POST['website_id'],
                    'survey_id'=>$_POST['survey_id'],
                    'enabled'=>'',
                    'disabled_input'=>'NO',
                    'attribute_load' => '',
                    'name' => $this->name,
                    'processpath' => $this->processpath));
    $this->model->populate_validation_rules();
    }

    protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      $attribute_website = ORM::factory($this->websitemodelname, $id);
      return (in_array($attribute_website->website_id, $this->auth_filter['values']));
    }
    return true;
  }
}
