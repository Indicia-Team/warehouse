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
 * @package  Services
 * @subpackage Data
 * @author  Indicia Team
 * @license  http://www.gnu.org/licenses/gpl.html GPL
 * @link   http://code.google.com/p/indicia/
 */

/**
 * Base controller class for data & reporting services.
 *
 * @package  Services
 * @subpackage Data
 */
class Data_Service_Base_Controller extends Service_Base_Controller {

  /**
  * Cleanup a write once nonce from the cache. Should be called after a call to authenticate.
  * Read nonces do not need to be deleted - they are left to expire.
  */
  protected function delete_nonce()
  {
    // Unless the request explicitly requests that the nonce should persist, delete it as a write nonce is
    // one time only. The exception to this is when a submission contains images which are sent afterwards,
    // in which case the last image will delete the nonce
    if (!array_key_exists('persist_auth', $_REQUEST) || $_REQUEST['persist_auth']!='true') {
      if (array_key_exists('nonce', $_REQUEST))
      {
        $nonce = $_REQUEST['nonce'];
        $this->cache->delete($nonce);
      }
    }
  }

  /**
  * Generic method to handle a request for data or a report. Depends on the sub-class
  * implementing a read_data method.
  */
  protected function handle_request()
  {
    // Authenticate for a 'read' parameter
    kohana::log('debug', 'Requesting data from Warehouse');
    kohana::log('debug', print_r($_REQUEST, true));
    $this->authenticate('read');
    // return data will return an assoc array containing records and/or parameterRequest.
    $records=$this->read_data();
    $mode = $this->get_output_mode();
    $responseStruct = $this->get_response_structure($records);
    switch ($mode)
    {
      case 'json':
        $a =  json_encode($responseStruct);
        if (array_key_exists('callback', $_REQUEST))
        {
          $a = $_REQUEST['callback']."(".$a.")";
          $this->content_type = 'Content-Type: application/javascript';
        } else {
          $this->content_type = 'Content-Type: application/json';
        }
        $this->response = $a;
        break;
      case 'xml':
        if (array_key_exists('xsl', $_REQUEST))
        {
          $xsl = $_REQUEST['xsl'];
          if (!strpos($xsl, '/'))
          // xsl is not a fully qualified path, so point it to the media folder.
          $xsl = url::base().'media/services/stylesheets/'.$xsl;
        }
        else
        {
          $xsl = '';
        }
        $this->response = $this->xml_encode($responseStruct, $xsl, TRUE);
        $this->content_type = 'Content-Type: text/xml';
        break;
      case 'csv':
        $this->response =  $this->csv_encode($records);
        $this->content_type = 'Content-Type: text/comma-separated-values';
        break;
      default:
        // Code to load from a view
        if (file_exists('views',"services/data/$entity/$mode"))
        {
          $this->response = $this->view_encode($records, View::factory("services/data/$entity/$mode"));
        }
        else
        {
          throw new ServiceError("$this->entity data cannot be output using mode $mode.");
        }
    }
  }
  
  /** 
   * By default, a service request returns the records only. This can be controlled by the GET or POST parameters
   * wantRecords (default 1), wantColumns (default 0), wantCount (default 0) and wantParameters (default 0). If there is 
   * only one of these set to true, then the requested structure is returned alone. Otherwise the structure returned is 
   * 'records' => $records, 'columns' => $this->view_columns, 'count' => n.
   * Note that if the report parameters are incomplete, then the response will always be just the 
   * parameter request. 
   */
  protected function get_response_structure($data) {
    $wantRecords = !isset($_REQUEST['wantRecords']) || $_REQUEST['wantRecords']==='0';
    $wantColumns = isset($_REQUEST['wantColumns']) && $_REQUEST['wantColumns']==='1';
    $wantCount = isset($_REQUEST['wantCount']) && $_REQUEST['wantCount']==='1';
    $wantParameters = (isset($_REQUEST['wantParameters']) && $_REQUEST['wantParameters']==='1')
      || (isset($_REQUEST['wantRecords']) && $_REQUEST['wantRecords']==='1' && !isset($data['records']));
    $array = array();
    if ($wantRecords && isset($data['records'])) 
      $array['records'] = $data['records'];
    if ($wantColumns && isset($this->view_columns)) 
      $array['columns'] = $this->view_columns;
    if ($wantParameters && isset($data['parameterRequest'])) 
      $array['parameterRequest'] = $data['parameterRequest'];
    if ($wantCount) {
      $count = $this->record_count();
      if ($count!==false)
        $array['count']=$count;
    }
    // if only returning records, simplify the array down to just return the list of records rather than the full structure
    if (count($array)===1 && isset($array['records']))
      return array_pop($array);
    else
      return $array;
  }
  
  /**
   * Default implementation of method to get the record count. Must be implemented in subclasses in order to get the 
   * count and therefore enable pagination.
   */
  protected function record_count() {
    return false;
  }

