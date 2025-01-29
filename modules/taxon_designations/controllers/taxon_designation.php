<?php

/**
 * @file
 * Controller for the list of taxon designations.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * Controller class for the taxon designations plugin module.
 */
class Taxon_designation_Controller extends Gridview_Base_Controller {

  public function __construct() {
    parent::__construct('taxon_designation', 'taxon_designation/index');
    $this->columns = array(
      'id'          => '',
      'title'       => '',
      'category'    => '',
    );
    $this->pagetitle = "Taxon Designations";
    $this->model = ORM::factory('taxon_designation');
  }

  /**
   * Get the list of terms ready for the location types list.
   */
  protected function prepareOtherViewData(array $values) {
    return [
      'category_terms' => $this->get_termlist_terms('indicia:taxon_designation_categories'),
    ];
  }

  /**
   * As the designations list is global, need to be admin to change it.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $this->auth->has_any_website_access('admin');
  }

  /**
   * Upload function for a JNCC style designations spreadsheet.
   */
  public function upload_csv() {
    try {
      // Validate the uploaded CSV.
      $ups = Kohana::config('indicia.maxUploadSize');
      $_FILES = Validation::factory($_FILES)->add_rules(
        'csv_upload', 'upload::valid', 'upload::required',
        'upload::type[csv]', "upload::size[$ups]"
      );
      if (count($_FILES) === 0) {
        echo "No file was uploaded.";
      }
      elseif ($_FILES->validate()) {
        if (array_key_exists('name_is_guid', $_POST) && $_POST['name_is_guid'] === 'true') {
          $finalName = strtolower($_FILES['csv_upload']['name']);
        }
        else {
          $finalName = time() . strtolower($_FILES['csv_upload']['name']);
        }
        $fTmp = upload::save('csv_upload', $finalName);
        $qry = http_build_query([
          'file' => basename($fTmp),
          'taxon_list_id' => $_POST['taxon_list_id'],
        ]);
        url::redirect("taxon_designation/import_progress?$qry");
      }
      else {
        kohana::log('error', 'Validation errors uploading file ' . $_FILES['csv_upload']['name']);
        kohana::log('error', print_r($_FILES->errors('form_error_messages'), TRUE));
        throw new Exception(implode('; ', $_FILES->errors('form_error_messages')));
      }
    }
    catch (Exception $e) {
      $this->handle_error($e);
    }
  }

  /**
   * Controller method for the import_progress path.
   *
   * Displays the upload template with progress bar and status message, which
   * then initiates the actual import.
   */
  public function import_progress() {
    if (file_exists(kohana::config('upload.directory') . "/$_GET[file]")) {
      $this->template->content = new View('taxon_designation/upload');
      $this->template->title = 'Uploading designations';
    }
  }

