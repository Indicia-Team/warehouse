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
 * Controller providing CRUD access to the surveys list.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Survey_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('survey');
    $this->columns = array(
      'id'          => '',
      'title'       => '',
      'description' => '',
      'website'     => ''
    );
    $this->pagetitle = "Survey datasets";
    $this->set_website_access('admin');
  }
  
  protected function prepareOtherViewData($values)
  { 
    $websites = ORM::factory('website');
    if (!empty($values['survey:parent_id']))
      // parent list already has a link to a website, so we can't change it 
      $websites = $websites->in('id', ORM::factory('survey', $values['survey:parent_id'])->website_id);
    elseif (!empty($values['survey:website_id']))
      // can't change website for existing survey
      $websites = $websites->where('id', $values['survey:website_id']);
    elseif (!$this->auth->logged_in('CoreAdmin'))
      $websites = $websites->in('id',$this->auth_filter['values']);
    $arr = array();
    foreach ($websites->where('deleted','false')->orderby('title','asc')->find_all() as $website)
      $arr[$website->id] = $website->title;
    return array(
      'websites' => $arr
    );
  }
  
  /**
   * Override the default action columns for a grid - just an edit link - to 
   * add a link to the attributes list for othe survey.
   */
  protected function get_action_columns() {
    return array(
      array(
        'caption'=>'edit',
        'url'=>$this->controllerpath."/edit/{id}"
      ),
      array(
        'caption'=>'setup attributes',
        'url'=>"attribute_by_survey/{id}?type=sample"
      )
    );
  }

  /**
   * Check access to a survey when editing. The survey's website must be in the list
   * of websites the user is authorised to administer.   
   */
  protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      $survey = new Survey_Model($id);
      return (in_array($survey->website_id, $this->auth_filter['values']));
    }
    return true;
  }
  
  /**
   * You can only access the list of surveys if at least an editor of one website.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('editor');
  }
  
  /**
   * Retrieves additional values from the model that are required by the edit form.
   * @return array List of additional values required by the form.
   */
  protected function getModelValues() {
  	$r = parent::getModelValues();
  	if ($this->model->parent_id)
  		$r['parent:title'] = $this->model->parent->title;
  	return $r;
  }
}

?>
