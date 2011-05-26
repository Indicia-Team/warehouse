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
 * Controller providing CRUD access to the samples list.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Sample_Controller extends Gridview_Base_Controller
{
  public function __construct()
  {
    parent::__construct('sample', 'gv_sample', 'sample/index');
    $this->pagetitle = 'Samples';
    $this->columns = array
    (
      'website' => 'Website',
      'survey' => 'Survey',
      'entered_sref' => 'Spatial Ref.',
      'location' => 'Location',
      'date_start' => 'Date'
    );
    $this->auth_filter = $this->gen_auth_filter;
  }

  protected function getModelValues() {
    $r = parent::getModelValues();
    $this->loadAttributes($r);
    $r['website_id']=ORM::factory('survey', $r['sample:survey_id'])->website_id;
    return $r;      
  }
  
  /**
   * Load default values either when creating a sample new or reloading after a validation failure.
   * This adds the custome attributes list to the data available for the view. 
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    $this->loadAttributes($r);
    if (array_key_exists('sample:survey_id', $_POST)) {
      $r['sample:survey_id'] = $_POST['sample:survey_id'];
      $r['website_id']=ORM::factory('survey', $r['sample:survey_id'])->website_id;
    }
    return $r;
  }

  /**
   * Get the list of terms ready for the sample methods list. 
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'method_terms' => $this->get_termlist_terms('indicia:sample_methods')    
    );   
  }

  /**
   * Return a list of the tabs to display for this controller's actions.
   */
  protected function getTabs($name) {
    return array(
      array(
        'controller' => 'occurrence',
        'title' => 'Occurrences',
        'views'=>'sample',
        'actions'=>array('edit')
      ), array(
        'controller' => 'sample_comment',
        'title' => 'Comments',
        'views'=>'sample',
        'actions'=>array('edit')
      ), array(
        'controller' => 'sample_image',
        'title' => 'Images',
        'views'=>'sample',
        'actions'=>array('edit')
      )
    );
  }

  /**
   * Check access to a sample when editing. The sample's website must be in the list
   * of websites the user is authorised to administer.
   */
  protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      $sample = ORM::factory('sample', $id);
      return (in_array($sample->survey->website_id, $this->auth_filter['values']));
    }
    return true;
  }
}
