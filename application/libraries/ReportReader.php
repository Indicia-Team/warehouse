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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

/**
 * The report reader encapsulates logic for reading reports from a number of
 * sources, and opens up * report methods in a transparent way to the report
 * controller.
 */
interface ReportReader {
  const REPORT_DESCRIPTION_BRIEF = 1;
  const REPORT_DESCRIPTION_DEFAULT = 2;
  const REPORT_DESCRIPTION_FULL = 3;

  /**
   * Constructs a reader for the specified report.
   */
  public function __construct($db, $report);

  /**
   * Returns the title of the report.
   */
  public function getTitle();

  /**
   * Returns the description of the report.
   */
  public function getDescription();

  /**
   * Returns the query specified.
   */
  public function getQuery();

  /**
   * Uses source-specific validation methods to check whether the report query is valid.
   */
  public function isValid();

  /**
   * Returns the order by clause for the query.
   */
  public function getOrderClause(array $providedParams);

  /**
   * Gets a list of parameters (name => type).
   */
  public function getParams();

  /**
   * Returns a description of the report appropriate to the level specified.
   */
  public function describeReport($descLevel);

  /**
   * Gets a list of the columns (name => array('display' => display, 'style' => style, 'img' => true|false))
   */
  public function getColumns();

  /**
   * Gets a list of the attribute subquery definitions.
   */
  public function getAttributeDefns();

  /**
   * Gets a text flag describing where vague date processing is active : 'true'/'false'.
   */
  public function getVagueDateProcessing();

  /**
   * <p> Gets an object describing the download processing: stdClass(enabled->'true'/'false'/'#paramValue#', id->name of PK in main query) </p>
   */
  public function getDownloadDetails();

}
