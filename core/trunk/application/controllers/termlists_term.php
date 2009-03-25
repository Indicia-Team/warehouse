<?php
/**
 * INDICIA
 * @link http://code.google.com/p/indicia/
 * @package Indicia
 */

/**
 * Taxa_termlist page controller
 *
 *
 * @package Indicia
 * @subpackage Controller
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author xxxxxxx <xxx@xxx.net> / $Author$
 * @copyright xxxx
 * @version $Rev$ / $LastChangedDate$
 */

class Termlists_term_Controller extends Gridview_Base_Controller {
	public function __construct() {
		parent::__construct(
			'termlists_term',
			'gv_termlists_term',
		       	'termlists_term/index');
		$this->base_filter['parent_id']=null;
		$this->base_filter['preferred']='t';
		$this->columns = array(
			'term'=>'',
			'language'=>'',
			);
		$this->pagetitle = "Terms";
		$this->pageNoUriSegment = 4;
		$this->model = ORM::factory('termlists_term');
	}

	private function getSynonomy($meaning_id) {
		return ORM::factory('termlists_term')
			->where(array(
				'preferred' => 'f',
				'deleted' => 'f',
				'meaning_id' => $meaning_id
			))->find_all();
	}

	private function formatCommonSynonomy(ORM_Iterator $res){
		$syn = "";
		foreach ($res as $synonym) {
			if ($synonym->term->language->iso != "lat"){
				$syn .= $synonym->term->term;
				$syn .=	($synonym->term->language_id != null) ?
					",".$synonym->term->language->iso."\n" :
					'';
			}
		}
		return $syn;
	}
	/**
	 * Override the default page functionality to filter by termlist.
	 */
	public function page($termlist_id, $page_no, $limit){
		// At this point, $termlist_id has a value - the framework will trap the other case.
		// No further filtering of the gridview required as the very fact you can access the parent term list
		// means you can access all the terms for it.
		if (!$this->termlist_authorised($termlist_id))
		{
			$this->access_denied('table to view records with a termlist ID='.$termlist_id);
			return;
        }
		$this->base_filter['termlist_id'] = $termlist_id;
		$this->pagetitle = "Terms in ".ORM::factory('termlist',$termlist_id)->title;
		$this->view->termlist_id = $termlist_id;
		parent::page($page_no, $limit);
	}

	public function page_gv($termlist_id, $page_no, $limit){
		$this->base_filter['termlist_id'] = $termlist_id;
		$this->view->termlist_id = $termlist_id;
		parent::page_gv($page_no, $limit);
	}

	public function edit($id,$page_no,$limit) {
		// At this point, $id is provided - the framework will trap the empty or null case.
		if (!$this->record_authorised($id))
		{
			$this->access_denied('record with ID='.$id);
			return;
        }
		// Generate model
		$this->model->find($id);
		$gridmodel = ORM::factory('gv_termlists_term');

		// Add grid component
		$grid =	Gridview_Controller::factory($gridmodel,
				$page_no,
				$limit,
				4);
		$grid->base_filter = $this->base_filter;
		$grid->base_filter['parent_id'] = $id;
		$grid->columns = $this->columns;
		$grid->actionColumns = array(
			'edit' => 'termlists_term/edit/£id£'
		);

		// Add items to view
		$vArgs = array(
			'termlist_id' => $this->model->termlist_id,
			'table' => $grid->display(),
			'synonomy' => $this->formatCommonSynonomy($this->
					getSynonomy($this->model->meaning_id)),
			);
		$this->setView('termlists_term/termlists_term_edit', 'Term', $vArgs);

	}
	// Auxilliary function for handling Ajax requests from the edit method gridview component
	public function edit_gv($id,$page_no,$limit) {
		$this->auto_render=false;

		$gridmodel = ORM::factory('gv_term_termlist');

		$grid =	Gridview_Controller::factory($gridmodel,
				$page_no,
				$limit,
				4);
		$grid->base_filter = $this->base_filter;
		$grid->base_filter['parent_id'] = $id;
		$grid->columns =  $this->columns;
		$grid->actionColumns = array(
			'edit' => 'termlists_term/edit/£id£'
		);
		return $grid->display();
	}
	/**
	 * Creates a new term given the id of the termlist to initially attach it to
	 */
	public function create($termlist_id){
		// At this point, $termlist_id has a value - the framework will trap the other case.
		if (!$this->termlist_authorised($termlist_id))
		{
			$this->access_denied('table to create records with a taxon list ID='.$termlist_id);
			return;
        }
		$parent = $this->input->post('parent_id', null);
		$this->model->parent_id = $parent;

		$vArgs = array(
			'table' => null,
			'termlist_id' => $termlist_id,
			'synonomy' => null);

		$this->setView('termlists_term/termlists_term_edit', 'Term', $vArgs);

	}

	public function save(){
		$_POST['preferred'] = 't';
		parent::save();
	}

