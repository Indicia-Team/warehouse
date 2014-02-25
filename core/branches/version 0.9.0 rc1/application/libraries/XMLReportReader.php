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
 * @package Indicia
 * @subpackage Libraries
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link    http://code.google.com/p/indicia/
 */

/**
* The report reader encapsulates logic for reading reports from a number of sources, and opens up * report
* methods in a transparent way to the report controller.
*/
class XMLReportReader_Core implements ReportReader
{
  public $columns = array();
  public $defaultParamValues = array();
  private $name;
  private $title;
  private $description;
  private $row_class;
  private $query;
  private $countQuery=null;
  private $field_sql;
  private $order_by;
  private $params = array();
  private $tables = array();
  private $attributes = array();
  private $automagic = false;
  private $vagueDateProcessing = 'true';
  private $download = 'OFF';
  private $surveyParam='survey_id';
  private $websiteFilterField = 'w.id';
  private $trainingFilterField = 'o.training';
  private $createdByField;
  
  /**
   * @var boolean Identify if we have got SQL defined in the columns array. If so we are able to auto-generate the
   * sql for the columns list.
   */
  private $hasColumnsSql = false;
  
  /**
   * @var array List of column definitions that have data type and sql defined so therefore allow filtering.
   */
  public $filterableColumns = array();

  /**
   * @var boolean Identify if we have got SQL defined for aggregated fields. If so we need to implement a group by for
   * the other fields.
   */
  private $hasAggregates = false;
  
  /** 
   * @var boolean Track if this report supports the standard set of parameters.
   */
  private $hasStandardParams = false;

  /**
   * Returns a simple array containing the title and description of a report. Static so you don't have to load the full report object to get this
   * information.
   */
  public static function loadMetadata($report) {
    $reader = new XMLReader();
    if ($reader->open($report)===false)
      throw new Exception("Report $report could not be opened.");
    $metadata = array();
    while($reader->read()) {
      if ($reader->nodeType==XMLREADER::ELEMENT && $reader->name=='report') {
        $metadata['title'] = $reader->getAttribute('title');
        $metadata['description'] = $reader->getAttribute('description');
        break;
      }
    }
    $reader->close();
    return $metadata;
  }

  /**
  * Constructs a reader for the specified report.
  * @param string $report Report file path
  * @param array $websiteIds List of websites to include data for
  * @param string $sharing Set to reporting, verification, moderation, peer_review, data_flow or me (=user's data)
  * depending on the type of data from other websites to include in this report.
  */
  public function __construct($report, $websiteIds, $sharing='reporting')
  {
    Kohana::log('debug', "Constructing XMLReportReader for report $report.");
    try
    {
      $a = explode('/', $report);
      $this->name = $a[count($a)-1];
      $reader = new XMLReader();
      $reader->open($report);
      while($reader->read())
      {
        switch($reader->nodeType)
        {
          case (XMLREADER::ELEMENT):
            switch ($reader->name)
              {
              case 'report':
                $this->title = $reader->getAttribute('title');
                $this->description = $reader->getAttribute('description');
                $this->row_class = $reader->getAttribute('row_class');
                break;
              case 'query':
                $this->websiteFilterField = $reader->getAttribute('website_filter_field');
                if ($this->websiteFilterField===null)
                  // default field name for filtering against websites
                  $this->websiteFilterField = 'w.id';
                $this->trainingFilterField = $reader->getAttribute('training_filter_field');
                if ($this->trainingFilterField===null)
                  // default field name for filtering training records
                  $this->trainingFilterField = 'o.training';
                if (!$this->createdByField = $reader->getAttribute('created_by_field'))
                  // default field name for filtering the user ID that created the record
                  $this->createdByField = 'o.created_by_id';
                if (!$this->samples_id_field = $reader->getAttribute('samples_id_field'))
                  // default table alias for the samples table, so we can join to the id
                  $this->samples_id_field = 's.id';
                if (!$this->occurrences_id_field = $reader->getAttribute('occurrences_id_field'))
                  // default table alias for the occurrences table, so we can join to the id
                  $this->occurrences_id_field = 'o.id';
                if (!$this->locations_id_field = $reader->getAttribute('locations_id_field'))
                  // default table alias for the locations table, so we can join to the id
                  $this->locations_id_field = 'l.id';
                if (!$this->people_id_field = $reader->getAttribute('people_id_field'))
                  // default table alias for the people table, so we can join to the id
                  $this->people_id_field = 'p.id';
                // load the standard set of parameters for consistent filtering of reports?
                if ($reader->getAttribute('standard_params')!==null)
                  $this->hasStandardParams=true;
                $reader->read();
                $this->query = $reader->value;
                break;
              case 'field_sql':
                $reader->read();
                $field_sql = $reader->value;
                // drop a marker in so we can insert custom attr fields later
                $field_sql .= '#fields#';
                break;
              case 'order_by':
                $reader->read();
                $this->order_by[] = $reader->value;
                break;
              case 'param':
                $this->mergeParam($reader->getAttribute('name'), $reader);
                break;
              case 'column':
                $this->mergeXmlColumn($reader);
                break;
              case 'table':
                $this->automagic = true;
                $this->setTable(
                    $reader->getAttribute('tablename'),
                    $reader->getAttribute('where'));
                break;
              case 'subTable':
                $this->setSubTable(
                    $reader->getAttribute('tablename'),
                    $reader->getAttribute('parentKey'),
                    $reader->getAttribute('tableKey'),
                    $reader->getAttribute('join'),
                    $reader->getAttribute('where'));
                break;
              case 'tabColumn':
                 $this->mergeTabColumn(
                    $reader->getAttribute('name'),
                    $reader->getAttribute('func'),
                    $reader->getAttribute('display'),
                    $reader->getAttribute('style'),
                    $reader->getAttribute('feature_style'),
                    $reader->getAttribute('class'),
                    $reader->getAttribute('visible'),
                    false
                    );
                break;
              case 'attributes':
                $this->setAttributes(
                    $reader->getAttribute('where'),
                    $reader->getAttribute('separator'),
                    $reader->getAttribute('hideVagueDateFields'),// determines whether to hide the main vague date fields for attributes.
                    $reader->getAttribute('meaningIdLanguage'));//if not set, lookup lists use term_id. If set, look up lists use meaning_id, with value either 'preferred' or the 3 letter iso language to use.
                break;
              case 'vagueDate': // This switches off vague date processing.
                $this->vagueDateProcessing = $reader->getAttribute('enableProcessing');
                break;
              case 'download': // This enables download processing.. potentially dangerous as updates DB.
                $this->setDownload($reader->getAttribute('mode'));
                break;
              case 'mergeTabColumn':
                 $this->setMergeTabColumn(
                    $reader->getAttribute('name'),
                    $reader->getAttribute('tablename'),
                    $reader->getAttribute('separator'),
                    $reader->getAttribute('where'),
                    $reader->getAttribute('display'),
                    $reader->getAttribute('visible'));
                break;
              }
              break;
          case (XMLReader::END_ELEMENT):
            switch ($reader->name)
              {
                case 'subTable':
                  $this->tableIndex=$this->tables[$this->tableIndex]['parent'];
                break;
              }
             break;
        }
      }
      $reader->close();
      // Add a token to mark where additional filters can insert in the WHERE clause.
      if ($this->query && strpos($this->query, '#filters#')===false) {
        if (strpos($this->query, '#order_by#')!==false)
          $this->query = str_replace('#order_by#', "#filters#\n#order_by#", $this->query);
        else
          $this->query .= '#filters#';
      }
      if ($this->hasColumnsSql) {
        // column sql is defined in the list of column elements, so autogenerate the query.
        $this->autogenColumns();
        if ($this->hasAggregates) {
          $this->buildGroupBy();
        }
      } elseif ($this->query) {
        // sort out the field list or use count(*) for the count query. Do this at the end so the queries are
        // otherwise the same.
        if (!empty($field_sql)) {
          $this->countQuery = str_replace('#field_sql#', ' count(*) ', $this->query);
          $this->query = str_replace('#field_sql#', $field_sql, $this->query);
        }
        // column SQL is part of the SQL statement, or defined in a field_sql element.
        // Get any extra columns from the query data. Do this at the end so that the specified columns appear first, followed by any unspecified ones.
        $this->inferFromQuery();
      }
    }
    catch (Exception $e)
    {
      throw new Exception("Report: $report\n".$e->getMessage());
    }
  }
  