  /**
  * Encode the results of a query array as a csv string
  */
  protected function csv_encode($array)
  {
    // Get the column titles in the first row
    if(!is_array($array) || !isset($array['records']) || !is_array($array['records']) || count($array['records']) == 0)
      return '';
    $headers = array_keys($array['records'][0]);
    if(isset($this->view_columns)){
      $newheaders = array();
      foreach ($headers as $header) {
        if(isset($this->view_columns[$header])){
          if(isset($this->view_columns[$header]['display'])){
            $newheader = $this->view_columns[$header]['display'];
          } else {
            $newheader = $header;
          }
          if(!isset($this->view_columns[$header]['visible']) || $this->view_columns[$header]['visible'] !== 'false'){
            $newheaders[] = $newheader;
          }
        } else {
          $newheaders[] = $header;
        }
      }
      $headers = $newheaders;
    }
    $result = $this->get_csv($headers);
    foreach ($array['records'] as $row) {
      if(isset($this->view_columns)){
        $newrow = array();
        foreach ($row as $key => $value) {
          if(isset($this->view_columns[$key])){
             if(!isset($this->view_columns[$key]['visible']) || $this->view_columns[$key]['visible'] !== 'false'){
              $newrow[] = $value;
            }
          } else {
            $newrow[] = $value;
          }
        }
        $row = $newrow;
      }
      $result .= $this->get_csv(array_values($row));
    }
    return $result;
  }

  /**
  * Return a line of CSV from an array. This is instead of PHP's fputcsv because that
  * function only writes straight to a file, whereas we need a string.
  */
  function get_csv($data,$delimiter=',',$enclose='"')
  {
    $newline="\r\n";
    $output = '';
    foreach ($data as $cell)
    {
      //Test if numeric
      if (!is_numeric($cell))
      {
        //Escape the enclose
        $cell = str_replace($enclose,$enclose.$enclose,$cell);
        //Not numeric enclose
        $cell = $enclose . $cell . $enclose;
      }
      if ($output=='')
      {
        $output = $cell;
      }
      else
      {
        $output.=  $delimiter . $cell;
      }
    }
    $output.=$newline;
    return $output;
  }


  /**
  * Get the results of the query using the supplied view to render each row.
  */
  protected function view_encode($array, $view)
  {
    $output = '';
    foreach ($array as $row)
    {
      $view->row= $row;
      $output .= $view->render();
    }
  }

  /**
  * Encodes an array as xml. Uses $this->entity to decide the name of the root element.
  * Recurses into the array where array values are themselves arrays. Also inserts
  * xlink paths to any foreign keys, and gets the caption of the foreign entity.
  */
  protected function xml_encode($array, $xsl, $indent=false, $recursion=0)
  {
    // Keep an array to track any elements that must be skipped. For example if an array contains
    // {person_id=>1, person=>James Brown} then the xml output for the id is <person id="1">James Brown</person>.
    // There is no need to output the person separately so it gets flagged in this array for skipping.
    $to_skip=array();
    if (!$recursion)
    {
      // if we are outputting a specific record, root is singular
      if ($this->uri->total_arguments())
      {
        $root = $this->entity;
        // We don't need to repeat the element for each record, as there is only 1.
        $array = $array[0];
      }
      else
      {
        $root = inflector::plural($this->entity);
      }
      $data = '<?xml version="1.0"?>';
      if ($xsl)
        $data .= '<?xml-stylesheet type="text/xsl" href="'.$xsl.'"?>';
      $data .= ($indent?"\r\n":'').
      "<$root xmlns:xlink=\"http://www.w3.org/1999/xlink\">".
      ($indent?"\r\n":'');
    }
    else
    {
      $data = '';
    }

    foreach ($array as $element => $value)
    {
      if (!in_array($element, $to_skip))
      {
        if ($value)
        {
          if (is_numeric($element))
          {
            $element = $this->entity;
          }
          // Check if we can provide links to the related models. $this->entity is not set for reports, where this cannot be done.
          if ((substr($element, -3)=='_id') && (array_key_exists(substr($element, 0, -3), $array)) && isset($this->entity))
          {
            // create the model on demand, because it can tell us about relationships between things, but we don't want the overhead
            // of creation when not required.
            if (!isset($this->model))
              $this->model=ORM::factory($this->entity);
            $element = substr($element, 0, -3);
            // This is a foreign key described by another field, so create an xlink path
            if (array_key_exists($element, $this->model->belongs_to))
            {
              // Belongs_to specifies a fk table that does not match the attribute name
              $fk_entity=$this->model->belongs_to[$element];
            }
            elseif ($element=='parent')
            {
              $fk_entity=$this->entity;
            } else {
              // Belongs_to specifies a fk table that matches the attribute name
              $fk_entity=$element;
            }
            $data .= ($indent?str_repeat("\t", $recursion):'');
            $data .= "<$element id=\"$value\" xlink:href=\"".url::base(TRUE)."services/data/$fk_entity/$value\">";
            $data .= $array[$element];
            // We output the associated caption element already, so add it to the list to skip
            $to_skip[count($to_skip)-1]=$element;
          }
          else
          {
            $data .= ($indent?str_repeat("\t", $recursion):'').'<'.$element.'>';
            if (is_array($value)) {
              $data .= ($indent?"\r\n":'').$this->xml_encode($value, NULL, $indent, ($recursion + 1)).($indent?str_repeat("\t", $recursion):'');
            }
            else
            {
              $data .= htmlspecialchars($value);
            }
          }
          $data .= '</'.$element.'>'.($indent?"\r\n":'');
        }
      }
    }
    if (!$recursion)
    {
      $data .= "</$root>";
    }
    return $data;
  }


}

?>