  /**
   * AJAX callback to handle upload of a chunk of designations spreadsheet.
   */
  public function upload() {
    $this->auto_render = FALSE;
    $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
    $cache = Cache::instance();
    kohana::log('debug', 'in upload method');
    if (file_exists($csvTempFile)) {
      // Create the file pointer, plus one for errors.
      $handle = fopen($csvTempFile, "r");
      $count = 0;
      $limit = (isset($_GET['limit']) ? $_GET['limit'] : FALSE);
      $filepos = (isset($_GET['filepos']) ? $_GET['filepos'] : 0);
      $offset = (isset($_GET['offset']) ? $_GET['offset'] : 0);
      // Skip rows to allow for the last file position.
      fseek($handle, $filepos);
      if ($filepos == 0) {
        // First row, so load the column headings. Force lowercase so we can
        // case insensitive search later.
        $headings = array_map('strtolower', fgetcsv($handle, 1000, ","));
        // Also work out the termlist_id for the cateogories.
        $r = $this->db
          ->select('id')
          ->from('termlists')
          ->where(array('external_key' => "indicia:taxon_designation_categories"))
          ->get()->result_array(FALSE);
        $obj = array('termlist_id' => $r[0]['id'], 'headings' => $headings);
        $filepos = ftell($handle);
        $cache->set(basename($_GET['uploaded_csv']) . 'metadata', $obj);
      }
      else {
        $obj = $cache->get(basename($_GET['uploaded_csv']) . 'metadata');
      }
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && ($limit === FALSE || $count < $limit)) {
        if (trim(implode('', $data)) === '') {
          // Skip any empty lines.
          continue;
        }
        $count++;
        $filepos = ftell($handle);
        $designationTitle = $this->findValue($data, ['designation title', 'designation'], $obj);
        $designationCode = $this->findValue($data, ['designation code'], $obj);
        $designationAbbr = $this->findValue($data, ['designation abbr', 'designation abbreviation'], $obj);
        $designationDescription = $this->findValue($data, ['designation description'], $obj);
        $designationCategory = $this->findValue($data, ['designation category', 'reporting category'], $obj);
        $taxonExternalKey = $this->findValue($data, [
          'taxon external key',
          'taxon version key',
          'recommended taxon version key',
        ], $obj);
        $taxon = $this->findValue($data, [
          'taxon',
          'current taxon name',
          'recommended taxon',
        ], $obj);
        if (empty($taxonExternalKey) && empty($taxon)) {
          throw new exception('Missing taxon or external key - cannot link to a taxon');
        }
        $startDate = $this->findValue($data, ['start date', 'year'], $obj);
        $source = $this->findValue($data, ['source'], $obj);
        $geographicConstraint = $this->findValue($data, ['geographic constraint'], $obj);
        // First step - ensure a designation category exists.
        $r = $this->db
          ->select('id')
          ->from('list_termlists_terms')
          ->where(array('termlist_id' => $obj['termlist_id'], 'term' => $designationCategory))
          ->get()->result_array(FALSE);
        if (count($r) === 0) {
          kohana::log('debug', "Inserting category $designationCategory");
          $this->db->query("SELECT insert_term(?, 'eng', NULL, NULL, 'indicia:taxon_designation_categories');", [$designationCategory]);
          $r = $this->db
            ->select('id')
            ->from('list_termlists_terms')
            ->where([
              'termlist_id' => $obj['termlist_id'],
              'term' => $designationCategory,
            ])
            ->get()->result_array(FALSE);
        }
        $catId = $r[0]['id'];
        // Second step - ensure the designation exists.
        $r = $this->db
          ->select('id')
          ->from('taxon_designations')
          ->where([
            'category_id' => $catId,
            'title' => $designationTitle,
            'deleted' => 'f',
          ])
          ->get()->result_array(FALSE);
        if (count($r) === 0) {
          kohana::log('debug', "Inserting designation $designationTitle");
          $this->db->insert('taxon_designations', array(
            'title' => $designationTitle,
            'code' => $designationCode,
            'abbreviation' => $designationAbbr,
            'description' => $designationDescription,
            'category_id' => $catId,
            'created_on' => date("Ymd H:i:s"),
            'created_by_id' => $_SESSION['auth_user']->id,
            'updated_on' => date("Ymd H:i:s"),
            'updated_by_id' => $_SESSION['auth_user']->id,
          ));
          $r = $this->db
            ->select('id')
            ->from('taxon_designations')
            ->where([
              'category_id' => $catId,
              'title' => $designationTitle,
            ])
            ->get()->result_array(FALSE);
        }
        $desId = $r[0]['id'];
        // Third step - find the pre-existing taxon/taxa.
        $where = [
          't.deleted' => 'f',
          'ttl.deleted' => 'f',
          'ttl.taxon_list_id' => $_GET['taxon_list_id'],
        ];
        if (!empty($taxon)) {
          $where['t.taxon'] = trim($taxon);
        }
        if (empty($taxonExternalKey) && empty($taxon)) {
          throw new exception('Missing taxon or external key - cannot link to a taxon');
        }
        $this->db
          ->select('t.id, ttl.preferred')
          ->from('taxa as t')
          ->join('taxa_taxon_lists as ttl', 'ttl.taxon_id', 't.id')
          ->where($where);
        if (!empty($taxonExternalKey)) {
          $this->db->where("coalesce(t.search_code, t.external_key)='" . trim($taxonExternalKey) . "'");
        }
        $r = $this->db
          ->orderby('ttl.preferred', 'DESC')
          ->get()->result_array(FALSE);
        // Convert years to a date.
        if (preg_match('/\d\d\d\d/', $startDate)) {
          $startDate = $startDate . '-01-01';
        }
        if (empty($startDate)) {
          $startDate = NULL;
        }
        $hasPreferred = FALSE;
        foreach ($r as $taxon) {
          // If there was at least one preferred matching taxon, skip any
          // remaining synonyms. If none are preferred, then link to all the
          // matching synonyms.
          $hasPreferred = $hasPreferred || ($taxon['preferred'] === 't');
          if ($hasPreferred && $taxon['preferred'] === 'f') {
            break;
          }
          // Insert a link from each matched taxon to the designation, if not
          // already present.
          $r = $this->db
            ->select('id')
            ->from('taxa_taxon_designations')
            ->where(array(
              'taxon_designation_id' => $desId,
              'taxon_id' => $taxon['id'],
              'deleted' => 'f',
            ))
            ->get()->result_array(FALSE);
          if (count($r) === 0) {
            $this->db->insert('taxa_taxon_designations', array(
              'taxon_id' => $taxon['id'],
              'taxon_designation_id' => $desId,
              'start_date' => $startDate,
              'source' => $source,
              'geographical_constraint' => $geographicConstraint,
              'created_on' => date("Ymd H:i:s"),
              'created_by_id' => $_SESSION['auth_user']->id,
              'updated_on' => date("Ymd H:i:s"),
              'updated_by_id' => $_SESSION['auth_user']->id,
            ));
          }
          else {
            $this->db->update('taxa_taxon_designations', array(
              'taxon_id' => $taxon['id'],
              'taxon_designation_id' => $desId,
              'start_date' => $startDate,
              'source' => $source,
              'geographical_constraint' => $geographicConstraint,
              'updated_on' => date("Ymd H:i:s"),
              'updated_by_id' => $_SESSION['auth_user']->id,
            ), array('id' => $r[0]['id']));
            kohana::log('debug', 'updated');
          }
        }
      }
    }
    $progress = $filepos * 100 / filesize($csvTempFile);
    $r = "{\"uploaded\":$count,\"progress\":$progress,\"filepos\":$filepos}";
    echo $r;
    fclose($handle);
  }

  /**
   * Controller method for the upload_complate path.
   *
   * Called at the end of upload. Displays a message about the number of
   * designations uploaded, cleans the cache and upload file, then navigates to
   * the taxon designation index page.
   */
  public function upload_complete() {
    $this->session->set_flash('flash_info', $_GET['total'] . " designations were uploaded.");
    $cache = Cache::instance();
    $cache->delete(basename($_GET['uploaded_csv']) . 'metadata');
    $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
    unlink($csvTempFile);
    url::redirect('taxon_designation/index');
  }

  /**
   * Finds a field value if it exists in the data for a CSV row.
   *
   * @param type $data
   *   Data array to search in.
   * @param array $names
   *   List of the possible column titles that this value can match against.
   * @param array $obj
   */
  private function findValue($data, array $names, array $obj) {
    foreach ($names as $name) {
      $idx = array_search($name, $obj['headings']);
      if ($idx !== FALSE) {
        break;
      }
    }
    return $idx === FALSE ? NULL : $data[$idx];
  }

}
