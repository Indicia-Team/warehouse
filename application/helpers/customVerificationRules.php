<?php

class customVerificationRules {

  /**
   * Returns help information.
   *
   * @todo Tranfser to the main Read the Docs documentation.
   */
  public static function helpBlock() {
    return <<<TXT
      <pre>

        Format for rulesets:
          Title *
          Description
          Fail icon *
          Fail message *
          Skip ruleset if life stage not one of ... (comma separated)
          Skip ruleset if latitude not greater than ...
          Skip ruleset if latitude not less than ...
          Skip ruleset if longitude not greater than ...
          Skip ruleset if longitude not less than ...
          Skip ruleset if location not one of ... (indexed)

        Data import format for rules:
          taxon
          taxon id
          fail icon
          fail message
          grid ref system - system code (e.g. OSGB or OSIE) for the grid references in 'grid refs' or 'limit to grid refs'. Applies to all grid refs specified for this rule.
          limit to stages - semi-colon separated list of life stages that this rule applies to.
          limit to grid refs - semi-colon separated list of grid references covering the records that this rule applies to.
          limit to min longitude - decimal minimum allowed longitude for the records that this rule applies to.
          limit to min latitude - decimal minimum allowed latitude for the records that this rule applies to.
          limit to max longitude - decimal maximum allowed longitude for the records that this rule applies to.
          limit to max latitude - decimal maximum allowed latitude for the records that this rule applies to.
          limit to location IDs - semi-colon separated list of location IDs.
          reverse rule - if set (TO TRUE/T?), then the outcome of the rule checks are reversed, e.g. geography rules define a region inside which records will be flagged, or an abundance check flag is raised for records with a count less than the defined value.
          rule type - one of the following:
            abundance - checks for records of a species which have an exact count given for their abundance and the count is greater than a certain value.
            geography - checks for records of a species that are outside an area which you define, e.g. a bounding box, grid reference, or administrative location. Can also find records north or south of a latitude line, or east or west of a longitude line.
            period - checks for records before or after a given year, e.g. can highlight records before the year of arrival of a newly arrived species.
            phenology - checks for records that don't fall in a defined time of year. Phenology check ranges can be specified using days of the year (1-366) or months (1-2).
            species_recorded - checks for any records of a species, e.g. can be used to build a rarity list ruleset. No additional parameters are required for this rule type.
          max individual count - required for abundance rules.
          grid refs - optional list of grid references the records are allowed in, for geography checks.
          min longitude - optional min decimal longitude allowed for records, for geography checks.
          min latitude - optional min decimal latitude allowed for records, for geography checks.
          max longitude - optional max decimal longitude allowed for records, for geography checks.
          max latitude - optional max decimal latitude allowed for records, for geography checks.
          location IDs - optional semi-colon separated list of indexed location IDs (i.e. higher geography), for geography checks.
          min year - optional minimum 4 digit year, for period checks.
          max year - optional maximum 4 digit year, for period checks.
          min month - optional minumum month (1-12) for phenology checks.
          max month - optional maximum month (1-12) for phenology checks.
          min day - optional minumum day within the specified min month (1-31) for phenology checks.
          max day - optional maximum day within the specified max month (1-31) for phenology checks.

      </pre>
TXT;
  }

