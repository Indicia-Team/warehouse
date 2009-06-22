<?php
/**
* INDICIA
* @link http://code.google.com/p/indicia/
* @package Indicia
*/

/**
* Sample page controller
*
*
* @package Indicia
* @subpackage Controller
* @license http://www.gnu.org/licenses/gpl.html GPL
* @author Nicholas Clarke <xxx@xxx.net> / $Author$
* @copyright xxxx
* @version $Rev$ / $LastChangedDate$
*/
class Sample_Controller extends Gridview_Base_Controller
{
  public function __construct()
  {
    parent::__construct('sample', 'gv_sample', 'sample/index');
    $this->pagetitle = 'Samples';
    $this->model = ORM::factory('sample');
    $this->columns = array
    (
      'entered_sref' => 'Spatial Ref.',
      'location' => 'Location',
      'location_name' => 'Location Name',
      'vague_date' => 'Date',
      'recorder_names' => 'Recorder Names',
    );
  }
  /**
  * Action for sample/create page/
  * Displays a page allowing entry of a new sample.
  */
  public function create()
  {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else
    {
      $this->setView('sample/sample_edit', 'Sample');
    }
  }

  /**
  * Action for sample/edit page
  * Edit website data
  */
  public function edit($id  = null, $page_no, $limit)
  {
    if (!$this->page_authorised())
    {
      $this->access_denied();
    }
    else if ($id == null)
    {
      $this->setError('Invocation error: missing argument', 'You cannot call edit sample without an ID');
    }
    else
    {
      $this->model = ORM::factory('sample', $id);
      $gridmodel = ORM::factory('gv_occurrence');
      $grid = Gridview_Controller::factory($gridmodel,	$page_no,  $limit, 4);
      $grid->base_filter = array('sample_id' => $id, 'deleted' => 'f');
      $grid->columns = array('taxon' => '');
      $grid->actionColumns = array('edit' => 'occurrence/edit/£id£');
      $vArgs = array('occurrences' => $grid->display());
      $this->setView('sample/sample_edit', 'Sample', $vArgs);
    }
  }

  public function edit_gv($id = null, $page_no, $limit)
  {
    $this->auto_render = false;
    $gridmodel = ORM::factory('gv_occurrence');
    $grid = Gridview_Controller::factory($gridmodel,	$page_no,  $limit, 4);
    $grid->base_filter = array('sample_id' => $id, 'deleted' => 'f');
    $grid->columns = array('taxon' => '');

    return $grid->display();
  }
}
