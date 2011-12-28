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
 * @package	Taxon Designations
 * @subpackage Controllers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Controller class for the taxon designations plugin module.
 */
class Taxon_designation_Controller extends Gridview_Base_Controller {

  public function __construct()
  {
    parent::__construct('taxon_designation', 'taxon_designation/index');
    $this->columns = array(
      'id'          => '',
      'title'       => '',
      'category'    => ''
    );
    $this->pagetitle = "Taxon Designations";
    $this->model = ORM::factory('taxon_designation');
  }

  /**
   * Get the list of terms ready for the location types list.
   */
  protected function prepareOtherViewData($values)
  {
    return array(
      'category_terms' => $this->get_termlist_terms('indicia:taxon_designation_categories')
    );
  }
  
  /**
   * As the designations list is global, need to be admin to change it.
   */
  protected function page_authorised() {
    return $this->auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin');
  }
  
  /** 
   * Upload function for a JNCC style designations spreadsheet.
   */
  public function upload_csv() {
    try
    {
      // We will be using a POST array to send data, and presumably a FILES array for the
      // media.
      // Upload size
      $ups = Kohana::config('indicia.maxUploadSize');
      $_FILES = Validation::factory($_FILES)->add_rules(
        'csv_upload', 'upload::valid', 'upload::required',
        'upload::type[csv]', "upload::size[$ups]"
      );
      if (count($_FILES)===0) {
        echo "No file was uploaded.";
      }
      elseif ($_FILES->validate())
      {
        if (array_key_exists('name_is_guid', $_POST) && $_POST['name_is_guid']=='true') 
          $finalName = strtolower($_FILES['csv_upload']['name']);
        else
          $finalName = time().strtolower($_FILES['csv_upload']['name']);
        $fTmp = upload::save('csv_upload', $finalName);
        url::redirect('taxon_designation/import_progress?file='.urlencode(basename($fTmp)));
      }
      else
      {
        kohana::log('error', 'Validation errors uploading file '. $_FILES['media_upload']['name']);
        kohana::log('error', print_r($_FILES->errors('form_error_messages'), true));
        Throw new ArrayException('Validation error', $_FILES->errors('form_error_messages'));
      }
    }
    catch (Exception $e)
    {
      $this->handle_error($e);
    }
  }
  
  /**
   * Controller method for the import_progress path. Displays the upload template with 
   * progress bar and status message, which then initiates the actual import.
   */
  public function import_progress() {
    if (file_exists(kohana::config('upload.directory').'/'.$_GET['file'])) {
      $this->template->content = new View('taxon_designation/upload');
      $this->template->title = 'Uploading designations';
    }
  }
  
