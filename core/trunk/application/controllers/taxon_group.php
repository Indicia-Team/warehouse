<?php

class Taxon_Group_Controller extends Gridview_Base_Controller {
	public function __construct() {
		parent::__construct('taxon_group', 'taxon_group', 'taxon_group/index');
		$this->columns = array(
			'title'=>'');
		$this->pagetitle = "Taxon Groups";
		$this->session = Session::instance();
		$this->model = ORM::factory('taxon_group');
	}

	/**
	 * Action for taxon_group/create page/
	 * Displays a page allowing entry of a new taxon group.
	 */
	public function create() {
		if (!$this->page_authorised())
		{
			$this->access_denied();
		}
		else
		{
    		$this->setView('taxon_group/taxon_group_edit', 'Taxon Group');
		}
	}

	public function edit($id = null) {
		if ($id == null)
        {
	   		$this->setError('Invocation error: missing argument', 'You cannot call edit a taxon group without an ID');
        }
        else
        {
            $this->model = new Taxon_Group_Model($id);
            $this->setView('taxon_group/taxon_group_edit', 'Taxon Group');
        }
	}

}

?>
