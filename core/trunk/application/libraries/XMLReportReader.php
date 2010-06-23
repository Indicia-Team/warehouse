<?php

/**
* INDICIA
* @link http://code.google.com/p/indicia/
* @package Indicia
*/

/**
* <h1>XML Report reader</h1>
* <p>The report reader encapsulates logic for reading reports from a number of sources, and opens up * report methods in a transparent way to the report controller.</p>
*
* @package Indicia
* @subpackage Controller
* @license http://www.gnu.org/licenses/gpl.html GPL
* @author Nicholas Clarke <xxx@xxx.net> / $Author$
* @copyright xxxx
* @version $Rev$ / $LastChangedDate$
*/

class XMLReportReader_Core implements ReportReader
{
  private $name;
  private $title;
  private $description;
  private $row_class;
  private $query;
  private $order_by;
  private $params = array();
  private $columns = array();
  private $tables = array();
  private $attributes = array();
  private $automagic = false;
  private $vagueDateProcessing = 'true';
  private $download = 'OFF';

  /**
  * <p> Constructs a reader for the specified report. </p>
  */
  public function __construct($report)
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
                $reader->read();
                $this->query = $reader->value;
                $this->inferFromQuery();
                break;
              case 'order_by':
                $reader->read();
                $this->order_by[] = $reader->value;
                break;
              case 'param':
              	$this->mergeParam(
                    $reader->getAttribute('name'),
                    $reader->getAttribute('display'),
                    $reader->getAttribute('datatype'),
                    $reader->getAttribute('description'),
                    $reader->getAttribute('query'),
                    $reader->getAttribute('lookup_values'),
                    $reader->getAttribute('population_call'));
                break;
              case 'column':
                $this->mergeColumn(
                    $reader->getAttribute('name'),
                    $reader->getAttribute('display'),
                    $reader->getAttribute('style'),
                    $reader->getAttribute('class'),
                    $reader->getAttribute('visible'),
                    $reader->getAttribute('img'),
                    $reader->getAttribute('orderby'),
                    false
                );
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
                    $reader->getAttribute('class'),
                    $reader->getAttribute('visible'),
                    false
              	    );
              	break;
              case 'attributes':
              	$this->setAttributes(
                    $reader->getAttribute('where'),
                    $reader->getAttribute('separator'),
                    $reader->getAttribute('hideVagueDateFields')); // determines whether to hide the main vague date fields for attributes.
                break;
              case 'vagueDate': // This switches off vague date processing.
              	$this->vagueDateProcessing = $reader->getAttribute('enableProcessing');
              	break;
              case 'download': // This enables download processing.. potentially dangerous as updates DB.
              	$this->setDownload($reader->getAttribute('mode'));;
              	break;
              case 'mergeTabColumn':
               	$this->setMergeTabColumn(
              	    $reader->getAttribute('name'),
              	    $reader->getAttribute('tablename'),
              	    $reader->getAttribute('separator'),
                    $reader->getAttribute('where'),
                    $reader->getAttribute('display'));
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
    }
    catch (Exception $e)
    {
      throw new Exception("Report: $report\n".$e->getMessage());
    }
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
          'parameters' => $this->params,
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
          'parameters' => $this->params
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
 	$thisDefn->id = 'occurrences_id';
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
	$query .= " FROM ";
	for($i = 0; $i <= $attributes->parentTableIndex; $i++){
		if ($i == 0) {
    		$query .= $this->tables[$i]['tablename']." lt".$i;
		} else { // making assumption to reduce the size of the query that all left outer join tables can be excluded
    		if ($this->tables[$i]['join'] == null) {
    			$query .= " INNER JOIN ".$this->tables[$i]['tablename']." lt".$i." ON (".$this->tables[$i]['tableKey']." = ".$this->tables[$i]['parentKey'];
    		    if($this->tables[$i]['where'] != null) {
    			    $query .= " AND ".preg_replace("/#this#/", "lt".$i, $this->tables[$i]['where']);
 			    }
    		    $query .= ") ";
    		}
		}
	}
    $query .= " INNER JOIN ".$parentSingular."_attribute_values vt ON (vt.".$parentSingular."_id = "." lt".$attributes->parentTableIndex.".id and vt.deleted = FALSE) ";
    $query .= " INNER JOIN ".$parentSingular."_attributes at ON (vt.".$parentSingular."_attribute_id = at.id and at.deleted = FALSE) ";
    $query .= " INNER JOIN ".$parentSingular."_attributes_websites rt ON (rt.".$parentSingular."_attribute_id = at.id and rt.deleted = FALSE) ";
  	// where list
	$previous=false;
	if($this->tables[0]['where'] != null) {
		$query .= " WHERE ".preg_replace("/#this#/", "lt0", $this->tables[0]['where']);
		$previous = true;
	}
	if($attributes->where != null) {
		$query .= ($previous ? " AND " : " WHERE ").$attributes->where;
	}
    $query .= " ORDER BY lt".$attributes->parentTableIndex.".id";
    return $query;
  }

  private function mergeParam($name, $display = '', $type = '', $description = '', $query='', $lookup_values='', $population_call='')
  {
    if (array_key_exists($name, $this->params))
    {
      if ($display != '') $this->params[$name]['display'] = $display;
      if ($type != '') $this->params[$name]['datatype'] = $type;
      if ($description != '') $this->params[$name]['description'] = $description;
      if ($query != '') $this->params[$name]['query'] = $query;
      if ($lookup_values != '') $this->params[$name]['lookup_values'] = $lookup_values;
      if ($population_call != '') $this->params[$name]['population_call'] = $population_call;
    }
    else
    {
      $this->params[$name] = array(
        'datatype'=>$type, 
        'display'=>$display, 
        'description'=>$description, 
        'query' => $query, 
        'lookup_values' => $lookup_values,
        'population_call' => $population_call
      );
    }
  }

  private function mergeColumn($name, $display = '', $style = '', $class='', $visible='', $img='', $orderby='', $autodef=true)
  {
    if (array_key_exists($name, $this->columns))
    {
      if ($display != '') $this->columns[$name]['display'] = $display;
      if ($style != '') $this->columns[$name]['style'] = $style;
      if ($class != '') $this->columns[$name]['class'] = $class;
      if ($visible == 'false' || $this->columns[$name]['visible'] == 'false') $this->columns[$name]['visible'] = 'false';
      if ($img == 'true' || $this->columns[$name]['img'] == 'true') $this->columns[$name]['img'] = 'true';
      if ($orderby != '') $this->columns[$name]['orderby'] = $orderby;
      if ($autodef != '') $this->columns[$name]['autodef'] = $autodef;
    }
    else
    {
      $this->columns[$name] = array(
          'display' => $display,
          'style' => $style,
          'class' => $class,
          'visible' => $visible == '' ? 'true' : $visible,
          'img' => $img == '' ? 'false' : $img,
          'orderby' => $orderby,
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

  private function mergeTabColumn($name, $func = '', $display = '', $style = '', $class='', $visible='', $autodef=false)
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
    if(!$found){
		$this->tables[$this->tableIndex]['columns'][] = array(
    	      'name' => $name,
        	  'func' => $func);
    	if($display == '') {
			$display = $this->tables[$this->tableIndex]['tablename']." ".$name;
	    }
    }
    $this->mergeColumn('lt'.$this->tableIndex."_".$name, $display, $style, $class, $visible, $autodef);
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

  private function setAttributes($where, $separator, $hideVagueDateFields)
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
  */
  private function inferFromQuery()
  {
    // Find the columns we're searching for - nested between a SELECT and a FROM.
    // To ensure we can detect the word FROM and SELECT, use a regex to wrap spaces around them, then can
    // do a regular string search
    // This can't handle complex valid queries where there is a nested select
    $this->query=preg_replace("/\b(select)\b/i", ' select ', $this->query);
    $this->query=preg_replace("/\b(from)\b/i", ' from ', $this->query);
    $i0 = strpos($this->query, ' select ') + 7;
    $i1 = strpos($this->query, ' from ') - $i0;
    $cols = explode(',', substr($this->query, $i0, $i1));
    // We have cols, which may either be of the form 'x' or of the form 'x as y'
    foreach ($cols as $col)
    {
      $a = explode(' as ', strtolower($col));
      if (count($a) == 2)
      {
        // Okay, we have an 'as' clause
        $this->mergeColumn(trim($a[1]));
      }
      else
      {
        // Treat this as a single thing
        // But it might have a . in it if it's a multi-table query, so look at the last bit
        $b = explode('.' , $a[0]);
        $b = $b[count($b) - 1];
        $this->mergeColumn(trim($b));
      }
    }

    // Okay, now we need to find parameters, which we do with regex.
    preg_match_all('/#([a-z0-9_]+)#%/i', $this->query, $matches);
    // Here is why I remember (yet again) why I hate PHP...
    foreach ($matches[1] as $param)
    {
      $this->mergeParam($param);
    }
  }

}