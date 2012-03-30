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
 * @package Core
 * @subpackage Controllers
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */

/**
 * Controller providing CRUD access for a sample comment
 *
 * @package  Core
 * @subpackage Controllers
 */
class Sample_comment_Controller extends Gridview_Base_Controller
{
  public function __construct()
  {
error_log("sample_comment::__construct() called.");
    parent::__construct('sample_comment', 'sample_comment', 'sample_comment/index');
    $this->columns = array(
      'comment' => '', 'updated_on' => ''
    );
    $this->pagetitle = "Sample Comments";
  }

  /**
  * Override the default page functionality to filter by sample_id.
  */
  public function page($page_no, $filter=null)
  {
error_log("sample_comment::page() called.");
    $sample_id=$filter;
    // At this point, $sample_id has a value - the framework will trap the other case.
    // No further filtering of the gridview required as the very fact you can access the parent sample
    // means you can access all the comments for it.
    $this->base_filter['sample_id'] = $sample_id;
    parent::page($page_no);
    $this->view->sample_id = $sample_id;
  }

  /**
   * Method to retrieve pages for the index grid of taxa_taxon_list entries from an AJAX
   * pagination call. Overrides the base class behaviour to enforce a filter on the
   * taxon list id.
   */
  public function page_gv($page_no, $filter=null)
  {
    $sample_id=$filter;
    $this->base_filter['sample_id'] = $sample_id;
    return parent::page_gv($page_no);
  }

  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      // sample id is passed as first argument in URL when creating.
      $r['sample:id'] = $this->uri->argument(1);
      $r['sample_comment:sample_id'] = $this->uri->argument(1);
    }
    return $r;
  }

  /**
   * Override the default return page behaviour so that after saving a comment you
   * are returned to the sample entry which has the comment.
   */
  protected function get_return_page() {
    if (array_key_exists('sample_comment:sample_id', $_POST)) {
      return "sample/edit/".$_POST['sample_comment:sample_id']."?tab=images";
    } else {
      return $this->model->object_name;
    }
  }

  /**
   * Define non-standard behaviuor for the breadcrumbs, since this is accessed via a taxon list
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('sample', 'Samples');
    if ($this->model->id) {
      // editing an existing item, so our argument is the sample_comment_id
      $sample_id = $this->model->sample_id;
    } else {
      // creating a new one so our argument is the sample id
      $sample_id = $this->uri->argument(1);
    }
    $sampleTitle = ORM::Factory('sample', $sample_id)->caption();
    $this->page_breadcrumbs[] = html::anchor('sample/edit/'.$sample_id.'?tab=Comments', $sampleTitle);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

}