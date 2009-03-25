<?php
/**
* INDICIA
* @link http://code.google.com/p/indicia/
* @package Indicia
*/

/**
* Taxa_taxon_list controller
*
*
* @package Indicia
* @subpackage Controller
* @license http://www.gnu.org/licenses/gpl.html GPL
* @author Nicholas Clarke <nicholas.clarke@gmail.com> / $Author$
* @copyright xxxx
* @version $Rev$ / $LastChangedDate$
*/

class Report_viewer_Controller extends Indicia_Controller
{
  
  private $repServ;
  
  public function __construct()
  {
    $this->pageTitle = 'Reports';
    // As we're local, we just call the class report with suppress set to true, which will prevent
    // it from writing to the screen.
    $this->repServ = new ReportEngine();
    parent::__construct();
  }
  /**
  * <p> Index page - basically exists to let one pick a report to display. This can be in a number
  * of ways - firstly it lists the reports installed on the indicia Core. Secondly it should allow
  * the user to specify a remote report by url. Thirdly it should allow the user to provide their 
  * own report. </p>
  */
  public function index()
  {
    // Get the list of reports - at the moment we just grab default level
    $localReports = $this->repServ->listLocalReports(2);
    
    $view = new View('report/index');
    $view->localReports = $localReports;
    
    $this->template->title = "Report Browser";
    $this->template->content = $view;
  }
  
  
  public function resume($uid)
  {
    $srvResponse = $this->repServ->resumeReport($uid, $_POST);
    
    if (array_key_exists('error', $srvResponse))
    {    
      $localReports = $this->repServ->listLocalReports(2);
      
      $view = new View('report/index');
      $view->localReports = $localReports;
      $view->error = $srvResponse;
      
      $this->template->title = "Report Browser";
      $this->template->content = $view;
    }
    else if (array_key_exists('parameterRequest', $srvResponse))
    {
      $view = new View('report/params');
      $view->request = $srvResponse;
      $this->template->title = "Report Parameters";
    }
    else
    {
      $view = new View('report/view');
      $view->result = $srvResponse;
      $this->template->title = "View Report";
    }
    $this->template->content = $view;
  }
  
  /**
  * Process and deliver a local report - no parameters
  */
  public function local($report)
  {
    $srvResponse = $this->repServ->requestReport($report, 'local', 'xml');
    
    if (array_key_exists('error', $srvResponse))
    {    
      $localReports = $this->repServ->listLocalReports(2);
      
      $view = new View('report/index');
      $view->localReports = $localReports;
      $view->error = $srvResponse;
      
      $this->template->title = "Report Browser";
      $this->template->content = $view;
    }
    else if (array_key_exists('parameterRequest', $srvResponse))
    {
      $view = new View('report/params');
      $view->request = $srvResponse;
      $this->template->title = "Report Parameters";
    }
    else
    {
      $view = new View('report/view');
      $view->result = $srvResponse;
      $this->template->title = "View Report";
    }
    $this->template->content = $view;
  }
}