  /**
   * Builds the request body that applies a custom ruleset.
   *
   * @param int $rulesetId
   *   ID of the ruleset to build the request for.
   * @param array $query
   *   Query containing the current outer filter to merge with.
   * @param int $userId
   *   Warehouse user ID.
   * @param int $esMajorVersion
   *   Major esVersion, if defined in the config. Version 6 has some code
   *   adaptations in Painless script for missing functions.
   *
   * @return string
   *   Request body.
   */
  public static function buildCustomRuleRequest($rulesetId, array $query, $userId, $esMajorVersion) {
    $db = new Database();
    $datetime = new DateTime();
    $timestamp = $datetime->format('Y-m-d H:i:s');

    $ruleset = $db->select('*')->from('custom_verification_rulesets')->where('id', $rulesetId)->get()->current();
    if (empty($ruleset)) {
      throw new exception("Ruleset id $rulesetId not found");
    }
    // Get the filters that limit the set of records this ruleset can be
    // applied to.
    $rulesetFilters = self::getRulesetFilters($db, $ruleset);
    if (!isset($query['bool'])) {
      $query['bool'] = [];
    }
    if (!isset($query['bool']['must'])) {
      $query['bool']['must'] = [];
    }
    $query['bool']['must'] = array_merge($query['bool']['must'], $rulesetFilters);
    $queryText = json_encode($query);

    $rules = $db->select('*')
      ->from('custom_verification_rules')
      ->where([
        'custom_verification_ruleset_id' => $ruleset->id,
        'deleted' => 'f',
      ])
      ->orderby('id')
      ->get();
    $ruleScripts = [];

    foreach ($rules as $rule) {
      $ruleScripts[] = self::getRuleScript($ruleset, $rule);
    }
    $allRuleScripts = implode("\n", $ruleScripts);
    $scriptSource = self::getPainlessFunctions($ruleset->id, $userId, $timestamp, $esMajorVersion);
    $scriptSource .= <<<PAINLESS

if (ctx._source.identification.custom_verification_rule_flags == null) {
  ctx._source.identification.custom_verification_rule_flags = new ArrayList();
}
ArrayList flags = ctx._source.identification.custom_verification_rule_flags;
flags.removeIf(a -> a.custom_verification_ruleset_id === $ruleset->id);
/* Prep some data to make the tests simpler. */
ArrayList geoIds = new ArrayList();
if (ctx._source.location.higher_geography !== null) {
  for (item in ctx._source.location.higher_geography) {
    geoIds.add(Integer.parseInt(item.id));
  }
}
def latLng = splitString(ctx._source.location.point, ',');
def lat = Float.parseFloat(latLng[0]);
def lng = Float.parseFloat(latLng[1]);
$allRuleScripts

PAINLESS;
    // Remove linefeed so the JSON format is valid.
    $scriptSourceEscaped = str_replace("\n", ' ', $scriptSource);
    $requestBody = <<<TXT
    {
      "script": {
        "source": "$scriptSourceEscaped",
        "lang": "painless"
      },
      "query": $queryText
    }
TXT;
    return $requestBody;
  }

  /**
   * Builds the request body that clears a user's flags from a dataset.
   *
   * @param array $query
   *   Query containing the current outer filter to merge with.
   * @param int $userId
   *   Warehouse user ID.
   *
   * @return string
   *   Request body.
   */
  public static function buildClearFlagsRequest(array $query, $userId) {
    // Adjust query so only fetching records that have a flag added by this
    // user.
    if (!isset($query['bool'])) {
      $query['bool'] = [];
    }
    if (!isset($query['bool']['must'])) {
      $query['bool']['must'] = [];
    }
    $query['bool']['must'][] = [
      'nested' => [
        'path' => 'identification.custom_verification_rule_flags',
        'query' => [
          'term' => [
            'identification.custom_verification_rule_flags.created_by_id' => $userId,
          ],
        ],
      ],
    ];
    $queryText = json_encode($query);
    $scriptSource = <<<PAINLESS
      if (ctx._source.identification.custom_verification_rule_flags != null) {
        ArrayList flags = ctx._source.identification.custom_verification_rule_flags;
        flags.removeIf(a -> a.created_by_id == $userId);
      }
PAINLESS;
    // Remove linefeed so the JSON format is valid.
    $scriptSourceEscaped = str_replace("\n", ' ', $scriptSource);
    $requestBody = <<<TXT
    {
      "script": {
        "source": "$scriptSourceEscaped",
        "lang": "painless"
      },
      "query": $queryText
    }
TXT;
    return $requestBody;
  }

  /**
   * Get the maximum days in a given month number.
   *
   * For validation purposes, so assumes a leap year.
   *
   * @param int $month
   *   Month number (1-12).
   *
   * @return int
   *   Number of days.
   */
  public function getDaysInMonth($month) {
    return [
      31,
      29,
      31,
      30,
      31,
      30,
      31,
      31,
      30,
      31,
      30,
      31,
    ][$month - 1];
  }

