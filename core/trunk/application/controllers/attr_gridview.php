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
 * Class which generates a grid listing custom attributes. Generates just the grid control, not
 * the containing page.
 *
 * @package	Core
 * @subpackage Controllers
 */
class Attr_Gridview_Controller extends Controller {

  /**
   * Factory method to instantiate an attribute gridview controller class of the correct type.
   *
   * @param ATTR_ORM @model 1Instance of a model for the correct attribute type.
   * @param integer @page Page number to display in the grid.
   */
  public static function factory($model,$page,$limit,$uri_segment,$createpath,$createbutton){
    $gridview = new Attr_Gridview_Controller();
    $gridview->model = $model;
    $gridview->columns = $model->table_columns;
    $gridview->page = $page;
    $gridview->limit = $limit;
    $gridview->createpath = $createpath;
    $gridview->createbutton = $createbutton;
    $gridview->uri_segment = $uri_segment;
    $gridview->base_filter = null;
    $gridview->auth_filter = null;
    $gridview->actionColumns = array();
    return $gridview;
  }

  function display() {
    /**
     * Renders the grid with whatever parameters are supplied
     */
    $gridview = new View('attr_gridview');
    $gridview_body = new View('gridview_body');

    # 2 things we could be up to here - filtering or table sort.
    // Get all the parameters
    $filter_type = $this->input->get('filter_type',null);
    $filter_website = $this->input->get('website_id',null);
    $filter_survey = $this->input->get('survey_id',null);

    // because of the pants way that database connections are handled (ie one at a time)
    // it is impossible to create a new specific ID model whilst building a where clause on another table.
    // Do it here, now, rather than in the case statement.
    if ($filter_website != null AND is_numeric($filter_website) AND $filter_website >= 0){
      $website= new Website_Model($filter_website);
      if ($filter_survey != null AND is_numeric($filter_survey) AND $filter_survey >= 0 ){
        $survey= new Survey_Model($filter_survey);
      } else {
        $survey = null;
      }
    } else {
      $website = null;
    }

    $orderby = $this->input->get('orderby','id');
    $direction = $this->input->get('direction','asc');

    $arrorder = explode(',',$orderby);
    $arrdirect = explode(',',$direction);
    if (count($arrorder)==count($arrdirect)){
      $orderclause = array_combine($arrorder,$arrdirect);
    } else {
      $orderclause = array('id' => 'asc');
    }
    $lists = $this->model->orderby($orderclause);

    // If we are logged on as a site controller, then need to restrict access to
    // records on websites we are site controller for. However this is actually done by
    // the client filtering: no server side stuff is needed.
    // Core Admins get access to everything - again through a wider client filter selection

    // Are we doing server-side filtering?
    if ($this->base_filter != null){
      $filter = $this->base_filter;
      $lists = $lists->where($filter);
    }
    $gridview->filter_type = $filter_type;
    // Are we doing client-side filtering?
    switch ($filter_type) {
      case 1: // Filter by Website
        if (!is_null($website)) {
          $lists = $lists->where(array('website_id' => $filter_website));
          $gridview->website_id = $filter_website;
          $gridview->filter_summary = 'Filter applied: Website = "'.$website->title.'"';
          if (!is_null($survey)){
            $lists = $lists->where(array('survey_id' => $filter_survey));
            $gridview->survey_id = $filter_survey;
            $gridview->filter_summary = $gridview->filter_summary.' : Survey = "'.$survey->title.'"';
          } else {
            $gridview->filter_summary = $gridview->filter_summary.' : Attributes Common to all surveys on the website';
            $lists = $lists->where(array('survey_id IS' => null));
          }
        } else {
          $lists = $lists->where(array('website_id' => -1));
          $gridview->filter_summary = 'Filter applied: [Invalid Website]';
        }
        break;
      case 3: // Created by me
        $lists = $lists->where(array('website_id IS' => null,
                      'created_by_id' => $_SESSION['auth_user']->id));
        $gridview->filter_summary = "Filter: Created by Me.";
        break;
      case 4: // Distinct Attributes: CORE Admin only
        $lists = $lists->where(array('website_id IS' => null));
        $gridview->filter_summary = "Filter: Distinct Attributes.";
        break;
      default:
      case 2: // Public Attributes
        $lists = $lists->where(array('website_id IS' => null,
                      'public' => 't'));
        $gridview->filter_summary = "Filter: Public Attributes.";
        break;
    }


    $offset = ($this->page -1) * $this->limit;
    $table = $lists->find_all($this->limit, $offset);

    $pagination = new Pagination(array(
      'style' => 'extended',
      'items_per_page' => $this->limit,
      'uri_segment' => $this->uri_segment,
      'total_items' => $lists->count_last_query(),
      'auto_hide' => true
    ));

    $gridview_body->table = $table;
    $gridview->body = $gridview_body;
    $gridview->pagination = $pagination;
    $gridview->columns = $this->columns;
    $gridview->actionColumns = $this->actionColumns;
    $gridview->createpath = $this->createpath;
    $gridview->createbuttonname = $this->createbutton;
    $gridview_body->columns = $this->columns;
    $gridview_body->actionColumns = $this->actionColumns;
    $gridview->filter_summary = '<br /><p>'.$gridview->filter_summary.'</p>';

    if(request::is_ajax()){
      if ($this->input->get('type',null) == 'pager'){
        echo $pagination;
      } else {
        $this->auto_render=false;
        $gridview_body->render(true);
      }

    } else {
      return $gridview->render();
    }
  }
}
?>
