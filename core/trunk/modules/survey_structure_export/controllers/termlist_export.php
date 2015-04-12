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
 * @package    Survey Structure Export
 * @subpackage Controllers
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Controller class for the survey structure export plugin module.
 */
class Termlist_export_Controller extends Indicia_Controller {

  /**
   * @var array Holds a list of log messages describing the results of an import.
   */
  private $log=array();
  
  /**
   * @var integer The user's ID.
   */
  private $userId;
  
  /**
   * @var integer The ID of the website we are importing into.
   */
  private $website_id;
  
  /**
   * @const SQL_FETCH_ALL_TERMS Query definition which retrieves all the terms for a termlist ID
   * in preparation for export.
   */
  const SQL_FETCH_ALL_TERMS = "select array_to_string(array_agg((t.term || '|' || t.language_iso || '|' || coalesce(t.sort_order::varchar, '') || '|' || coalesce(tp.term::varchar, ''))::varchar order by t.sort_order, t.term), '**') as terms
from cache_termlists_terms t
left join cache_termlists_terms tp on tp.id=t.parent_id
where {where}";

  /**
   * @const SQL_FIND_EXISTING_TERM = Query definition to find if a term definition already exists in the termlist.
   */
  const SQL_FIND_EXISTING_TERM = "select 1
   from termlists_terms tlt
   join terms t on t.id=tlt.term_id and t.deleted=false
   join languages l on l.id=t.language_id and l.deleted=false
   left join termlists_terms tltp on tltp.id=tlt.parent_id and tltp.deleted=false
   left join terms tp on tp.id=tltp.term_id and tp.deleted=false
   where tlt.deleted=false and tlt.termlist_id={termlist_id}
   and t.term='{term}' and l.iso='{language_iso}'
   and coalesce(tlt.sort_order::varchar, '')='{sort_order}'
   and coalesce(tp.term, '')='{parent}'";

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct();
  }
  
  /**
   * Controller action for the export tab content. Displays the view containing a block of 
   * exportable content as well as a textarea into which exports from elsewhere can be pasted.
   */
  public function index() {
    $this->view = new View('termlist_export/index');
    $this->view->termlistId=$this->uri->last_segment();
    // Get the term data associated with the termlist ready to export
    $export = $this->getTerms($this->view->termlistId);
    $this->view->export = json_encode($export);
    $this->template->content = $this->view;
  }
 
  /**
   * Controller action called when Save clicked. Perform the import when text has been pasted into the import text area.
   */
  public function save() {    
    $termlistId = $_POST['termlist_id'];
    $termlist = $this->db
        ->select('website_id, title')
        ->from('termlists')
        ->where(array('id'=>$termlistId))
        ->get()->result_array(FALSE);
    $this->website_id=$termlist[0]['website_id'];
    if (empty($_POST['import_termlist_contents'])) {
      $this->template->title = 'Error during termlist import';
      $this->view = new View('templates/error_message');
      $this->view->message='Please ensure you copy the details of a termlists\'s terms into the "Import termlist contents" box before importing.';
      $this->template->content = $this->view;
    } else {
      // start a transaction
      $this->db->query('BEGIN;');
      try {
        $importData = json_decode($_POST['import_termlist_contents'], true);
        $this->doImport($importData);
        $this->template->title = 'Import Complete';
        $this->view = new View('termlist_export/import_complete');
        $this->view->log = $this->log;
        $this->template->content = $this->view;
        $this->db->query('COMMIT;');
      } catch (Exception $e) {
        $this->db->query('ROLLBACK;');
        error::log_error('Exception during termlist content import', $e);
        $this->template->title = 'Error during termlist content import';
        $this->view = new View('templates/error_message');
        $this->view->message='An error occurred during the termlist content import and no changes have been made to the database. ' .
                             'Please make sure the import data is valid. More information can be found in the warehouse logs.';
        $this->template->content = $this->view;
      }
    }
    $this->page_breadcrumbs[] = html::anchor('termlist', 'Termlists');
    $this->page_breadcrumbs[] = html::anchor('termlist/edit/'.$termlistId, $termlist[0]['title']);
    $this->page_breadcrumbs[] = $this->template->title;
  }

  /**
   * Import a pasted definition of a set of custom attributes.
   *
   * @param string $importData The definition of the terms to import.
   */
  public function doImport($importData) {
    if (isset($_SESSION['auth_user']))
      $this->userId = $_SESSION['auth_user']->id;
    else {
      global $remoteUserId;
      if (isset($remoteUserId))
        $this->userId = $remoteUserId;
      else {
        $defaultUserId = Kohana::config('indicia.defaultPersonId');
        $this->userId = ($defaultUserId ? $defaultUserId : 1);
      }
    }
    $this->populateTermlist($importData);
  }

  /**
   * Populate the termlist with the imported terms
   *
   * @param string $importData The definition of the terms to import.
   * @internal param array $attrDef Definition of the attribute as defined by the imported data.
   */
  private function populateTermlist($importData) {
    // now we need to create the terms required by the termlist. Split the terms string into individual terms.
    $terms = explode('**', $importData['terms']);
    $termlist_id = $_POST['termlist_id'];
    foreach ($terms as $term) {
      // the tokens defining the term are separated by pipes.
      $term = explode('|', $term);
      // does the term already exist in the list?
      $existing = $this->db->query(str_replace(
          array('{termlist_id}', '{term}', '{language_iso}', '{sort_order}', '{parent}'),
          array($_POST['termlist_id'], $term[0], $term[1], $term[2], $term[3]),
          self::SQL_FIND_EXISTING_TERM))->result()->count();

      if (!$existing) {
        $this->log[] = $this->db->last_query();
        // sanitise the sort order
        $term[2] = empty($term[2]) ? 'null' : $term[2];
        $this->db->query("select insert_term('$term[0]', '$term[1]', $term[2], $termlist_id, null);");
        $this->log[] = "Added term $term[0]";
      } else {
        $this->log[] = "Term $term[0] already exists";
      }
    }
    // Now re-iterate through the terms and set the term parents
    foreach ($terms as $term) {
      // the tokens defining the term are separated by pipes.
      $term = explode('|', $term);
      if (!empty($term[3])) {
        $this->db->query("update termlists_terms tlt set parent_id=tltp.id, updated_on=now()
          from terms t, termlists_terms tltp
          join terms tp on tp.id=tltp.term_id and tp.deleted=false and tp.term='$term[3]'
          where tlt.termlist_id=$termlist_id and t.id=tlt.term_id and t.deleted=false and t.term='$term[0]'
          and tltp.termlist_id=tlt.termlist_id and tltp.deleted=false");
      }
    }
  }
  
  /**
   * Retrieves the data for a list of terms in a given termlist.
   * 
   * @param integer $termlistId The ID of the termlist to retrieve terms for.
   * @return array A version of the data which has been changed into structured
   * arrays of the data from the tables.
   */
  public function getTerms($termlistId) {
    $r = $this->db->query(str_replace('{where}', "t.termlist_id=$termlistId", self::SQL_FETCH_ALL_TERMS))->result_array(FALSE);
    return $r[0];
  }
  
}