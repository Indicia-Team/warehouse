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
 * @package Individuals and associations
 * @subpackage Controllers
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */

/**
 * Controller providing CRUD access for a known subject comment
 *
 * @package  Core
 * @subpackage Controllers
 */
class Known_subject_comment_Controller extends Gridview_Base_Controller
{
  public function __construct()
  {
    parent::__construct('known_subject_comment');
    $this->columns = array(
      'comment' => '', 'updated_on' => 'Updated on'
    );
    $this->pagetitle = "Known Subject Comments";
  }

  /**
  * Override the default index functionality to filter by known_subject_id.
  */
  public function index()
  {
    if ($this->uri->total_arguments()>0) {
      $this->base_filter=array('known_subject_id' => $this->uri->argument(1));
    }
    parent::index();
    // pass the known_subject_id into the view, so the create button can use it to autoset
    // the known subject of the new comment.
    if ($this->uri->total_arguments()>0) {
      $this->view->known_subject_id=$this->uri->argument(1);
    }
  }

  /**
   *  Setup the default values to use when loading this controller to edit a new page.
   */
  protected function getDefaults() {
    $r = parent::getDefaults();
    if ($this->uri->method(false)=='create') {
      $r['known_subject:id'] = $this->uri->argument(1);
      $r['known_subject_comment:known_subject_id'] = $this->uri->argument(1);
    }
    return $r;
  }

  /**
   * Override the default return page behaviour so that after saving a comment you
   * are returned to the known subject entry which has the comment.
   */
  protected function get_return_page() {
    if (array_key_exists('known_subject_comment:known_subject_id', $_POST)) {
      return "known_subject/edit/".$_POST['known_subject_comment:known_subject_id']."?tab=images";
    } else {
      return $this->model->object_name;
    }
  }

  /**
   * Define non-standard behaviuor for the breadcrumbs, since this is accessed via a taxon list
   */
  protected function defineEditBreadcrumbs() {
    $this->page_breadcrumbs[] = html::anchor('known_subject', 'Known Subjects');
    if ($this->model->id) {
      // editing an existing item, so our argument is the known_subject_comment id
      $known_subject_id = $this->model->known_subject_id;
    } else {
      // creating a new one so our argument is the known_subject id
      $known_subject_id = $this->uri->argument(1);
    }
    $knownSubjectTitle = ORM::Factory('known_subject', $known_subject_id)->caption();
    $this->page_breadcrumbs[] = html::anchor('known_subject_comment/edit/'.$known_subject_id.'?tab=Comments', $knownSubjectTitle);
    $this->page_breadcrumbs[] = $this->model->caption();
  }

}