  /**
   * Converts a month and optional day in month to day of year.
   *
   * @param bool $defaultMonthEnd
   *   If the day is not specified, return the first day of the month (false)
   *   or the last day of the month (true).
   * @param int $month
   *   Month number (1-12).
   * @param int $day
   *   Optional day number (1-31).
   *
   * @return int
   *   Day of year (1-365).
   */
  public static function dayInMonthToDayInYear($defaultMonthEnd, $month, $day = NULL) {
    $day = $day ?? ($defaultMonthEnd ? self::getDaysInMonth($month) : 1);
    // Use current year as arbitrary default.
    $year = date('Y');
    $date = DateTimeImmutable::createFromFormat('Y-j-n', "$year-$month-$day");
    // Convert to day of year (0-365) and add one to match ES day_of_year field
    // (1-366).
    return ((integer) date('z', $date)) + 1;
  }

  /**
   * Retrieve filters that limit the records this ruleset can be applied to.
   *
   * @param Database $db
   *   Database connection.
   * @param object $ruleset
   *   Ruleset metadata read from the database.
   *
   * @return array
   *   List of filter definitions, e.g. life stage or geographic limits.
   */
  private static function getRulesetFilters(Database $db, $ruleset) {
    // The outer filter will be restricted to the taxa in the rules within the
    // set.
    $allTaxaKeys = $db->query("SELECT string_agg(DISTINCT taxon_external_key, ',') as keylist FROM custom_verification_rules WHERE deleted=false AND custom_verification_ruleset_id=$ruleset->id")->current()->keylist;
    if (empty($allTaxaKeys)) {
      throw new exception('No rules in this ruleset');
    }
    $rulesetFilters = [
      ['terms' => ['taxon.accepted_taxon_id' => explode(',', $allTaxaKeys)]],
    ];
    // Also limit to stages if the ruleset has limit_to_stages set.
    if (!empty($ruleset->limit_to_stages)) {
      $stages = str_getcsv(substr($ruleset->limit_to_stages, 1, strlen($ruleset->limit_to_stages) - 2));
      $rulesetFilters[] = ['terms' => ['occurrence.life_stage' => $stages]];
    }
    // Finally, limit geography if specified in the ruleset.
    if (!empty($ruleset->limit_to_geography)) {
      $geoLimits = json_decode($ruleset->limit_to_geography);
      // Limit on a bounding box.
      if (!empty($geoLimits->min_lat) || !empty($geoLimits->max_lat) || !empty($geoLimits->min_lng) || !empty($geoLimits->max_lng)) {
        $rulesetFilters[] = [
          'geo_bounding_box' => [
            'location.point' => [
              'top' => empty($geoLimits->max_lat) ? 90 : $geoLimits->max_lat,
              'left' => empty($geoLimits->min_lng) ? -180 : $geoLimits->min_lng,
              'bottom' => empty($geoLimits->min_lat) ? -90 : $geoLimits->min_lat,
              'right' => empty($geoLimits->max_lng) ? 180 : $geoLimits->max_lng,
            ],
          ],
        ];
      }
      // Limit on higher geography (indexed location) IDs.
      if (!empty($geoLimits->location_ids)) {
        $rulesetFilters[] = [
          'nested' => [
            'path' => 'location.higher_geography',
            'query' => [
              'terms' => [
                'location.higher_geography.id' => $geoLimits->location_ids,
              ],
            ],
          ],
        ];
      }
      // @todo Grid ref.
    }
    return $rulesetFilters;
  }

  /**
   * Return the script required to test a record against a single rule.
   *
   * @param obj $ruleset
   *   Ruleset metadata read from the database.
   * @param obj $rule
   *   Rule metadata read from the database.
   *
   * @return string
   *   Painless script to test the current document against a rule.
   */
  private static function getRuleScript($ruleset, $rule) {
    // Message and icon are set in the ruleset but can be overridden by a rule.
    $message = $rule->fail_message ?? $ruleset->fail_message;
    $icon = $rule->fail_icon ?? $ruleset->fail_icon;
    $ruleParams = json_decode($rule->definition);
    $checks = [];
    $ruleIsToBeAppliedChecks = implode(' && ', self::getApplicabilityChecksForRule($rule));

    switch ($rule->rule_type) {
      case 'abundance':
        self::getAbundanceChecks($rule, $ruleParams, $checks);
        break;

      case 'geography':
        self::getGeographyChecks($rule, $ruleParams, $checks);
        break;

      case 'period':
        self::getPeriodChecks($rule, $ruleParams, $checks);
        break;

      case 'phenology':
        self::getPhenologyChecks($rule, $ruleParams, $checks);
        break;

      case 'species_recorded':
        // Just a presence check so no additional checks required to fire the
        // rule.
        break;

      default:
        throw new exception("Unrecognised rule type $rule->type");
    }

    $testForFail = implode(' || ', $checks);
    return <<<TXT
          /* Rule ID $rule->id. */
          if ($ruleIsToBeAppliedChecks) {
            if ($testForFail) {
              flags.add(failInfo($rule->id, '$icon', '$message'));
            }
          }
TXT;
  }

