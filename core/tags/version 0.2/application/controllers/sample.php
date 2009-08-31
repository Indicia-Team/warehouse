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
 * Controller providing CRUD access to the samples list.
 *
 * @package	Core
 * @subpackage Controllers
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
      $vArgs = array(
          'occurrences' => $grid->display(),
          'method_terms' => $this->get_termlist_terms('indicia:sample_methods')
      );
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

  /**
   * Returns a set of terms for a termlist.
   *
   * @param string $termlist Name of the termlist, from the termlist's external_key field.
   */
  protected function get_termlist_terms($termlist) {
    $arr=array();
    $sample_method_termlist = ORM::factory('termlist')->where('external_key', $termlist)->find();
    $terms = ORM::factory('termlists_term')->where(array('termlist_id' => $sample_method_termlist, 'deleted' => 'f'))->find_all();
    foreach ($terms as $term) {
      $arr[$term->id] = $term->term->term;
    }
    return $arr;
  }
}