  /**
   * AJAX callback to handle upload of a single chunk of designations spreadsheet.
   */
  public function upload() {
    $this->auto_render=false;
    $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
    $cache= Cache::instance();
    kohana::log('debug', 'in upload method');
    if (file_exists($csvTempFile))
    {
      // Following helps for files from Macs
      ini_set('auto_detect_line_endings',1);
      // create the file pointer, plus one for errors
      $handle = fopen ($csvTempFile, "r");
      $count=0;
      $limit = (isset($_GET['limit']) ? $_GET['limit'] : false);
      $filepos = (isset($_GET['filepos']) ? $_GET['filepos'] : 0);
      $offset = (isset($_GET['offset']) ? $_GET['offset'] : 0);
      // skip rows to allow for the last file position
      fseek($handle, $filepos);
      if ($filepos==0) {
        // first row, so load the column headings
        $headings = fgetcsv($handle, 1000, ",");
        // Also work out the termlist_id for the cateogories
        $r = $this->db
            ->select('id')
            ->from('termlists')
            ->where(array('external_key'=>"indicia:taxon_designation_categories"))
            ->get()->result_array(false);
        $obj = array('termlist_id'=>$r[0]['id'], 'headings'=>$headings);
        $filepos = ftell($handle);
        $cache->set(basename($_GET['uploaded_csv']).'metadata', $obj);
      } else {
        $obj = $cache->get(basename($_GET['uploaded_csv']).'metadata');
      }
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && ($limit===false || $count<$limit)) {
        $count++;
        $filepos = ftell($handle);
        $designationTitle = $this->findValue($data, 'designation title', $obj);
        $designationCode = $this->findValue($data, 'designation code', $obj);
        $designationAbbr = $this->findValue($data, 'designation abbr', $obj);
        $designationDescription = $this->findValue($data, 'designation description', $obj);
        $designationCategory = $this->findValue($data, 'designation category', $obj);
        $taxonExternalKey = $this->findValue($data, 'taxon external key', $obj);
        $startDate = $this->findValue($data, 'start date', $obj);
        $source = $this->findValue($data, 'source', $obj);
        $geographicConstraint = $this->findValue($data, 'geographic constraint', $obj);
        // First step - ensure a designation category exists
        $r = $this->db
            ->select('id')
            ->from('list_termlists_terms')
            ->where(array('termlist_id'=>$obj['termlist_id'], 'term'=>$designationCategory))
            ->get()->result_array(false);
        if (count($r)==0) {
          kohana::log('debug', 'inserting category '.$designationCategory);
          $this->db->query("SELECT insert_term('$designationCategory', 'eng', null, null, 'indicia:taxon_designation_categories');");
          $r = $this->db
            ->select('id')
            ->from('list_termlists_terms')
            ->where(array('termlist_id'=>$obj['termlist_id'], 'term'=>$designationCategory))
            ->get()->result_array(false);
        }
        $catId = $r[0]['id'];
        kohana::log('debug', "got category $catId");
        // Second step - ensure the designation exists
        $r = $this->db
            ->select('id')
            ->from('taxon_designations')
            ->where(array('category_id'=>$catId, 'title'=>$designationTitle, 'deleted'=>'f'))
            ->get()->result_array(false);
        if (count($r)==0) {
          kohana::log('debug', 'inserting designation '.$designationTitle);
          $this->db->insert('taxon_designations', array(
            'title'=>$designationTitle,
            'code'=>$designationCode,
            'abbreviation'=>$designationAbbr,
            'description'=>$designationDescription,
            'category_id'=>$catId,
            'created_on'=>date("Ymd H:i:s"),
            'created_by_id'=>$_SESSION['auth_user']->id,
            'updated_on'=>date("Ymd H:i:s"),
            'updated_by_id'=>$_SESSION['auth_user']->id   
          ));
          $r = $this->db
            ->select('id')
            ->from('taxon_designations')
            ->where(array('category_id'=>$catId, 'title'=>$designationTitle))
            ->get()->result_array(false);
        }
        $desId = $r[0]['id'];
        // Third step - find the pre-existing taxon/taxa
        $r = $this->db
            ->select('id')
            ->from('taxa')
            ->where(array('external_key'=>$taxonExternalKey))
            ->get()->result_array(false);
        // convert years to a date
        if (preg_match('/\d\d\d\d/', $startDate))
          $startDate = $startDate.'-01-01';
        foreach ($r as $taxon) {
          // Insert a link from each matched taxon to the designation
          $this->db->insert('taxa_taxon_designations', array(
            'taxon_id'=>$taxon['id'],
            'taxon_designation_id'=>$desId,
            'start_date'=>$startDate,
            'source'=>$source,
            'geographical_constraint'=>$geographicConstraint,
            'created_on'=>date("Ymd H:i:s"),
            'created_by_id'=>$_SESSION['auth_user']->id,
            'updated_on'=>date("Ymd H:i:s"),
            'updated_by_id'=>$_SESSION['auth_user']->id   
          ));
        }
      }
    }
    $progress = $filepos * 100 / filesize($csvTempFile);
    $r = "{\"uploaded\":$count,\"progress\":$progress,\"filepos\":$filepos}";
    echo $r;
    fclose($handle);
  }
  
  /**
   * Controller method for the upload_complate path, called at the end of upload.
   * Displays a message about the number of designations uploaded, cleans the cache
   * and upload file, then navigaes to the taxon designation index page.
   */
  public function upload_complete() {
    $this->session->set_flash('flash_info', $_GET['total']." designations were uploaded.");
    $cache->delete(basename($_GET['uploaded_csv']));
    $csvTempFile = DOCROOT . "upload/" . $_GET['uploaded_csv'];
    unlink($csvTempFile);
    url::redirect('taxon_designation/index'); 
  }
  
  /** 
   * Finds a field value if it exists in the data for a CSV row.
   * @param type $data
   * @param type $name
   * @param type $obj 
   */
  private function findValue($data, $name, $obj) {
    $idx = array_search($name, $obj['headings']);
    if ($idx===false) 
      return null;
    else
      return $data[$idx];
  }

}

?>