  /**
   * Define applicability limits for each specific rule.
   *
   * A rule is always limited to a specific taxon, but a rule can also be
   * limited to only certain life stages or by geography.
   *
   * @param obj $rule
   *   Rule data from the database.
   *
   * @return array
   *   List of filter clauses in Painless script language.
   */
  private static function getApplicabilityChecksForRule($rule) {
    // Start with a filter on the taxon ID.
    $applicabilityCheckList = [
      "ctx._source.taxon.accepted_taxon_id == '$rule->taxon_external_key'",
    ];
    // Rule may be only applicable to certain stages.
    if (!empty($rule->limit_to_stages)) {
      $stages = str_getcsv(substr($rule->limit_to_stages, 1, strlen($rule->limit_to_stages) - 2));
      // Escape so can be inserted into Painless script string.
      $stages = array_map(function ($stage) {
        return "'" . str_replace("'", "\\\'", strtolower($stage)) . "'";
      }, $stages);
      $stageTxt = implode(', ', $stages);
      $applicabilityCheckList[] = "ctx._source.occurrence.life_stage != null";
      $applicabilityCheckList[] = "[$stageTxt].indexOf(ctx._source.occurrence.life_stage.toLowerCase()) >= 0";
    }
    // Rule may be only applicable to a geographic area. Note this is a test
    // for which records to include, not a test for which records to fail, so
    // operators are reverse direction to the rule test.
    if (!empty($rule->limit_to_geography)) {
      $geoLimits = json_decode($rule->limit_to_geography);
      self::geographyToPainlessChecks($geoLimits, TRUE, TRUE, $applicabilityCheckList);
    }
    return $applicabilityCheckList;
  }