  /**
   * Apply the website and sharing related filters to the query.
   */
  public function applyPrivilegesFilters(&$query, $websiteIds, $training, $sharing, $userId) {
    if ($websiteIds) {
      if (in_array('', $websiteIds)) {
        foreach($websiteIds as $key=>$value) {
          if (empty($value))
            unset($websiteIds[$key]);
        }
      }
      $idList = implode($websiteIds, ',');
      // query can either pull in the filter or just the list of website ids.
      $filter = empty($this->websiteFilterField) ? "1=1" : "({$this->websiteFilterField} in ($idList) or {$this->websiteFilterField} is null)";
      $query = str_replace(array('#website_filter#', '#website_ids#'), array($filter, $idList), $query);
    } else
      // use a dummy filter to return all websites if core admin
      $query = str_replace('#website_filter#', '1=1', $query);
    if (!empty($this->trainingFilterField)) {
      if ($training==='true')
        $query = str_replace('#sharing_filter#', "({$this->trainingFilterField}=true OR {$this->trainingFilterField} IS NULL) AND #sharing_filter#", $query); 
      else 
        $query = str_replace('#sharing_filter#', "({$this->trainingFilterField}=false OR {$this->trainingFilterField} IS NULL) AND #sharing_filter#", $query); 
    }
    // an alternative way to inform a query about training mode....
    $query = str_replace('#training#', $training, $query); 
    // select the appropriate type of sharing arrangement (i.e. are we reporting, verifying, moderating etc?)
    if ($sharing==='me' && empty($userId))
      // revert to website type sharing if we have no known user Id.
      $sharing='website';
    if ($sharing==='me')
      // my data only so use the UserId if we have it. Note join to system is just a dummy to keep syntax correct.
      $query = str_replace(array('#agreements_join#','#sharing_filter#'), array('', "{$this->createdByField}=$userId"), $query);
    elseif (isset($idList)) {
      if ($sharing==='website') 
        // this website only
        $query = str_replace(
          array('#agreements_join#','#sharing_filter#'), 
          array('', "{$this->websiteFilterField} in ($idList)"), 
        $query);
      elseif (!empty($this->websiteFilterField)) {
        // implement the appropriate sharing agreement across websites
        $sharedWebsiteIdList = self::getSharedWebsiteList($websiteIds, $sharing);
        // add a join to users so we can check their privacy preferences. This does not apply if record input
        // on this website.
        $agreementsJoin = "JOIN users privacyusers ON privacyusers.id=".$this->createdByField;
        $query = str_replace(array('#agreements_join#','#sharing_filter#','#sharing_website_ids#'), 
            array($agreementsJoin, 
            "({$this->websiteFilterField} in ($idList) OR privacyusers.allow_share_for_$sharing=true OR privacyusers.allow_share_for_$sharing IS NULL)\n".
            "AND {$this->websiteFilterField} in ($sharedWebsiteIdList)", $sharedWebsiteIdList), $query);
      }
    }
    $query = str_replace('#sharing#', $sharing, $query);
    // cleanup some of the tokens in the SQL if they haven't already been done
    $query = str_replace(array('#agreements_join#','#sharing_filter#'), array('','1=1'), $query);
  }
  
  /**
  * A cached lookup of the websites that are available for a certain sharing mode.
  *
  * Only bother to cache the lookup if there is only 1 website (i.e. we are running a 
  * report from a client website or the warehouse user can only see 1 website). Otherwise
  * there are too many possibilities to be worth it. This is mainly to speed up client
  * website reporting anyway.
  */ 
  private function getSharedWebsiteList($websiteIds, $sharing) {
    if (count($websiteIds ===1)) {
      $cacheId = 'website-shares-'.implode('', $websiteIds)."-$sharing";
      $cache = Cache::instance();
      if ($cached = $cache->get($cacheId)) 
        return $cached;
    }
    $db = new Database();
    $qry = $db->select('to_website_id')
        ->from('index_websites_website_agreements')
        ->where("receive_for_$sharing", 't')
        ->in('from_website_id', $websiteIds)
        ->get()->result();
    $ids = array();
    foreach($qry as $row) {
      $ids[] = $row->to_website_id;
    }
    $r = implode(',', $ids);
    $cache->set($cacheId, $r); 
    return $r;
  }

  /**
   * Use the sql attributes from the list of columns to auto generate the columns SQL.
   */
  private function autogenColumns() {
    $sql = array();
    $distinctSql = array();
    $countSql = array();
    foreach ($this->columns as $col=>$def) {
      if (isset($def['sql'])) {
        if (!isset($def['on_demand']) || $def['on_demand']!=="true")
          $sql[] = $def['sql'] . ' as ' . $col;
        if (isset($def['distincton']) && $def['distincton']=='true') {
          $distinctSql[] = $def['internal_sql'];
          // in_count lets the xml file exclude distinct on columns from the count query
          if (!isset($def['in_count']) || $def['in_count']=='true') {
            $countSql[] = $def['internal_sql'];
          }
        } else {
          // if the column is not distinct on, then it defaults to not in the count
          if (isset($def['in_count']) && $def['in_count']=='true') {
            $countSql[] = $def['internal_sql'];
          }
        }
      }
    }
    if (count($distinctSql)>0) {
      $distincton = ' distinct on ('.implode(', ', $distinctSql).') ';
    } else {
      $distincton = '';
    }
    if (count($countSql)>1) {
      $this->countQuery = str_replace('#columns#', ' count(distinct coalesce(' . implode(", '') || coalesce(", $countSql) . ", '')) ", $this->query);
    } 
    elseif (count($countSql)===1) {
      $this->countQuery = str_replace('#columns#', ' count(distinct ' . $countSql[0] . ') ', $this->query);
    }
    else {
      $this->countQuery = str_replace('#columns#', ' count(*) ', $this->query);
    }
    // merge this back into the query. Note we drop in a #fields# tag so that the query processor knows where to
    // add custom attribute fields.
    $this->query = str_replace('#columns#', $distincton . implode(",\n", $sql) . '#fields#', $this->query);
  }

