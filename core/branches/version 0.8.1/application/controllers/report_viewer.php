<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package	Core
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller providing access to the list of reports and report running.
 *
 * @package	Core
 * @subpackage Controllers
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
    $this->run(null, $uid);
  }

  /**
  * Process and deliver a local report - no parameters
  */
  public function local($report)
  {
    $this->run($report);
  }

  public function run($report, $uid=null)
  {
    try {
      if ($uid) {
        // Resume a report that has an existing identifier
        $srvResponse = $this->repServ->resumeReport($uid, $_POST);
      }	else {
        $srvResponse = $this->repServ->requestReport($report, 'local', 'xml');
      }
    } catch (Exception $e) {
      // Something went wrong, so back to the index page and flash the error.
      $localReports = $this->repServ->listLocalReports(2);

      $view = new View('report/index');
      $view->localReports = $localReports;
      $this->session->set_flash('flash_error', $e->getMessage());
      error::log_error("Error occurred running report $report.", $e);

      $this->template->title = "Report Browser";
      $this->template->content = $view;
      return;
    }
    if (array_key_exists('parameterRequest', $srvResponse['content']))
    {
      $view = new View('report/params');
      // Grab the lookup values for any lookup parameters
      foreach ($srvResponse['content']['parameterRequest'] as $name => $det) {
        if ($det['query']!='') {
          // Report specifies a query for a lookup, so we need to grab the lookup data. Use the report connection so
          // we are not exposed to injection attack (as this is hopefully a limited read only account).
          if (!$this->db) {
            $this->db = new Database('report');
          }
          $srvResponse['content']['parameterRequest'][$name]['lookup_values'] = $this->db->query($det['query'])->result_array();
        } else if (isset($det['lookup_values']) && is_string($det['lookup_values'])) {
        	$options = array();
        	foreach(explode(',', $det['lookup_values']) as $option) {
        		$parts=explode(':', $option);
        		$optionObj = new stdClass;
        		$optionObj->id = $parts[0];
        		$optionObj->caption = $parts[1];
        		$options[]=$optionObj;
        	}
        	$srvResponse['content']['parameterRequest'][$name]['lookup_values'] = $options;
        }
      }
      $view->report = $srvResponse;
      $this->template->title = $srvResponse['description']['title'];
    }
    else
    {
      $view = new View('report/view');
      $view->report = $srvResponse;
      $this->template->title = $srvResponse['description']['title'];
    }
    $this->template->content = $view;
  }
}
