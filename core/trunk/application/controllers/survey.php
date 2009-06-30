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
    parent::__construct('survey', 'gv_survey', 'survey/index');
    $this->columns = array(
      'title'=>'',
      'description'=>'',
      'website'=>'');
    $this->pagetitle = "Surveys";
    $this->model = ORM::factory('survey');
    $this->auth_filter = $this->gen_auth_filter;
  }

  /**
   * Action for survey/create page.
   * Displays a page allowing entry of a new survey.
   */
  public function create() {
    $this->setView('survey/survey_edit', 'Survey');
  }

  public function edit($id = null) {
    if ($id == null)
        {
         $this->setError('Invocation error: missing argument', 'You cannot call edit survey without an ID');
        }
        else if (!$this->record_authorised($id))
    {
      $this->access_denied('record with ID='.$id);
    }
        else
    {
      $this->model = new Survey_Model($id);
            $this->setView('survey/survey_edit', 'Survey');
    }
  }

    protected function record_authorised ($id)
  {
    if (!is_null($id) AND !is_null($this->auth_filter))
    {
      $survey = new Survey_Model($id);
      return (in_array($survey->website_id, $this->auth_filter['values']));
    }
    return true;
  }
}

?>