  /**
   * If there are columns marked with the aggregate attribute, then we can build a group by clause
   * using all the non-aggregate column sql.
   * This is done dynamically leaving us with the ability to automatically extend the group by field list,
   * e.g. if some custom attribute columns have been added to the report.
   */
  private function buildGroupBy() {
    $sql = array();
    foreach ($this->columns as $col=>$def) {
      if (isset($def['internal_sql']) 
          && (!isset($def['aggregate']) || $def['aggregate']!='true')
          && (!isset($def['on_demand']) || $def['on_demand']!='true')) {
        $sql[] = $def['internal_sql'];
      }
    }
    // Add the non-aggregated fields to the end of the query. Leave a token so that the query processor
    // can add more, e.g. if there are custom attribute columns, and also has a suitable place for a HAVING clause.
    if (count($sql)>0)
      $this->query .= "\nGROUP BY " . implode(', ', $sql) . '#group_bys#';
  }

  /**
  * <p> Returns the title of the report. </p>
  */
  public function getTitle()
  {
    return $this->title;
  }

  /**
  * <p> Returns the description of the report. </p>
  */
  public function getDescription()
  {
    return $this->description;
  }

  /**
  * <p> Returns the query specified. </p>
  */
  public function getQuery()
  {
    if ( $this->automagic == false) {
      return $this->query;
    }
    $query = "SELECT ";
    $j=0;
    for($i = 0; $i < count($this->tables); $i++){
    // In download mode make sure that the occurrences id is in the list

      foreach($this->tables[$i]['columns'] as $column){
        if ($j != 0) $query .= ",";
        if ($column['func']=='') {
          $query .= " lt".$i.".".$column['name']." AS lt".$i."_".$column['name'];
        } else {
          $query .= " ".preg_replace("/#parent#/", "lt".$this->tables[$i]['parent'], preg_replace("/#this#/", "lt".$i, $column['func']))." AS lt".$i."_".$column['name'];
        }
        $j++;
      }
    }
    // table list
    $query .= " FROM ";
    for($i = 0; $i < count($this->tables); $i++){
      if ($i == 0) {
          $query .= $this->tables[$i]['tablename']." lt".$i;
      } else {
          if ($this->tables[$i]['join'] != null) {
            $query .= " LEFT OUTER JOIN ";
             } else {
            $query .= " INNER JOIN ";
          }
          $query .= $this->tables[$i]['tablename']." lt".$i." ON (".$this->tables[$i]['tableKey']." = ".$this->tables[$i]['parentKey'];
          if($this->tables[$i]['where'] != null) {
            $query .= " AND ".preg_replace("/#this#/", "lt".$i, $this->tables[$i]['where']);
         }
          $query .= ") ";
      }
    }
    // where list
    $previous=false;
    if($this->tables[0]['where'] != null) {
      $query .= " WHERE ".preg_replace("/#this#/", "lt0", $this->tables[0]['where']);
      $previous = true;
    }
    // when in download mode set a where clause
    // only down load records which are complete or verified, and have not been downloaded before.
    // for the final download, only download thhose records which have gone through an initial download, and hence assumed been error checked.
    if($this->download != 'OFF'){
      for($i = 0; $i < count($this->tables); $i++){
        if ($this->tables[$i]['tablename'] == "occurrences") {
          $query .= ($previous ? " AND " : " WHERE ").
            " (lt".$i.".record_status in ('C'::bpchar, 'V'::bpchar) OR '".$this->download."'::text = 'OFF'::text) ".
              " AND (lt".$i.".downloaded_flag in ('N'::bpchar, 'I'::bpchar) OR '".$this->download."'::text != 'INITIAL'::text) ".
              " AND (lt".$i.".downloaded_flag = 'I'::bpchar OR ('".$this->download."'::text != 'CONFIRM'::text AND '".$this->download."'::text != 'FINAL'::text))";
          break;
        }
      }
    }
    return $query;
  }

  public function getCountQuery()
  {
    return $this->countQuery;
  }

  /**
  * <p> Uses source-specific validation methods to check whether the report query is valid. </p>
  */
  public function isValid(){}

  /**
  * <p> Returns the order by clause for the query. </p>
  */
  public function getOrderClause()
  {
    if ($this->order_by) {
      return implode(', ', $this->order_by);
    }
  }

  /**
  * <p> Gets a list of parameters (name => array('display' => display, ...)) </p>
  */
  public function getParams()
  {
    return $this->params;
  }

  /**
  * <p> Gets a list of the columns (name => array('display' => display, 'style' => style, 'visible' => visible)) </p>
  */
  public function getColumns()
  {
    return $this->columns;
  }

  /**
  * <p> Returns a description of the report appropriate to the level specified. </p>
  */
  public function describeReport($descLevel)
  {
    switch ($descLevel)
    {
      case (ReportReader::REPORT_DESCRIPTION_BRIEF):
        return array(
            'name' => $this->name,
            'title' => $this->getTitle(),
            'row_class' => $this->getRowClass(),
            'description' => $this->getDescription());
        break;
      case (ReportReader::REPORT_DESCRIPTION_FULL):
        // Everything
        return array
        (
          'name' => $this->name,
          'title' => $this->getTitle(),
          'description' => $this->getDescription(),
          'row_class' => $this->getRowClass(),
          'columns' => $this->columns,
          'parameters' => array_diff_key($this->params, $this->defaultParamValues),
          'query' => $this->query,
          'order_by' => $this->order_by
        );
        break;
      case (ReportReader::REPORT_DESCRIPTION_DEFAULT):
      default:
        // At this report level, we include most of the useful stuff.
        return array
        (
          'name' => $this->name,
          'title' => $this->getTitle(),
          'description' => $this->getDescription(),
          'row_class' => $this->getRowClass(),
          'columns' => $this->columns,
          'parameters' => array_diff_key($this->params, $this->defaultParamValues)
        );
    }
  }

  /**
   */
  public function getAttributeDefns()
  {
     return $this->attributes;
  }

  public function getVagueDateProcessing()
  {
    return $this->vagueDateProcessing;
  }

  public function getDownloadDetails()
  {
   $thisDefn = new stdClass;
   $thisDefn->mode = $this->download;
   $thisDefn->id = 'occurrence_id';
   if($this->automagic) {
     for($i = 0; $i < count($this->tables); $i++){
      if($this->tables[$i]['tablename'] == 'occurrences'){ // Warning, will not work with multiple occurrence tables
         $thisDefn->id = "lt".$i."_id";
         break;
      }
     }
   }
   return $thisDefn;
  }
  //* PRIVATE FUNCTIONS *//

