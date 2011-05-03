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
 * @author	Indicia Team
 * @link http://code.google.com/p/indicia/
 * @license http://www.gnu.org/licenses/gpl.html GPL
 */

/**
 * Controller for the trigger page.
 *
 * @package Core
 * @subpackage Controllers
 */
class Trigger_Controller extends Gridview_Base_Controller {

  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct('trigger', 'trigger/index');
    $this->columns = array(
      'name'=>'',
      'description'=>'',
      'created_by_name'=>'Owner'
    );
    $this->pagetitle = "Triggers";
  }
  
  public function index() {
    $this->base_filter['private_for_user_id'] = array(null, $_SESSION['auth_user']->id);
    $this->base_filter['user_id'] = array(null, $_SESSION['auth_user']->id);
    parent::index();
  }
  
  /**
   * Override to specify action columns for subscription.
   */
  protected function get_action_columns() {
    $r = array();
    if ($this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin'))
      $r[] = array(
          'caption' => 'edit trigger',
          'url'=>$this->controllerpath.'/edit/{id}',
          'visibility_field' => 'edit_trigger'
      );
    $r[] = array(
        'caption' => 'subscribe',
        'url'=>'trigger_action/create/{id}',
        'visibility_field' => 'subscribe'
    );
    $r[] = array(
        'caption' => 'edit subscription',
        'url'=>'trigger_action/edit/{id}',
        'visibility_field' => 'edit_subscription'
    );
    return $r;
  }
  
  /** 
  * Provide the list of trigger templates to the edit view
  */
  protected function prepareOtherViewData($values) {
    $files = Array();    
    $templateDir = Kohana::config('indicia.localReportDir').'/trigger_templates/';
    $dh = opendir($templateDir);
    while ($file = readdir($dh))  {
      if ($file != '..' && $file != '.' && is_file($templateDir.$file))
      { 
        $file = str_replace('.xml', '', $file);
        $files["trigger_templates/$file"] = $file;
      }
    }
    return array('triggerFileList' => $files);
  }
  
  public function record_authorised($id)
  {
    return $this->auth->logged_in('CoreAdmin');
  }
  
  /**
   * Controller action to display the parameters editing page for the report associated with this
   * trigger. Displayed after clicking Next on the main edit page.
   */
  public function edit_params($id=null) {
    $this->model = ORM::Factory($this->model->object_name, $id);    
    if ($id) 
      // existing record, so we can get the params json data to convert it to individual params
      $params = json_decode($this->model->params_json, true);    
    else
      $params = array(); 
    $this->setView('trigger/params_edit', 'Parameters for '.$this->model->caption(), array(
      'values'=>$_POST,
      'other_data' => array('defaults' => $params)
    )); 
    $this->defineEditBreadcrumbs();
  }
  
  /**
   * Override the save method so we can convert the report parameters form into a JSON
   * value to store in the triggers.json_params field.
   */
  public function save() {
    // build the parameters JSON value from the params form
    $params = array();
    foreach($_POST as $key=>$value) {
      if (substr($key, 0, 6)=='param-') {
        // The last part of the key is the field name
        $tokens = explode('-', $key);
        $param = array_pop($tokens);
        $params[$param] = $value;
      }
    }
    $_POST['params_json'] = json_encode($params);   
    parent::save();
  }
  
}
?>
