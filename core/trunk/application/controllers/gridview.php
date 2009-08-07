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

 defined('SYSPATH') or die('No direct script access.');

/**
 * Generates a gridview control.
 *
 * @package Core
 * @subpackage Controllers
 */
class Gridview_Controller extends Controller {
  public static function factory($model,$page,$limit,$uri_segment){
    $gridview = new Gridview_Controller();
    $gridview->model = $model;
    $gridview->columns = array_combine(array_keys($model->table_columns), array_pad(array(), count($model->table_columns), null));
    $gridview->page = $page;
    $gridview->limit = $limit;
    $gridview->uri_segment = $uri_segment;
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

    $gridview = new View('gridview');
    $gridview_body = new View('gridview_body');

    # 2 things we could be up to here - filtering or table sort.
    // Get all the parameters
    $filtercol = $this->input->get('columns',null);
    $filters = $this->input->get('filters',null);
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

    // If we are logged on as a site controller, then need to restrict access to those
    // records on websites we are site controller for.
    // Core Admins get access to everything - no filter applied.
    if ($this->auth_filter != null){
      $filter = $this->auth_filter;
      $lists = $lists->in($filter['field'], $filter['values']);
    }
    // Are we doing server-side filtering?
    if ($this->base_filter != null){
      $filter = $this->base_filter;
      $lists = $lists->where($filter);
    }
    // Are we doing client-side filtering?
    if ($filtercol!=null){
      $arrcols = explode(',',$filtercol);
      $arrfilters = explode(',',$filters);
      if (count($arrcols)==count($arrfilters)){
        $client_filter = array_combine($arrcols,$arrfilters);
        $lists = $lists->like($client_filter);
      }
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
    $gridview_body->columns = $this->columns;
    $gridview_body->actionColumns = $this->actionColumns;

    if(request::is_ajax() && !$forceFullTable){
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
