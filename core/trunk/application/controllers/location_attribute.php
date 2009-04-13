<?php

class Location_attribute_Controller extends Attr_Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('location_attribute',		// modelname
              'Location',				// name
              'location_attributes_website',	// website table modelname
              'gv_location_attribute',	// gridmodelname
              'custom_attribute/index',	// viewname
              NULL);						// controllerpath
    $this->columns = array(
      'website'=>'',
      'survey'=>'',
      'caption'=>'',
      'data_type'=>'');
    $this->pagetitle = "Location Attribute";
    $this->model = ORM::factory('location_attribute');
    $this->auth_filter = $this->gen_auth_filter;
  }

}
?>
