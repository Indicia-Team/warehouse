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
 * The report reader encapsulates logic for reading reports from a number of sources, and opens up * report
 * methods in a transparent way to the report controller.
 */
class XMLReportReader_Core implements ReportReader {
  public $columns = [];
  public $defaultParamValues = [];
  private $name;
  private $title;
  private $description;
  private array $attachment = [];
  private $row_class;
  private $query;
  private $countQuery;
  private $countQueryBase = NULL;
  private $countFields;
  private $field_sql;
  private $order_by;
  private $params = [];
  private $tables = [];
  private $attributes = [];
  private $automagic = FALSE;
  private $vagueDateProcessing = 'true';
  private $download = 'OFF';
  private $surveyParam='survey_id';
  private $websiteFilterField = 'w.id';
  private $trainingFilterField = 'o.training';
  private $blockedSharingTasksField = 'blocked_sharing_tasks';
  private $createdByField;
  private $colsToInclude = [];
  private $tableIndex;
  private $nextTableIndex;

  public $surveys_id_field;
  public $samples_id_field;
  public $samples2_id_field;
  public $occurrences_id_field;
  public $occurrences2_id_field;
  public $locations_id_field;
  public $locations2_id_field;
  public $people_id_field;
  public $taxa_taxon_lists_id_field;
  public $termlists_terms_id_field;
  public $count_field;

  /**
   * Database connection object.
   *
   * @var object
   */
  private $db;

  /**
   * Identify if we have got SQL defined in the columns array.
   *
   * If so we are able to auto-generate the sql for the columns list.
   *
   * @var bool
   */
  private $hasColumnsSql = FALSE;

  /**
   * List of column definitions that allow filtering.
   *
   * These must have datatype and sql defined.
   *
   * @var array
   */
  public $filterableColumns = [];

  /**
   * Track if this report supports the standard set of parameters.
   *
   * If so, names the entity.
   *
   * @var bool
   */
  public $loadStandardParamsSet = FALSE;

  /**
   * @var boolean True if loading the legacy set of standard parameters for reporting occurrences against the
   * old cache structure via the cache_occurrences view which simulates the structure for backwards compatibility.
   */
  public $loadLegacyStandardParamsSet = FALSE;

  /**
   * @var boolean Identify if we have got SQL defined for aggregated fields. If so we need to implement a group by for
   * the other fields.
   */
  private $hasAggregates = FALSE;

  /**
   * Returns a simple array containing the title and description of a report. Static so you don't have to load the full report object to get this
   * information.
   */
  public static function loadMetadata($report) {
    $reader = new XMLReader();
    if ($reader->open($report) === FALSE) {
      throw new Exception("Report $report could not be opened.");
    }
    $metadata = array('title' => 'Untitled (' . $report . ')', 'description' => 'No description provided');
    while($reader->read()) {
      if ($reader->nodeType == XMLREADER::ELEMENT && $reader->name === 'report') {
        $metadata['title'] = $reader->getAttribute('title');
        $metadata['description'] = $reader->getAttribute('description');
        $metadata['featured'] = $reader->getAttribute('featured');
        $metadata['restricted'] = $reader->getAttribute('restricted');
        $metadata['summary'] = $reader->getAttribute('summary');
        if (!$metadata['featured']) {
          unset($metadata['featured']);
        }
        if (!$metadata['restricted']) {
          unset($metadata['restricted']);
        }
        if (!$metadata['summary']) {
          unset($metadata['summary']);
        }
        if (!$metadata['title']) {
          $metadata['title'] = 'Untitled (' . basename($report) . ')';
        }
        if (!$metadata['description']) {
          $metadata['description'] = 'No description provided';
        }
      }
      elseif ($reader->nodeType==XMLREADER::ELEMENT && $reader->name === 'query') {
        if ($reader->getAttribute('standard_params')) {
          $metadata['standard_params'] = $reader->getAttribute('standard_params');
          if (!$metadata['standard_params'])
            unset($metadata['standard_params']);
        }
        // No need to read further than the query element
        break;
      }
    }
    $reader->close();
    return $metadata;
  }

