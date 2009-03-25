<?php

class Occurrence_attribute_Controller extends Attr_Gridview_Base_Controller {

	public function __construct() {
		parent::__construct('occurrence_attribute',		// modelname
							'Occurrence',				// name
							'occurrence_attributes_website',	// website table modelname
							'gv_occurrence_attribute',	// gridmodelname
							'custom_attribute/index',	// viewname
							NULL);						// controllerpath
		$this->columns = array(
			'website'=>'',
			'survey'=>'',
			'caption'=>'',
			'data_type'=>'');
		$this->pagetitle = "Custom Occurrence Attribute";
		$this->model = ORM::factory('occurrence_attribute');
		$this->auth_filter = $this->gen_auth_filter;
	}
	 
}
?>
