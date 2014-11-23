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
 * Controller providing CRUD access to the media for a survey media file.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Survey_medium_Controller extends Gridview_Base_Controller
{
	public function __construct()
  {
    parent::__construct('survey_medium');
    $this->columns = array(
      'id'=>'ID',
      'caption'=>'',
      'path'=>'Media',
      'media_type'=>'Type'
    );
    $this->pagetitle = "Media files";
  }

 /**
  * Override the default index functionality to filter by survey_id.
  */
  public function index()
  { 
    if ($this->uri->total_arguments()>0) {
      $this->base_filter=array('survey_id' => $this->uri->argument(1));
    }
    parent::index();
    // pass the survey id into the view, so the create button can use it to autoset
    // the survey of the new image.
    if ($this->uri->total_arguments()>0) {
      $this->view->survey_id=$this->uri->argument(1);
    }
  }
  
  /**
   *  Setup the default values to use when loading this controller to edit a new page.   
   */
  protected function getDefaults() {    
    $r = parent::getDefaults();    
    if ($this->uri->method(false)=='create') {
      // survey id is passed as first argument in URL when creating. But the image
      // gets linked by meaning, so fetch the meaning_id.
      $r['survey:id'] = $this->uri->argument(1);
      $r['survey_medium:survey_id'] = $this->uri->argument(1);
      $r['survey_medium:caption'] = kohana::lang('misc.new_image');
    }
    return $r;
  }
  
  /**
   * Override the default return page behaviour so that after saving an image you
   * are returned to the occurence entry which has the image.
   */
  protected function get_return_page() {
    if (array_key_exists('survey_medium:survey_id', $_POST)) {
      return "survey/edit/".$_POST['survey_medium:survey_id']."?tab=Media_Files";
    } else {
      return $this->model->object_name;
    }
  }
  
  /**
   * Get the list of terms ready for the media types list. 
   */
  protected function prepareOtherViewData($values)
  {    
    return array(
      'media_type_terms' => $this->get_termlist_terms('indicia:media_types')    
    );   
  }

  /**
   * Define non-standard behaviuor for the breadcrumbs, since this is accessed via a survey
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('survey', 'Surveys');
    if ($this->model->id) {
      // editing an existing item
      $surveyId = $this->model->survey_id;
    } else {
      // creating a new one so our argument is the survey id
      $surveyId = $this->uri->argument(1);
    }
    $survey = ORM::factory('survey', $surveyId);
    $this->page_breadcrumbs[] = html::anchor('survey/edit/'.$surveyId, $survey->caption());
    $this->page_breadcrumbs[] = $this->model->caption();
  }

}