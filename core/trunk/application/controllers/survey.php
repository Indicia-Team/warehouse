<?php

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
