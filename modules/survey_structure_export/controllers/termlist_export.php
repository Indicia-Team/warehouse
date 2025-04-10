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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Controller class for the survey structure export plugin module.
 */
class Termlist_export_Controller extends Indicia_Controller {

  /**
   * The view object.
   *
   * @var mixed
   */
  protected $view;

  /**
   * @var array Holds a list of log messages describing the results of an import.
   */
  private $log = [];

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
  const SQL_FETCH_ALL_TERMS = "SELECT array_to_string(array_agg(entry), '**') AS terms FROM
  (
    SELECT
      t.term || '|' ||
      t.language_iso || '|' ||
      coalesce(t.sort_order::varchar, '') || '|' ||
      coalesce(tp.term::varchar, '')::varchar || '|' ||
      array_to_string(array_agg(ts.term || '~' || ts.language_iso ORDER BY ts.term, ts.language_iso), '`'::varchar) AS entry
    FROM cache_termlists_terms t
    LEFT JOIN cache_termlists_terms tp ON tp.id = t.parent_id
    LEFT JOIN cache_termlists_terms ts ON ts.meaning_id = t.meaning_id AND ts.termlist_id = t.termlist_id AND ts.preferred = false
    WHERE t.preferred = true AND t.termlist_id=?
    GROUP BY t.term, t.language_iso, t.sort_order, tp.term
    ORDER BY t.sort_order, t.term
  ) AS list";

  /**
   * @const SQL_FIND_EXISTING_TERM = Query definition to find if a term definition already exists in the termlist.
   */
  const SQL_FIND_EXISTING_TERM = <<<SQL
    SELECT 1
    FROM termlists_terms tlt
    JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
    JOIN languages l ON l.id = t.language_id AND l.deleted = false
    LEFT JOIN termlists_terms tltp ON tltp.id = tlt.parent_id AND tltp.deleted = false
    LEFT JOIN terms tp ON tp.id = tltp.term_id AND tp.deleted = false
    WHERE tlt.deleted = false AND tlt.termlist_id = ?
    AND t.term = ? and l.iso = ?
    AND coalesce(tlt.sort_order::varchar, '') = ?
    AND coalesce(tp.term, '') = ?
  SQL;

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
    $this->view->termlistId = $this->uri->last_segment();
    // Get the term data associated with the termlist ready to export.
    $export = $this->getTerms($this->view->termlistId);
    $this->view->export = json_encode($export);
    $this->template->content = $this->view;
  }

  /**
   * Controller action called when Save clicked. Perform the import when text
   * has been pasted into the import text area.
   */
  public function save() {
    $termlistId = $_POST['termlist_id'];
    $termlist = $this->db
      ->select('website_id, title')
      ->from('termlists')
      ->where(['id' => $termlistId])
      ->get()->result_array(FALSE);
    $this->website_id = $termlist[0]['website_id'];
    if (empty($_POST['import_termlist_contents'])) {
      $this->template->title = 'Error during termlist import';
      $this->view = new View('templates/error_message');
      $this->view->message = 'Please ensure you copy the details of a termlists\'s terms into the "Import termlist contents" box before importing.';
      $this->template->content = $this->view;
    }
    else {
      // Start a transaction.
      $this->db->query('BEGIN;');
      try {
        $importData = json_decode($_POST['import_termlist_contents'], TRUE);
        $this->doImport($importData);
        $this->template->title = 'Import Complete';
        $this->view = new View('termlist_export/import_complete');
        $this->view->log = $this->log;
        $this->template->content = $this->view;
        $this->db->query('COMMIT;');
      }
      catch (Exception $e) {
        $this->db->query('ROLLBACK;');
        error_logger::log_error('Exception during termlist content import', $e);
        $this->template->title = 'Error during termlist content import';
        $this->view = new View('templates/error_message');
        $this->view->message='An error occurred during the termlist content import and no changes have been made to the database. ' .
                             'Please make sure the import data is valid. More information can be found in the warehouse logs.';
        $this->template->content = $this->view;
      }
    }
    $this->page_breadcrumbs[] = html::anchor('termlist', 'Termlists');
    $this->page_breadcrumbs[] = html::anchor('termlist/edit/' . $termlistId, $termlist[0]['title']);
    $this->page_breadcrumbs[] = $this->template->title;
  }

  /**
   * Import a pasted definition of a set of custom attributes.
   *
   * @param string $importData The definition of the terms to import.
   */
  public function doImport($importData) {
    if (isset($_SESSION['auth_user'])) {
      $this->userId = $_SESSION['auth_user']->id;
    }
    else {
      global $remoteUserId;
      if (isset($remoteUserId)) {
        $this->userId = $remoteUserId;
      }
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
    // Now we need to create the terms required by the termlist.
    // Split the terms string into individual terms.
    $terms = explode('**', $importData['terms']);
    $termlist_id = $_POST['termlist_id'];
    foreach ($terms as $termTokens) {
      // The tokens defining the term are separated by pipes.
      list($term, $lang, $sort, $parent) = explode('|', $termTokens);
      // Does the term already exist in the list?
      $existing = $this->db->query(self::SQL_FIND_EXISTING_TERM, [
        $_POST['termlist_id'],
        $term,
        $lang,
        $sort,
        $parent
      ])->result()->count();
      if (!$existing) {
        $this->log[] = $this->db->last_query();
        // Sanitise the sort order.
        $sort = empty($sort) ? 'null' : pg_escape_literal($this->db->getLink(), $sort);
        $this->db->query("select insert_term(?, ?, $sort, ?, null);", [$term, $lang, $termlist_id]);
        $this->log[] = "Added term $term";
      }
      else {
        $this->log[] = "Term $term already exists";
      }
    }
    // Now re-iterate through the terms and set the term parents.
    foreach ($terms as $term) {
      // The tokens defining the term are separated by pipes.
      $term = explode('|', $term);
      if (!empty($term[3])) {
        // SQL escaping.
        $escapedTerm = pg_escape_string($this->db->getLink(), $term[0]);
        $escapedParent = pg_escape_string($this->db->getLink(), $term[3]);
        $this->db->query("UPDATE termlists_terms tlt SET parent_id = tltp.id, updated_on = now()
          FROM terms t, termlists_terms tltp
          JOIN terms tp ON tp.id = tltp.term_id AND tp.deleted = false AND tp.term = ?,
          WHERE
            tlt.termlist_id = ? AND t.id = tlt.term_id
            AND t.deleted = false AND t.term = ?
            AND tltp.termlist_id = tlt.termlist_id AND tltp.deleted = false", [$escapedParent, $termlist_id, $escapedTerm]
        );
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
  private function getTerms(int $termlistId) {
    $r = $this->db->query(self::SQL_FETCH_ALL_TERMS, [$termlistId])->result_array(FALSE);
    return $r[0];
  }

}
