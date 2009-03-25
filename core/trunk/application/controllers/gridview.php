<?php
/**
 * Generates a gridview control.
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
	function display() {
		/**
		 * Renders the grid with whatever parameters are supplied
		 */
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

		$pagination = Pagination::factory(array(
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