  /**
   * Constructs a reader for the specified report.
   *
   * @param object $db
   *   Report database connection.
   * @param string $report
   *   Report file path.
   * @param array $colsToInclude
   *   Optional list of column names to include in the report output.
   */
  public function __construct($db, $report, array $colsToInclude = []) {
    Kohana::log('debug', "Constructing XMLReportReader for report $report.");
    try {
      $this->db = $db;
      $a = explode('/', $report);
      $this->name = $a[count($a) - 1];
      $reader = new XMLReader();
      $reader->open($report);
      $this->colsToInclude = $colsToInclude;
      while ($reader->read()) {
        switch ($reader->nodeType) {
          case (XMLREADER::ELEMENT):
            switch ($reader->name) {
              case 'report':
                $this->title = $reader->getAttribute('title');
                $this->description = $reader->getAttribute('description');
                $this->row_class = $reader->getAttribute('row_class');
                break;

              case 'query':
                $sp = $reader->getAttribute('standard_params');
                $this->websiteFilterField = $reader->getAttribute('website_filter_field');
                if ($this->websiteFilterField === NULL)
                  // default field name for filtering against websites
                  $this->websiteFilterField = 'w.id';
                $this->trainingFilterField = $reader->getAttribute('training_filter_field');
                if ($this->trainingFilterField === NULL) {
                  // default field name for filtering training records
                  if (!empty($sp) && $sp === 'samples') {
                    $this->trainingFilterField = 's.training';
                  } else {
                    $this->trainingFilterField = 'o.training';
                  }
                }
                $this->blockedSharingTasksField = $reader->getAttribute('blocked_sharing_tasks_field');
                if ($this->blockedSharingTasksField === NULL) {
                  if (!empty($sp)) {
                    $this->blockedSharingTasksField = $sp === 'samples' ? 's.blocked_sharing_tasks' : 'o.blocked_sharing_tasks';
                  }
                }
                if (!$this->createdByField = $reader->getAttribute('created_by_field')) {
                  // Default field name for filtering the user ID that created
                  // the record.
                  $this->createdByField = 'o.created_by_id';
                }
                if (!$this->surveys_id_field = $reader->getAttribute('surveys_id_field')) {
                  // Default table alias for the surveys table, so we can join
                  // to the id.
                  $this->surveys_id_field = 'su.id';
                }
                if (!$this->samples_id_field = $reader->getAttribute('samples_id_field')) {
                  // Default table alias for the samples table, so we can join
                  // to the id.
                  $this->samples_id_field = 's.id';
                }
                if (!$this->samples2_id_field = $reader->getAttribute('samples2_id_field')) {
                  // Default table alias for the second samples table, so we
                  // can join to the id: used when geting attributes for both
                  // in a parent/child arrangement.
                  $this->samples2_id_field = 's2.id';
                }
                if (!$this->occurrences_id_field = $reader->getAttribute('occurrences_id_field')) {
                  // Default table alias for the occurrences table, so we can
                  // join to the id.
                  $this->occurrences_id_field = 'o.id';
                }
                if (!$this->occurrences2_id_field = $reader->getAttribute('occurrences2_id_field')) {
                  // Default table alias for the second occurrences table, so
                  // we can join to the id.
                  $this->occurrences2_id_field = 'o2.id';
                }
                if (!$this->locations_id_field = $reader->getAttribute('locations_id_field')) {
                  // Default table alias for the locations table, so we can
                  // join to the id.
                  $this->locations_id_field = 'l.id';
                }
                if (!$this->locations2_id_field = $reader->getAttribute('locations2_id_field')) {
                  // Default table alias for the second locations table, so we
                  // can join to the id: used when geting attributes for both
                  // in a parent/child arrangement.
                  $this->locations2_id_field = 'l2.id';
                }
                if (!$this->people_id_field = $reader->getAttribute('people_id_field')) {
                  // Default table alias for the people table, so we can join
                  // to the id.
                  $this->people_id_field = 'p.id';
                }
                if (!$this->taxa_taxon_lists_id_field = $reader->getAttribute('taxa_taxon_lists_id_field')) {
                  // Default table alias for the taxa_taxon_lists table, so we
                  // can join to the preferred_taxa_taxon_list_id when getting
                  // attributes. Attribute values are only attached to the
                  // preferred taxa and not common names or synonyms.
                  $this->taxa_taxon_lists_id_field = 'ttl.preferred_taxa_taxon_list_id';
                }
                if (!$this->termlists_terms_id_field = $reader->getAttribute('termlists_terms_id_field')) {
                  // Default table alias for the termlists_terms table, so we
                  // can join to the id.
                  $this->termlists_terms_id_field = 'tlt.id';
                }
                if (!$this->count_field = $reader->getAttribute('count_field')) {
                  // Field used in count queries unless in_count fields are
                  // specified.
                  $this->count_field = '*';
                }
                // Load the standard set of parameters for consistent filtering
                // of reports?
                $standardParams = $reader->getAttribute('standard_params');
                if ($standardParams !== NULL) {
                  // Default to loading the occurrences standard parameters
                  // set. But this can be overridden.
                  $this->loadStandardParamsSet = $standardParams === 'true' ? 'occurrences' : $standardParams;
                }
                // Reports using the old cache_occurrences structure set
                // standard params to true rather than occurrences.
                if ($standardParams === 'true') {
                  $this->loadLegacyStandardParamsSet = TRUE;
                }
                $reader->read();
                $this->query = $reader->value;
                break;

              case 'count_query':
                $reader->read();
                $this->countQuery = $reader->value;
                break;

              case 'field_sql':
                $reader->read();
                $field_sql = $reader->value;
                // Drop a marker in so we can insert custom attr fields later.
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
                $this->automagic = TRUE;
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
                  FALSE
                );
                break;

              case 'attributes':
                $this->setAttributes(
                    $reader->getAttribute('where'),
                    $reader->getAttribute('separator'),
                    // Determines whether to hide the main vague date fields
                    // for attributes.
                    $reader->getAttribute('hideVagueDateFields'),
                    // If not set, lookup lists use term_id. If set, look up
                    // lists use meaning_id, with value either 'preferred' or
                    // the 3 letter iso language to use.
                    $reader->getAttribute('meaningIdLanguage'));
                break;

              case 'vagueDate':
                // This switches off vague date processing.
                $this->vagueDateProcessing = $reader->getAttribute('enableProcessing');
                break;

              case 'download':
                // This enables download processing.. potentially dangerous as
                // updates DB.
                $this->setDownload($reader->getAttribute('mode'));
                break;

              case 'mergeTabColumn':
                $this->setMergeTabColumn(
                  $reader->getAttribute('name'),
                  $reader->getAttribute('tablename'),
                  $reader->getAttribute('separator'),
                  $reader->getAttribute('where'),
                  $reader->getAttribute('display')
                );
                break;

              case 'attachment':
                $attachment = [];
                $attachment['filename'] = $reader->getAttribute('filename');
                $reader->read();
                $attachment['query'] = $reader->value;
                $this->attachment = $attachment;

            }
            break;

          case (XMLReader::END_ELEMENT):
            switch ($reader->name) {
              case 'subTable':
                $this->tableIndex = $this->tables[$this->tableIndex]['parent'];
                break;
            }
            break;
        }
      }
      $reader->close();
      // Add a token to mark where additional filters can insert in the WHERE
      // clause.
      if ($this->query && strpos($this->query, '#filters#') === FALSE) {
        if (strpos($this->query, '#order_by#') !== FALSE) {
          $this->query = str_replace('#order_by#', "#filters#\n#order_by#", $this->query);
        }
        else {
          $this->query .= '#filters#';
        }
      }
      // Also for count query if specified.
      if ($this->countQuery) {
        if (strpos($this->countQuery, '#filters#') === FALSE) {
          $this->countQuery .= '#filters#';
        }
      }
      elseif ($this->query) {
        $this->countQuery = $this->query;
      }
      if ($this->hasColumnsSql) {
        // Column sql is defined in the list of column elements, so
        // autogenerate the query.
        $this->autogenColumns();
        if ($this->hasAggregates) {
          $this->buildGroupBy();
        }
      }
      elseif ($this->query) {
        // Sort out the field list or use count(*) for the count query. Do this
        // at the end so the queries are otherwise the same.
        if (!empty($field_sql)) {
          $this->countQueryBase = str_replace('#field_sql#', '#count#', $this->countQuery);
          $this->countFields = $this->count_field;
          $this->query = str_replace('#field_sql#', $field_sql, $this->query);
        }
        // Column SQL is part of the SQL statement, or defined in a field_sql
        // element.
        // Get any extra columns from the query data. Do this at the end so
        // that the specified columns appear first, followed by any unspecified
        // ones.
        $this->inferFromQuery();
      }
    }
    catch (Exception $e) {
      throw new Exception("Report: $report\n" . $e->getMessage());
    }
  }

  /**
   * Apply the website and sharing related filters to the query.
   */
  public function applyWebsitePermissions(&$query, $websiteIds, $providedParams, $sharing, $userId) {
    if ($websiteIds) {
      if (in_array('', $websiteIds)) {
        foreach ($websiteIds as $key => $value) {
          if (empty($value)) {
            unset($websiteIds[$key]);
          }
        }
      }
      $websiteIdList = implode(',', $websiteIds);
      // Query can either pull in the filter or just the list of website ids.
      $filter = empty($this->websiteFilterField) ? "1=1" : "({$this->websiteFilterField} in ($websiteIdList) or {$this->websiteFilterField} is null)";
      $query = str_replace(
        ['#website_filter#', '#website_ids#'],
        [$filter, $websiteIdList],
        $query
      );
    }
    else {
      // Use a dummy filter to return all websites if core admin.
      $query = str_replace(
        ['#website_filter#', '#website_ids#'],
        ['1=1', 'SELECT id FROM websites'],
        $query
      );
    }
    if (!empty($this->trainingFilterField)) {
      $boolStr = $providedParams['training'] === 'true' ? 'true' : 'false';
      $query = str_replace('#sharing_filter#', "{$this->trainingFilterField}=$boolStr AND #sharing_filter#", $query);
    }
    // An alternative way to inform a query about training mode....
    $query = str_replace('#training#', $providedParams['training'], $query);
    // Select the appropriate type of sharing arrangement (i.e. are we
    // reporting, verifying, moderating etc?)
    if ($sharing === 'me' && empty($userId)) {
      // Revert to website type sharing if we have no known user Id.
      $sharing = 'website';
    }
    $agreementsJoins = [];
    $sharingFilters = [];
    if ($sharing === 'me') {
      // My data only so use the UserId if we have it.
      $sharingFilters[] = "{$this->createdByField}=$userId";
      // 'me' is a subtype of reporting
      $sharing = 'reporting';
    }
    if (isset($websiteIdList)) {
      if ($sharing === 'website') {
        $sharingFilters[] = "{$this->websiteFilterField} in ($websiteIdList)";
      }
      elseif (!empty($this->websiteFilterField)) {
        // Implement the appropriate sharing agreement across websites.
        // Add a filter so we can check their privacy preferences. This does
        // not apply if record input on this website, or for the admin user
        // account.
        $sharedWebsiteIdList = implode(',', warehouse::getSharedWebsiteList($websiteIds, $this->db, $sharing));
        if (!empty($this->blockedSharingTasksField)) {
          $sharingCode = warehouse::sharingTermToCode($sharing);
          $sharingFilters[] = "($this->websiteFilterField in ($websiteIdList) OR $this->createdByField=1 OR " .
            "$this->blockedSharingTasksField IS NULL OR NOT $this->blockedSharingTasksField @> ARRAY['$sharingCode'::character ])";
          // Some reports may rely on the syntax of an agreement join being
          // present. Therefore we insert a dummy join that will have little or
          // no effect on performance.
          $agreementsJoins[] = 'JOIN system sys ON sys.id=1';
        }
        else {
          $agreementsJoins[] = "JOIN users privacyusers ON privacyusers.id=$this->createdByField";
          $sharingFilters[] = "($this->websiteFilterField in ($websiteIdList) OR privacyusers.id=1 OR " .
              "privacyusers.allow_share_for_$sharing=true OR privacyusers.allow_share_for_$sharing IS NULL)";
        }
        // If scope not controlled by a survey standard parameter filter, then
        // apply a website_id filter. Avoid doing this unnecessary as it
        // affects performance.
        if (!$this->coveringSurveyFilter($providedParams, $sharedWebsiteIdList)) {
          $sharingFilters[] = "$this->websiteFilterField in ($sharedWebsiteIdList)";
        }
        $query = str_replace('#sharing_website_ids#', $sharedWebsiteIdList, $query);
      }

    }
    // Add a dummy sharing filter if nothing else set, for the sake of syntax.
    if (empty($sharingFilters)) {
      $sharingFilters[] = '1=1';
    }
    $query = str_replace(
      ['#agreements_join#', '#sharing_filter#', '#sharing#'],
      [
        implode("\n", $agreementsJoins),
        implode("\n AND ", $sharingFilters),
        $sharing,
      ],
      $query
    );
  }

  /**
   * Sorts a string of comma separated numbers.
   *
   * @param string $string
   *   String containing comma separated numbers.
   *
   * @return string
   *   String of sorted, comma separated numbers.
   */
  private function sortCsvString($string) {
    $list = explode(',', $string);
    sort($list);
    return implode(',', $list);
  }

  /**
   * Check if we have a survey filter param which covers permissions.
   *
   * If doing a standard params filter including a filter on survey and all
   * the requested surveys are allowed (i.e. in the list of allowed websites)
   * then we can drop the website filter from the query. This saves extra
   * work for the query optimised.
   *
   * @param array $providedParams
   *   Report parameters.
   * @param string $sharedWebsiteIdList
   *   Comma separated list of allowed website IDs according to current share
   *   settings.
   *
   * @return bool
   *   True if the survey filter covers permissions requirements so the website
   *   filter can be dropped.
   */
  private function coveringSurveyFilter(array $providedParams, $sharedWebsiteIdList) {
    if ($this->loadStandardParamsSet && !empty($providedParams['survey_list']) || !empty($providedParams['survey_id'])) {
      $surveys = empty($providedParams['survey_list']) ? $providedParams['survey_id'] : $providedParams['survey_list'];
      $sortedSurveys = $this->sortCsvString($surveys);
      $sortedWebsites = $this->sortCsvString($sharedWebsiteIdList);
      // Cache key will use a hash so survey and website ID lists not too long
      // for a filename.
      $hash = md5("$sortedSurveys:$sortedWebsites");
      $cacheId = "covering-survey-filter-swh-$hash";
      $cache = Cache::instance();
      if ($cached = $cache->get($cacheId)) {
        // Extra safety check in case a hash value collision.
        if ($cached[0] === "$sortedSurveys:$sortedWebsites") {
          return $cached[1];
        }
      }
      // Doing a standard params filter on survey ID. If all the requested
      // surveys are allowed then we don't need a separate website filter.
      $qry = $this->db->select('count(*)')
        ->from('surveys')
        ->in('id', $surveys)
        ->notin('website_id', $sharedWebsiteIdList)
        ->get()->current();
      $cache->set($cacheId, ["$sortedSurveys:$sortedWebsites", $qry->count === '0']);
      return $qry->count === '0';
    }
    return FALSE;
  }

  /**
   * Use the sql attributes from the list of columns to auto generate the columns SQL.
   */
  private function autogenColumns() {
    $sql = [];
    $distinctSql = [];
    $countSql = [];
    foreach ($this->columns as $col => $def) {
      if (!empty($this->colsToInclude) && !in_array($col, $this->colsToInclude)) {
        continue;
      }
      if (isset($def['sql'])) {
        if (!isset($def['on_demand']) || $def['on_demand'] !== "true") {
          $sql[] = $def['sql'] . ' as ' . $this->db->escape_identifier($col);
        }
        if (isset($def['distincton']) && $def['distincton'] == 'true') {
          $distinctSql[] = $def['internal_sql'];
          // In_count lets the xml file exclude distinct on columns from the
          // count query.
          if (!isset($def['in_count']) || $def['in_count'] == 'true') {
            $countSql[] = $def['internal_sql'];
          }
        }
        else {
          // If the column is not distinct on, then it defaults to not in the
          // count.
          if (isset($def['in_count']) && $def['in_count'] == 'true') {
            $countSql[] = $def['internal_sql'];
          }
        }
      }
      elseif ($col === 'date' && empty($def['sql']) && $this->vagueDateProcessing) {
        $sql[] = 'null as date';
      }
    }
    if (count($distinctSql) > 0) {
      $distincton = ' distinct on (' . implode(', ', $distinctSql) . ') ';
    }
    else {
      $distincton = '';
    }
    $this->countQueryBase = str_replace('#columns#', '#count#', $this->countQuery);
    if (count($countSql) > 1) {
      // Concatenate the fields so we can get a distinct list.
      $this->countFields = 'coalesce(' . implode("::text, '') || coalesce(", $countSql) . "::text, '')";
    }
    elseif (count($countSql) === 1) {
      $this->countFields = $countSql[0];
    }
    else {
      $this->countFields = $this->count_field;
    }
    if (count($countSql) > 0 && !preg_match('/distinct #columns#/i', $this->query)) {
      $this->countFields = "DISTINCT $this->countFields";
    }
    // Merge the distincton back into the query. Note we drop in a #fields# tag
    // so that the query processor knows where to add custom attribute fields.
    $this->query = str_replace('#columns#', $distincton . implode(",\n", $sql) . '#fields#', $this->query);
  }

  /**
   * If there are columns marked with the aggregate attribute, then we can build a group by clause
   * using all the non-aggregate column sql.
   * This is done dynamically leaving us with the ability to automatically extend the group by field list,
   * e.g. if some custom attribute columns have been added to the report.
   */
  private function buildGroupBy() {
    $sql = [];
    foreach ($this->columns as $col => $def) {
      if (isset($def['internal_sql'])
          && (!isset($def['aggregate']) || $def['aggregate'] != 'true')
          && (!isset($def['on_demand']) || $def['on_demand'] != 'true')) {
        $sql[] = $def['internal_sql'];
      }
    }
    // Add the non-aggregated fields to the end of the query. Leave a token so
    // that the query processor can add more, e.g. if there are custom
    // attribute columns, and also has a suitable place for a HAVING clause.
    if (count($sql) > 0) {
      if (strpos($this->query, '#group_bys#') === FALSE) {
        $this->query .= "\nGROUP BY " . implode(', ', $sql) . '#group_bys# #having#';
      }
      else {
        $this->query = str_replace('#group_bys#', "GROUP BY " . implode(', ', $sql) . '#group_bys# #having#', $this->query);
      }
    }
  }

  /**
   * Returns the title of the report.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Returns the description of the report.
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Return metadata about attachments for direct email reports.
   *
   * @return array
   */
  public function getAttachment() {
    return $this->attachment;
  }

  /**
   * Returns the query specified.
   */
  public function getQuery() {
    if ($this->automagic == FALSE) {
      return $this->query;
    }
    $query = "SELECT ";
    $j = 0;
    for ($i = 0; $i < count($this->tables); $i++) {
      // In download mode make sure that the occurrences id is in the list.
      foreach ($this->tables[$i]['columns'] as $column) {
        if ($j != 0) {
          $query .= ",";
        }
        if ($column['func']=='') {
          $query .= " lt" . $i . "." . $column['name'] . " AS lt" . $i . "_" . $column['name'];
        }
        else {
          $query .= " " . preg_replace("/#parent#/", "lt" . $this->tables[$i]['parent'], preg_replace("/#this#/", "lt".$i, $column['func']))." AS lt".$i."_".$column['name'];
        }
        $j++;
      }
    }
    // Table list.
    $query .= " FROM ";
    for ($i = 0; $i < count($this->tables); $i++) {
      if ($i == 0) {
        $query .= $this->tables[$i]['tablename'] . " lt$i";
      }
      else {
        if ($this->tables[$i]['join'] != NULL) {
          $query .= " LEFT OUTER JOIN ";
        } else {
          $query .= " INNER JOIN ";
        }
        $query .= $this->tables[$i]['tablename'] . " lt" . $i . " ON (" . $this->tables[$i]['tableKey'] . " = " . $this->tables[$i]['parentKey'];
        if ($this->tables[$i]['where'] != NULL) {
          $query .= " AND " . preg_replace("/#this#/", "lt$i", $this->tables[$i]['where']);
        }
        $query .= ") ";
      }
    }
    // Where list.
    $previous = FALSE;
    if ($this->tables[0]['where'] != NULL) {
      $query .= " WHERE " . preg_replace("/#this#/", "lt0", $this->tables[0]['where']);
      $previous = TRUE;
    }
    // when in download mode set a where clause
    // only down load records which are complete or verified, and have not been downloaded before.
    // for the final download, only download thhose records which have gone through an initial download, and hence assumed been error checked.
    if ($this->download != 'OFF') {
      for ($i = 0; $i < count($this->tables); $i++) {
        if ($this->tables[$i]['tablename'] == "occurrences") {
          $query .= ($previous ? " AND " : " WHERE ") .
            " (lt$i.record_status in ('C'::bpchar, 'V'::bpchar) OR '" . $this->download . "'::text = 'OFF'::text) " .
              " AND (lt$i.downloaded_flag in ('N'::bpchar, 'I'::bpchar) OR '" . $this->download . "'::text != 'INITIAL'::text) " .
              " AND (lt$i.downloaded_flag = 'I'::bpchar OR ('" . $this->download . "'::text != 'CONFIRM'::text AND '" . $this->download . "'::text != 'FINAL'::text))";
          break;
        }
      }
    }
    return $query;
  }

  /**
   * Retrieve the query required to count the records.
   *
   * @return string
   *   SQL statement.
   */
  public function getCountQuery() {
    return str_replace('#count#', "COUNT($this->countFields)", ($this->countQueryBase ?? ''));
  }

  /**
   * Retrieve the query required to count the records, but with fields instead.
   *
   * SELECT ..., ..., ... instead of SELECT COUNT(...), allowing a limit to be
   * applied to prevent counting past the first page of a report grid. Used to
   * facilitate an optimisation to prevent bad abort early query plans being
   * chosen.
   *
   * @return string
   *   SQL statement.
   */
  public function getCountQueryWithRowData() {
    return str_replace('#count#', $this->countFields, $this->countQueryBase);
  }

  /**
   * Uses source-specific validation methods to check whether the report query is valid.
   */
  public function isValid(){}

  /**
   * Returns the order by clause for the query.
   *
   * @param array $providedParams
   *   List of parameters provided, allowing the report to selectively order by
   *   fields specified by param definitions.
   */
  public function getOrderClause(array $providedParams) {
    $paramOrderFields = [];
    foreach ($providedParams as $param => $value) {
      if ($value !== '' && isset($this->params[$param]) && isset($this->params[$param]['order_by'])) {
        $paramOrderFields[] = $this->params[$param]['order_by'];
      }
    }
    $r = $paramOrderFields ?: $this->order_by ?: [];
    return implode(', ', $r);
  }

  /**
   * Gets a list of parameters.
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * Gets a list of the columns.
   */
  public function getColumns() {
    if (empty($this->colsToInclude))
      return $this->columns;
    else {
      // user override of the columns to return
      $columns = [];
      foreach ($this->columns as $col => $coldef) {
        if (in_array($col, $this->colsToInclude)) {
          $columns[$col] = $coldef;
        }
      }
      return $columns;
    }
  }

  /**
   * Returns a description of the report appropriate to the level specified.
   */
  public function describeReport($descLevel) {
    switch ($descLevel) {
      case (ReportReader::REPORT_DESCRIPTION_BRIEF):
        return [
          'name' => $this->name,
          'title' => $this->getTitle(),
          'row_class' => $this->getRowClass(),
          'description' => $this->getDescription()
        ];

      case (ReportReader::REPORT_DESCRIPTION_FULL):
        // Everything.
        $r = [
          'name' => $this->name,
          'title' => $this->getTitle(),
          'description' => $this->getDescription(),
          'row_class' => $this->getRowClass(),
          'columns' => $this->columns,
          'parameters' => array_diff_key($this->params, $this->defaultParamValues),
          'query' => $this->query,
          'order_by' => $this->order_by
        ];
        // For direct email triggers, there can be a file attachment query.
        $attachment = $this->getAttachment();
        if (!empty($attachment)) {
          $r['attachment_query'] = $attachment['query'];
          $r['attachment_filename'] = $attachment['filename'];
        }
        return $r;

      case (ReportReader::REPORT_DESCRIPTION_DEFAULT):
      default:
        // At this report level, we include most of the useful stuff.
        return [
          'name' => $this->name,
          'title' => $this->getTitle(),
          'description' => $this->getDescription(),
          'row_class' => $this->getRowClass(),
          'columns' => $this->columns,
          'parameters' => array_diff_key($this->params, $this->defaultParamValues)
        ];
    }
  }

  /**
   */
  public function getAttributeDefns() {
    return $this->attributes;
  }

  public function getVagueDateProcessing() {
    return $this->vagueDateProcessing;
  }

  public function getDownloadDetails() {
    $thisDefn = new stdClass();
    $thisDefn->mode = $this->download;
    $thisDefn->id = 'occurrence_id';
    if ($this->automagic) {
      for ($i = 0; $i < count($this->tables); $i++) {
        if ($this->tables[$i]['tablename'] == 'occurrences') {
          // Warning, will not work with multiple occurrence tables.
          $thisDefn->id = 'lt' . $i . '_id';
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
  private function getRowClass() {
    return $this->row_class;
  }

  private function buildAttributeQuery($attributes) {
    $parentSingular = inflector::singular($this->tables[$attributes->parentTableIndex]['tablename']);
    if ($parentSingular == 'cache_occurrence') {
      $parentSingular = 'occurrence';
    }
    // This processing assumes some properties of the attribute tables - eg columns the data is stored in and deleted columns
    $query = "SELECT vt.".$parentSingular."_id as main_id,
      vt.text_value, vt.float_value, vt.int_value, vt.date_start_value, vt.date_end_value, vt.date_type_value,
      at.id, at.caption, at.data_type, at.termlist_id, at.multi_value ";
    // table list
    $from = ""; // this is built from back to front, to scan up the tree of tables that are only relevent to this attribute request.
    $i = $attributes->parentTableIndex;
    while(TRUE){
      if ($i == 0) {
        $from = $this->tables[$i]['tablename'] . " lt" . $i . $from;
        break;
      }
      else {
        $from = " INNER JOIN ".$this->tables[$i]['tablename']." lt".$i.
                " ON (".$this->tables[$i]['tableKey']." = ".$this->tables[$i]['parentKey'].
                ($this->tables[$i]['where'] != null ?
                    " AND ".preg_replace("/#this#/", "lt".$i, $this->tables[$i]['where']) :
                    "").") ".$from;
        $i = $this->tables[$i]['parent']; // next process the parent for this table, until we've scanned upto zero.
      }
    }
    $query .= " FROM ".$from;
    $query .= " INNER JOIN " . $parentSingular . "_attribute_values vt ON (vt." . $parentSingular . "_id = " . " lt" . $attributes->parentTableIndex . ".id and vt.deleted = FALSE) ";
    $query .= " INNER JOIN " . $parentSingular . "_attributes at ON (vt." . $parentSingular . "_attribute_id = at.id and at.deleted = FALSE) ";
    $query .= " INNER JOIN " . $parentSingular . "_attributes_websites rt ON (rt." . $parentSingular . "_attribute_id = at.id and rt.deleted = FALSE and (rt.restrict_to_survey_id = #" .
        $this->surveyParam."# or rt.restrict_to_survey_id is null)) ";
    // where list
    $previous=FALSE;
    if ($this->tables[0]['where'] != NULL) {
      $query .= " WHERE ".preg_replace("/#this#/", "lt0", $this->tables[0]['where']);
      $previous = TRUE;
    }
    if ($attributes->where != NULL) {
      $query .= ($previous ? " AND " : " WHERE ").$attributes->where;
    }
    $query .= " ORDER BY rt.form_structure_block_id, rt.weight, at.id, lt".$attributes->parentTableIndex.".id ";
    return $query;
  }

  /**
   * Retrieves the lookup values for a lookup parameter as a HTML table.
   *
   * @param array $param
   *   Parameter definition.
   *
   * @return string
   *   Table as HTML.
   */
  private function getLookupValuesAsTable(array $param) {
    if (empty($param['lookup_values'])) {
      return NULL;
    }
    $values = explode(',', $param['lookup_values']);
    $rows = [];
    foreach ($values as $value) {
      $tokens = explode(':', $value);
      if (count($tokens) === 2) {
        $rows[] = "<tr><th scope=\"row\">$tokens[0]</th><td>$tokens[1]</td></tr>";
      }
    }
    $rows = implode("<br/>    ", $rows);
    $table = <<<TBL
<table>
  <caption>Lookup values</caption>
  <thead>
    <tr>
      <th>Value</th>
      <th>Caption</th>
    </tr>
  </thead>
  <tbody>
    $rows
  </tbody>
</table>

TBL;
    return $table;
  }

  /**
   * Copies attributes from an element to an array.
   */
  private function copyOverAttributes($reader, &$arr, $attrs) {
    foreach ($attrs as $attr) {
      $val = $reader === NULL ? '' : $reader->getAttribute($attr);
      // Don't overwrite any existing setting with a blank value.
      if (!isset($arr[$attr]) || $val !== '') {
        $arr[$attr] = $val;
      }
    }
  }

  /**
   * Merges a parameter into the list of parameters read for the report. Updates existing
   * ones if there is a name match.
   * @todo Review the handling of $this->surveyParam
   */
  private function mergeParam($name, $reader = NULL) {
    // Some parts of the code assume the survey will be identified by a parameter called survey or survey_id.
    if ($name === 'survey_id' || $name === 'survey') {
      $this->surveyParam = $name;
    }
    if (!array_key_exists($name, $this->params)) {
      $this->params[$name] = [];
    }
    $this->copyOverAttributes($reader, $this->params[$name], [
      'display',
      'datatype',
      'allow_buffer',
      'fieldname',
      'alias',
      'emptyvalue',
      'default',
      'description',
      'query',
      'preprocess',
      'lookup_values',
      'population_call',
      'linked_to',
      'linked_filter_field',
      'order_by',
    ]);

    if ($this->params[$name]['datatype'] === 'lookup') {
      $this->params[$name]['description_extra'] = $this->getLookupValuesAsTable($this->params[$name]);
      $this->params[$name]['description'];
    }
    // if we have a default value, keep a list
    if (isset($this->params[$name]['default']) && $this->params[$name]['default'] !== NULL) {
      $this->defaultParamValues[$name] = $this->params[$name]['default'];
    }
    // Does the parameter define optional join elements which are associated
    // with specific parameter values?
    if ($reader !== NULL) {
      $paramXml = $reader->readInnerXML();
      if (!empty($paramXml)) {
        $reader = new XMLReader();
        // Wrap contents of param in a container so we only have 1 top level
        // node.
        $reader->XML("<container>$paramXml</container>");
        while ($reader->read()) {
          if ($reader->nodeType == XMLREADER::ELEMENT && $reader->name === 'join') {
            if (!isset($this->params[$name]['joins'])) {
              $this->params[$name]['joins'] = [];
            }
            $this->params[$name]['joins'][] = [
              'value' => $reader->getAttribute('value'),
              'operator' => $reader->getAttribute('operator'),
              'sql' => $reader->readString(),
            ];
          }
          if ($reader->nodeType == XMLREADER::ELEMENT && $reader->name === 'where') {
            if (!isset($this->params[$name]['wheres'])) {
              $this->params[$name]['wheres'] = [];
            }
            $this->params[$name]['wheres'][] = [
              'value' => $reader->getAttribute('value'),
              'operator' => $reader->getAttribute('operator'),
              'sql' => $reader->readString(),
            ];
          }
        }
      }
    }
  }

  /**
   * If a report declares that it uses the standard set of parameters, then load them.
   */
  public function loadStandardParams(&$providedParams, $sharing) {
    if ($this->loadStandardParamsSet) {
      $standardParamsHelper = "report_standard_params_{$this->loadStandardParamsSet}";
      $deprecated = $standardParamsHelper::getDeprecatedParameters();
      // For backwards compatibility, convert a few param names...
      foreach ($deprecated as $paramDef) {
        $this->convertDeprecatedParam($providedParams, $paramDef);
      }
      // Always include the operation params, as their default might be needed
      // even when no parameter is provided. E.g. the default website_list_op
      // param comes into effect if just a website_list is provided.
      $opParams = $standardParamsHelper::getOperationParameters();
      foreach ($opParams as $param => $cfg) {
        if (!empty($providedParams[$param])) {
          $this->params["{$param}_op"] = $cfg;
        }
        if (!empty($providedParams["{$param}_context"])) {
          $this->params["{$param}_op_context"] = $cfg;
        }
      }
      $params = $standardParamsHelper::getParameters();
      if ($this->loadLegacyStandardParamsSet) {
        $legacy = $standardParamsHelper::getLegacyStructureParameters();
        foreach ($legacy as $param => $array) {
          $params[$param] = array_merge($params[$param], $array);
        }
      }
      $this->defaultParamValues = array_merge($standardParamsHelper::getDefaultParameterValues(), $this->defaultParamValues);
      // Any defaults can be skipped if a context parameter is provided to
      // override the default. Doesn't apply to *_op defaults since these pair
      // with other parameters.
      foreach (array_keys($this->defaultParamValues) as $param) {
        if (!preg_match('/_op$/', $param) && isset($providedParams[$param . '_context'])) {
          unset($this->defaultParamValues[$param]);
        }
      }
      $providedParams = array_merge($this->defaultParamValues, $providedParams);
      // Load up the params for any which have a value provided.
      foreach ($params as $param => $cfg) {
        if (isset($providedParams[$param])) {
          if (isset($cfg['joins'])) {
            foreach ($cfg['joins'] as &$join) {
              if (!empty($join['sql'])) {
                $join['sql'] = preg_replace('/#alias:([a-z]+)#/', '$1', $join['sql']);
              }
            }
          }
          $this->params[$param] = $cfg;
        }
      }
      // now load any context parameters - i.e. filters defined by the user's permissions that must always apply.
      // Use a new loop so that prior changes to $cfg are lost.
      foreach ($params as $param => $cfg) {
        if (isset($providedParams[$param . '_context'])) {
          if (isset($cfg['joins'])) {
            foreach ($cfg['joins'] as &$join) {
              if (!empty($join['sql'])) {
                // construct a unique alias for any joined tables
                $join['sql'] = preg_replace('/#alias:([a-z]+)#/', '${1}_context', $join['sql']);
                // and ensure references to the param value point to the context version
                $join['sql'] = str_replace("#{$param}_op#", "#{$param}_op_context#", $join['sql']);
                $join['sql'] = str_replace("#$param#", "#{$param}_context#", $join['sql']);
              }
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
          $this->params[$param . '_context'] = $cfg;
        }
      }
    }
  }

  private function array_insert($array, $values, $offset) {
    return array_slice($array, 0, $offset, TRUE) + $values + array_slice($array, $offset, NULL, TRUE);
  }

  /**
   * Returns the metadata for all possible parameters for this report, including the standard
   * parameters.
   * @return array List of parameter configurations.
   */
  public function getAllParams() {
    $params = array_merge($this->params);
    if ($this->loadStandardParamsSet) {
      $standardParamsHelper = "report_standard_params_{$this->loadStandardParamsSet}";
      $params = array_merge($params, $standardParamsHelper::getParameters());
      $opParams = $standardParamsHelper::getOperationParameters();
      foreach ($opParams as $param => $cfg) {
        $params = $this->array_insert($params, array("{$param}_op" => $cfg),
            array_search($param, array_keys($params)) + 1);
      }
    }
    return $params;
  }

  /**
   * Convert deprecated parameter names for backwards compatibility.
   *
   * To retain backwards compatibility with previous versions of standard
   * params, we convert some param names.
   *
   * @param array $providedParams
   *   The array of provided parameters which will be modified.
   * @param array $paramPair
   *   The parameter mapping definition. The first array entry is the old param
   *   name, the second is the new one. The third entry is set to TRUE for any
   *   string parameters which should be quoted.
   */
  private function convertDeprecatedParam(array &$providedParams, array $paramDef) {
    if (count($paramDef) === 2) {
      // Default to not handle as string.
      $paramDef[] = FALSE;
    }
    list($from, $to, $string) = $paramDef;
    $quote = $string ? "'" : '';
    if (!empty($providedParams[$from]) && empty($providedParams[$to])) {
      kohana::log('debug', "Converting provided param $from - $to");
      $providedParams[$to] = $quote . $providedParams[$from] . $quote;
      unset($providedParams[$from]);
    }
    if (!empty($providedParams[$from . '_context']) && empty($providedParams[$to . '_context'])) {
      kohana::log('debug', "Converting provided param {$from}_context - {$to}_context");
      $providedParams[$to . '_context'] = $quote . $providedParams[$from . '_context'] . $quote;
    }
    if (!empty($providedParams['paramsFormExcludes'])) {
      $excludes = json_decode($providedParams['paramsFormExcludes'], TRUE);
      if (in_array($from, $excludes) || in_array("{$from}_context", $excludes)) {
        if (in_array($from, $excludes))
          $excludes[] = $to;
        if (in_array("{$from}_context", $excludes))
          $excludes[] = "{$to}_context";
        $providedParams['paramsFormExcludes'] = json_encode($excludes);
      }
    }
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
        'autodef' => FALSE
      );
    }
    // build a definition from the XML
    $def = [];
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

  private function mergeColumn($name, $display = '', $style = '', $feature_style='', $class='', $visible='', $img='',
    $orderby='', $mappable='', $autodef=TRUE) {
    if (array_key_exists($name, $this->columns)) {
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
      $this->columns[$name] = [
        'display' => $display,
        'style' => $style,
        'feature_style' => $feature_style,
        'class' => $class,
        'visible' => $visible == '' ? 'true' : $visible,
        'img' => $img == '' ? 'false' : $img,
        'orderby' => $orderby,
        'mappable' => empty($mappable) ? 'false' : $mappable,
        'autodef' => $autodef,
      ];
    }
  }

  private function setTable($tablename, $where) {
    $this->tables = [];
    $this->tableIndex = 0;
    $this->nextTableIndex = 1;
    $this->tables[$this->tableIndex] = [
      'tablename' => $tablename,
      'parent' => -1,
      'parentKey' => '',
      'tableKey' => '',
      'join' => '',
      'attributes' => '',
      'where' => $where,
      'columns' => [],
    ];
  }

  private function setSubTable($tablename, $parentKey, $tableKey, $join, $where) {
    if ($tableKey == '') {
      if ($parentKey == 'id') {
        $tableKey = 'lt' . $this->nextTableIndex . "." . (inflector::singular($this->tables[$this->tableIndex]['tablename'])) . '_id';
      } else {
        $tableKey = 'lt' . $this->nextTableIndex . '.id';
      }
    } else {
      $tableKey = 'lt' . $this->nextTableIndex . "." . $tableKey;
    }
    if ($parentKey == '') {
      $parentKey = 'lt' . $this->tableIndex . "." . (inflector::singular($tablename)) . '_id';
    }
    else {
      // Force the link as this table has foreign key to parent table, standard
      // naming convention.
      $parentKey = 'lt' . $this->tableIndex . '.' . $parentKey;
    }
    $this->tables[$this->nextTableIndex] = [
      'tablename' => $tablename,
      'parent' => $this->tableIndex,
      'parentKey' => $parentKey,
      'tableKey' => $tableKey,
      'join' => $join,
      'attributes' => '',
      'where' => $where,
      'columns' => [],
    ];
    $this->tableIndex = $this->nextTableIndex;
    $this->nextTableIndex++;
  }

  private function mergeTabColumn($name, $func = '', $display = '', $style = '', $feature_style = '', $class = '', $visible = '', $autodef = FALSE) {
    $found = FALSE;
    for ($r = 0; $r < count($this->tables[$this->tableIndex]['columns']); $r++){
      if ($this->tables[$this->tableIndex]['columns'][$r]['name'] == $name) {
        $found = TRUE;
        if ($func != '') {
          $this->tables[$this->tableIndex]['columns'][$r]['func'] = $func;
        }
      }
    }
    if (!$found) {
      $this->tables[$this->tableIndex]['columns'][] = [
        'name' => $name,
        'func' => $func,
      ];
      if ($display == '') {
        $display = $this->tables[$this->tableIndex]['tablename']." ".$name;
      }
    }
    // force visible if the column is already declared as visible. This prevents the id field from being forced to hidden if explicitly included.
    if (isset($this->columns['lt' . $this->tableIndex . "_" . $name]['visible']) && $this->columns['lt' . $this->tableIndex . "_" . $name]['visible'] == 'true')
      $visible = 'true';
    $this->mergeColumn('lt' . $this->tableIndex . "_" . $name, $display, $style, $feature_style, $class, $visible, 'false', $autodef);
  }

  private function setMergeTabColumn($name, $tablename, $separator, $where = '', $display = '') {
    // In this case the data for the column in merged into one, if there are more than one records
    // To do this we highjack the attribute handling functionality.
    $tableKey = (inflector::singular($this->tables[$this->tableIndex]['tablename'])) . '_id';

    $thisDefn = new stdClass();
    $thisDefn->caption = 'caption';
    $thisDefn->main_id = $tableKey; // main_id is the name of the column in the subquery holding the PK value of the parent table.
    $thisDefn->parentKey = "lt" . $this->tableIndex . "_id"; // parentKey holds the column in the main query to compare the main_id against.
    $thisDefn->id = 'id'; // id is the name of the column in the subquery holding the attribute id.
    $thisDefn->separator = $separator;
    $thisDefn->hideVagueDateFields = 'false';
    $thisDefn->columnPrefix = 'merge_' . count($this->attributes);

    if ($display == '') {
      $display = "$tablename $name";
    }

    $thisDefn->query = "SELECT $tableKey, '$display' as caption, '' as id, 'T' as data_type, $name as text_value, 't' as multi_value FROM $tablename " . ($where == '' ? '' : " WHERE $where");
    $this->attributes[] = $thisDefn;
    // Make sure id column of parent table is in list of columns returned from query.
    $this->mergeTabColumn('id', '', '', '', '', 'false', TRUE);
  }

  private function setAttributes($where, $separator, $hideVagueDateFields, $meaningIdLanguage) {
    $thisDefn = new stdClass;
    $thisDefn->caption = 'caption'; // caption is the name of the column in the subquery holding the attribute caption.
    $thisDefn->main_id = 'main_id'; // main_id is the name of the column in the subquery holding the PK value of the parent table.
    $thisDefn->parentKey = "lt" . $this->tableIndex . "_id"; // parentKey holds the column in the main query to compare the main_id against.
    $thisDefn->id = 'id'; // id is the name of the column in the subquery holding the attribute id.
    $thisDefn->separator = $separator;
    $thisDefn->hideVagueDateFields = $hideVagueDateFields;
    $thisDefn->columnPrefix = 'attr_' . $this->tableIndex . '_';
    // Folowing is used the query builder only.
    $thisDefn->parentTableIndex = $this->tableIndex;
    $thisDefn->where = $where;
    $thisDefn->meaningIdLanguage = $meaningIdLanguage;
    $thisDefn->query = $this->buildAttributeQuery($thisDefn);
    $this->attributes[] = $thisDefn;
    // Make sure id column of parent table is in list of columns returned from query.
    $this->mergeTabColumn('id', '', '', '', '', 'false', TRUE);
  }

  private function setDownload($mode) {
    $this->download = $mode;
  }

 /**
  * Infers parameters such as column names and parameters from the query string.
  * Column inference can handle queries where there is a nested select provided it has a
  * matching from. Commas that are part of nested selects or function calls are ignored
  * provided they are enclosed in brackets.
  */
  private function inferFromQuery() {
    // Find the columns we're searching for - nested between a SELECT and a
    // FROM. To ensure we can detect the words FROM, SELECT and AS, use a regex
    // to wrap spaces around them, then can do a regular string search.
    $this->query = preg_replace("/\b(select)\b/i", ' select ', $this->query);
    $this->query = preg_replace("/\b(from)\b/i", ' from ', $this->query);
    $this->query = preg_replace("/\b(as)\b/i", ' as ', $this->query);
    $i0 = strpos($this->query, ' select ') + 7;
    $nesting = 1;
    $offset = $i0;
    do {
      $nextSelect = strpos($this->query, ' select ', $offset);
      $nextFrom = strpos($this->query, ' from ', $offset);
      if ($nextSelect !== FALSE && $nextSelect < $nextFrom) {
        // Found start of sub-query.
        $nesting++;
        $offset = $nextSelect + 7;
      } else {
        $nesting--;
        if ($nesting != 0) {
          // Found end of sub-query.
          $offset = $nextFrom + 5;
        }
      }
    }
    while ($nesting > 0);

    $i1 = $nextFrom - $i0;
    // get the columns list, ignoring the marker to show where additional columns can be inserted
    $colString = str_replace('#fields#', '', substr($this->query, $i0, $i1));

    // Now divide up the list of columns, which are comma separated, but ignore
    // commas nested in brackets.
    $colStart = 0;
    $nextComma = strpos($colString, ',', $colStart);
    while ($nextComma !== FALSE) {
      // Loop through columns.
      $nextOpen = strpos($colString, '(', $colStart);
      while ($nextOpen !== FALSE && $nextComma !== FALSE && $nextOpen < $nextComma) {
        // Skipping commas in brackets.
        $offset = $this->strposclose($colString, $nextOpen) + 1;
        $nextComma = strpos($colString, ',', $offset);
        $nextOpen = strpos($colString, '(', $offset);
      }
      if ($nextComma !== FALSE) {
        // Extract column and move on to next.
        $cols[] = substr($colString, $colStart, ($nextComma - $colStart));
        $colStart = $nextComma + 1;
        $nextComma = strpos($colString, ',', $colStart);
      }
    }
    // Extract final column.
    $cols[] = substr($colString, $colStart);

    // We have cols, which may either be of the form 'x', 'table.x' or 'x as
    // y'. Either way the column name is the part after the last space and full
    // stop.
    foreach ($cols as $col) {
      // Break down by spaces.
      $b = explode(' ', trim($col));
      // Break down the part after the last space.
      $c = explode('.', array_pop($b));
      $d = array_pop($c);
      $this->mergeColumn(trim($d));
    }

    // Okay, now we need to find parameters, which we do with regex.
    preg_match_all('/#([a-z0-9_]+)#%/i', $this->query, $matches);
    // Here is why I remember (yet again) why I hate PHP...
    foreach ($matches[1] as $param) {
      $this->mergeParam($param);
    }
  }

  /**
   * Returns numeric pos of the closing bracket matching an opening bracket.
   *
   * @param string $haystack
   *   The string to search.
   * @param int $open
   *   The numeric position of the opening bracket.
   *
   * @return int
   *   The numeric position of the closing bracket or FALSE if not present.
   */
  private function strposclose($haystack, $open) {
    $nesting = 1;
    $offset = $open + 1;
    do {
      $nextOpen = strpos($haystack, '(', $offset);
      $nextClose = strpos($haystack, ')', $offset);
      if ($nextClose === FALSE) {
        return FALSE;
      }
      if ($nextOpen !== FALSE and $nextOpen < $nextClose) {
        $nesting++;
        $offset = $nextOpen + 1;
      }
      else {
        $nesting--;
        $offset = $nextClose + 1;
      }
    } while ($nesting > 0);
    return $offset - 1;
  }

}
