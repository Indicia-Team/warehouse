<?php

/**
 * @file
 * Base class for grid view controllers.
 *
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Base class for controllers which support paginated grids of any datatype. Also
 * supports basic CSV data upload into the grid's underlying model.
 */
abstract class Gridview_Base_Controller extends Indicia_Controller {

  protected $gridReport = FALSE;

  /**
   * Columns to add to the grid view.
   *
   * @var array
   */
  protected $columns;

  /**
   * Name of the associated model.
   *
   * @var string
   */
  protected $modelname;

  /**
   * Name of the associated view.
   *
   * @var string
   */
  protected $viewname;

  /**
   * The view object.
   *
   * @var mixed
   */
  protected $view;

  /**
   * Path to the controller class (excluding .php).
   *
   * @var string
   */
  protected $controllerpath;

  /**
   * Key/value pairs for filters to be applied to the data.
   *
   * @var array
   */
  protected array $base_filter;

  /**
   * View containing the upload CSV file form.
   *
   * @var View
   */
  protected $upload_csv_form;

  /* Constructor.
   *
   * @param string $modelname
   *   Name of the model for the grid.
   * @param string $viewname
   *   Name of the view which contains the grid. Defaults to the model name +
   *   /index.
   * @param string $controllerpath
   *   Path to the controller from the controllers folder. $viewname and
   *   $controllerpath can be ommitted if the names are all the same.
   */
  public function __construct($modelname, $viewname = NULL, $controllerpath = NULL) {
    $this->model = ORM::factory($modelname);
    $this->modelname = $modelname;
    $this->viewname = is_null($viewname) ? "$modelname/index" : $viewname;
    $this->controllerpath = is_null($controllerpath) ? $modelname : $controllerpath;
    $this->base_filter = [];
    $this->auth_filter = NULL;
    $this->pagetitle = "Abstract gridview class - override this title!";

    parent::__construct();
  }

  /**
   * This is the main controller action method for the index page of the grid.
   */
  public function index() {
    $this->view = new View($this->viewname);
    $this->add_upload_csv_form();
    $grid = new View('gridview');
    $grid->source = $this->modelname;
    $grid->id = $this->modelname;
    if (isset($this->columns)) {
      $grid->columns = $this->columns;
    }
    $filter = $this->base_filter;
    if (isset($this->auth_filter['field'])) {
      $filter[$this->auth_filter['field']] = $this->auth_filter['values'];
    }
    $grid->filter = $filter;
    $grid->gridReport = $this->gridReport;
    // Add grid to view.
    $this->view->grid = $grid->render();

    // Templating.
    $this->template->title = $this->pagetitle;
    $this->template->content = $this->view;

    // Setup breadcrumbs.
    $this->page_breadcrumbs[] = html::anchor($this->modelname, $this->pagetitle);
  }

  /**
   * Return the default action columns for a grid.
   *
   * Default is just an edit link. If required, override this in controllers to
   * specify a different set of actions.
   */
  protected function get_action_columns() {
    return [
      [
        'caption' => 'edit',
        'url' => "$this->controllerpath/edit/{id}",
      ],
    ];
  }

  /**
   * Adds the upload csv form to the view (which should then insert it at the bottom of the grid).
   */
  protected function add_upload_csv_form() {
    $this->upload_csv_form = new View('templates/upload_csv');
    $this->upload_csv_form->returnPage = 1;
    $this->upload_csv_form->staticFields = NULL;
    $this->upload_csv_form->controllerpath = $this->controllerpath;
    $this->view->upload_csv_form = $this->upload_csv_form;
  }

  /**
   * Overridable function to determine if an edit page should be read only.
   *
   * @return bool
   *   True if edit page should be read only.
   */
  protected function get_read_only($values) {
    return FALSE;
  }

  /**
   * Controller function to display a generic import wizard for any data.
   */
  public function importer() {
    $this->SetView('importer', '', ['model' => $this->controllerpath]);
    $this->template->title = "$this->pagetitle Import";
    // Setup a breadcrumb as if we are in the edit page since this will give us
    // the correct links upwards.
    $this->defineEditBreadcrumbs();
    // But make it clear the bottom level breadcrumb is the importer.
    $this->page_breadcrumbs[count($this->page_breadcrumbs) - 1] = kohana::lang('misc.model_import', $this->model->caption());
  }

  /**
   * Loads the custom attributes for a taxon, sample, location, survey, person or occurrence into the load array.
   * Also sets up any lookup lists required.
   * This is only called by sub-classes for entities that have associated attributes.
   */
  protected function loadAttributes(&$r, $in) {
    // First load up the possible attribute list.
    $this->db->from('list_' . $this->model->object_name . '_attributes');
    foreach ($in as $field => $values) {
      if (count($values)) {
        $this->db->in($field, $values);
      }
    }
    if ($this->model->include_public_attributes) {
      $this->db->orwhere('public', 't');
    }
    $result = $this->db->get()->as_array(TRUE);
    $attrs = [];
    foreach ($result as $attr) {
      $attrs[$attr->id] = [
        // The attribute value ID, which we don't know yet.
        'id' => NULL,
        $this->model->object_name . '_id' => NULL,
        $this->model->object_name . '_attribute_id' => $attr->id,
        'data_type' => $attr->data_type,
        'caption' => $attr->caption,
        'value' => NULL,
        'raw_value' => NULL,
        'multi_value' => $attr->multi_value,
        'termlist_id' => isset($attr->lookup_termlist_id) ? $attr->lookup_termlist_id : $attr->termlist_id,
        'validation_rules' => $attr->validation_rules,
      ];
    }
    // Now load up the values and splice into the array.
    if ($this->model->id !== 0) {
      $where = [$this->model->object_name . '_id' => $this->model->id];
      $this->db
        ->from('list_' . $this->model->object_name . '_attribute_values')
        ->where($where);
      $result = $this->db->get()->as_array(FALSE);
      $toRemove = [];
      foreach ($result as $value) {
        $attrId = $value[$this->model->object_name . '_attribute_id'];
        if (isset($attrs[$attrId])) {
          // Copy the attribute def into an array entry specific to this value.
          $attrs["$attrId:$value[id]"] = array_merge($attrs[$attrId]);
          $attrs["$attrId:$value[id]"]['id'] = $value['id'];
          $attrs["$attrId:$value[id]"]['value'] = $value['value'];
          $attrs["$attrId:$value[id]"]['raw_value'] = $value['raw_value'];
          // Remember the non-value specific attribute so we can remove it at
          // the end.
          $toRemove[] = $attrId;
        }
      }
      // Clean up any attributes which are repeated in the list because they
      // have values.
      foreach ($toRemove as $attrId) {
        unset($attrs[$attrId]);
      }
    }
    $r['attributes'] = $attrs;
    // Now work out if we need termlist content for lookups.
    foreach ($attrs as $attr) {
      // If there are any lookup lists in the attributes, preload the options.
      if (!empty($attr['termlist_id'])) {
        $r['terms_' . $attr['termlist_id']] = $this->get_termlist_terms($attr['termlist_id']);
        $r['terms_' . $attr['termlist_id']][''] = '-no value-';
      }
    }
  }

}
