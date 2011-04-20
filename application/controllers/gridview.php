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
 * @package  Core
 * @subpackage Controllers
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Generates a gridview control.
 *
 * @package Core
 * @subpackage Controllers
 */
class Gridview_Controller extends Controller {

  private $gridId = null;
  
  /** 
   * Factory method used for instantiation to ensure it is set up correctly.
   */
  public static function factory($model, $page, $uri_segment, $gridId=null){
    $gridview = new Gridview_Controller();
    $gridview->model = $model;
    $gridview->columns = array_combine(array_keys($model->table_columns), array_pad(array(), count($model->table_columns), null));
    $gridview->page = $page;    
    $gridview->uri_segment = $uri_segment;
    $gridview->gridId = $gridId;
    $gridview->base_filter = null;
    $gridview->auth_filter = null;
    $gridview->actionColumns = array();
    return $gridview;
  }

  /**
   * Renders the grid with whatever parameters are supplied.
   *
   * @param boolean $forceFullTable Set to true to force the entire grid to be output,
   * even if in an AJAX request. This allows an AJAX request to embed the grid into a tab,
   * for example.
   */
  function display($forceFullTable=false) {
    # 2 things we could be up to here - filtering or table sort.
    // Get all the parameters
    $filtercol = $this->input->get('columns',null);
    $filters = $this->input->get('filters',null);
    if (isset($this->fixedSort)) {
      $orderby = $this->fixedSort;
      $direction = $this->fixedSortDir;
    } else {
      $orderby = $this->input->get('orderby','id');
      $direction = $this->input->get('direction','asc');
    }
    $arrorder = explode(',',$orderby);
    $arrdirect = explode(',',$direction);
    if (count($arrorder)==count($arrdirect)){
      $orderclause = array_combine($arrorder,$arrdirect);
    } else {
      $orderclause = array('id' => 'asc');
    }
    $lists = $this->model->orderby($orderclause);
    
    // If we are logged on as a site controller, then need to restrict access to those
    // records on websites we are site controller for.
    // Core Admins get access to everything - no filter applied.
    if ($this->auth_filter != null){
      $filter = $this->auth_filter;
      $lists = $lists->in($filter['field'], $filter['values']);
    }
    // Are we doing server-side filtering?
    if ($this->base_filter != null){      
      foreach($this->base_filter as $field=>$values) {
        if (is_array($values))
          $lists = $lists->in($field, $values);
        else
          $lists = $lists->where($field,$values);
      }
    }
    // Are we doing client-side filtering?
    if ($filtercol!=null){
      $arrcols = explode(',',$filtercol);
      $arrfilters = explode(',',$filters);
      if (count($arrcols)==count($arrfilters)){
        $client_filter = array_combine($arrcols,$arrfilters);
        // For id filters, use a WHERE filter for exact match
        if (isset($client_filter['id'])) {
          // only do an id filter if the provided text is numeric
          if (preg_match('/^[0-9]+$/', $client_filter['id']))
            $lists = $lists->where(array('id' => $client_filter['id']));
          else
            // a dummy filter to force return no records, since the user searched for text in a number.
            $lists = $lists->where(array('id' => 0));
          unset($client_filter['id']);
        }
        // Other filters are a LIKE filter.
        if (count($client_filter)>0)
          $lists = $lists->like($client_filter);
        
      }
    }    
    $limit = kohana::config('pagination.default.items_per_page');
    $offset = ($this->page -1) * $limit;
    $table = $lists->find_all($limit, $offset);

    $pagination = new Pagination(array(
      'style' => 'extended',
      'uri_segment' => $this->uri_segment,
      'total_items' => $lists->count_last_query(),
      'auto_hide' => true
    ));
    if ($this->input->get('type',null) == 'pager' && request::is_ajax()) {
      // request for just the pagination below the grid
      $this->auto_render=false;
      echo $pagination; // This DOES need to be echoed
    } else {    
      // Request for the grid. This could be an AJAX request for just the table body, or a 
      // normal request for the entire grid inc pagination.      
      $gridview_body = new View('gridview_body');    
      $gridview_body->table = $table;    
      $gridview_body->columns = $this->columns;
      $gridview_body->actionColumns = $this->actionColumns;
      if(request::is_ajax() && !$forceFullTable) {
        // request for just the grid body
        $this->auto_render=false;
        return $gridview_body->render(true);       
      } else {
        $gridview = new View('gridview');
        // We are outputting the whole grid, pagination and all
        $gridview->body = $gridview_body;
        // create a unique id for our grid unless one is forced from outside
        $id = $this->gridId ? $this->gridId : md5(time().rand());
        $gridview->id = $id;
        $gridview->pagination = $pagination;
        $gridview->columns = $this->columns;
        $gridview->actionColumns = $this->actionColumns;
        $gridview->sortable = !isset($this->fixedSort);
        return $gridview->render();
      }
    }
  }
}
?>