  /**
   * Returns the css class to apply to rows in the report.
   */
  private function getRowClass()
  {
    return $this->row_class;
  }
  private function buildAttributeQuery($attributes)
  {
    $parentSingular = inflector::singular($this->tables[$attributes->parentTableIndex]['tablename']);
    // This processing assumes some properties of the attribute tables - eg columns the data is stored in and deleted columns
    $query = "SELECT vt.".$parentSingular."_id as main_id,
      vt.text_value, vt.float_value, vt.int_value, vt.date_start_value, vt.date_end_value, vt.date_type_value,
      at.id, at.caption, at.data_type, at.termlist_id, at.multi_value ";
    $j=0;
    // table list
    $from = ""; // this is built from back to front, to scan up the tree of tables that are only relevent to this attribute request.
    $i = $attributes->parentTableIndex;
    while(true){
      if ($i == 0) {
          $from = $this->tables[$i]['tablename']." lt".$i.$from;
          break;
      } else {
          $from = " INNER JOIN ".$this->tables[$i]['tablename']." lt".$i.
                  " ON (".$this->tables[$i]['tableKey']." = ".$this->tables[$i]['parentKey'].
                  ($this->tables[$i]['where'] != null ? 
                      " AND ".preg_replace("/#this#/", "lt".$i, $this->tables[$i]['where']) :
                      "").") ".$from;
          $i = $this->tables[$i]['parent']; // next process the parent for this table, until we've scanned upto zero.
      }
    }
    $query .= " FROM ".$from;
    $query .= " INNER JOIN ".$parentSingular."_attribute_values vt ON (vt.".$parentSingular."_id = "." lt".$attributes->parentTableIndex.".id and vt.deleted = FALSE) ";
    $query .= " INNER JOIN ".$parentSingular."_attributes at ON (vt.".$parentSingular."_attribute_id = at.id and at.deleted = FALSE) ";
    $query .= " INNER JOIN ".$parentSingular."_attributes_websites rt ON (rt.".$parentSingular."_attribute_id = at.id and rt.deleted = FALSE and (rt.restrict_to_survey_id = #".
        $this->surveyParam."# or rt.restrict_to_survey_id is null)) ";
    // where list
    $previous=false;
    if($this->tables[0]['where'] != null) {
      $query .= " WHERE ".preg_replace("/#this#/", "lt0", $this->tables[0]['where']);
      $previous = true;
    }
    if($attributes->where != null) {
      $query .= ($previous ? " AND " : " WHERE ").$attributes->where;
    }
    $query .= " ORDER BY rt.form_structure_block_id, rt.weight, at.id, lt".$attributes->parentTableIndex.".id ";
    return $query;
  }

  /**
   * Merges a parameter into the list of parameters read for the report. Updates existing
   * ones if there is a name match.
   * @todo Review the handling of $this->surveyParam
   */
  private function mergeParam($name, $reader=null) {
    // Some parts of the code assume the survey will be identified by a parameter called survey or survey_id.
    if ($name==='survey_id' || $name==='survey')      
      $this->surveyParam = $name;
    $display = ($reader===null) ? '' : $reader->getAttribute('display');
    $type = ($reader===null) ? '' : $reader->getAttribute('datatype');
    $allow_buffer = ($reader===null) ? '' : $reader->getAttribute('allow_buffer');
    $fieldname = ($reader===null) ? '' : $reader->getAttribute('fieldname');
    $alias = ($reader===null) ? '' : $reader->getAttribute('alias');
    $emptyvalue = ($reader===null) ? '' : $reader->getAttribute('emptyvalue');
    $default = ($reader===null) ? null : $reader->getAttribute('default');
    $description = ($reader===null) ? '' : $reader->getAttribute('description');
    $query = ($reader===null) ? '' : $reader->getAttribute('query');
    $lookup_values = ($reader===null) ? '' : $reader->getAttribute('lookup_values');
    $population_call = ($reader===null) ? '' : $reader->getAttribute('population_call');
    $linked_to = ($reader===null) ? '' : $reader->getAttribute('linked_to');
    $linked_filter_field = ($reader===null) ? '' : $reader->getAttribute('linked_filter_field');
    if (array_key_exists($name, $this->params))
    {
      if ($display != '') $this->params[$name]['display'] = $display;
      if ($type != '') $this->params[$name]['datatype'] = $type;
      if ($allow_buffer != '') $this->params[$name]['allow_buffer'] = $allow_buffer;
      if ($fieldname != '') $this->params[$name]['fieldname'] = $fieldname;
      if ($alias != '') $this->params[$name]['alias'] = $alias;
      if ($emptyvalue != '') $this->params[$name]['emptyvalue'] = $emptyvalue;
      if ($default != null) $this->params[$name]['default'] = $default;
      if ($description != '') $this->params[$name]['description'] = $description;
      if ($query != '') $this->params[$name]['query'] = $query;
      if ($lookup_values != '') $this->params[$name]['lookup_values'] = $lookup_values;
      if ($population_call != '') $this->params[$name]['population_call'] = $population_call;
      if ($linked_to != '') $this->params[$name]['linked_to'] = $linked_to;
      if ($linked_filter_field != '') $this->params[$name]['linked_filter_field'] = $linked_filter_field;
    }
    else
    {
      $this->params[$name] = array(
        'datatype'=>$type,
        'allow_buffer'=>$allow_buffer,
        'fieldname'=>$fieldname,
        'alias'=>$alias,
        'emptyvalue'=>$emptyvalue,
        'default'=>$default,
        'display'=>$display,
        'description'=>$description,
        'query' => $query,
        'lookup_values' => $lookup_values,
        'population_call' => $population_call,
        'linked_to' => $linked_to,
        'linked_filter_field' => $linked_filter_field
      );
    }
    // if we have a default value, keep a list
    if (isset($this->params[$name]['default']) && $this->params[$name]['default']!==null) {
      $this->defaultParamValues[$name] = $this->params[$name]['default'];
    }
    // Does the parameter define optional join elements which are associated with specific parameter values?
    if ($reader!==null) {
      $paramXml = $reader->readInnerXML();
      if (!empty($paramXml)) {
        $reader = new XMLReader();
        $reader->XML($paramXml);
        while ($reader->read()) {
          if ($reader->nodeType==XMLREADER::ELEMENT && $reader->name=='join') {
            if (!isset($this->params[$name]['joins']))
              $this->params[$name]['joins']=array();
            $this->params[$name]['joins'][] = array(
              'value'=>$reader->getAttribute('value'),
              'operator'=>$reader->getAttribute('operator'),
              'sql'=>$reader->readString()
            );
          }
          if ($reader->nodeType==XMLREADER::ELEMENT && $reader->name=='where') {
            if (!isset($this->params[$name]['wheres']))
              $this->params[$name]['wheres']=array();
            $this->params[$name]['wheres'][] = array(
              'value'=>$reader->getAttribute('value'),
              'operator'=>$reader->getAttribute('operator'),
              'sql'=>$reader->readString()
            );
          }
        }
      }

    }
  }
  