  /**
   * Convert a geography definition to Painless script check clauses.
   *
   * Take the geography defined for a ruleset limit, rule limit, or a geography
   * rule check and convert it to filter clauses in Painless script format.
   *
   * @param object $geoParams
   *   Geography definition parameters, supports the following:
   *   * min_lat
   *   * max_lat
   *   * min_lng
   *   * max_lng
   *   * location_ids
   *   * grid_refs + grid_ref_system
   * @param bool $includeIfTouches
   *   Should the result include records where the point lies on the line, or
   *   only those which are completely over the line.
   * @param bool $includeRecordsWhichPassChecks
   *   Should the result include the records which pass the defined checks, or
   *   fail? Default for a limit is to include records which pass, default for
   *   a rule check is to include records which fail unless reverse_rule is
   *   true.
   * @param array &$checks
   *   List of Painless checks that additional checks will be appended to.
   *
   * @todo Consider whether we need to occurrences as grid squares instead of
   *   just points.
   */
  private static function geographyToPainlessChecks($geoParams, $includeIfTouches, $includeRecordsWhichPassChecks, array &$checks) {
    // Prepare operators according to the reverse rule setting.
    $eq = $includeIfTouches ? '=' : '';
    $opMin = ($includeRecordsWhichPassChecks ? '>' : '<') . $eq;
    $opMax = ($includeRecordsWhichPassChecks ? '<' : '>') . $eq;
    // Latitude longitude range checks.
    if (!empty($geoParams->min_lat)) {
      $checks[] = "lat $opMin $geoParams->min_lat";
    }
    if (!empty($geoParams->max_lat)) {
      $checks[] = "lat $opMax $geoParams->max_lat";
    }
    if (!empty($geoParams->min_lng)) {
      $checks[] = "lng $opMin $geoParams->min_lng";
    }
    if (!empty($geoParams->max_lng)) {
      $checks[] = "lng $opMax $geoParams->max_lng";
    }
    // Checks against indexed locations (higher geogprahy IDs) such as Vice
    // Counties.
    if (!empty($geoParams->location_ids)) {
      $checkInOrOut = $includeRecordsWhichPassChecks ? '' : '!';
      $checks[] = $checkInOrOut . 'higherGeoIntersection([' . implode(',', $geoParams->location_ids) . '], geoIds)';
    }
    if (!empty($geoParams->grid_refs)) {
      if (empty($geoParams->grid_ref_system) || !spatial_ref::is_valid_system($geoParams->grid_ref_system)) {
        throw new exception('Grid references specified without a valid grid ref system.');
      }
      // A list of checks to determine if record inside this square.
      $inSquareCheckCode = [];
      foreach ($geoParams->grid_refs as $gridRef) {
        $webMercatorWkt = spatial_ref::sref_to_internal_wkt($gridRef, $geoParams->grid_ref_system);
        if (strpos($webMercatorWkt, 'POLYGON((') === FALSE) {
          throw new exception('Grid reference given is not actually a grid square.');
        }
        $latLngWkt = spatial_ref::internal_wkt_to_wkt($webMercatorWkt, 4326);
        $coordString = preg_replace(['/^POLYGON\(\(/', '/\)\)$/'], ['', ''], $latLngWkt);
        $coordList = explode(',', $coordString);
        $minLat = NULL;
        $maxLat = NULL;
        $minLng = NULL;
        $maxLng = NULL;
        foreach ($coordList as $coordPair) {
          [$x, $y] = explode(' ', $coordPair);
          $minLat = $minLat === NULL ? $y : min($minLat, $y);
          $maxLat = $maxLat === NULL ? $y : max($maxLat, $y);
          $minLng = $minLng === NULL ? $x : min($minLng, $y);
          $maxLng = $maxLng === NULL ? $x : max($maxLng, $y);
        }
        $inSquareCheckCode[] = "(lat >$eq $minLat && lat <$eq $maxLat && lng >$eq $minLng && lng <$eq $maxLng)";
      }
      // Either check if record in any of the squares, or check that record in
      // none of the squares.
      $checks[] = $includeRecordsWhichPassChecks ? '(' . implode(' || ', $inSquareCheckCode) . ')' : '(!' . implode(' && !', $inSquareCheckCode) . ')';
    }
  }

  /**
   * Return the code clause required to test a record against abundance rules.
   *
   * @param object $rule
   *   Rule metadata read from the database.
   * @param object $ruleParams
   *   Params and values defined for the rule.
   * @param array $checks
   *   List of checks which will be added to. Checks will be later combined
   *   with an OR operation.
   */
  private static function getAbundanceChecks($rule, $ruleParams, array &$checks) {
    // Prepare operators according to the reverse_rule setting.
    $opEnd = $rule->reverse_rule === 't' ? '<' : '>';
    $checks[] = "ctx._source.occurrence.individual_count != null && Integer.parseInt(ctx._source.occurrence.individual_count) $opEnd $ruleParams->max_individual_count";
  }

  /**
   * Return the code clause required to test a record against a geography rule.
   *
   * @param object $rule
   *   Rule metadata read from the database.
   * @param object $ruleParams
   *   Params and values defined for the rule.
   * @param array $checks
   *   List of checks which will be added to. Checks will be later combined
   *   with an OR operation.
   *
   * @return string
   *   Painless script to test the current document against a rule.
   */
  private static function getGeographyChecks($rule, $ruleParams, array &$checks) {
    return self::geographyToPainlessChecks($ruleParams, FALSE, $rule->reverse_rule === 't', $checks);
  }

  /**
   * Return the code clause required to test a record against a period rule.
   *
   * @param object $rule
   *   Rule metadata read from the database.
   * @param object $ruleParams
   *   Params and values defined for the rule.
   * @param array $checks
   *   List of checks which will be added to. Checks will be later combined
   *   with an OR operation.
   */
  private static function getPeriodChecks($rule, $ruleParams, array &$checks) {
    // Prepare operators according to the reverse_rule setting.
    $opStart = $rule->reverse_rule === 't' ? '>' : '<';
    $opEnd = $rule->reverse_rule === 't' ? '<' : '>';
    if (!empty($ruleParams->min_year)) {
      $checks[] = "Integer.parseInt(ctx._source.event.year) $opStart $ruleParams->min_year";
    }
    if (!empty($ruleParams->max_year)) {
      $checks[] = "Integer.parseInt(ctx._source.event.year) $opEnd $ruleParams->max_year";
    }
  }

