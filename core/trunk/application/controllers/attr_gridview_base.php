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
    $this->createbutton="New $name Attribute";
    $this->gridmodel = ORM::factory($this->gridmodelname);
    $this->pageNoUriSegment = 3;
    $this->base_filter = array('deleted'=>'f');
    $this->auth_filter = null;
    $this->gen_auth_filter = null;
    $this->columns = $this->gridmodel->table_columns;

    $this->pagetitle = "Abstract Attribute gridview class - override this title!";
    $this->view = new View($this->viewname);
    parent::__construct();

    $filter_type = $this->input->get('filter_type',null);
    $website_id = $this->input->get('website_id',null);
    $survey_id = $this->input->get('survey_id',null);

    $this->actionColumns = array(
      'edit' => $this->controllerpath."/edit/£id£?filter_type=$filter_type&amp;website_id=$website_id&survey_id=$survey_id"
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
   *  Setup the default values to use when loading this controller to edit a new page.
   *  Create function is called with the website_id and optional survey id. These are used to generate the
   * *_attribute_website record after the *_attribute   
   */
  protected function getDefaults() {
    $r = parent::getDefaults();    
    $r['metaFields:disabled_input']='NO';
    $r['attribute_load'] = new View(
    		'templates/attribute_load', 
        array('website_id'=>$this->input->get('website_id', null), 'model' => $this->model)
    );
    $r['enabled'] = '';
    $r['webrec_key'] = $this->model->object_name.'_id';
    return $r;
  }
  
  /**
   * Setup the values to be loaded into the edit view.
   */
  protected function getModelValues() {
    $r = parent::getModelValues();
    // We need to know if this attribute is unique to the website
    $count = ORM::factory($this->websitemodelname)->where($this->model->object_name.'_id',$this->model->id)->find_all()->count();    
    $r['metaFields:disabled_input']=$count<=1 ? 'NO' : 'YES';
    $r['attribute_load'] = '';
    $r['enabled'] = $count<=1 ? '' : 'disabled="disabled"';    
    $r['webrec_key'] = $this->model->object_name.'_id';
    $this->model->populate_validation_rules();
    return $r;  
  }
  
  /**
   * Returns some addition information required by the edit view, which is not associated with 
   * a particular record. 
   */
  protected function prepareOtherViewData()
  {    
    return array(
      'filter_type' => $this->input->get('filter_type', null),
      'website_id' => $this->input->get('website_id', null),
      'survey_id' => $this->input->get('survey_id', null),
      'name' => $this->name,   
      'controllerpath' => $this->controllerpath,
      'webrec_entity' => $this->websitemodelname
    );   
  }
  
  /**
   * Force the base class methods to link the form values to controls named custom_attribute:* which allows
   * a single generic form to be used for several different models.      
   */
  protected function getAttrPrefix() {
    return 'custom_attribute';
  }
  
  /**
   * Code that is run when showing a controller's edit page - either from the create action
   * or the edit action. Override the base class behaviour to share the same edit pages between
   * the different types of attributes.
   * 
   * @param int $id The record id (for editing) or null (for create).
   * @param array $valuse Associative array of valuse to populate into the form.   * 
   * @access private   
   */
  protected function showEditPage($values) {    
    $other = $this->prepareOtherViewData();            
    $mn = 'custom_attribute';      
    $this->setView($mn."/".$mn."_edit", $this->model->caption(), array(
    	'values'=>$values,
      'other_data'=>$other
    )); 
  }
  
  public function save() {       
    if ($_POST['submit']=='Reuse' ) {      
      if (is_numeric($_POST['load_attr_id'])) {
        $this->model = ORM::factory($this->model->object_name, $_POST['load_attr_id']);
        $values = $this->getModelValues();
        $values['attribute_load'] = '';
        $values['enabled']='disabled="disabled"';
        $values['metaFields:disabled_input']='YES';        
      } else {
        $values = $this->getDefaults();
        $values['attribute_load'] = new View('templates/attribute_load', array(
            'website_id'=>$this->input->post('website_id', null),
            'model' => $this->model,
            'error_message' => 'The attribute must be selected before the Reuse button is pressed'
        ));        
      }
      $this->showEditPage($values);     
    } else {
      if ($_POST['metaFields:disabled_input'] == 'NO') {
        // Build the validation_rules field from the set of controls that are associated with it.
        $rules = array();
        foreach(array('required', 'alpha', 'email', 'url', 'alpha_numeric', 'numeric', 'standard_text') as $rule) {          
          if (array_key_exists('valid_'.$rule, $_POST) && $_POST['valid_'.$rule]==1) {            
            array_push($rules, $rule);
          }
        }
        if (array_key_exists('valid_length', $_POST) && $_POST['valid_length']==1)   $rules[] = 'length['.$_POST['valid_length_min'].','.$_POST['valid_length_max'].']';
        if (array_key_exists('valid_decimal', $_POST) && $_POST['valid_decimal']==1) $rules[] = 'decimal['.$_POST['valid_dec_format'].']';
        if (array_key_exists('valid_regex', $_POST) && $_POST['valid_regex']==1)		 $rules[] = 'regex['.$_POST['valid_regex_format'].']';
        if (array_key_exists('valid_min', $_POST) && $_POST['valid_min']==1)		     $rules[] = 'min['.$_POST['valid_min_value'].']';
        if (array_key_exists('valid_max', $_POST) && $_POST['valid_max']==1)		     $rules[] = 'max['.$_POST['valid_max_value'].']';
  
        if (!empty($rules)) {
          $_POST['custom_attribute:validation_rules'] = implode("\r\n", $rules);        
          kohana::log('debug', 'Posted rules '.$_POST['custom_attribute:validation_rules']);
        }
        // Make sure checkboxes have a value
        if (!array_key_exists('public', $_POST)) $_POST['public'] = '0'; 
        if (!array_key_exists('multi_value', $_POST)) $_POST['multi_value'] = '0';
      }       
      parent::save();
    }
  }

  protected function page_authorised()
  {
    return $this->auth->logged_in();
  }

  public function page($page_no) {
    if ($this->page_authorised() == false) {
      $this->access_denied();
      return;
    }
    $grid =	Attr_Gridview_Controller::factory($this->gridmodel,
      $page_no,
      $this->pageNoUriSegment,
      $this->controllerpath."/create",
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

  public function page_gv($page_no) {
    $this->auto_render = false;
    $grid =	Attr_Gridview_Controller::factory($this->gridmodel,
      $page_no,      
      $this->pageNoUriSegment,
      $this->controllerpath."/create",
      $this->createbutton);
    $grid->base_filter = $this->base_filter;
    $grid->auth_filter = $this->auth_filter;
    $grid->columns = array_intersect_key($grid->columns, $this->columns);
    $grid->actionColumns = $this->actionColumns;
    return $grid->display();
  }

  /**
   * Returns to the index view for this controller.
   */
  protected function show_submit_succ($id, $deletion=false) {
    Kohana::log("debug", "Submitted record ".$id." successfully.");
    $action = $deletion ? "deleted" : "saved";
    $this->session->set_flash('flash_info', "The attribute was $action successfully.");
    url::redirect($this->model->object_name.'?filter_type='.$_GET['filter_type'].'&website_id='.$_GET['website_id'].'&survey_id='.$_GET['survey_id']);
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
