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

  /**
  * <p> Constructs a reader for the specified report. </p>
  */
  public function __construct($report)
  {
    Kohana::log('info', "Constructing XMLReportReader for report $report.");
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
                    $reader->getAttribute('query'));
                break;
              case 'column':
                $this->mergeColumn(
                    $reader->getAttribute('name'),
                    $reader->getAttribute('display'),
                    $reader->getAttribute('style'),
                    $reader->getAttribute('class'),
                    $reader->getAttribute('visible'),
                    false
                );
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
   * Returns the css class to apply to rows in the report.
   */
  public function getRowClass()
  {
    return $this->row_class;
  }

  /**
  * <p> Returns the query specified. </p>
  */
  public function getQuery()
  {
    return $this->query;
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
          'columnns' => $this->columns,
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
          'columnns' => $this->columns,
          'parameters' => $this->params
        );
    }
  }

  private function mergeParam($name, $display = '', $type = '', $description = '', $query='')
  {
    if (array_key_exists($name, $this->params))
    {
      if ($display != '') $this->params[$name]['display'] = $display;
      if ($type != '') $this->params[$name]['datatype'] = $type;
      if ($description != '') $this->params[$name]['description'] = $description;
      if ($query != '') $this->params[$name]['query'] = $query;
    }
    else
    {
      $this->params[$name] = array('datatype'=>$type, 'display'=>$display, 'description'=>$description, 'query' => $query);
    }
  }

  private function mergeColumn($name, $display = '', $style = '', $class='', $visible='', $autodef='true')
  {
    if (array_key_exists($name, $this->columns))
    {
      if ($display != '') $this->columns[$name]['display'] = $display;
      if ($style != '') $this->columns[$name]['style'] = $style;
      if ($class != '') $this->columns[$name]['class'] = $class;
      if ($visible != '') $this->columns[$name]['visible'] = $visible;
      if ($autodef != '') $this->columns[$name]['autodef'] = $autodef;
    }
    else
    {
      $this->columns[$name] = array(
          'display' => $display,
          'style' => $style,
          'class' => $class,
          'visible' => $visible,
          'autodef' => $autodef);
    }
  }

  /**
  * Infers parameters such as column names and parameters from the query string.
  */
  private function inferFromQuery()
  {
    // Find the columns we're searching for - nested between a SELECT and a FROM.
    // To ensure we can detect the word FROM and SELECT, use a regex to wrap spaces around them, then can
    // do a regular string search
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