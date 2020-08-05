<?php

class Diagnostics_Controller extends Indicia_Controller {

  public function index() {
    $reportEngine = new ReportEngine();
    $this->template->title = 'Warehouse diagnostics';
    $this->template->content = new View('diagnostics/index');
  }

}