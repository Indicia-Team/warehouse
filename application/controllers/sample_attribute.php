<?php

class Sample_attribute_Controller extends Attr_Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('sample_attribute',		// modelname
              'Sample',				// name
              'sample_attributes_website',	// website table modelname
              'gv_sample_attribute',	// gridmodelname
              'custom_attribute/index',	// viewname
              NULL);						// controllerpath
    $this->columns = array(
      'website'=>'',
      'survey'=>'',
      'caption'=>'',
      'data_type'=>'');
    $this->pagetitle = "Sample Attribute";
    $this->model = ORM::factory('sample_attribute');
    $this->auth_filter = $this->gen_auth_filter;
  }

}
?>