	protected function wrap($array) {

		$sa = array(
			'id' => 'termlists_term',
			'fields' => array(),
			'fkFields' => array(),
			'superModels' => array(),
			'metaFields' => array()
		);

		// Declare which fields we consider as native to this model
		$nativeFields = array_intersect_key($array, $this->model->table_columns);

		// Use the parent method to wrap these
		$sa = parent::wrap($nativeFields);

		// Declare child models
		if (array_key_exists('meaning_id', $array) == false ||
			$array['meaning_id'] == '') {
				$sa['superModels'][] = array(
					'fkId' => 'meaning_id',
					'model' => parent::wrap(
						array_intersect_key($array, ORM::factory('meaning')
						->table_columns), false, 'meaning'));
			}

		$termFields = array_intersect_key($array, ORM::factory('term')
			->table_columns);
		if (array_key_exists('term_id', $array) && $array['term_id'] != ''){
			$termFields['id'] = $array['term_id'];
		}
		$sa['superModels'][] = array(
			'fkId' => 'term_id',
			'model' => parent::wrap($termFields, false, 'term'));

		$sa['metaFields']['synonomy'] = array(
			'value' => $array['synonomy']
		);

		return $sa;
	}

	/**
	 * Overrides the fail functionality to add args to the view.
	 */
	protected function submit_fail(){
		$mn = $this->model->object_name;
		$vArgs = array(
			'termlist_id' => $this->model->termlist_id,
			'synonomy' => null,
		);
		$this->setView($mn."/".$mn."_edit", ucfirst($mn), $vArgs);
	}

	/**
	 * Overrides the success function to add in synonomies
	 */
	protected function submit_succ($id){

		// Now do the same thing for synonomy
		$arrLine = split("\n", trim($this
			->model->submission['metaFields']['synonomy']['value']));
		$arrSyn = array();

		foreach ($arrLine as $line) {
			if (trim($line) == '') break;
			$b = preg_split("/(?<!\\\\ ),/",$line);
			if (count($b) >= 2) {
				$arrSyn[$b[0]] = array('lang' => trim($b[1]));
			} else {
				$arrSyn[$b[0]] = array('lang' => 'eng');
			}
		}
		Kohana::log("info", "Number of synonyms is: ".count($arrSyn));

		Kohana::log("info", "Looking for existing terms with meaning ".$this->model->meaning_id);
		$existingSyn = $this->getSynonomy($this->model->meaning_id);

		// Iterate through existing synonomies, discarding those that have
		// been deleted and removing existing ones from the list to add

		foreach ($existingSyn as $syn){
			// Is the term from the db in the list of synonyms?
			if (array_key_exists($syn->term->term, $arrSyn) &&
				$arrSyn[$syn->term->term]['lang'] ==
				$syn->term->language->iso )
			{
				$arrSyn = array_diff_key($arrSyn, array($syn->term->term => ''));
				Kohana::log("info", "Known synonym: ".$syn->term->term);
			} else {
				// Synonym has been deleted - remove it from the db
				$syn->deleted = 't';
				Kohana::log("info", "New synonym: ".$syn->term->term);
				$syn->save();
			}
		}

		// $arraySyn should now be left only with those synonyms
		// we wish to add to the database

		Kohana::log("info", "Synonyms remaining to add: ".count($arrSyn));
		$sm = ORM::factory('termlists_term');
		foreach ($arrSyn as $term => $syn) {

			$sm->clear();

			$lang = $syn['lang'];

			// Wrap a new submission
			Kohana::log("info", "Wrapping submission for synonym ".$term);

			$syn = $_POST;
			$syn['term_id'] = null;
			$syn['term'] = $term;
			$syn['language_id'] = ORM::factory('language')->where(array(
				'iso' => $lang))->find()->id;
			$syn['id'] = '';
			$syn['preferred'] = 'f';
			$syn['meaning_id'] = $this->model->meaning_id;

			$sub = $this->wrap($syn);

			$sm->submission = $sub;
			$sm->submit();
		}

		url::redirect('termlists_term/'.$this->model->termlist_id);
	}

	protected function record_authorised ($id)
	{
		// note this function is not accessed when creating a record
		// for this controller, any null ID termlist_term can not be accessed
		if (is_null($id)) return false;
		$term = new Termlists_term_Model($id);
		// for this controller, any termlist_term that does not exist can not be accessed.
		// ie prevent sly creation using the edit function
		if (!$term->loaded) return false;
		return ($this->termlist_authorised($term->termlist_id));
	}

	protected function termlist_authorised ($id)
	{
		// for this controller, any null ID termlist can not be accessed
		if (is_null($id)) return false;
		if (!is_null($this->gen_auth_filter))
		{
			$termlist = new Termlist_Model($id);
			// for this controller, any termlist that does not exist can not be accessed.
			if (!$termlist->loaded) return false;
			return (in_array($termlist->website_id, $this->gen_auth_filter['values']));
		}
		return true;
	}
}
?>
