<?php

class Taxon_designation_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('taxon_designation', 'gv_taxon_designation', 'taxon_designation/index');
  }
  
  public function index() {
    echo "here";
  }

}

?>