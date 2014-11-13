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
      case 'tsv':
        $this->response =  $this->tsv_encode($records);
        $this->content_type = 'Content-Type: text/tab-separated-values';
        break;
      case 'nbn':
        $this->response =  $this->nbn_encode($records);
        $this->content_type = 'Content-Type: text/plain';
        break;
      case 'gpx': 
        $this->response = $this->gpx_encode($records, TRUE);
        $this->content_type = 'Content-Type: application/gpx+xml';
        break;
      case 'kml': // Keyhole Markup Language 
        $this->response = $this->kml_encode($records, TRUE);
        $this->content_type = 'Content-Type: application/vnd.google-earth.kml+xml';
        break;
      default:
        // Code to load from a view
        if (file_exists('views',"services/data/$entity/$mode"))
        {
          $this->response = $this->view_encode($records, View::factory("services/data/$entity/$mode"));
        }
        else
        {
          throw new EntityAccessError("$this->entity data cannot be read using mode $mode.", 1002);
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
    $wantRecords = !isset($_REQUEST['wantRecords']) || $_REQUEST['wantRecords']!=='0';
    $wantColumns = isset($_REQUEST['wantColumns']) && $_REQUEST['wantColumns']==='1';
    $wantCount = isset($_REQUEST['wantCount']) && $_REQUEST['wantCount']==='1';
    $wantParameters = (isset($_REQUEST['wantParameters']) && $_REQUEST['wantParameters']==='1')
      || ($wantRecords && !isset($data['records']));
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
    return $this->do_encode($array,'csv');
  }
  
  /**
  * Encode the results of a query array as a tsv (tab separated value) string
  */
  protected function tsv_encode($array)
  {
    return $this->do_encode($array,'tsv');
  }
  
  /**
  * Encode the results of a query array as an NBN exchange format string
  */
  protected function nbn_encode($array)
  {
    return $this->do_encode($array,'nbn');
  }
  
  /**
   * Encode an array using the supplied type of encoding.
   */
  protected function do_encode($array, $type) {
    $fn = "get_$type";
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
    $result = $this->$fn($headers);
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
      $result .= $this->$fn(array_values($row));
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
   * Return a line of TSV (tab separated values) from an array.
   * IANA compliant: no tabs allowed in the fields, so we replace any.
   */
  function get_tsv($data,$delimiter="\t",$replace=' ')
  {
    $newline="\r\n";
    $output = '';
    foreach ($data as $idx => $cell)
    {
      // replace all delimiter values with a dummy
      $output .=  ($idx === 0 ? '' : $delimiter) . preg_replace('/\s+/', $replace, $cell);
    }
    $output.=$newline;
    return $output;
  }
  
  /**
  * Return a line of NBN exchange format data from an array. 
  */
  function get_nbn($data)
  {
    $newline="\r\n";
    $output = '';
    foreach ($data as $cell)
    {
      // NBN file format does not allow new lines or tabs in any cells. So replace with spaces.
      $cell = str_replace(array("\n","\r","\t"),array(' ',' ','  '),$cell);
      if ($output=='')
      {
        $output = $cell;
      }
      else
      {
        $output.=  "\t" . $cell;
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

  /**
   * Encodes an array as kml - fixed format XML style.
   * Uses $this->entity to decide the name of the root element.
   * Recurses into the array where array values are themselves arrays. Also inserts
   * xlink paths to any foreign keys, and gets the caption of the foreign entity.
   */
  protected function kml_encode($array, $indent=false)
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
    $data = '<?xml version="1.0"?>'.($indent ? "\r\n" : '').
            '<kml xmlns="http://www.opengis.net/kml/2.2">'.($indent ? "\r\n\t" : '').
            '<Document xmlns:atom="http://purl.org/atom/ns#">'.($indent ? "\r\n\t\t" : '').
            '<name>'.$root.'</name>'.($indent ? "\r\n\t\t" : '').
            '<Style id="s_ylw-pushpin_h1">'.($indent ? "\r\n\t\t\t" : '').
            '<IconStyle>'.($indent ? "\r\n\t\t\t\t" : '').
            '<scale>1.3</scale>'.($indent ? "\r\n\t\t\t\t" : '').
            '<Icon>'.($indent ? "\r\n\t\t\t\t\t\t" : '').
            '<href>http://maps.google.com/mapfiles/kml/pushpin/ylw-pushpin.png</href>'.($indent ? "\r\n\t\t\t\t" : '').
            '</Icon>'.($indent ? "\r\n\t\t\t\t" : '').
            '<hotSpot x="20" y="2" xunits="pixels" yunits="pixels"/>'.($indent ? "\r\n\t\t\t" : '').
            '</IconStyle>'.($indent ? "\r\n\t\t\t" : '').
            '<LineStyle>'.($indent ? "\r\n\t\t\t\t" : '').
            '<color>ffff7700</color>'.($indent ? "\r\n\t\t\t\t" : '').
            '<width>4</width>'.($indent ? "\r\n\t\t\t" : '').
            '</LineStyle>'.($indent ? "\r\n\t\t\t" : '').
            '<PolyStyle>'.($indent ? "\r\n\t\t\t\t" : '').
            '<color>19ff7755</color>'.($indent ? "\r\n\t\t\t" : '').
            '</PolyStyle>'.($indent ? "\r\n\t\t" : '').
            '</Style>'.($indent ? "\r\n\t\t" : '').
            '<Style id="s_ylw-pushpin">'.($indent ? "\r\n\t\t\t" : '').
            '<IconStyle>'.($indent ? "\r\n\t\t\t\t" : '').
            '<scale>1.1</scale>'.($indent ? "\r\n\t\t\t\t" : '').
            '<Icon>'.($indent ? "\r\n\t\t\t\t\t\t" : '').
            '<href>http://maps.google.com/mapfiles/kml/pushpin/ylw-pushpin.png</href>'.($indent ? "\r\n\t\t\t\t" : '').
            '</Icon>'.($indent ? "\r\n\t\t\t\t" : '').
            '<hotSpot x="20" y="2" xunits="pixels" yunits="pixels"/>'.($indent ? "\r\n\t\t\t" : '').
            '</IconStyle>'.($indent ? "\r\n\t\t\t" : '').
            '<LineStyle>'.($indent ? "\r\n\t\t\t\t" : '').
            '<color>ffff0000</color>'.($indent ? "\r\n\t\t\t\t" : '').
            '<width>3</width>'.($indent ? "\r\n\t\t\t" : '').
            '</LineStyle>'.($indent ? "\r\n\t\t\t" : '').
            '<PolyStyle>'.($indent ? "\r\n\t\t\t\t" : '').
            '<color>19ff0055</color>'.($indent ? "\r\n\t\t\t" : '').
            '</PolyStyle>'.($indent ? "\r\n\t\t" : '').
            '</Style>'.($indent ? "\r\n\t\t" : '').
            '<StyleMap id="m_ylw-pushpin">'.($indent ? "\r\n\t\t\t" : '').
		        '<Pair>'.($indent ? "\r\n\t\t\t\t" : '').
			      '<key>normal</key>'.($indent ? "\r\n\t\t\t\t" : '').
			      '<styleUrl>#s_ylw-pushpin</styleUrl>'.($indent ? "\r\n\t\t\t" : '').
		        '</Pair>'.($indent ? "\r\n\t\t\t" : '').
		        '<Pair>'.($indent ? "\r\n\t\t\t\t" : '').
			      '<key>highlight</key>'.($indent ? "\r\n\t\t\t\t" : '').
			      '<styleUrl>#s_ylw-pushpin_hl</styleUrl>'.($indent ? "\r\n\t\t\t" : '').
		        '</Pair>'.($indent ? "\r\n\t\t" : '').
	          '</StyleMap>'.($indent ? "\r\n" : '');
    $recordNum = 1;
    foreach ($array["records"] as $record)
    {
      $data .= $this->kml_encode_array($recordNum, $root, $record, $indent, 2);
      $recordNum++;
    }

    $data .= ($indent?"\t":'').'</Document>'.($indent?"\r\n":'')."</kml>";
    return $data;
  }

  /**
   * Encodes an array element as kml - fixed format XML style.
   */
  protected function kml_encode_array($recordNum, $root, $array, $indent, $recursion)
  {
  	// Keep an array to track any elements that must be skipped - i.e. geometries, names, dates.
  	$to_skip=array();
    $data = ($indent?str_repeat("\t", $recursion):'').'<Placemark>'.($indent ? "\r\n" : '');
    // identify name
    $name = $root.'.'.(array_key_exists('id',$array) ? $array['id'] : $recordNum); // default if no name field in record
    if (!empty($array['name'])) {
      $name = $array['name'];
      $to_skip[]='name';
    } elseif (!empty($array['location_name'])) {
      $name = $array['location_name'];
      $to_skip[]='location_name';
    }
    $data .= ($indent?str_repeat("\t", 1+$recursion):'').'<name>'.htmlspecialchars($name).'</name>'.($indent ? "\r\n" : '');
    // identify date
    if(array_key_exists('date',$array)){
      $data .= ($indent?str_repeat("\t", 1+$recursion):'').
               '<TimeStamp>'.($indent ? "\r\n".str_repeat("\t", 2+$recursion) : '').
               '<when>'. htmlspecialchars($array['date']).'</when>'.($indent ? "\r\n".str_repeat("\t", 1+$recursion) : '').
               '</TimeStamp>'.($indent ? "\r\n".str_repeat("\t", 1+$recursion) : '').
               '<styleUrl>#m_ylw-pushpin</styleUrl>'.($indent ? "\r\n" : '');
//      $to_skip[]='date';
    }
    // identify geometry
    // The geometry in KML must be long/lat decimal degrees (WGS84, EPSG:4326).
    // We assume that the geometry provided to us is in this format:
    // this currently precludes the use of direct table data download by kml as the views for these do a straight
    // st_astext, which displays the internal Postgis system used. Without explicit data of which system the data is being
    // delivered in, it is impossible to automatically convert to EPSG:4326.
    $geoms = array();
    $numGeoms = (array_key_exists('geom',$array) ? 1 : 0);
    foreach ($array as $element => $value)
      if (substr($element, -5)=='_geom') $numGeoms++;
    if(array_key_exists('geom',$array)){
      if(($extractGeom = $this->wkt_to_kml($array['geom'],$indent,($numGeoms>1 ? 2 : 1)+$recursion)) !== false){
        $geoms[] = $extractGeom;
        $to_skip[] = 'geom';
      }
    }
    foreach ($array as $element => $value){
      if (substr($element, -5)=='_geom') {
        if(($extractGeom = $this->wkt_to_kml($value,$indent,($numGeoms>1 ? 2 : 1)+$recursion)) !== false){
          $to_skip[] = $element;
          $geoms[] = $extractGeom;
        }
      }
    }
    $geoms = array_unique($geoms);
    if($numGeoms==1 && count($geoms)==1) {
      $data .= $geoms[0];
    }  else if(count($geoms)>0) {
      $data .= ($indent ? str_repeat("\t", 1+$recursion) : '').'<MultiGeometry>'.($indent ? "\r\n" : '').
               implode('',$geoms).
               ($indent ? str_repeat("\t", 1+$recursion) : '').'</MultiGeometry>'.($indent ? "\r\n" : '');
    }
    // Now deal with extra fields. These are displayed as a table in GoogleEarth
    $data .= ($indent?str_repeat("\t", 1+$recursion):'').'<ExtendedData>'.($indent ? "\r\n" : '');
    $root = url::base().'upload/';
    foreach ($array as $element => $value)
    {
      if (!in_array($element, $to_skip))
      {
        if ($value && !is_array($value))
        {
          // convert images to html
          if ($element==='images') 
            $value='<img width="300" src="'.$root.str_replace(',', '"/><img width="300" src="'.$root, $value).'"/>';
          $data .= ($indent?str_repeat("\t", 2+$recursion):'').
                   '<Data name="'.$element.'">'.($indent ? "\r\n".str_repeat("\t", 3+$recursion) : '').
                   '<value>'. htmlspecialchars($value).'</value>'.($indent ? "\r\n".str_repeat("\t", 2+$recursion) : '').
                   '</Data>'.($indent ? "\r\n" : '');
        }
      }
    }
    $data .= ($indent?str_repeat("\t", 1+$recursion):'').'</ExtendedData>'.($indent ? "\r\n" : '');
    $data .= ($indent?str_repeat("\t", $recursion):'').'</Placemark>'.($indent ? "\r\n" : '');
    return $data;
  }

  /*
   * Converts database format geometry in Well Known Text (WKT) format to KML.
   * Assummed to be EPSG:4326.
   * Recursive.
   */
  protected function wkt_to_kml($geom, $indent, $recursion){
    $geom = trim($geom);
    if(preg_match("/^POINT\((.*)\)$/i", $geom, $matches)){
      return ($indent ? str_repeat("\t", $recursion) : '').'<Point>'.
             ($indent ? "\r\n".str_repeat("\t", 1+$recursion) : '').'<coordinates>'.
             htmlspecialchars(preg_replace('/ /',',',$matches[1],1)).',0</coordinates>'.
             ($indent ? "\r\n".str_repeat("\t", $recursion) : '').'</Point>'.($indent ? "\r\n" : '');
    }
    if(preg_match("/^MULTIPOINT\((.*)\)$/i", $geom, $matches)){
      $coords = explode(',',$matches[1]); // separate into pairs
      $data = ($indent ? str_repeat("\t", $recursion) : '').'<MultiGeometry>'.($indent ? "\r\n" : '');
      foreach($coords as $coord){
        $data .= ($indent ? str_repeat("\t", 1+$recursion) : '').'<Point>'.
                 ($indent ? "\r\n".str_repeat("\t", 2+$recursion) : '').'<coordinates>'.
                 htmlspecialchars(preg_replace('/ /',',',$matches[1],1)).',0</coordinates>'.
                 ($indent ? "\r\n".str_repeat("\t", 1+$recursion) : '').'</Point>'.($indent ? "\r\n" : '');
      }
      $data .= ($indent?str_repeat("\t", $recursion):'').'</MultiGeometry>'.($indent ? "\r\n" : '');
      return $data;
    }
    if(preg_match("/^LINESTRING\((.*)\)$/i", $geom, $matches)){
      return $this->kml_line($matches[1], $indent, $recursion, "LineString");
    }
    if(preg_match("/^MULTILINESTRING\((.*)\)$/i", $geom, $matches)){
      preg_match_all("/\(([^\)]*)\)/", trim($matches[1]), $lines);
      $data = ($indent?str_repeat("\t", $recursion):'').'<MultiGeometry>'.($indent ? "\r\n" : '');
      for($ri=0; $ri<count($lines[1]); $ri++){
        $data .= $this->kml_line($lines[1][$ri], $indent, 1+$recursion, "LineString");
      }
      $data .= ($indent?str_repeat("\t", $recursion):'').'</MultiGeometry>'.($indent ? "\r\n" : '');
      return $data;
    }
    if(preg_match("/^POLYGON\((.*)\)$/i", $geom, $matches)){
      preg_match_all("/\((.*)\)/", trim($matches[1]), $rings);
      // outer (first) ring
      $data = ($indent?str_repeat("\t", $recursion):'').'<Polygon>'.
              ($indent ? "\r\n".str_repeat("\t", 1+$recursion) : '').'<outerBoundaryIs>'.($indent ? "\r\n" : '').
              $this->kml_line($rings[1][0], $indent, 2+$recursion, "LinearRing").
              ($indent ? str_repeat("\t", 1+$recursion) : '').'</outerBoundaryIs>'.($indent ? "\r\n" : '');
      // optional inner rings
      if(count($rings[1])>1){
        for($ri=1; $ri<count($rings[1]); $ri++){
          $data .= ($indent?str_repeat("\t", 1+$recursion):'').'<innerBoundaryIs>'.($indent ? "\r\n" : '').
                  $this->kml_line($rings[1][$ri], $indent, 2+$recursion, "LinearRing").
                  ($indent ? str_repeat("\t", 1+$recursion) : '').'</innerBoundaryIs>'.($indent ? "\r\n" : '');
        }
      }
      $data .= ($indent?str_repeat("\t", $recursion):'').'</Polygon>'.($indent ? "\r\n" : '');
      return $data;
    }
    if(preg_match("/^MULTIPOLYGON\((.*)\)$/i", $geom, $matches)){
      // each polygon starts and ends with double brackets.
      preg_match_all("/\((\((?:[^\)]|\),)*\))\)/", trim($matches[1]), $polygons);
      $data = ($indent?str_repeat("\t", $recursion):'').'<MultiGeometry>'.($indent ? "\r\n" : '');
      foreach($polygons[1] as $polygon){
        // each ring surrounded by brackets.
        preg_match_all("/\((.*)\)/", trim($polygon), $rings);
    	// outer (first) ring
    	$data .= ($indent?str_repeat("\t", 1+$recursion):'').'<Polygon>'.
    			($indent ? "\r\n".str_repeat("\t", 2+$recursion) : '').'<outerBoundaryIs>'.($indent ? "\r\n" : '').
    			$this->kml_line($rings[1][0], $indent, 3+$recursion, "LinearRing").
    			($indent ? str_repeat("\t", 2+$recursion) : '').'</outerBoundaryIs>'.($indent ? "\r\n" : '');
    	// optional inner rings
    	if(count($rings[1])>1){
    		for($ri=1; $ri<count($rings[1]); $ri++){
    			$data .= ($indent?str_repeat("\t", 2+$recursion):'').'<innerBoundaryIs>'.($indent ? "\r\n" : '').
    			$this->kml_line($rings[1][$ri], $indent, 3+$recursion, "LinearRing").
    			($indent ? str_repeat("\t", 2+$recursion) : '').'</innerBoundaryIs>'.($indent ? "\r\n" : '');
    		}
    	}
    	$data .= ($indent?str_repeat("\t", 1+$recursion):'').'</Polygon>'.($indent ? "\r\n" : '');
      }
      $data .= ($indent?str_repeat("\t", $recursion):'').'</MultiGeometry>'.($indent ? "\r\n" : '');
      return $data;
    }
    if(preg_match("/^GEOMETRYCOLLECTION\((.*)\)$/i", $geom, $matches)){
      $geoms = array();
      $sub = array();
      if(preg_match_all("/((?<!MULTI)POINT\([^\)]*\))/", trim($matches[1]), $points)) {
        $sub = $points[1];
      }
      if(preg_match_all("/(MULTIPOINT\([^\)]*\))/", trim($matches[1]), $multipoints)) {
        $sub = array_merge($sub,$multipoints[1]);
      }
      if(preg_match_all("/((?<!MULTI)LINESTRING\([^\)]*\))/", trim($matches[1]), $lines)) {
        $sub = array_merge($sub,$lines[1]);
      }
      if(preg_match_all("/(MULTILINESTRING\((?:[^\)]|\),)*\)\))/", trim($matches[1]), $multilines)) {
        $sub = array_merge($sub,$multilines[1]);
      }
      if(preg_match_all("/((?<!MULTI)POLYGON\(\((?:[^\)]|\),)*\)\))/", trim($matches[1]), $polygons)) {
        $sub = array_merge($sub,$polygons[1]);
      }
      if(preg_match_all("/(MULTIPOLYGON\(\(\((?:[^\)]|\),|\)\),)*\)\)\))/", trim($matches[1]), $multipolygons)) {
        $sub = array_merge($sub,$multipolygons[1]);
      }
      if(count($sub)>0) {
        foreach($sub as $geometry) {
          if(($extractGeom = $this->wkt_to_kml($geometry,$indent,1+$recursion)) !== false){
            $geoms[] = $extractGeom;
          } else return false;
        }
      } else return false;
      return ($indent ? str_repeat("\t", $recursion) : '').'<MultiGeometry>'.($indent ? "\r\n" : '').
             implode('',$geoms).
             ($indent ? str_repeat("\t", $recursion) : '').'</MultiGeometry>'.($indent ? "\r\n" : '');
    }
    return false;
  }
  
  protected function kml_line($ring, $indent, $recursion, $type){
    $coords = explode(',',$ring);
    $data = ($indent ? str_repeat("\t", $recursion) : '').
            '<'.$type.'>'.($indent ? "\r\n".str_repeat("\t", 1+$recursion) : '').
            '<coordinates>'.($indent ? "\r\n" : '');
    foreach($coords as $coord){
      $data .= ($indent ? str_repeat("\t", 2+$recursion) : '').htmlspecialchars(preg_replace('/ /',',',$coord,1)).',0'.($indent ? "\r\n" : '');
    }
    $data .= ($indent?str_repeat("\t", 1+$recursion):'').
             '</coordinates>'.($indent ? "\r\n".str_repeat("\t", $recursion) : '').
             '</'.$type.'>'.($indent ? "\r\n" : '');
    return $data;
  }
  
  /**
   * Encodes an array as gpx - fixed format XML style.
   * Uses $this->entity to decide the name of the root element.
   */
  protected function gpx_encode($array, $indent=false)
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
    $data = '<?xml version="1.0"?>'.($indent ? "\r\n" : '').
            '<gpx creator="Indicia" version="1.1">'.($indent ? "\r\n\t" : '').
            '<metadata>'.($indent ? "\r\n\t\t" : '').
            '<name>'.$root.'</name>'.($indent ? "\r\n\t\t" : '').
            '<author>Indicia</author>'.($indent ? "\r\n\t\t" : '').
            '<desc>Created by Indicia</desc>'.($indent ? "\r\n\t\t" : '').
            '<time>'.date(DATE_ATOM).'</time>'.($indent ? "\r\n\t" : '').
            '</metadata>'.($indent ? "\r\n" : '');
    $recordNum = 1;
    foreach ($array["records"] as $element => $value)
    {
      $data .= $this->gpx_encode_array($recordNum, $root, $value, $indent, 1);
      $recordNum++;
    }
  
    $data .= "</gpx>";
    return $data;
  }
  
  /**
   * Encodes an array element as GPX - fixed format XML style.
   */
  protected function gpx_encode_array($recordNum, $root, $array, $indent, $recursion)
  {
    // Keep an array to track any elements that must be skipped.
    $to_skip=array('geom');
    $data = '';
    // identify name
    $name = $root.'.'.(array_key_exists('id',$array) ? $array['id'] : $recordNum); // default if no name field in record
    if(array_key_exists('name',$array)){
      $name = $array['name'];
      $to_skip[]='name';
    } else if(array_key_exists('location_name',$array)){
      $name = $array['location_name'];
      $to_skip[]='location_name';
    }
    // for gpx date is put into description
    foreach ($array as $element => $value)
      if (substr($element, -5)=='_geom')
        $to_skip[] = $element;
    $desc = '';
    $elements = array();
    foreach ($array as $element => $value){
      if (!in_array($element, $to_skip))
        if ($value && !is_array($value))
          $elements[] = htmlspecialchars($element).' : '.htmlspecialchars($value);
    }
    $desc = count($elements)>0 ? '<desc><![CDATA['.implode("\r\n",$elements).']]></desc>' : '';
    
    // identify geometry. The geometry in GPX must be long/lat decimal degrees (WGS84, EPSG:4326). See comments in KML.
    $geoms = array();
    if(array_key_exists('geom',$array))
      if(($extractGeom = $this->wkt_to_gpx($name,$desc,$array['geom'],$indent,$recursion)) !== false)
        $geoms[] = $extractGeom;
    foreach ($array as $element => $value){
      if (substr($element, -5)=='_geom')
        if(($extractGeom = $this->wkt_to_gpx($name,$desc,$value,$indent,$recursion)) !== false)
          $geoms[] = $extractGeom;
    }
    if(count($geoms)>0)
      $data .= implode('',$geoms);
  	return $data;
  }
  
  protected function wkt_to_gpx($name, $desc, $geom, $indent, $recursion){
    $geom = trim($geom);
    $data = '';
    if(preg_match("/^POINT\((.*)\)$/i", $geom, $matches)){
      $latlong = explode(' ', $matches[1]);
      return $data.($indent ? str_repeat("\t", $recursion) : '').'<wpt lat="'.$latlong[1].'" lon="'.$latlong[0].'"><name>'.htmlspecialchars($name).'</name>'.$desc.'</wpt>'.($indent ? "\r\n" : '');
    }
    if(preg_match("/^MULTIPOINT\((.*)\)$/i", $geom, $matches)){
      $coords = explode(',',$matches[1]); // separate into pairs
      foreach($coords as $coord){
        $latlong = explode(' ', $coord);
        $data .= ($indent ? str_repeat("\t", $recursion) : '').'<wpt lat="'.$latlong[1].'" lon="'.$latlong[0].'"><name>'.htmlspecialchars($name).'</name>'.$desc.'</wpt>'.($indent ? "\r\n" : '');
      }
      return $data;
    }
    if(preg_match("/^LINESTRING\((.*)\)$/i", $geom, $matches)){
      return $this->gpx_route($name, $desc, $matches[1], $indent, $recursion, false);
    }
  	if(preg_match("/^MULTILINESTRING\((.*)\)$/i", $geom, $matches)){
      preg_match_all("/\(([^\)]*)\)/", trim($matches[1]), $lines);
  		for($ri=0; $ri<count($lines[1]); $ri++){
  			$data .= $this->gpx_route($name, $desc, $lines[1][$ri], $indent, $recursion, false);
  		}
  		return $data;
  	}
  	if(preg_match("/^POLYGON\((.*)\)$/i", $geom, $matches)){
      // Polygons are represented as a route of the outside perimeter
      preg_match_all("/\((.*)\)/", trim($matches[1]), $rings);
      // outer (first) ring
      $data .= $this->gpx_route($name, $desc, $rings[1][0], $indent, $recursion, true);
      return $data;
    }
    if(preg_match("/^MULTIPOLYGON\((.*)\)$/i", $geom, $matches)){
     // Polygons are represented as a route of the outside perimeter
      // each polygon starts and ends with double brackets.
      preg_match_all("/\((\((?:[^\)]|\),)*\))\)/", trim($matches[1]), $polygons, true);
      foreach($polygons[1] as $polygon){
        // each ring surrounded by brackets.
        preg_match_all("/\((.*)\)/", trim($polygon), $rings);
        // outer (first) ring
        $data .= $this->gpx_route($name, $desc, $rings[1][0], $indent, $recursion, true);
      }
      return $data;
    }
    if(preg_match("/^GEOMETRYCOLLECTION\((.*)\)$/i", $geom, $matches)){
      $geoms = array();
  		$sub = array();
  		if(preg_match_all("/((?<!MULTI)POINT\([^\)]*\))/", trim($matches[1]), $points)) {
  			$sub = $points[1];
  		}
  		if(preg_match_all("/(MULTIPOINT\([^\)]*\))/", trim($matches[1]), $multipoints)) {
  			$sub = array_merge($sub,$multipoints[1]);
  		}
  		if(preg_match_all("/((?<!MULTI)LINESTRING\([^\)]*\))/", trim($matches[1]), $lines)) {
  			$sub = array_merge($sub,$lines[1]);
  		}
  		if(preg_match_all("/(MULTILINESTRING\((?:[^\)]|\),)*\)\))/", trim($matches[1]), $multilines)) {
  			$sub = array_merge($sub,$multilines[1]);
  		}
  		if(preg_match_all("/((?<!MULTI)POLYGON\(\((?:[^\)]|\),)*\)\))/", trim($matches[1]), $polygons)) {
  			$sub = array_merge($sub,$polygons[1]);
  		}
  		if(preg_match_all("/(MULTIPOLYGON\(\(\((?:[^\)]|\),|\)\),)*\)\)\))/", trim($matches[1]), $multipolygons)) {
  			$sub = array_merge($sub,$multipolygons[1]);
  		}
  		if(count($sub)>0) {
  			foreach($sub as $geometry) {
  				if(($extractGeom = $this->wkt_to_gpx($name,$desc,$geometry,$indent,1+$recursion)) !== false){
  					$geoms[] = $extractGeom;
  				};
  			}
  		}
  		if(count($geoms)>0) return $data.implode('',$geoms);
  		return '';
  	}
  	return false;
  }
  
  protected function gpx_route($name, $desc, $ring, $indent, $recursion, $closed){
    $coords = explode(',',$ring);
    $numCoords = count($coords);
    $data = ($indent ? str_repeat("\t", $recursion) : '').'<rte>'.($indent ? "\r\n" : '');
    $data .= ($indent ? str_repeat("\t", 1+$recursion) : '').'<name>'.htmlspecialchars($name).'</name>'.$desc.($indent ? "\r\n" : '');
    foreach($coords as $i => $coord){
      $latlong = explode(' ', $coord);
      $data .=  ($indent ? str_repeat("\t", 1+$recursion) : '').'<rtept lat="'.$latlong[1].'" lon="'.$latlong[0].'"><name>'.($closed && $i == $numCoords-1 ? "" : htmlspecialchars($name)." (".(1+$i).")").'</name></rtept>'.($indent ? "\r\n" : '');
  	}
    $data .= ($indent ? str_repeat("\t", $recursion) : '').'</rte>'.($indent ? "\r\n" : '');
  	return $data;
  }
}

?>