  /**
   * Return the code clause required to test a record against a phenology rule.
   *
   * @param object $rule
   *   Rule metadata read from the database.
   * @param object $ruleParams
   *   Params and values defined for the rule.
   * @param array $checks
   *   List of checks which will be added to. Checks will be later combined
   *   with an OR operation.
   */
  private static function getPhenologyChecks($rule, $ruleParams, array &$checks) {
    // Prepare operators according to the reverse_rule setting.
    $opStart = $rule->reverse_rule === 't' ? '>' : '<';
    $opEnd = $rule->reverse_rule === 't' ? '<' : '>';
    if (!empty($ruleParams->min_month)) {
      if (!empty($ruleParams->min_day)) {
        $minDoy = self::dayInMonthToDayInYear(FALSE, $ruleParams->min_month, $ruleParams->min_day);
        $checks[] = "Integer.parseInt(ctx._source.event.day_of_year) $opStart $minDoy";
      }
      else {
        $checks[] = "Integer.parseInt(ctx._source.event.month) $opStart $ruleParams->min_month";
      }
    }
    if (!empty($ruleParams->max_month)) {
      if (!empty($ruleParams->max_day)) {
        $maxDoy = self::dayInMonthToDayInYear(TRUE, $ruleParams->max_month, $ruleParams->max_day);
        $checks[] = "Integer.parseInt(ctx._source.event.day_of_year) $opEnd $maxDoy";
      }
      else {
        $checks[] = "Integer.parseInt(ctx._source.event.month) $opEnd $ruleParams->max_month";
      }
    }
  }

  /**
   * Retrieves functions to add to Painless custom rule scripts.
   *
   * A couple of general purpose functions. Also adds a splitString function
   * which is version safe (unlike String.splitOnToken).
   *
   * @param int $rulesetId
   *   ID of the ruleset.
   * @param int $userId
   *   User warehouse ID.
   * @param string $timestamp
   *   Formatted timestamp to store against flags.
   * @param string $esMajorVersion
   *   Elasticsearch major version numbers, so polyfills for unsupported
   *   functions can be added.
   *
   * @return string
   *   Painless script.
   */
  private static function getPainlessFunctions($rulesetId, $userId, $timestamp, $esMajorVersion) {
    $functions = <<<PAINLESS

/* Build an failure flag object to store in the document */
HashMap failInfo(int ruleId, String icon, String message) {
  return [
    'custom_verification_ruleset_id': $rulesetId,
    'custom_verification_rules_id': ruleId,
    'created_by_id': $userId,
    'result': 'fail',
    'icon': icon,
    'message': message,
    'check_date_time': '$timestamp'
  ];
}

/* Check if higher geography of a document intersects with a list of location Ids. */
boolean higherGeoIntersection(def higherGeoList, ArrayList list) {
  ArrayList recordGeoIds = new ArrayList();
  if (higherGeoList !== null) {
    for (id in higherGeoList) {
      recordGeoIds.add(id);
    }
  }
  recordGeoIds.retainAll(list);
  return recordGeoIds.size() > 0;
}

PAINLESS;
    // Elasticsearch 6 doesn't support splitOnToken - see
    // https://github.com/elastic/elasticsearch/issues/20952. Can be dropped
    // once BRC community warehouse on ES 7+.
    if ($esMajorVersion === 6) {
      $functions .= <<<PAINLESS

List splitString(String input, String ch) {
  int off = 0;
  int next = 0;
  ArrayList list = new ArrayList();

  while ((next = input.indexOf(ch, off)) != -1) {
    list.add(input.substring(off, next));
    off = next + 1;
  }

  if (off == 0) {
    list.add(input);
    return list;
  }

  list.add(input.substring(off, input.length()));

  return list;
}

PAINLESS;
    }
    else {
      $functions .= <<<PAINLESS

String[] splitString(String input, String ch) {
  return input.splitOnToken(ch);
}

PAINLESS;
    }
    return $functions;
  }

}