  /**
   * If a report declares that it uses the standard set of parameters, then load them.
   */
  public function loadStandardParams(&$providedParams, $sharing) {
    if ($this->hasStandardParams) {
      // For backwards compatibility, convert a few param names...
      $this->convertDeprecatedParam($providedParams, 'location_id','location_list');
      $this->convertDeprecatedParam($providedParams, 'indexed_location_id','indexed_location_list');
      // always include the operation params, as their default might be needed even when no parameter is provided. E.g.
      // the default website_list_op param comes into effect if just a website_list is provided.
      $opParams = array(
        'occurrence_id' => array('datatype'=>'lookup', 'default'=>'', 'display'=>'ID operation',
            'description'=>'Record ID lookup operation', 'lookup_values'=>'=:is,>=:is at least,<=:is at most'
        ),
        'website_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Website IDs mode', 
            'description'=>'Include or exclude the list of websites', 'lookup_values'=>'in:Include,not in:Exclude'
        ),
        'survey_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Survey IDs mode', 
            'description'=>'Include or exclude the list of surveys', 'lookup_values'=>'in:Include,not in:Exclude'
        ),
        'input_form_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Input forms mode', 
            'description'=>'Include or exclude the list of input forms', 'lookup_values'=>'in:Include,not in:Exclude'
        ),
        'location_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Location IDs mode', 
            'description'=>'Include or exclude the list of locations', 'lookup_values'=>'in:Include,not in:Exclude'
        ),
        'indexed_location_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Indexed location IDs mode', 
            'description'=>'Include or exclude the list of indexed locations', 'lookup_values'=>'in:Include,not in:Exclude'
        ),
      );
      foreach ($opParams as $param => $cfg) {
        if (!empty($providedParams[$param]))
          $this->params["{$param}_op"] = $cfg;
        if (!empty($providedParams["{$param}_context"]))
          $this->params["{$param}_op_context"] = $cfg;
      }
      $params = array(
        'idlist' => array('datatype'=>'idlist', 'default'=>'', 'display'=>'List of IDs', 'emptyvalue'=>'', 'fieldname'=>'o.id', 'alias'=>'occurrence_id',
            'description'=>'Comma separated list of occurrence IDs to filter to'
        ),
        'searchArea' => array('datatype'=>'geometry', 'default'=>'', 'display'=>'Boundary',
            'description'=>'Boundary to search within',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"st_intersects(o.public_geom, st_geomfromtext('#searchArea#',900913))")
            )
        ),
        'occurrence_id' => array('datatype'=>'integer', 'default'=>'', 'display'=>'ID',
            'description'=>'Record ID',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.id #occurrence_id_op# #occurrence_id#")
            )
        ),
        'location_name' => array('datatype'=>'text', 'default'=>'', 'display'=>'Location name', 
            'description'=>'Name of location to filter to (contains search)',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.location_name ilike '%#location_name#%'")
            )
        ),      
        'location_list' => array('datatype'=>'integer', 'default'=>'', 'display'=>'Location IDs', 
            'description'=>'Comma separated list of location IDs',
            'joins' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"JOIN locations #alias:lfilt# on #alias:lfilt#.id #location_list_op# (#location_list#) and #alias:lfilt#.deleted=false " .
                  "and st_intersects(coalesce(#alias:lfilt#.boundary_geom, #alias:lfilt#.centroid_geom), #sample_geom_field#)")
            )
        ),
        'indexed_location_list' => array('datatype'=>'integer', 'default'=>'', 'display'=>'Location IDs (indexed)', 
            'description'=>'Comma separated list of location IDs, for locations that are indexed using the spatial index builder',
            'joins' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"JOIN index_locations_samples #alias:ils# on #alias:ils#.sample_id=o.sample_id and #alias:ils#.location_id #indexed_location_list_op# (#indexed_location_list#)")
            )
        ),
        'date_from' => array('datatype'=>'date', 'default'=>'', 'display'=>'Date from',
            'description'=>'Date of first record to include in the output',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"('#date_from#'='Click here' OR o.date_end >= CAST(COALESCE('#date_from#','1500-01-01') as date))")
            )
        ),
        'date_to' => array('datatype'=>'date', 'default'=>'', 'display'=>'Date to', 
            'description'=>'Date of last record to include in the output',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"('#date_to#'='Click here' OR o.date_start <= CAST(COALESCE('#date_to#','1500-01-01') as date))")
            )
        ),
        'date_age' => array('datatype'=>'text', 'default'=>'', 'display'=>'Date from time ago',
            'description'=>'E.g. enter "1 week" or "3 days" to define the how old records can be before they are dropped from the report.',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.date_start>now()-'#date_age#'::interval")
            )
        ),
        'input_date_from' => array('datatype'=>'date', 'default'=>'', 'display'=>'Input date from',
            'description'=>'Input date of first record to include in the output',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"('#input_date_from#'='Click here' OR o.cache_created_on >= CAST('#input_date_from#' as date))")
            )
        ),
        'input_date_to' => array('datatype'=>'date', 'default'=>'', 'display'=>'Input date to', 
            'description'=>'Input date of last record to include in the output',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"('#input_date_to#'='Click here' OR o.cache_created_on < CAST('#input_date_to#' as date)+'1 day'::interval)")
            )
        ),
        'input_date_age' => array('datatype'=>'text', 'default'=>'', 'display'=>'Input date from time ago',
            'description'=>'E.g. enter "1 week" or "3 days" to define the how long ago records can be input before they are dropped from the report.',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.cache_created_on>now()-'#input_date_age#'::interval")
            )
        ),
        'edited_date_from' => array('datatype'=>'date', 'default'=>'', 'display'=>'Last update date from',
            'description'=>'Last update date of first record to include in the output',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"('#edited_date_from#'='Click here' OR o.cache_updated_on >= CAST('#edited_date_from#' as date))")
            )
        ),
        'edited_date_to' => array('datatype'=>'date', 'default'=>'', 'display'=>'Last update date to', 
            'description'=>'Last update date of last record to include in the output',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"('#edited_date_to#'='Click here' OR o.cache_updated_on < CAST('#edited_date_to#' as date)+'1 day'::interval)")
            )
        ),
        'edited_date_age' => array('datatype'=>'text', 'default'=>'', 'display'=>'Last update date from time ago',
            'description'=>'E.g. enter "1 week" or "3 days" to define the how long ago records can be last updated before they are dropped from the report.',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.cache_updated_on>now()-'#edited_date_age#'::interval")
            )
        ),
        'verified_date_from' => array('datatype'=>'date', 'default'=>'', 'display'=>'Verification status change date from',
            'description'=>'Verification status change date of first record to include in the output',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"('#verified_date_from#'='Click here' OR o.verified_on >= CAST('#verified_date_from#' as date))")
            )
        ),
        'verified_date_to' => array('datatype'=>'date', 'default'=>'', 'display'=>'Verification status change date to', 
            'description'=>'Verification status change date of last record to include in the output',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"('#verified_date_to#'='Click here' OR o.verified_on < CAST('#verified_date_to#' as date)+'1 day'::interval)")
            )
        ),
        'verified_date_age' => array('datatype'=>'text', 'default'=>'', 'display'=>'Verification status change date from time ago',
            'description'=>'E.g. enter "1 week" or "3 days" to define the how long ago records can have last had their status changed before they are dropped from the report.',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.verified_on>now()-'#verified_date_age#'::interval")
            )
        ),
        'quality' => array('datatype'=>'lookup', 'default'=>'', 'display'=>'Quality', 
            'description'=>'Minimum quality of records to include', 
            'lookup_values'=>'=V:Verified records only,C:Recorder was certain,L:Recorder thought the record was at least likely,P:Pending verification,' .
                'T:Pending verification for trusted recorders,!R:Everything except rejected,all:Everything including rejected,D:Queried records only,'.
                'R:Rejected records only,DR:Queried or rejected records',
            'wheres' => array(
              array('value'=>'V', 'operator'=>'equal', 'sql'=>"o.record_status='V'"),
              array('value'=>'C', 'operator'=>'equal', 'sql'=>"o.record_status<>'R' and o.certainty='C'"),
              array('value'=>'L', 'operator'=>'equal', 'sql'=>"o.record_status<>'R' and o.certainty in ('C','L')"),
              array('value'=>'P', 'operator'=>'equal', 'sql'=>"o.record_status in ('C','S')"),
              array('value'=>'T', 'operator'=>'equal', 'sql'=>"o.record_status in ('C','S')"),
              array('value'=>'!R', 'operator'=>'equal', 'sql'=>"o.record_status<>'R'"),
              array('value'=>'D', 'operator'=>'equal', 'sql'=>"o.record_status='D'"),
              array('value'=>'R', 'operator'=>'equal', 'sql'=>"o.record_status='R'"),
              array('value'=>'DR', 'operator'=>'equal', 'sql'=>"o.record_status in ('R','D')"),
              // The all filter does not need any SQL
            ),
            'joins' => array(
              array('value'=>'T', 'operator'=>'equal', 'sql'=>
  "LEFT JOIN index_locations_samples #alias:ils# on #alias:ils#.sample_id=o.sample_id
  JOIN user_trusts #alias:ut# on (#alias:ut#.survey_id=o.survey_id
      OR #alias:ut#.taxon_group_id=o.taxon_group_id
      OR (#alias:ut#.location_id=#alias:ils#.location_id or #alias:ut#.location_id is null)
    )
    AND #alias:ut#.deleted=false
    AND ((o.survey_id = #alias:ut#.survey_id) or (#alias:ut#.survey_id is null and (#alias:ut#.taxon_group_id is not null or #alias:ut#.location_id is not null)))
    AND ((o.taxon_group_id = #alias:ut#.taxon_group_id) or (#alias:ut#.taxon_group_id is null and (#alias:ut#.survey_id is not null or #alias:ut#.location_id is not null)))
    AND ((#alias:ils#.location_id = #alias:ut#.location_id) OR (#alias:ut#.location_id IS NULL and (#alias:ut#.survey_id is not null or #alias:ut#.taxon_group_id is not null)))
    AND o.created_by_id = #alias:ut#.user_id")
            )
        ),
        'release_status' => array('datatype'=>'lookup', 'default'=>'R', 'display'=>'Release status',
            'description'=>'Release status of the record',
            'lookup_values'=>'R:Released,U:Unreleased because part of a project that has not yet released the records,P:Recorder has requested a precheck before release,A:All',
            'wheres' => array(
              array('value'=>'R', 'operator'=>'equal', 'sql'=>"(o.release_status='R' or o.release_status is null)"),
              array('value'=>'U', 'operator'=>'equal', 'sql'=>"(o.release_status='U' or o.release_status is null)"),
              array('value'=>'P', 'operator'=>'equal', 'sql'=>"(o.release_status='P' or o.release_status is null)"),
              // The all filter does not need any SQL
            ),
        ),
        'autochecks' => array('datatype'=>'lookup', 'default'=>'', 'display'=>'Automated checks', 
            'description'=>'Filter to only include records that have passed or failed automated checks', 
            'lookup_values'=>'N:Not filtered,F:Include only records that fail checks,P:Include only records which pass checks',
            'wheres' => array(
              array('value'=>'F', 'operator'=>'equal', 'sql'=>"o.data_cleaner_info is not null and o.data_cleaner_info<>'pass'"),
              array('value'=>'P', 'operator'=>'equal', 'sql'=>"o.data_cleaner_info = 'pass'")
            )
        ),
        'has_photos' => array('datatype'=>'boolean', 'default'=>'', 'display'=>'Photo records only',
            'description'=>'Only include records which have photos?',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.images is not null")
            )
        ),
        'user_id' => array('datatype'=>'integer', 'default'=>'', 'display'=>"Current user's warehouse ID"),
        'my_records' => array('datatype'=>'boolean', 'default'=>'', 'display'=>"Only include my records",
            'wheres' => array(
              array('value'=>'1', 'operator'=>'equal', 'sql'=>"o.created_by_id=#user_id#")
            )
        ),
        'group_id' => array('datatype'=>'integer', 'default'=>'', 'display'=>"ID of a group to filter to the members of",
            'description'=>'Specify the ID of a recording group. This filters the report to the members of the group.',
            'joins' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"join groups_users #alias:gu# on #alias:gu#.user_id=o.created_by_id and #alias:gu#.group_id=#group_id#")
            )
        ),
        'website_list' => array('datatype'=>'string', 'default'=>'', 'display'=>"Website IDs", 
            'description'=>'Comma separated list of IDs',
            'wheres' => array(
               array('value'=>'', 'operator'=>'', 'sql'=>"o.website_id #website_list_op# (#website_list#)")
            )
        ),
        'survey_list' => array('datatype'=>'string', 'default'=>'', 'display'=>"Survey IDs", 
            'description'=>'Comma separated list of IDs',
            'wheres' => array(
               array('value'=>'', 'operator'=>'', 'sql'=>"o.survey_id #survey_list_op# (#survey_list#)")
            )
        ),
        'input_form_list' => array('datatype'=>'string', 'default'=>'', 'display'=>"Input forms", 
            'description'=>'Comma separated list of input form paths',
            'wheres' => array(
               array('value'=>'', 'operator'=>'', 'sql'=>"o.input_form #input_form_list_op# (#input_form_list#)")
            )
        ),
        'taxon_group_list' => array('datatype'=>'string', 'default'=>'', 'display'=>"Taxon Group IDs", 
            'description'=>'Comma separated list of IDs',
            'wheres' => array(
               array('value'=>'', 'operator'=>'', 'sql'=>"o.taxon_group_id in (#taxon_group_list#)")
            )
        ),
        'taxa_taxon_list_list' => array('datatype'=>'string', 'default'=>'', 'display'=>"Taxa taxon list IDs", 
            'description'=>'Comma separated list of preferred IDs',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.preferred_taxa_taxon_list_id in (#taxa_taxon_list_list#)")
            ),
            'preprocess' => // faster than embedding this query in the report            
  "with recursive q as ( 
    select id 
    from cache_taxa_taxon_lists t 
    where id in (#taxa_taxon_list_list#) 
    union all 
    select tc.id 
    from q 
    join cache_taxa_taxon_lists tc on tc.parent_id = q.id 
  ) select array_to_string(array_agg(distinct id::varchar), ',') from q"
        ),
        'taxon_meaning_list' => array('datatype'=>'string', 'default'=>'', 'display'=>"Taxon meaning IDs", 
            'description'=>'Comma separated list of taxon meaning IDs',
            'wheres' => array(
              array('value'=>'', 'operator'=>'', 'sql'=>"o.taxon_meaning_id in (#taxon_meaning_list#)")
            ),
            'preprocess' => // faster than embedding this query in the report            
  "with recursive q as ( 
    select id, taxon_meaning_id 
    from cache_taxa_taxon_lists t 
    where taxon_meaning_id in (#taxon_meaning_list#) 
    union all 
    select tc.id, tc.taxon_meaning_id 
    from q 
    join cache_taxa_taxon_lists tc on tc.parent_id = q.id 
  ) select array_to_string(array_agg(distinct taxon_meaning_id::varchar), ',') from q"
        )
      );
      $this->defaultParamValues = array_merge(array(
          'occurrence_id_op'=>'=',
          'website_list_op'=>'in',
          'survey_list_op'=>'in',
          'input_form_list_op'=>'in',
          'location_list_op'=>'in',
          'indexed_location_list_op'=>'in',
          'occurrence_id_op_context'=>'=',
          'website_list_op_context'=>'in',
          'survey_list_op_context'=>'in',
          'input_form_list_op_context'=>'in',
          'location_list_op_context'=>'in',
          'indexed_location_list_op_context'=>'in',
          'release_status'=>'R'
      ), $this->defaultParamValues);
      $providedParams = array_merge($this->defaultParamValues, $providedParams);
      // load up the params for any which have a value provided
      foreach ($params as $param => $cfg) {
        if (isset($providedParams[$param])) {
          if (isset($cfg['joins'])) {
            foreach ($cfg['joins'] as &$join)
              $join['sql'] = preg_replace('/#alias:([a-z]+)#/', '$1', $join['sql']);
          }
          $this->params[$param] = $cfg;
        }
      }
      // now load any context parameters - i.e. filters defined by the user's permissions that must always apply.
      // Use a new loop so that prior changes to $cfg are lost.
      foreach ($params as $param => $cfg) {
        if (isset($providedParams[$param.'_context'])) {
          if (isset($cfg['joins'])) {
            foreach ($cfg['joins'] as &$join) {
              // construct a unique alias for any joined tables
              $join['sql'] = preg_replace('/#alias:([a-z]+)#/', '${1}_context', $join['sql']);
              // and ensure references to the param value point to the context version
              $join['sql'] = str_replace("#{$param}_op#", "#{$param}_op_context#", $join['sql']);
              $join['sql'] = str_replace("#$param#", "#{$param}_context#", $join['sql']);
            }
          }
          if (isset($cfg['wheres'])) {
            foreach ($cfg['wheres'] as &$where) {
              // ensure references to the param value point to the context version
              $where['sql'] = str_replace("#{$param}_op#", "#{$param}_op_context#", $where['sql']);
              $where['sql'] = str_replace("#$param#", "#{$param}_context#", $where['sql']);
            }
          }
          // and any references in the preprocessing query point to the context version of the param value
          if (isset($cfg['preprocess'])) 
            $cfg['preprocess'] = str_replace("#$param#", "#{$param}_context#", $cfg['preprocess']);
          $this->params[$param.'_context'] = $cfg;
        }
      }
    }
  }
  
  /**
   * To retain backwards compatibility with previous versions of standard params, we convert some param names.
   * @param array $providedParams The array of provided parameters which will be modified.
   * @param string $from The deprecated parameter name which will be swapped from.
   * @param string $from The new parameter name which will be use instead.
   */
  private function convertDeprecatedParam(&$providedParams, $from, $to) {
    if (isset($providedParams[$from]) && !isset($providedParams[$to]))
      $providedParams[$to]=$providedParams[$from];
    if (isset($providedParams[$from.'_context']) && !isset($providedParams[$to.'_context']))
      $providedParams[$to.'_context']=$providedParams[$from.'_context'];
  }

  /**
   * Merges a column definition pointed to by an XML reader into the list of columns.
   */
  private function mergeXmlColumn($reader) {
    $name = $reader->getAttribute('name');
    if (!array_key_exists($name, $this->columns))
    {
      // set a default column setup
      $this->columns[$name] = array(
        'visible' => 'provisional_true',
        'img' => 'false',
        'autodef' => false
      );
    }
    // build a definition from the XML
    $def = array();
    if ($reader->moveToFirstAttribute()) {
      do {
        if ($reader->name!='name')
          $def[$reader->name] = $reader->value;
      } while ($reader->moveToNextAttribute());
    }
    // move back up to where we started
    $reader->moveToElement();
    $this->columns[$name] = array_merge($this->columns[$name], $def);
    // remember if we have info required to auto-build the column SQL, plus aggregate fields
    $this->hasColumnsSql = $this->hasColumnsSql || isset($this->columns[$name]['sql']);
    $this->hasAggregates = $this->hasAggregates || (isset($this->columns[$name]['aggregate']) && $this->columns[$name]['aggregate']=='true');
    // do we have any datatype attributes, used for column based filtering? Data types can't be used without the SQL
    if (isset($this->columns[$name]['datatype']) && isset($this->columns[$name]['sql']))
      $this->filterableColumns[$name] = $this->columns[$name];
    // internal sql is used in group by and count queries. If not set, just use the SQL
    if (!empty($this->columns[$name]['sql'])) 
      $this->columns[$name]['internal_sql'] = empty($this->columns[$name]['internal_sql']) ? 
          $this->columns[$name]['sql'] : $this->columns[$name]['internal_sql'];
  }

  private function mergeColumn($name, $display = '', $style = '', $feature_style='', $class='', $visible='', $img='', $orderby='', $mappable='', $autodef=true)
  {
    if (array_key_exists($name, $this->columns))
    {
      if ($display != '') $this->columns[$name]['display'] = $display;
      if ($style != '') $this->columns[$name]['style'] = $style;
      if ($feature_style != '') $this->columns[$name]['feature_style'] = $feature_style;
      if ($class != '') $this->columns[$name]['class'] = $class;
      if ($visible === 'false') {
        if($this->columns[$name]['visible'] !== 'true') // allows a false to override a provisional_true, but not a true.
          $this->columns[$name]['visible'] = 'false';
      } elseif ($visible === 'true') // don't make any change if $visible is not set
        $this->columns[$name]['visible'] = 'true';
      if ($img == 'true' || $this->columns[$name]['img'] == 'true') $this->columns[$name]['img'] = 'true';
      if ($orderby != '') $this->columns[$name]['orderby'] = $orderby;
      if ($mappable != '') $this->columns[$name]['mappable'] = $mappable;
      if ($autodef != '') $this->columns[$name]['autodef'] = $autodef;
    }
    else
    {
      $this->columns[$name] = array(
          'display' => $display,
          'style' => $style,
          'feature_style' => $feature_style,
          'class' => $class,
          'visible' => $visible == '' ? 'true' : $visible,
          'img' => $img == '' ? 'false' : $img,
          'orderby' => $orderby,
          'mappable' => empty($mappable) ? 'false' : $mappable,
          'autodef' => $autodef);
    }
  }

  private function setTable($tablename, $where)
  {
    $this->tables = array();
    $this->tableIndex = 0;
    $this->nextTableIndex = 1;
    $this->tables[$this->tableIndex] = array(
          'tablename' => $tablename,
          'parent' => -1,
          'parentKey' => '',
          'tableKey' => '',
          'join' => '',
        'attributes' => '',
          'where' => $where,
          'columns' => array());
  }

  private function setSubTable($tablename, $parentKey, $tableKey, $join, $where)
  {
    if($tableKey == ''){
      if($parentKey == 'id'){
        $tableKey = 'lt'.$this->nextTableIndex.".".(inflector::singular($this->tables[$this->tableIndex]['tablename'])).'_id';
      } else {
        $tableKey = 'lt'.$this->nextTableIndex.'.id';
      }
    } else {
      $tableKey = 'lt'.$this->nextTableIndex.".".$tableKey;
    }
    if($parentKey == ''){
      $parentKey = 'lt'.$this->tableIndex.".".(inflector::singular($tablename)).'_id';
    } else { // force the link as this table has foreign key to parent table, standard naming convention.
      $parentKey = 'lt'.$this->tableIndex.".".$parentKey;
    }
    $this->tables[$this->nextTableIndex] = array(
          'tablename' => $tablename,
           'parent' => $this->tableIndex,
          'parentKey' => $parentKey,
          'tableKey' => $tableKey,
           'join' => $join,
          'attributes' => '',
          'where' => $where,
           'columns' => array());
    $this->tableIndex=$this->nextTableIndex;
    $this->nextTableIndex++;
  }

  private function mergeTabColumn($name, $func = '', $display = '', $style = '', $feature_style = '', $class='', $visible='', $autodef=false)
  {
    $found = false;
    for($r = 0; $r < count($this->tables[$this->tableIndex]['columns']); $r++){
      if($this->tables[$this->tableIndex]['columns'][$r]['name'] == $name) {
        $found = true;
        if($func != '') {
          $this->tables[$this->tableIndex]['columns'][$r]['func'] = $func;
        }
      }
    }
    if (!$found) {
      $this->tables[$this->tableIndex]['columns'][] = array(
            'name' => $name,
            'func' => $func);
      if($display == '') {
        $display = $this->tables[$this->tableIndex]['tablename']." ".$name;
      }
    }
    // force visible if the column is already declared as visible. This prevents the id field from being forced to hidden if explicitly included.
    if (isset($this->columns['lt'.$this->tableIndex."_".$name]['visible']) && $this->columns['lt'.$this->tableIndex."_".$name]['visible']=='true')
      $visible = 'true';
    $this->mergeColumn('lt'.$this->tableIndex."_".$name, $display, $style, $feature_style, $class, $visible, 'false', $autodef);
  }

  private function setMergeTabColumn($name, $tablename, $separator, $where = '', $display = '')
  {
    // in this case the data for the column in merged into one, if there are more than one records
    // To do this we highjack the attribute handling functionality.
    $tableKey = (inflector::singular($this->tables[$this->tableIndex]['tablename'])).'_id';

    $thisDefn = new stdClass;
    $thisDefn->caption = 'caption';
    $thisDefn->main_id = $tableKey; // main_id is the name of the column in the subquery holding the PK value of the parent table.
     $thisDefn->parentKey = "lt".$this->tableIndex."_id"; // parentKey holds the column in the main query to compare the main_id against.
    $thisDefn->id = 'id'; // id is the name of the column in the subquery holding the attribute id.
     $thisDefn->separator = $separator;
    $thisDefn->hideVagueDateFields = 'false';
     $thisDefn->columnPrefix = 'merge_'.count($this->attributes);

    if($display == ''){
      $display = $tablename.' '.$name;
    }

    $thisDefn->query =  "SELECT ".$tableKey.", '".$display."' as caption, '' as id, 'T' as data_type, ".$name." as text_value, 't' as multi_value FROM ".$tablename.($where == '' ? '' : " WHERE ".$where);
    $this->attributes[] = $thisDefn;
    // Make sure id column of parent table is in list of columns returned from query.
    $this->mergeTabColumn('id', '', '', '', '', 'false', true);
  }

  private function setAttributes($where, $separator, $hideVagueDateFields, $meaningIdLanguage)
  {
    $thisDefn = new stdClass;
    $thisDefn->caption = 'caption'; // caption is the name of the column in the subquery holding the attribute caption.
    $thisDefn->main_id = 'main_id'; // main_id is the name of the column in the subquery holding the PK value of the parent table.
     $thisDefn->parentKey = "lt".$this->tableIndex."_id"; // parentKey holds the column in the main query to compare the main_id against.
    $thisDefn->id = 'id'; // id is the name of the column in the subquery holding the attribute id.
    $thisDefn->separator = $separator;
    $thisDefn->hideVagueDateFields = $hideVagueDateFields;
    $thisDefn->columnPrefix = 'attr_'.$this->tableIndex.'_';
    // folowing is used the query builder only
    $thisDefn->parentTableIndex = $this->tableIndex;
    $thisDefn->where = $where;
    $thisDefn->meaningIdLanguage = $meaningIdLanguage;
    $thisDefn->query = $this->buildAttributeQuery($thisDefn);
    $this->attributes[] = $thisDefn;
    // Make sure id column of parent table is in list of columns returned from query.
    $this->mergeTabColumn('id', '', '', '', '', 'false', true);
  }

  private function setDownload($mode)
  {
    $this->download = $mode;
  }

 /**
  * Infers parameters such as column names and parameters from the query string.
  * Column inference can handle queries where there is a nested select provided it has a
  * matching from. Commas that are part of nested selects or function calls are ignored
  * provided they are enclosed in brackets.
  */
  private function inferFromQuery()
  {
    // Find the columns we're searching for - nested between a SELECT and a FROM.
    // To ensure we can detect the words FROM, SELECT and AS, use a regex to wrap
    // spaces around them, then can do a regular string search
    $this->query=preg_replace("/\b(select)\b/i", ' select ', $this->query);
    $this->query=preg_replace("/\b(from)\b/i", ' from ', $this->query);
    $this->query=preg_replace("/\b(as)\b/i", ' as ', $this->query);
    $i0 = strpos($this->query, ' select ') + 7;
    $nesting = 1;
    $offset = $i0;
    do {
      $nextSelect = strpos($this->query, ' select ', $offset);
      $nextFrom = strpos($this->query, ' from ', $offset);
      if ($nextSelect !== false && $nextSelect < $nextFrom) {
        //found start of sub-query
        $nesting++;
        $offset = $nextSelect + 7;
      } else {
        $nesting--;
        if ($nesting != 0) {
          //found end of sub-query
          $offset = $nextFrom + 5;
        }
      }
    }
    while ($nesting > 0);

    $i1 = $nextFrom - $i0;
    // get the columns list, ignoring the marker to show where additional columns can be inserted
    $colString = str_replace('#fields#', '', substr($this->query, $i0, $i1));

    // Now divide up the list of columns, which are comma separated, but ignore
    // commas nested in brackets
    $colStart = 0;
    $nextComma =  strpos($colString, ',', $colStart);
    while ($nextComma !== false)
    {//loop through columns
      $nextOpen =  strpos($colString, '(', $colStart);
      while ($nextOpen !== false && $nextComma !==false && $nextOpen < $nextComma)
      { //skipping commas in brackets
        $offset = $this->strposclose($colString, $nextOpen) + 1;
        $nextComma =  strpos($colString, ',', $offset);
        $nextOpen =  strpos($colString, '(', $offset);
      }
      if ($nextComma !== false) {
        //extract column and move on to next
        $cols[] = substr($colString, $colStart, ($nextComma - $colStart));
        $colStart = $nextComma + 1;
        $nextComma =  strpos($colString, ',', $colStart);
     }
    }
    //extract final column
    $cols[] = substr($colString, $colStart);

    // We have cols, which may either be of the form 'x', 'table.x' or 'x as y'. Either way the column name is the part after the last
    // space and full stop.
    foreach ($cols as $col)
    {
      // break down by spaces
      $b = explode(' ' , trim($col));
      // break down the part after the last space, by
      $c = explode('.' , array_pop($b));
      $d = array_pop($c);
      $this->mergeColumn(trim($d));
    }

    // Okay, now we need to find parameters, which we do with regex.
    preg_match_all('/#([a-z0-9_]+)#%/i', $this->query, $matches);
    // Here is why I remember (yet again) why I hate PHP...
    foreach ($matches[1] as $param)
    {
      $this->mergeParam($param);
    }
  }

  /**
   * Returns the numeric position of the closing bracket matching the opening bracket
   * @param <string> $haystack The string to search
   * @param <int> $open The numeric position of the opening bracket
   * @return The numeric position of the closing bracket or false if not present
   */
  private function strposclose($haystack, $open) {
    $nesting = 1;
    $offset = $open + 1;
    do {
      $nextOpen =  strpos($haystack, '(', $offset);
      $nextClose =  strpos($haystack, ')', $offset);
      if ($nextClose === false) return false;
      if ($nextOpen !== false and $nextOpen < $nextClose) {
        $nesting++;
        $offset = $nextOpen + 1;
      } else {
        $nesting--;
        $offset = $nextClose + 1;
      }
    }
    while ($nesting > 0);
    return $offset -1;
  }
}