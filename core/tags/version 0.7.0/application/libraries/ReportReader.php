<?php

/**
* INDICIA
* @link http://code.google.com/p/indicia/
* @package Indicia
*/

/**
* <h1>Report reader</h1>
* <p>The report reader encapsulates logic for reading reports from a number of sources, and opens up * report methods in a transparent way to the report controller.</p>
*
* @package Indicia
* @subpackage Controller
* @license http://www.gnu.org/licenses/gpl.html GPL
* @author Nicholas Clarke <xxx@xxx.net> / $Author$
* @copyright xxxx
* @version $Rev$ / $LastChangedDate$
*/
interface ReportReader
{
  const REPORT_DESCRIPTION_BRIEF = 1;
  const REPORT_DESCRIPTION_DEFAULT = 2;
  const REPORT_DESCRIPTION_FULL = 3;
  
  /**
  * <p> Constructs a reader for the specified report. </p>
  */
  public function __construct($report, $websiteIds);

  /**
   * <p> Returns the title of the report. </p>
   */
  public function getTitle();
  
  /**
   * <p> Returns the description of the report. </p>
   */
  public function getDescription();
  
  /**
   * <p> Returns the query specified. </p>
   */
  public function getQuery();
  
  /**
   * <p> Uses source-specific validation methods to check whether the report query is valid. </p>
   */
  public function isValid();
  
  /**
  * <p> Returns the order by clause for the query. </p>
  */
  public function getOrderClause();
  
  /**
  * <p> Gets a list of parameters (name => type) </p>
  */
  public function getParams();
  
  /**
  * <p> Returns a description of the report appropriate to the level specified. </p>
  */
  public function describeReport($descLevel);
  
  /**
  * <p> Gets a list of the columns (name => array('display' => display, 'style' => style, 'img' => true|false)) </p>
  */
  public function getColumns();
  /**
  * <p> Gets a list of the attribute subquery definitions </p>
  */
  public function getAttributeDefns();
  /**
  * <p> Gets a text flag describing where vague date processing is active : 'true'/'false' </p>
  */
  public function getVagueDateProcessing();
  /**
  * <p> Gets an object describing the download processing: stdClass(enabled->'true'/'false'/'#paramValue#', id->name of PK in main query) </p>
  */
  public function getDownloadDetails();
}