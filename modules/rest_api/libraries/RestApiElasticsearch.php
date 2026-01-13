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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

// Max load from ES, keep fairly low to avoid PHP memory overload.
define('MAX_ES_SCROLL_SIZE', 2000);
define('SCROLL_TIMEOUT', '5m');

/**
 * Library class to support Elasticsearch proxied requests.
 */
class RestApiElasticsearch {

  /**
   * Defines which ES CSV column download template to use.
   *
   * Only supports "default" or empty string currently.
   *
   * @var string
   */
  private $esCsvTemplate = 'default';

  /**
   * Elastic proxy configuration key.
   *
   * Set to the key of the configuration section, if using Elasticsearch.
   *
   * @var bool
   */
  private $elasticProxy;

  /**
   * Resource filtering options.
   *
   * Options applied to the resource (e.g. from the configuration for a
   * client proj_id, either in config or in the rest_api_client_connections
   * table).
   *
   * @var array
   */
  private $resourceOptions;

  /**
   * For ES paged downloads, holds the mode (scroll or composite).
   *
   * @var string
   */
  private $pagingMode = 'off';

  /**
   * For ES paged downloads, holds the current request state.
   *
   * Either initial or nextPage.
   *
   * @var string
   */
  private $pagingModeState;

  /**
   * Templates for ES CSV output.
   *
   * @var array
   */
  private $esCsvTemplates = [
    "default" => [
      ['caption' => 'Record ID', 'field' => 'id'],
      ['caption' => 'RecordKey', 'field' => '_id'],
      ['caption' => 'Sample ID', 'field' => 'event.event_id'],
      ['caption' => 'Date interpreted', 'field' => '#event_date#'],
      ['caption' => 'Date start', 'field' => 'event.date_start'],
      ['caption' => 'Date end', 'field' => 'event.date_end'],
      ['caption' => 'Recorded by', 'field' => 'event.recorded_by'],
      ['caption' => 'Determined by', 'field' => 'identification.identified_by'],
      ['caption' => 'Grid reference', 'field' => 'location.output_sref'],
      ['caption' => 'System', 'field' => 'location.output_sref_system'],
      [
        'caption' => 'Coordinate uncertainty (m)',
        'field' => 'location.coordinate_uncertainty_in_meters',
      ],
      ['caption' => 'Lat/Long', 'field' => 'location.point'],
      ['caption' => 'Location name', 'field' => '#sitename:obscureifsensitive#'],
      ['caption' => 'Sensitive location', 'field' => '#sitename:showifsensitive#'],
      ['caption' => 'Higher geography', 'field' => '#higher_geography::name#'],
      [
        'caption' => 'Vice County',
        'field' => '#higher_geography:Vice County:name#',
      ],
      [
        'caption' => 'Vice County number',
        'field' => '#higher_geography:Vice County:code#',
      ],
      ['caption' => 'Identified by', 'field' => 'identification.identified_by'],
      ['caption' => 'Taxon accepted name', 'field' => 'taxon.accepted_name'],
      ['caption' => 'Taxon recorded name', 'field' => 'taxon.taxon_name'],
      ['caption' => 'Taxon common name', 'field' => 'taxon.vernacular_name'],
      ['caption' => 'Taxon group', 'field' => 'taxon.group'],
      ['caption' => 'Kindom', 'field' => 'taxon.kingdom'],
      ['caption' => 'Phylum', 'field' => 'taxon.phylum'],
      ['caption' => 'Order', 'field' => 'taxon.order'],
      ['caption' => 'Family', 'field' => 'taxon.family'],
      ['caption' => 'Genus', 'field' => 'taxon.genus'],
      ['caption' => 'Taxon Version Key', 'field' => 'taxon.taxon_id'],
      [
        'caption' => 'Accepted Taxon Version Key',
        'field' => 'taxon.accepted_taxon_id',
      ],
      ['caption' => 'Sex', 'field' => 'occurrence.sex'],
      ['caption' => 'Stage', 'field' => 'occurrence.life_stage'],
      ['caption' => 'Quantity', 'field' => 'occurrence.organism_quantity'],
      ['caption' => 'Zero abundance', 'field' => 'occurrence.zero_abundance'],
      ['caption' => 'Sensitive', 'field' => 'metadata.sensitive'],
      [
        'caption' => 'Record status',
        'field' => 'identification.verification_status',
      ],
      [
        'caption' => 'Record substatus',
        'field' => '#null_if_zero:identification.verification_substatus#',
      ],
      ['caption' => 'Query status', 'field' => 'identification.query'],
      ['caption' => 'Verifier', 'field' => 'identification.verifier.name'],
      ['caption' => 'Verified on', 'field' => 'identification.verified_on'],
      ['caption' => 'Website', 'field' => 'metadata.website.title'],
      ['caption' => 'Survey dataset', 'field' => 'metadata.survey.title'],
      ['caption' => 'Media', 'field' => '#occurrence_media#'],
    ],
    "easy-download" => [
      ['caption' => 'ID', 'field' => 'id'],
      ['caption' => 'RecordKey', 'field' => '_id'],
      ['caption' => 'External key', 'field' => 'occurrence.source_system_key'],
      [
        'caption' => 'Source',
        'field' => '#datasource_code:<wt> | <st> {|} <gt>#',
      ],
      ['caption' => 'Rank', 'field' => 'taxon.taxon_rank'],
      ['caption' => 'Taxon', 'field' => 'taxon.accepted_name'],
      ['caption' => 'Common name', 'field' => 'taxon.vernacular_name'],
      ['caption' => 'Taxon group', 'field' => 'taxon.group'],
      ['caption' => 'Kingdom', 'field' => 'taxon.kingdom'],
      ['caption' => 'Order', 'field' => 'taxon.order'],
      ['caption' => 'Family', 'field' => 'taxon.family'],
      ['caption' => 'TaxonVersionKey', 'field' => 'taxon.accepted_taxon_id'],
      ['caption' => 'Site name', 'field' => '#sitename:obscureifsensitive#'],
      ['caption' => 'Sensitive site', 'field' => '#sitename:showifsensitive#'],
      ['caption' => 'Original map ref', 'field' => 'location.input_sref'],
      ['caption' => 'Latitude', 'field' => '#lat:decimal#'],
      ['caption' => 'Longitude', 'field' => '#lon:decimal#'],
      [
        'caption' => 'Projection (input)',
        'field' => '#sref_system:location.input_sref_system:alphanumeric#',
      ],
      [
        'caption' => 'Precision',
        'field' => 'location.coordinate_uncertainty_in_meters',
      ],
      ['caption' => 'Output map ref', 'field' => 'location.output_sref_blurred'],
      [
        'caption' => 'Projection (output)',
        'field' => '#sref_system:location.output_sref_system_blurred:alphanumeric#',
      ],
      ['caption' => 'Sensitive output map ref', 'field' => '#conditional_value:location.output_sref:metadata.sensitivity_blur:=:F#'],
      ['caption' => 'Biotope', 'field' => 'event.habitat'],
      [
        'caption' => 'VC number',
        'field' => '#higher_geography:Vice County:code#',
      ],
      [
        'caption' => 'Vice County',
        'field' => '#higher_geography:Vice County:name#',
      ],
      ['caption' => 'Date interpreted', 'field' => '#event_date#'],
      ['caption' => 'Date from', 'field' => 'event.date_start'],
      ['caption' => 'Date to', 'field' => 'event.date_end'],
      ['caption' => 'Date type', 'field' => 'event.date_type'],
      ['caption' => 'Sample method', 'field' => 'event.sampling_protocol'],
      ['caption' => 'Recorder', 'field' => 'event.recorded_by'],
      ['caption' => 'Determiner', 'field' => 'identification.identified_by'],
      [
        'caption' => 'Recorder certainty',
        'field' => 'identification.recorder_certainty',
      ],
      ['caption' => 'Sex', 'field' => 'occurrence.sex'],
      ['caption' => 'Stage', 'field' => 'occurrence.life_stage'],
      [
        'caption' => 'Count of sex or stage',
        'field' => 'occurrence.organism_quantity',
      ],
      ['caption' => 'Zero abundance', 'field' => 'occurrence.zero_abundance'],
      ['caption' => 'Sensitive', 'field' => 'metadata.sensitive'],
      ['caption' => 'Comment', 'field' => 'occurrence.occurrence_remarks'],
      ['caption' => 'Sample comment', 'field' => 'event.event_remarks'],
      ['caption' => 'Images', 'field' => '#occurrence_media#'],
      [
        'caption' => 'Input on date',
        'field' => '#datetime:metadata.created_on:d/m/Y H\:i#',
      ],
      [
        'caption' => 'Last edited on date',
        'field' => '#datetime:metadata.updated_on:d/m/Y H\:i#',
      ],
      [
        'caption' => 'Verification status 1',
        'field' => '#verification_status:astext#',
      ],
      [
        'caption' => 'Verification status 2',
        'field' => '#verification_substatus:astext#',
      ],
      ['caption' => 'Query', 'field' => '#query:astext#'],
      ['caption' => 'Verifier', 'field' => 'identification.verifier.name'],
      [
        'caption' => 'Verified on',
        'field' => '#datetime:identification.verified_on:d/m/Y H\:i#',
      ],
      ['caption' => 'Licence', 'field' => 'metadata.licence_code'],
      [
        'caption' => 'Automated checks',
        'field' => '#true_false:identification.auto_checks.result:Passed checks:Failed checks#',
      ],
    ],
    "mapmate" => [
      ['caption' => 'Taxon', 'field' => 'taxon.accepted_name'],
      ['caption' => 'Site', 'field' => '#sitename:mapmate#'],
      ['caption' => 'Sensitive site', 'field' => '#sitename:showifsensitive#'],
      ['caption' => 'Gridref', 'field' => 'location.output_sref_blurred'],
      ['caption' => 'Sensitive gridref', 'field' => '#conditional_value:location.output_sref:metadata.sensitivity_blur:=:F#'],
      [
        'caption' => 'VC',
        'field' => '#higher_geography:Vice County:code:mapmate#',
      ],
      ['caption' => 'Recorder', 'field' => '#truncate:event.recorded_by:62#'],
      ['caption' => 'Determiner', 'field' => '#determiner:mapmate#'],
      ['caption' => 'Date', 'field' => '#event_date:mapmate#'],
      ['caption' => 'Quantity', 'field' => '#organism_quantity:mapmate#'],
      ['caption' => 'Method', 'field' => '#method:mapmate#'],
      ['caption' => 'Sex', 'field' => '#sex:mapmate#'],
      ['caption' => 'Stage', 'field' => '#life_stage:mapmate#'],
      ['caption' => 'Sensitive', 'field' => 'metadata.sensitive'],
      ['caption' => 'Status', 'field' => '#constant:Not recorded#'],
      [
        'caption' => 'Comment',
        'field' => '#sample_occurrence_comment:nonewline:notab:addref#',
      ],
      ['caption' => 'RecordKey', 'field' => '_id'],
      ['caption' => 'Common name', 'field' => 'taxon.vernacular_name'],
      [
        'caption' => 'Source',
        'field' => '#datasource_code:<wt> | <st> {|} <gt>#',
      ],
      [
        'caption' => 'NonNumericQuantity',
        'field' => '#organism_quantity:exclude_integer#',
      ],
      [
        'caption' => 'Input on date',
        'field' => '#datetime:metadata.created_on:d/m/Y H\:i\:s#',
      ],
      [
        'caption' => 'Last edited on date',
        'field' => '#datetime:metadata.updated_on:d/m/Y G\:i\:s#',
      ],
      [
        'caption' => 'Verification status 1',
        'field' => '#verification_status:astext#',
      ],
      [
        'caption' => 'Verification status 2',
        'field' => '#verification_substatus:astext#',
      ],
      ['caption' => 'Query', 'field' => '#query:astext#'],
      ['caption' => 'Licence', 'field' => 'metadata.licence_code'],
      ['caption' => 'Verified by', 'field' => 'identification.verifier.name'],
      ['caption' => 'Rank', 'field' => 'taxon.taxon_rank'],
    ]
  ];

  /**
   * The columns specified in the request to add to the template.
   *
   * @var array
   */
  private $esCsvTemplateAddColumns;

  /**
   * The columns specified in the request to remove from the template.
   *
   * @var array
   */
  private $esCsvTemplateRemoveColumns;

  /**
   * Constructor.
   *
   * @param string $elasticProxy
   *   Name of the Elastic proxy being used from the REST config file.
   * @param array $resourceOptions
   *   Options applied to the resource (e.g. from the configuration for a
   *   client proj_id, either in config or in the rest_api_client_connections
   *   table).
   */
  public function __construct($elasticProxy, array $resourceOptions = []) {
    $this->elasticProxy = $elasticProxy;
    $this->resourceOptions = $resourceOptions;
  }

  /**
   * Ensures a requested ES resource name is allowed.
   *
   * Checks against the 'allowed' entry in the config, which contains regular
   * expressions for resource patterns that are allowed.
   */
  public function checkResourceAllowed() {
    $esConfig = kohana::config('rest.elasticsearch');
    $thisProxyCfg = $esConfig[$this->elasticProxy];
    $resource = str_replace("$_SERVER[SCRIPT_NAME]/services/rest/$this->elasticProxy/", '', $_SERVER['PHP_SELF']);
    if (isset($thisProxyCfg['allowed'])) {
      // OPTIONS request always allowed.
      $allowed = $_SERVER['REQUEST_METHOD'] === 'OPTIONS';
      if (!$allowed) {
        // Not options, so need to check config allows the method/resource
        // combination.
        if (isset($thisProxyCfg['allowed'][strtolower($_SERVER['REQUEST_METHOD'])])) {
          foreach (array_keys($thisProxyCfg['allowed'][strtolower($_SERVER['REQUEST_METHOD'])]) as $regex) {
            if (preg_match($regex, $resource)) {
              $allowed = TRUE;
            }
          }
        }
        if (!$allowed) {
          RestObjects::$apiResponse->fail('Bad request', 400,
            "Elasticsearch request $resource ($_SERVER[REQUEST_METHOD]) disallowed by Warehouse REST API proxy configuration.");
        }
      }
    }
  }

  /**
   * Handles a request to Elasticsearch via a proxy.
   *
   * @param object|string $requestBody
   *   Request payload.
   * @param string $format
   *   Response format, e.g. 'csv', 'json'.
   * @param bool $ret
   *   Set to TRUE if the response should be returned rather than echoed.
   * @param string $resource
   *   Resource the request is for. Calculated from the request if not
   *   specified.
   * @param bool $requestIsRawString
   *   Set to TRUE if the request is a raw string to be sent as-is rather than
   *   an object to be encoded as a string.
   */
  public function elasticRequest($requestBody, $format, $ret = FALSE, $resource = NULL, $requestIsRawString = FALSE) {
    $esConfig = kohana::config('rest.elasticsearch');
    $thisProxyCfg = $esConfig[$this->elasticProxy];
    if (!$resource) {
      $resource = str_replace("$_SERVER[SCRIPT_NAME]/services/rest/$this->elasticProxy/", '', $_SERVER['PHP_SELF']);
    }
    $url = "$thisProxyCfg[url]/$thisProxyCfg[index]/$resource";
    $this->proxyToEs($url, $requestBody, $format, $ret, $requestIsRawString);
  }

  /**
   * Retrieves the Elasticsearch major version number from the config.
   *
   * If not specified returns null.
   *
   * @return int
   *   Major version number.
   */
  public function getMajorVersion() {
    $esVersion = kohana::config('rest.elasticsearch_version');
    if (!empty($esVersion)) {
      return (integer) (explode('.', $esVersion, 2)[0]);
    }
    return NULL;
  }

  /**
   * Adds permissions filters to ES search, based on website ID and user ID.
   *
   * If the authentication method configuration (e.g. jwtUser) includes the
   * option limit_to_website in the settings for the Elasticsearch endpoint,
   * then automatically adds a terms filter on metadata.website.id. Also,
   * if the settings include limit_to_own_data for the endpoint or the sharing
   * mode is "me", then adds a terms filter on metadata.created_by_id.
   */
  private function applyEsPermissionsQuery(&$postObj) {
    $filters = [];
    $esConfig = kohana::config('rest.elasticsearch');
    $thisProxyCfg = $esConfig[$this->elasticProxy];
    // Capture boolean filter defined by a filter_id.
    $filterDefBool = [];
    // Apply limit to current user if appropriate.
    if (!empty(RestObjects::$esConfig['limit_to_own_data']) || RestObjects::$scope === 'user' || RestObjects::$scope === 'userWithinWebsite') {
      if (empty(RestObjects::$clientUserId) && empty(RestObjects::$esConfig['allow_anonymous'])) {
        RestObjects::$apiResponse->fail('Internal server error', 500, 'No user_id available for my records report.');
      }
      if (!empty(RestObjects::$clientUserId)) {
        $filters[] = [
          'term' => ['metadata.created_by_id' => RestObjects::$clientUserId],
        ];
      }
    }

    // Apply limit to current website if appropriate.
    if (RestObjects::$scope === 'userWithinWebsite' || !empty(RestObjects::$esConfig['limit_to_website'])) {
      if (!RestObjects::$clientWebsiteId) {
        RestObjects::$apiResponse->fail('Internal server error', 500, 'No website_id available for website limited report.');
      }
      $filters[] = [
        'term' => ['metadata.website.id' => RestObjects::$clientWebsiteId],
      ];
    }
    // Apply limit to websites identified by scope if appropriate.
    if (substr(RestObjects::$scope, 0, 4) !== 'user' && (!isset($thisProxyCfg['apply_filters']) || $thisProxyCfg['apply_filters'] === TRUE)) {
      if (!RestObjects::$clientWebsiteId) {
        RestObjects::$apiResponse->fail('Internal server error', 500, 'No website_id available for website limited report.');
      }
      $filters[] = [
        'terms' => [
          'metadata.website.id' => warehouse::getSharedWebsiteList([RestObjects::$clientWebsiteId], RestObjects::$db, RestObjects::$scope),
        ],
      ];
    }
    if (!isset($thisProxyCfg['apply_filters']) || $thisProxyCfg['apply_filters'] === TRUE) {
      if (isset($this->resourceOptions['full_precision_sensitive_records'])) {
        // Precision explicitly set by a connection's options in the DB.
        $blur = $this->resourceOptions['full_precision_sensitive_records'] === TRUE ? 'F' : 'B';
      }
      else {
        // Otherwise, only verification or user's own records get full
        // precision.
        $blur = (RestObjects::$scope === 'verification' || substr(RestObjects::$scope, 0, 4) === 'user') ? 'F' : 'B';
      }
      $queryStringParts = [];
      if (empty($this->resourceOptions['allow_confidential']) || $this->resourceOptions['allow_confidential'] !== TRUE) {
        $queryStringParts[] = 'metadata.confidential:false';
        // Only user who owns record or verifier can see private samples,
        // unless confidential access allowed.
        if (substr(RestObjects::$scope, 0, 4) !== 'user' && RestObjects::$scope !== 'verification') {
          $queryStringParts[] = 'NOT metadata.hide_sample_as_private:true';
        }
      }
      if (empty($this->resourceOptions['allow_unreleased']) || $this->resourceOptions['allow_unreleased'] !== TRUE) {
        $queryStringParts[] = 'metadata.release_status:R';
      }
      if (isset($this->resourceOptions['allow_sensitive']) && $this->resourceOptions['allow_sensitive'] === FALSE) {
        $queryStringParts[] = 'metadata.sensitive:false';
      }
      $queryStringParts[] = "((metadata.sensitivity_blur:$blur) OR (!metadata.sensitivity_blur:*))";
      $filters[] = ['query_string' => ['query' => implode(' AND ', $queryStringParts)]];
      if (!empty($this->resourceOptions['filter_id'])) {
        require_once 'client_helpers/ElasticsearchProxyHelper.php';
        require_once 'client_helpers/helper_base.php';
        $filterData = RestObjects::$db->query('select definition from filters where id=? and deleted=false', [$this->resourceOptions['filter_id']])->current();
        if (!$filterData) {
          RestObjects::$apiResponse->fail('Internal Server Error', 500, 'Missing filter ID in connection configuration.');
        }
        ElasticsearchProxyHelper::applyFilterDef([], json_decode($filterData->definition, TRUE), $filterDefBool);
      }
    }
    if (count($filters) > 0) {
      if (!isset($postObj->query)) {
        $postObj->query = new stdClass();
      }
      if (!isset($postObj->query->bool)) {
        $postObj->query->bool = new stdClass();
      }
      if (!isset($postObj->query->bool->must)) {
        $postObj->query->bool->must = [];
      }
      $postObj->query->bool->must = array_merge($postObj->query->bool->must, $filters);
    }
    if (!empty($filterDefBool['must'])) {
      $postObj->query->bool->must = array_merge($postObj->query->bool->must, $filterDefBool['must']);
    }
    if (!empty($filterDefBool['must_not'])) {
      $postObj->query->bool->must_not = $filterDefBool['must_not'];
    }
  }

  /**
   * Copies a source field from an Elasticsearch document into a CSV row.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param string $sourceField
   *   Source field name or special field name.
   * @param array $row
   *   Output row array, will be update with the value to output.
   */
  private function copyIntoCsvRow(array $doc, $sourceField, array &$row) {
    // Fields starting '_' are special fields in the root of the doc. Others
    // are in the _source element.
    $docSource = strpos($sourceField, '_') === 0 || !isset($doc['_source']) ? $doc : $doc['_source'];
    if (preg_match('/^#(?P<sourceType>[a-z_]*):?(?<params>.*)?#$/', $sourceField, $matches)) {
      $fn = 'esGetSpecialField' .
        str_replace('_', '', ucwords($matches['sourceType']));

      // Split $matches['params'] into an array using colon as a separator.
      // First replace escaped colons ('\:') with another marker
      // ('EscapedColon') converting back to colons in the resulting array
      // elements.
      $params = empty($matches['params']) ? [] : explode(':', str_replace('\:', 'EscapedColon', $matches['params']));
      foreach ($params as &$param) {
        $param = str_replace('EscapedColon', ':', $param);
      }
      if ($matches['sourceType'] === 'id') {
        // Resets docSource to root if special function to format doc ID.
        $docSource = $doc;
      }
      if (method_exists($this, $fn)) {
        $row[] = $this->$fn($docSource, $params);
      }
      else {
        $row[] = "Invalid field $sourceField";
      }
    }
    else {
      if (!preg_match('/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)*$/', $sourceField)) {
        $row[] = "Invalid field $sourceField";
      }
      else {
        $value = $this->getRawEsFieldValue($docSource, $sourceField);
        // Auto-implode any array data so valid inside a CSV row.
        if (is_array($value)) {
          $value = implode(';', $value);
        }
        $row[] = $value;
      }
    }
  }

  /**
   * Converts an Elasticsearch response to a chunk of CSV data.
   *
   * @param array $itemList
   *   Decoded list of data from an Elasticsearch search.
   * @param int $handle
   *   File or output buffer handle.
   */
  private function esToCsv(array $itemList, $handle) {
    if (empty($itemList)) {
      return;
    }
    $esCsvTemplate = $this->getEsCsvTemplate();
    foreach ($itemList as $item) {
      $row = [];
      foreach ($esCsvTemplate as $source) {
        $this->copyIntoCsvRow($item, $source['field'], $row);
      }
      fputcsv($handle, $row);
    };
  }

  /**
   * Special field handler for the associations data.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldAssociations(array $doc) {
    $output = [];
    if (isset($doc['occurrence']['associations'])) {
      foreach ($doc['occurrence']['associations'] as $assoc) {
        $label = $assoc['accepted_name'];
        if (!empty($assoc['vernacular_name'])) {
          $label = $assoc['vernacular_name'] . " ($label)";
        }
        $output[] = $label;
      }
    }
    return implode('; ', $output);
  }

  /**
   * Special field handler for Elasticsearch custom attribute values.
   *
   * Concatenates values to a semi-colon separated string.
   *
   * Multiple attribute values are returned joined by semi-colons.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   The parameters should be:
   *   * 0 - the entity (event|occurrence)
   *   * 1 - the attribute ID.
   *   * 2 - optional - whether to merge event and parent_event attributes.
   *         Merges by default. Does not merge if set.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldAttrValue(array $doc, array $params) {
    $r = [];
    if (in_array($params[0], ['occurrence', 'sample', 'event', 'parent_event'])) {
      // Tolerate sample or event for sample attributes.
      $key = $params[0] === 'parent_event' ? 'parent_attributes' : 'attributes';
      $entity = in_array($params[0], ['sample', 'event', 'parent_event']) ? 'event' : 'occurrence';
      $attrList = [];
      if (isset($doc[$entity][$key])) {
        $attrList = $doc[$entity][$key];
      }
      // If requesting an event/sample attribute, the parent event/sample's
      // data can also be considered.
      if (
        $entity === 'event' &&
        $key === 'attributes' &&
        isset($doc['event']['parent_attributes']) &&
        !isset($params[2])
      ) {
        $attrList = array_merge($attrList, $doc['event']['parent_attributes']);
      }
      foreach ($attrList as $attr) {
        if ($attr['id'] == $params[1]) {
          if (is_array($attr['value'])) {
            $r = array_merge($r, $attr['value']);
          }
          else {
            $r[] = $attr['value'];
          }
        }
      }
    }
    return implode('; ', $r);
  }

  /**
   * Special field handler returns the value for the first non-empty field.
   *
   * Provide a comma-separated list of field names as the parameter. The value
   * of the first field in the list to have a non-empty value is returned.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Parameters defined for the special field.
   *
   * @return string
   *   Field value.
   */
  private function esGetSpecialFieldCoalesce(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for coalesce field';
    }
    $fields = explode(',', $params[0]);
    foreach ($fields as $field) {
      $value = $this->getRawEsFieldValue($doc, $field);
      if ($value !== '') {
        return $value;
      }
    }
    return '';
  }

  /**
   * Special field handler conditionally returns a field's value.
   *
   * Returns the value of a field only if the value of another field matches a
   * specified condition. For example, a full-precision grid reference can be
   * returned only if metadata.sensitivity_blur is set to 'F'.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Parameters defined for the special field.
   *
   * @return string
   *   Field value.
   */
  private function esGetSpecialFieldConditionalValue(array $doc, array $params) {
    if (count($params) !== 4) {
      return 'Incorrect params for conditional value field';
    }
    list($field, $fieldToCheck, $operator, $checkAgainst) = $params;
    if ($operator !== '=') {
      return 'Unsupported operator for conditional value field';
    }
    $valueToCheck = $this->getRawEsFieldValue($doc, $fieldToCheck);
    if ($valueToCheck === $checkAgainst) {
      return $this->getRawEsFieldValue($doc, $field);
    }
    return '';
  }

  /**
   * Special field handler which returns a constant value.
   *
   * For an empty column set the second argument to an empty string.
   * Useful where the output CSV must contain a column to which no
   * useful data can be mapped.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Parameters defined for the special field.
   *
   * @return string
   *   Returns constant passed as argument.
   */
  private function esGetSpecialFieldConstant(array $doc, array $params) {
    // No params = blank, 1 param is constant value, 2 or more is a mistake.
    if (count($params) > 1) {
      return 'Incorrect params for constant field';
    }
    return count($params) ? $params[0] : '';
  }

  /**
   * Special field handler for the datacleaner icons field.
   *
   * Text representation of icons for download.
   */
  private function esGetSpecialFieldDataCleanerIcons(array $doc) {
    $autoChecks = $doc['identification']['auto_checks'];
    $output = [];
    if ($autoChecks['enabled'] === 'false') {
      $output[] = 'Automatic rule checks will not be applied to records in this dataset.';
    }
    elseif (isset($autoChecks['result'])) {
      if ($autoChecks['result'] === 'true') {
        $output[] = 'All automatic rule checks passed.';
      }
      elseif ($autoChecks['result'] === 'false') {
        if (count($autoChecks['output']) > 0) {
          // Add an icon for each rule violation.
          foreach ($autoChecks['output'] as $violation) {
            $output[] = $violation['message'];
          }
        }
        else {
          $output[] = 'Automatic rule checks flagged issues with this record';
        }
      }
    }
    else {
      // Not yet checked.
      $output[] = 'Record not yet checked against rules.';
    }
    return implode('; ', $output);
  }

  /**
   * Special field handler for datasource codes.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Template to use for the output if overridding default.
   *
   * @return string
   *   Formatted value including website, survey dataset and,
   *   optionally, group info.
   */
  private function esGetSpecialFieldDatasourceCode(array $doc, array $params) {
    if (count($params) > 1) {
      return 'Incorrect params for datasource code field (must be 0 or 1)';
    }
    $w = $doc['metadata']['website'];
    $s = $doc['metadata']['survey'];
    if (isset($doc['metadata']['group'])) {
      $g = $doc['metadata']['group'];
    }
    else {
      $g = ['title' => '', 'id' => ''];
    }
    if (count($params)) {
      $pattern = $params[0];
    }
    else {
      $pattern = '<wi> (<wt>) | <si> (<st>)';
    }
    $regpatterns = ['/<wi>/', '/<wt>/', '/<si>/' , '/<st>/', '/<gi>/', '/<gt>/'];
    $replacements = [
      $w['id'],
      $w['title'],
      $s['id'],
      $s['title'],
      $g['id'],
      $g['title'],
    ];
    $output = preg_replace($regpatterns, $replacements, $pattern);
    // Disregarding whitespace, if the output string ends in something in curly
    // braces, then remove it. Allows us to conditionally remove a separator if
    // there's no group.
    $output = preg_replace('/\s*{.*}\s*$/', '', $output);
    // Remvove curly braces from output.
    $output = preg_replace('/({|})/', '', $output);
    return $output;
  }

  /**
   * Special field handler ES datetime fields to output with provided format.
   *
   * Return the datetime as a string formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters:
   *   1. ES field
   *   2. datetime format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldDatetime(array $doc, array $params) {
    if (count($params) !== 2) {
      return 'Incorrect params for Datetime field';
    }
    $dtvalue = $this->getRawEsFieldValue($doc, $params[0]);
    $dt = DateTime::createFromFormat('Y-m-d G:i:s.u', $dtvalue);
    if ($dt === FALSE) {
      return $dtvalue;
    }
    else {
      return $dt->format($params[1]);
    }
  }

  /**
   * Special field handler for determiner.
   *
   * Return name of determiner as specified by formatter.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldDeterminer(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for determiner field';
    }
    $recorder = $this->getRawEsFieldValue($doc, 'event.recorded_by');
    $value = $this->getRawEsFieldValue($doc, 'identification.identified_by');

    if ($params[0] === 'mapmate') {
      // If no determiner recorded, set value to recorder.
      if ($value === '') {
        $value = $recorder;
      }
      // Truncation to 62 characters required for MapMate.
      $value = substr($value, 0, 62);
    }
    return $value;
  }

  /**
   * Special field handler for Elasticsearch event dates.
   *
   * Converts event.date_from and event.date_to to a readable date string, e.g.
   * for inclusion in CSV output. Also handles date fields when prefixed by
   * `key`, e.g. when used in composite aggregation sources.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters in field definition.
   *   Can be empty or a string to specify a format.
   *
   * @return string
   *   Formatted readable date.
   */
  private function esGetSpecialFieldEventDate(array $doc, array $params) {
    if (count($params) > 1) {
      return 'Incorrect params for formatted date (must be zero or one)';
    }
    if (count($params)) {
      $format = $params[0];
    }
    else {
      $format = "";
    }
    // Check in case fields are in composite agg key.
    $root = $doc['key'] ?? $doc['event'];
    $start = $root['date_start'] ?? ($root['event-date_start'] ?? '');
    $end = $root['date_end'] ?? ($root['event-date_end'] ?? '');
    if (preg_match('/^\-?\d+$/', $start)) {
      $start = date('d/m/Y', $start / 1000);
    }
    if (preg_match('/^\-?\d+$/', $end)) {
      $end = date('d/m/Y', $end / 1000);
    }
    if (preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $start, $matches)) {
      $start = $matches[3] . '/' . $matches[2] . '/' . $matches[1];
    }
    if (preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $end, $matches)) {
      $end = $matches[3] . '/' . $matches[2] . '/' . $matches[1];
    }
    if (empty($start) && empty($end)) {
      if ($format === 'mapmate') {
        return '';
      }
      else {
        return 'Unknown';
      }
    }
    elseif (empty($end)) {
      if ($format === 'mapmate') {
        // Mapmate can't deal with unbound ranges
        // - replace with date of known bound.
        return $start;
      }
      else {
        return "After $start";
      }
    }
    elseif (empty($start)) {
      if ($format === 'mapmate') {
        // Mapmate can't deal with unbound ranges
        // - replace with date of known bound.
        return $end;
      }
      else {
        return "Before $end";
      }
    }
    elseif ($start === $end) {
      return $start;
    }
    else {
      if ($format === 'mapmate') {
        return $start . '-' . $end;
      }
      else {
        return "$start to $end";
      }
    }
  }

  /**
   * Special field handler for Elasticsearch higher geography.
   *
   * Converts location.higher_geography to a string, e.g. for inclusion in CSV
   * output. Configurable output by passing parameters:
   * * type - limit output to this type.
   * * field - limit output to content of this field (name, id, type or code).
   * * format - can be left empty or set to either json or mapmate.
   * E.g. pass type=Country, field=name, text=true to convert to a plaintext
   * Country name.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters in field definition.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldHigherGeography(array $doc, array $params) {
    if (isset($doc['location']) && isset($doc['location']['higher_geography'])) {
      if (empty($params) || empty($params[0])) {
        $r = $doc['location']['higher_geography'];
      }
      else {
        $r = [];
        foreach ($doc['location']['higher_geography'] as $loc) {
          if (strcasecmp($loc['type'], $params[0]) === 0) {
            if (!empty($params[1])) {
              $r[] = $loc[$params[1]];
            }
            else {
              $r[] = $loc;
            }
          }
        }
      }
      if (isset($params[2]) && $params[2] === 'json') {
        return json_encode($r);
      }
      else {
        $outputList = [];
        foreach ($r as $outputItem) {
          $outputList[] = is_array($outputItem) ? implode('; ', $outputItem) : $outputItem;
        }
        if (isset($params[2]) && $params[2] === 'mapmate') {
          if (count($outputList) === 1) {
            return $outputList[0];
          }
          else {
            return 0;
          }
        }
        else {
          return implode(' | ', $outputList);
        }
      }
    }
    else {
      if (isset($params[2]) && $params[2] === 'mapmate') {
        return 0;
      }
      else {
        return '';
      }
    }
  }

  /**
   * Return a Yes/No value for the field indicating image classifier agreement.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters in field definition.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldIdentificationClassifierAgreement(array $doc, array $params) {
    $value = $doc['identification']['classifier']['current_determination']['classifier_chosen'] ?? NULL;
    if ($value === 'true') {
      return 'Yes';
    }
    elseif ($value === 'false') {
      return 'No';
    }
    return '';
  }

  /**
   * Return the image classifiers top suggested taxon name.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters in field definition.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldIdentificationClassifierSuggestion(array $doc, array $params) {
    $suggestions = $doc['identification']['classifier']['suggestions'] ?? [];
    $topSuggestion = '';
    $topProbability = 0;
    foreach ($suggestions as $suggestion) {
      if ($suggestion['probability_given'] > $topProbability) {
        $topSuggestion = $suggestion['taxon_name_given'];
        $topProbability = $suggestion['probability_given'];
      }
    }
    return $topSuggestion;
  }


  /**
   * Special field handler for latitude data.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Format parameter.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldLat(array $doc, array $params) {
    // Check in case fields are in composite agg key.
    $root = $doc['key'] ?? $doc['location'];
    if (empty($root['point'])) {
      return 'n/a';
    }
    $coords = explode(',', $root['point']);
    $format = !empty($params) ? $params[0] : '';
    switch ($format) {
      case 'decimal':
        if (isset($params[1])) {
          // Format specifies decimal places to return.
          return number_format($coords[0], $params[1]);
        }
        // Default is full precision.
        return $coords[0];

      // Implemented as the default.
      case 'nssuffix':
      default:
        $precision = $params[1] ?? 3;
        $ns = $coords[0] >= 0 ? 'N' : 'S';
        $lat = number_format(abs($coords[0]), $precision);
        return "$lat$ns";
    }
  }

  /**
   * Special field handler for lat/lon data.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldLatLon(array $doc, array $params) {
    // Check in case fields are in composite agg key.
    $root = $doc['key'] ?? $doc['location'];
    if (empty($root['point'])) {
      return 'n/a';
    }
    $coords = explode(',', $root['point']);
    $precision = count($params) > 0 ? $params[0] : 3;
    $ns = $coords[0] >= 0 ? 'N' : 'S';
    $ew = $coords[1] >= 0 ? 'E' : 'W';
    $lat = number_format(abs($coords[0]), $precision);
    $lon = number_format(abs($coords[1]), $precision);
    return "$lat$ns $lon$ew";
  }

  /**
   * Special field handler for Elasticsearch life stage with format options.
   *
   * Converts occurrence.life_stage to values as specified in format option.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted life stage.
   */
  private function esGetSpecialFieldLifeStage(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for formatted life stage';
    }
    $value = isset($doc['occurrence']['life_stage']) ? strtolower($doc['occurrence']['life_stage']) : '';
    if ($params[0] === 'mapmate') {
      // Provides compatibility for import to MapMate.
      switch ($value) {
        case 'adult':
        case 'adults':
        case 'adult female':
        case 'adult male':
          return 'Adult';

        case 'larva':
          return 'Larval';

        case 'not recorded':
        case '':
          return 'Not recorded';

        case 'pre-adult':
          return 'Subadult';

        case 'in flower':
          return 'Flowering';

        default:
          return $value;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for locality data.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value containing a list of location names associated with the
   *   record.
   */
  private function esGetSpecialFieldLocality(array $doc) {
    $info = [];
    if (!empty($doc['location']['verbatim_locality'])) {
      $info[] = $doc['location']['verbatim_locality'];
    }
    if (!empty($doc['location']['higher_geography'])) {
      foreach ($doc['location']['higher_geography'] as $loc) {
        $info[] = "$loc[type]: $loc[name]";
      }
    }
    return implode('; ', $info);
  }

  /**
   * Special field handler for longitude data.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Format parameter.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldLon(array $doc, array $params) {
    // Check in case fields are in composite agg key.
    $root = $doc['key'] ?? $doc['location'];
    if (empty($root['point'])) {
      return 'n/a';
    }
    $coords = explode(',', $root['point']);
    $format = !empty($params) ? $params[0] : "";
    switch ($format) {
      case "decimal":
        if (isset($params[1])) {
          // Format specifies decimal places to return.
          return number_format($coords[1], $params[1]);
        }
        // Default is full precision.
        return $coords[1];

      // Implemented as the default.
      case "ewsuffix":
      default:
        $precision = $params[1] ?? 3;
        $ew = $coords[1] >= 0 ? 'E' : 'W';
        $lon = number_format(abs($coords[1]), $precision);
        return "$lon$ew";
    }
  }

  /**
   * Special field handler for ES fields that should treat zero as null.
   *
   * If the field value (fieldname in params) is zero, then return null, else
   * return the original value.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldNullIfZero(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for Null If Zero field';
    }
    $value = $this->getRawEsFieldValue($doc, $params[0]);
    return ($value === '0' || $value === 0) ? NULL : $value;
  }

  /**
   * Special field handler for method.
   *
   * Return name of sampling method as specified by formatter.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldMethod(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for method field';
    }
    $value = $this->getRawEsFieldValue($doc, 'event.sampling_protocol');

    if ($params[0] === 'mapmate') {
      if ($value === '') {
        $value = 'Unknown';
      }
      // Truncation to 62 characters required for MapMate.
      $value = substr($value, 0, 62);
    }
    return $value;
  }

  /**
   * Special field handler for occurrence media data.
   *
   * Concatenates media to a string.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldOccurrenceMedia(array $doc) {
    if (!empty($doc['occurrence']['media'])) {
      $items = [];
      foreach ($doc['occurrence']['media'] as $m) {
        $item = [
          $m['path'],
          empty($m['caption']) ? '' : $m['caption'],
          empty($m['licence']) ? '' : $m['licence'],
        ];
        $items[] = implode('; ', $item);
      }
      return implode(' | ', $items);
    }
    return '';
  }

  /**
   * Special field handler for Elasticsearch organism quantity.
   *
   * Allows organism quanities to be filtered/formatted as directed by params.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters in field definition.
   *
   * @return string
   *   A quantity formatted/filtered as indicated by passed parameter.
   */
  private function esGetSpecialFieldOrganismQuantity(array $doc, array $params) {
    $format = !empty($params) ? $params[0] : '';
    $quantity = !empty($doc['occurrence']['organism_quantity']) ? $doc['occurrence']['organism_quantity'] : '';
    if (!empty($doc['occurrence']['zero_abundance']) && $doc['occurrence']['zero_abundance'] !== 'false') {
      $zero = TRUE;
    }
    else {
      $zero = FALSE;
    }
    switch ($format) {
      case 'mapmate':
        // Mapmate will only accept integer values and uses a value
        // of -7 to indicate a negative record. MapMate interprets
        // a quantity of 0 to mean 'present'.
        if ($zero || $quantity === '0') {
          return -7;
        }
        elseif (preg_match('/^\d+$/', $quantity)) {
          return (int) $quantity;
        }
        else {
          // Zero in MapMate denotes present.
          return '0';
        }

      case 'integer':
        // Only return the value if it is an integer.
        if (preg_match('/^\d+$/', $quantity)) {
          return $quantity;
        }
        else {
          return '';
        }

      case 'exclude_integer':
        // Only return the value if it is not an integer.
        if (!preg_match('/^\d+$/', $quantity)) {
          return $quantity;
        }
        else {
          return '';
        }

      default:
        return $quantity;
    }
  }

  /**
   * Special field handler for ES identification query status.
   *
   * Return the identification query status formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldQuery(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for query field';
    }
    $value = $this->getRawEsFieldValue($doc, 'identification.query');
    if ($params[0] === 'astext') {
      // Provides backward compatibility with pre-ES downloads.
      if ($value === 'A') {
        return 'Answered';
      }
      elseif ($value === 'Q') {
        return 'Queried';
      }
      else {
        return $value;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler which combines the sample and occurrence comment.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Parameters defined for the special field.
   *
   * @return string
   *   Combined comment string.
   */
  private function esGetSpecialFieldSampleOccurrenceComment(array $doc, array $params) {
    $oComment = $doc['occurrence']['occurrence_remarks'] ?? '';
    $sComment = $doc['event']['event_remarks'] ?? '';
    if (!empty($params) && in_array("notab", $params)) {
      $oComment = str_replace("\t", ' ', $oComment);
      $sComment = str_replace("\t", ' ', $sComment);
    }
    if (!empty($params) && in_array("nonewline", $params)) {
      $oComment = str_replace(["\r\n", "\n", "\r"], ' ', $oComment);
      $sComment = str_replace(["\r\n", "\n", "\r"], ' ', $sComment);
    }
    if ($oComment !== '' && $sComment !== '') {
      $comment = "$oComment $sComment";
    }
    elseif ($oComment !== '') {
      $comment = $oComment;
    }
    elseif ($sComment !== '') {
      $comment = $sComment;
    }
    else {
      $comment = '';
    }
    if (!empty($params) && in_array("addref", $params)) {
      $ref = kohana::config('indicia.es_key_prefix') . $doc['id'];
      $comment = trim("$comment $ref");
    }
    return $comment;
  }

  /**
   * Special field handler for Elasticsearch sex with format options.
   *
   * Converts occurrence.sex to values as specified in format option.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted sex.
   */
  private function esGetSpecialFieldSex(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for formatted sex';
    }
    $value = isset($doc['occurrence']['sex']) ? strtolower($doc['occurrence']['sex']) : '';
    if ($params[0] === 'mapmate') {
      // Provides compatibility for import to MapMate.
      switch ($value) {
        case 'female':
          return 'f';

        case 'male':
          return 'm';

        case 'mixed':
          return 'g';

        case 'queen':
          return 'q';

        case 'not recorded':
        case 'not known':
        case 'unknown':
        case 'unsexed':
        case '':
          return 'u';

        default:
          return $value;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for ES sitename.
   *
   * Return location.verbatim_locality formatted as specified. The parameter
   * supplied dictates the format - select one of the following:
   * * obscureifsensitive - for sensitive records with a site name, replace the
   *   name with a placeholder indicating that it's witheld, for non-sensitive
   *   records the entire site name is returned.
   * * showifsensitive - only show the name for sensitive records.
   * * mapmate - for sensitive records with a site name, replace the name with
   *   a placeholder indicating that it's witheld, for non-sensitive records
   *   return the first 62 characters of the site name, or 'unnamed site' if no
   *   name given.
   * If the parameter is not supplied then the site name is always shown if the
   * user has permission to see sensitive records unblurred or the record is
   * not sensitive.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldSitename(array $doc, array $params) {
    $format = !empty($params) ? $params[0] : '';
    $value = $this->getRawEsFieldValue($doc, 'location.verbatim_locality');
    $shouldBlur = $doc['metadata']['sensitive'] === 'true' || $doc['metadata']['private'] === 'true';
    switch ($format) {
      case 'obscureifsensitive':
        if ($shouldBlur && !empty($value)) {
          return '[' . kohana::lang('es_fields.sensitiveLocation'). ']';
        }
        // Full site name.
        return $value;

      case 'showifsensitive':
        if ($shouldBlur && $value !== '!') {
          return $value;
        }
        return '';

      case 'mapmate':
        if ($shouldBlur && !empty($value)) {
          return '[' . kohana::lang('es_fields.sensitiveLocation'). ']';
        }
        // Truncation to 62 characters required for MapMate.
        if (empty($value)) {
          return 'unnamed site';
        }
        // Truncation to 62 characters required for MapMate, skipping the site
        // witheld ! character.
        return $value === '!' ? '' : substr($value, 0, 62);

      default:
        return $value === '!' ? '' : $value;
    }
  }

  /**
   * Special field handler for ES spatial ref system fields.
   *
   * Return the spatial ref system formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters:
   *   1. ES field
   *   2. format identifier.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldSrefSystem(array $doc, array $params) {
    if (count($params) !== 2) {
      return 'Incorrect params for sref system field';
    }
    $value = strval($this->getRawEsFieldValue($doc, $params[0]));
    if ($params[1] === 'alphanumeric') {
      // Ensure that EPSG codes are converted to alphanumeric string.
      // Provides backward compatibility with pre-ES downloads.
      if ($value === '4326') {
        return 'WGS84';
      }
      elseif ($value === '27700') {
        return 'OSGB36';
      }
      else {
        return strtoupper($value);
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for status data.
   *
   * Returns text instead of icons (for download purposes).
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldStatusIcons(array $doc) {
    $terms = [];
    $recordStatusLookup = [
      'V' => 'Accepted',
      'V1' => 'Accepted as correct',
      'V2' => 'Accepted as considered correct',
      'C' => 'Not reviewed',
      'C3' => 'Plausible',
      'R' => 'Not accepted',
      'R4' => 'Not accepted as unable to verify',
      'R5' => 'Not accepted as incorrect',
    ];
    if (!empty($doc['identification'])) {
      $status = $doc['identification']['verification_status'];
      if (!empty($doc['identification']['verification_substatus']) && $doc['identification']['verification_substatus'] !== 0) {
        $status .= $doc['identification']['verification_substatus'];
      }
      if (isset($recordStatusLookup[$status])) {
        $terms[] = $recordStatusLookup[$status];
      }
      if (!empty($doc['identification']['query'])) {
        $terms[] = $doc['identification']['query'] === 'A' ? 'Answered' : 'Queried';
      }
    }
    if (!empty($doc['metadata'])) {
      if (!empty($doc['metadata']['sensitive']) && $doc['metadata']['sensitive'] !== 'false') {
        $terms[] = 'Sensitive';
      }
      if (!empty($doc['metadata']['confidential']) && $doc['metadata']['confidential'] !== 'false') {
        $terms[] = 'Confidential';
      }
      if (!empty($doc['metadata']['created_by_id']) && $doc['metadata']['created_by_id'] === '1') {
        $terms[] = 'Anonymous user';
      }
    }
    if (!empty($doc['occurrence'])) {
      if (!empty($doc['occurrence']['zero_abundance']) && $doc['occurrence']['zero_abundance'] !== 'false') {
        $terms[] = 'Zero abundance';
      }
    }
    return implode('; ', $terms);
  }

  /**
   * Special field handler for taxon labels.
   *
   * @param array $doc
   *   Elasticsearch document.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldTaxonLabel(array $doc) {
    $name = empty($doc['taxon']['accepted_name']) ? $doc['taxon']['taxon_name'] : $doc['taxon']['accepted_name'];
    // Append vernacular when available.
    if (!empty($doc['taxon']['vernacular_name']) && $doc['taxon']['vernacular_name'] !== $name) {
      $name .= ' | ' . $doc['taxon']['vernacular_name'];
    }
    // Prepend taxon rank if above species.
    if (!empty($doc['taxon']['taxon_rank_sort_order']) && $doc['taxon']['taxon_rank_sort_order'] < 290) {
      $name = $doc['taxon']['taxon_rank'] . " $name";
    }
    return $name;
  }

  /**
   * Applies ES field values to a template.
   *
   * Field names can be supplied in [] inside the template and will be replaced
   * by the respectiv values.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param string $template
   *   Text template.
   *
   * @return string
   *   Template with tokens replaced by values.
   */
  private function applyFieldReplacements(array $doc, $template) {
    preg_match_all('/\[.*\]/', $template, $matches);
    $replaceKeys = [];
    $replaceValues = [];
    foreach ($matches as $group) {
      foreach ($group as $token) {
        $fieldPath = str_replace(['[', ']'], '', $token);
        $value = $this->getRawEsFieldValue($doc, $fieldPath);
        $replaceKeys[] = $token;
        $replaceValues[] = $value;
      }
    }
    return str_replace($replaceKeys, $replaceValues, $template);
  }

  /**
   * Special field handler for templated text.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   The first parameter must be the text template. An optional second
   *   parameter can indicate the path to a nested ES object - if present then
   *   the template will be repeated for each object and fields inside the
   *   current object will be available as token replacements.
   *
   * @return string
   *   Formatted value.
   */
  private function esGetSpecialFieldTemplate(array $doc, $params) {
    $output = '';
    if (count($params) > 1) {
      // 2nd parameter is a nested object path.
      $objects = $this->getRawEsFieldValue($doc, $params[1]);
      if (is_array($objects)) {
        foreach ($objects as $object) {
          $output .= $this->applyFieldReplacements($object, $params[0]);
        };
      }
    }
    else {
      $output = $params[0];
    }
    $output = $this->applyFieldReplacements($doc, $output);
    // Strip HTML tokens, as this is for CSV.
    return preg_replace('/<.[^<>]*?>/', ' ', $output);
  }

  /**
   * Special field handler to translate true/false values to specified output.
   *
   * Return the translated value.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters:
   *   1. ES field
   *   2. Translated string for 'true' values
   *   3. Translated string for 'false' values.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldTrueFalse(array $doc, array $params) {
    if (count($params) !== 3) {
      return 'Incorrect params for true_false field';
    }
    $value = $this->getRawEsFieldValue($doc, $params[0]);
    if ($value === 'true') {
      return $params[1];
    }
    elseif ($value === 'false') {
      return $params[2];
    }
    else {
      return '';
    }
  }

  /**
   * Special field handler to truncate output to specified number of characters.
   *
   * Return the a string no longer than specified number of characters.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   Provided parameters:
   *   1. ES field
   *   2. Number of characters.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldTruncate(array $doc, array $params) {
    if (count($params) !== 2) {
      return 'Incorrect params for truncate field';
    }
    $value = $this->getRawEsFieldValue($doc, $params[0]);
    return substr($value, 0, intval($params[1]));
  }

  /**
   * Special field handler for ES verification status.
   *
   * Return the verification status formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldVerificationStatus(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for verification status field';
    }
    $value = $this->getRawEsFieldValue($doc, 'identification.verification_status');
    if ($params[0] === 'astext') {
      // Provides backward compatibility with pre-ES downloads.
      if ($value === 'V') {
        return 'Accepted';
      }
      elseif ($value === 'C') {
        return 'Unconfirmed';
      }
      elseif ($value === 'R') {
        return 'Rejected';
      }
      elseif ($value === 'I') {
        return 'Input still in progress';
      }
      elseif ($value === 'D') {
        return 'Queried';
      }
      elseif ($value === 'S') {
        return 'Awaiting check';
      }
      else {
        return $value;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Special field handler for ES verification status.
   *
   * Return the verification status formatted as specified.
   *
   * @param array $doc
   *   Elasticsearch document.
   * @param array $params
   *   An identifier for the format.
   *
   * @return string
   *   Formatted string
   */
  private function esGetSpecialFieldVerificationSubstatus(array $doc, array $params) {
    if (count($params) !== 1) {
      return 'Incorrect params for verification substatus field';
    }
    $status = $this->getRawEsFieldValue($doc, 'identification.verification_status');
    $value = $this->getRawEsFieldValue($doc, 'identification.verification_substatus');
    if ($params[0] === 'astext') {
      // Provides backward compatibility with pre-ES downloads.
      if ($status === 'V') {
        if ($value === '1') {
          return 'Correct';
        }
        elseif ($value === '2') {
          return 'Considered correct';
        }
        else {
          return NULL;
        }
      }
      elseif ($status === 'C') {
        if ($value === '3') {
          return 'Plausible';
        }
        else {
          return 'Not reviewed';
        }
      }
      elseif ($status === 'R') {
        if ($value === '4') {
          return 'Unable to verify';
        }
        elseif ($value === '5') {
          return 'Incorrect';
        }
        else {
          return NULL;
        }
      }
      else {
        return NULL;
      }
    }
    else {
      return $value;
    }
  }

  /**
   * Function to find the value stored in an ES doc for a field.
   *
   * @param array $doc
   *   Document extracted from Elasticsearch.
   * @param string $source
   *   Fieldname, with nested segments separated by a period, e.g.
   *   identification.verification_status.
   *
   * @return mixed
   *   Data value or empty string if not found.
   */
  private function getRawEsFieldValue(array $doc, $source) {
    $search = explode('.', $source);
    $data = $doc;
    $failed = FALSE;
    foreach ($search as $field) {
      if (isset($data[$field])) {
        $data = $data[$field];
      }
      else {
        $failed = TRUE;
        break;
      }
    }
    if (isset($data['value_as_string'])) {
      // A formatted aggregation response stored in value property.
      return $data['value_as_string'];
    }
    elseif (isset($data['value'])) {
      // An aggregation response stored in value property.
      return $data['value'];
    }
    return $failed ? '' : $data;
  }

  /**
   * Works out the list of columns for an ES CSV download.
   *
   * @param object $postObj
   *   Request object.
   */
  private function getColumnsTemplate(&$postObj) {
    // Params for configuring an ES CSV download template get extracted and not
    // sent to ES.
    if (isset($postObj->columnsTemplate)) {
      $this->esCsvTemplate = $postObj->columnsTemplate;
      unset($postObj->columnsTemplate);
    }
    if (isset($postObj->addColumns)) {
      // Columns converted to associative array.
      $this->esCsvTemplateAddColumns = json_decode(json_encode($postObj->addColumns), TRUE);
      unset($postObj->addColumns);
    }
    if (isset($postObj->removeColumns)) {
      $this->esCsvTemplateRemoveColumns = (array) $postObj->removeColumns;
      unset($postObj->removeColumns);
    }
    if (isset($postObj->columnsSurveyId)) {
      if (!isset($this->esCsvTemplateAddColumns)) {
        $this->esCsvTemplateAddColumns = [];
      }
      $this->esCsvTemplateAddColumns = array_merge($this->esCsvTemplateAddColumns, $this->getSurveyAttributes('sample', $postObj->columnsSurveyId));
      $this->esCsvTemplateAddColumns = array_merge($this->esCsvTemplateAddColumns, $this->getSurveyAttributes('occurrence', $postObj->columnsSurveyId));
      unset($postObj->columnsSurveyId);
    }
  }

  /**
   * Works out the actual URL required for an Elasticsearch request.
   *
   * Caters for fact that the URL is different when scrolling to the next page
   * of a scrolled request. For unscrolled URLs adds the GET parameters to the
   * request if appropriate.
   *
   * @param string $url
   *   Elasticsearch alias URL.
   *
   * @return string
   *   Revised URL.
   */
  private function getEsActualUrl($url) {
    if ($this->pagingMode === 'scroll' && $this->pagingModeState === 'nextPage') {
      // On subsequent hits to a scrolled request, the URL is different.
      return preg_replace('/[a-z0-9_-]*\/_search$/', '_search/scroll', $url);
    }
    else {
      if (!empty($_GET)) {
        $params = array_merge($_GET);
        // Don't pass on the auth tokens and other non-elasticsearch GET parameters.
        unset($params['alias']);
        unset($params['user']);
        unset($params['website_id']);
        unset($params['secret']);
        unset($params['format']);
        unset($params['scroll']);
        unset($params['scroll_id']);
        unset($params['callback']);
        unset($params['aggregation_type']);
        unset($params['state']);
        unset($params['uniq_id']);
        unset($params['proj_id']);
        unset($params['sharing']);
        unset($params['user_id']);
        unset($params['_']);
        if ($this->pagingMode === 'scroll' && $this->pagingModeState === 'initial') {
          $params['scroll'] = SCROLL_TIMEOUT;
        }
        $url .= '?' . http_build_query($params);
      }
      return $url;
    }
  }

  /**
   * Determines the columns template for an ES download.
   *
   * @return array
   *   List of column definitions to download.
   */
  private function getEsCsvTemplate() {
    // Start with the template columns set, or an empty array.
    if (array_key_exists($this->esCsvTemplate, $this->esCsvTemplates)) {
      $csvTemplate = $this->esCsvTemplates[$this->esCsvTemplate];
    }
    else {
      $csvTemplate = [];
    }
    // Append extra columns.
    if (!empty($this->esCsvTemplateAddColumns)) {
      if (isset($this->esCsvTemplateAddColumns[0])) {
        // New format, v4+.
        $csvTemplate = array_merge($csvTemplate, $this->esCsvTemplateAddColumns);
      }
      else {
        // Old format <v4.
        kohana::log('alert', 'Deprecated Elasticsearch download addColumns format detected.');
        foreach ($this->esCsvTemplateAddColumns as $caption => $field) {
          $csvTemplate[] = ['caption' => $caption, 'field' => $field];
        }
      }
    }
    // Remove any that need to be removed.
    if (!empty($this->esCsvTemplateRemoveColumns)) {
      foreach ($this->esCsvTemplateRemoveColumns as $col) {
        unset($csvTemplate[$col]);
      }
    }
    return $csvTemplate;
  }

  /**
   * Builds the header for the top of a scrolled Elasticsearch output.
   *
   * For example, adds the CSV row.
   *
   * @param string $format
   *   Data format, either json or csv.
   *
   * @return string
   *   Header content.
   */
  private function getEsOutputHeader($format) {
    if ($format === 'csv') {
      $csvTemplate = $this->getEsCsvTemplate();
      $row = array_map(function ($column) {
        // Cells containing a quote, a comma or a new line will need to be
        // contained in double quotes.
        if (preg_match('/["\n,]/', $column['caption'])) {
          // Double quotes within cells need to be escaped.
          return '"' . preg_replace('/"/', '""', $column['caption']) . '"';
        }
        return $column['caption'];
      }, array_values($csvTemplate));
      return chr(0xEF) . chr(0xBB) . chr(0xBF) . implode(',', $row) . "\n";
    }
    return '';
  }

  /**
   * Calculate the data to post to an Elasticsearch search.
   *
   * @param object $postObj
   *   Request object.
   * @param string $format
   *   Format identifier. If CSV then we can use this to do source filtering
   *   to lower memory consumption.
   * @param array|null $file
   *   Cached info about the file if paging.
   * @param bool $isSearch
   *   TRUE if this is a search request.
   *
   * @return string
   *   Data to post.
   */
  private function getEsPostData($postObj, $format, $file, $isSearch) {
    if ($this->pagingMode === 'scroll' && $this->pagingModeState === 'nextPage') {
      // A subsequent hit on a scrolled request.
      $postObj = [
        'scroll_id' => $_GET['scroll_id'],
        'scroll' => SCROLL_TIMEOUT,
      ];
      return json_encode($postObj);
    }
    // Either unscrolled, or the first call to a scroll. So post the query.
    if ($this->pagingMode === 'scroll') {
      $postObj->size = MAX_ES_SCROLL_SIZE;
    }
    elseif ($this->pagingMode === 'composite' && isset($file['after_key'])) {
      $postObj->aggs->_rows->composite->after = $file['after_key'];
    }
    if ($isSearch) {
      $this->applyEsPermissionsQuery($postObj);
      // Ensure counts are accurate, not limited to 10K.
      $postObj->track_total_hits = TRUE;
    }
    if ($format === 'csv') {
      $csvTemplate = $this->getEsCsvTemplate();
      $fields = [];
      // Check for special fields - may need to force the underlying raw fields
      // into the list of requested fields.
      foreach ($csvTemplate as $column) {
        $field = $column['field'];
        if (strpos($field, '_') === 0) {
          // Fields starting _ are not inside _source object.
          continue;
        }
        if (preg_match('/^[a-z_]+(\.[a-z_]+)*$/', $field)) {
          $fields[] = $field;
        }
        elseif ($field === '#associations#') {
          $fields[] = 'occurrence.associations';
        }
        elseif (preg_match('/^#higher_geography(.*)#$/', $field)) {
          $fields[] = 'location.higher_geography.*';
        }
        elseif ($field === '#data_cleaner_icons#') {
          $fields[] = 'identification.auto_checks';
        }
        elseif (preg_match('/^#datasource_code(.*)#$/', $field)) {
          $fields[] = 'metadata.website';
          $fields[] = 'metadata.survey';
          $fields[] = 'metadata.group';
        }
        elseif (preg_match('/^#event_date(.*)#$/', $field)) {
          $fields[] = 'event.date_start';
          $fields[] = 'event.date_end';
        }
        elseif (preg_match('/^#vc(.*)#$/', $field)) {
          $fields[] = 'location.higher_geography';
        }
        elseif (preg_match('/^#sex(.*)#$/', $field)) {
          $fields[] = 'occurrence.sex';
        }
        elseif (preg_match('/^#life_stage(.*)#$/', $field)) {
          $fields[] = 'occurrence.life_stage';
        }
        elseif (preg_match('/^#organism_quantity(.*)#$/', $field)) {
          $fields[] = 'occurrence.organism_quantity';
          $fields[] = 'occurrence.zero_abundance';
        }
        elseif (preg_match('/^#(lat_lon|lat|lon)#$/', $field) || preg_match('/^#(lat|lon):(.*)#$/', $field)) {
          $fields[] = 'location.point';
        }
        elseif ($field === '#locality#') {
          $fields[] = 'location.verbatim_locality';
          $fields[] = 'location.higher_geography';
        }
        elseif ($field === '#occurrence_media#') {
          $fields[] = 'occurrence.media';
        }
        elseif ($field === '#status_icons#') {
          $fields[] = 'metadata';
          $fields[] = 'identification';
          $fields[] = 'occurrence.zero_abundance';
        }
        elseif ($field === '#taxon_label#') {
          $fields[] = 'taxon.taxon_name';
          $fields[] = 'taxon.vernacular_name';
          $fields[] = 'taxon.accepted_name';
          $fields[] = 'taxon.taxon_rank_sort_order';
          $fields[] = 'taxon.taxon_rank';
        }
        elseif (preg_match('/^#determiner(.*)#$/', $field)) {
          $fields[] = 'event.recorded_by';
          $fields[] = 'identification.identified_by';
        }
        elseif (preg_match('/^#sitename(:.*)?#$/', $field)) {
          $fields[] = 'location.verbatim_locality';
          $fields[] = 'metadata.sensitive';
          $fields[] = 'metadata.private';
        }
        elseif (preg_match('/^#conditional_value:([^#]*)#$/', $field, $matches)) {
          // $matches[1] contains the parameters, the first 2 of which refer to
          // fields that we'll need to include.
          $params = explode(':', $matches[1]);
          if (count($params) === 4) {
            list($field, $fieldToCheck) = $params;
            $fields[] = $field;
            $fields[] = $fieldToCheck;
          }
        }
        elseif ($field === '#idenfication_classifier_agreement#') {
          $fields[] = 'identification.classifiers.current_determination.classifier_chosen';
        }
        elseif ($field === '#idenfication_classifier_suggestion#') {
          $fields[] = 'identification.classifiers.suggestions';
        }
        elseif (preg_match('/^#template(.*)#$/', $field)) {
          // Find fields embedded in the template and add them.
          preg_match_all('/\[.*\]/', $field, $matches);
          foreach ($matches as $group) {
            foreach ($group as $token) {
              $fieldPath = str_replace(['[', ']'], '', $token);
              $fields[] = $fieldPath;
            }
          }
          // Also 2nd parameter can be a path to a nested object.
          $tokens = explode(':', $field);
          if (count($tokens) > 2) {
            $fields[] = trim($tokens[2], '#');
          }
        }
        elseif (preg_match('/^#method(.*)#$/', $field)) {
          $fields[] = 'event.sampling_protocol';
        }
        elseif (preg_match('/^#sample_occurrence_comment(.*)#$/', $field)) {
          $fields[] = 'event.event_remarks';
          $fields[] = 'occurrence.occurrence_remarks';
          $fields[] = 'id';
        }
        elseif (preg_match('/^#verification_status(.*)#$/', $field)) {
          $fields[] = 'identification.verification_status';
        }
        elseif (preg_match('/^#verification_substatus(.*)#$/', $field)) {
          $fields[] = 'identification.verification_substatus';
        }
        elseif (preg_match('/^#query(.*)#$/', $field)) {
          $fields[] = 'identification.query';
        }
        elseif (preg_match('/^#coalesce:(.*)#$/', $field, $matches)) {
          $fields = array_merge($fields, explode(',', $matches[1]));
        }
        elseif (preg_match('/^#attr_value:(event|sample|parent_event|occurrence):(\d+)#$/', $field, $matches)) {
          $key = $matches[1] === 'parent_event' ? 'parent_attributes' : 'attributes';
          // Tolerate sample or event for entity parameter.
          $entity = in_array($matches[1], ['sample', 'event', 'parent_event']) ? 'event' : 'occurrence';
          $fields[] = "$entity.$key";
          // When requesting an event attribute, allow the parent event
          // attribute value to be used if necessary.
          if ("$entity.$key" === 'event.attributes') {
            $fields[] = 'event.parent_attributes';
          }
        }
        elseif (preg_match('/^#null_if_zero:([a-z_]+(\.[a-z_]+)*)#$/', $field, $matches)) {
          $fields[] = $matches[1];
        }
        elseif (preg_match('/^#datetime:([a-z_]+(\.[a-z_]+)*):.*#$/', $field, $matches)) {
          $fields[] = $matches[1];
        }
        elseif (preg_match('/^#sref_system:([a-z_]+(\.[a-z_]+)*):.*#$/', $field, $matches)) {
          $fields[] = $matches[1];
        }
        elseif (preg_match('/^#truncate:([a-z_]+(\.[a-z_]+)*):.*#$/', $field, $matches)) {
          $fields[] = $matches[1];
        }
        elseif (preg_match('/^#true_false:([a-z_]+(\.[a-z_]+)*):.*#$/', $field, $matches)) {
          $fields[] = $matches[1];
        }
      }
      $postObj->_source = array_values(array_unique($fields));
    }
    $r = json_encode($postObj, JSON_UNESCAPED_SLASHES);
    return str_replace(['"#emptyobj#"', '"#emptyarray#"'], ['{}', '[]'], $r);
  }

  /**
   * Works out the mode of paging for chunked downloads.
   *
   * Supports Elasticsearch scroll or composite aggregations for paging. Mode
   * is stored in $this->pagingMode.
   */
  private function getPagingMode($format) {
    $this->pagingModeState = empty($_GET['state']) ? 'initial' : $_GET['state'];
    if (isset($_GET['aggregation_type']) && $_GET['aggregation_type'] === 'composite') {
      $this->pagingMode = 'composite';
    }
    elseif ($format === 'csv') {
      $this->pagingMode = 'scroll';
    }
  }

  /**
   * Retrieve the columns associated with a survey.
   *
   * @param string $type
   *   Either 'sample' or 'occurrence'.
   * @param int $id
   *   The survey ID.
   *
   * @return array
   *   Array of associative arrays describing each attribute.
   */
  private function getSurveyAttributes($type, int $id) {
    $cacheId = "survey-attrs-$type-$id";
    $cache = Cache::instance();
    if ($cached = $cache->get($cacheId)) {
      return unserialize($cached, ['field', 'caption']);
    }
    else {
      $sql = <<<SQL
        SELECT a.caption, a.id
        FROM {$type}_attributes_websites aw
        JOIN {$type}_attributes a on a.id = aw.{$type}_attribute_id
        WHERE restrict_to_survey_id=?;
      SQL;
      $columns = [];
      $attrs = RestObjects::$db->query($sql, [$id])->result_array();
      foreach ($attrs as $attr) {
        $columns[] = [
          'field' => "#attr_value:$type:$attr->id#",
          'caption' => $attr->caption,
        ];
      }
      $cache->set($cacheId, serialize($columns));
      return $columns;
    }
  }

  /**
   * Create a temporary file that will be used to build an ES download.
   *
   * @param string $format
   *   Data format, either json or csv.
   *
   * @return array
   *   File details, array containing filename and handle.
   */
  private function openPagingFile($format) {
    $uniqId = $_GET['uniq_id'] ?? $_GET['scroll_id'];
    $cache = Cache::instance();
    $info = $cache->get("es-paging-$uniqId");
    if ($info === NULL) {
      RestObjects::$apiResponse->fail('Bad request', 400, 'Invalid scroll_id or uniq_id parameter.');
    }
    $info['handle'] = fopen(DOCROOT . "download/$info[filename]", 'a');
    return $info;
  }

  /**
   * Builds an empty CSV file ready to received a paged ES request.
   *
   * @param string $format
   *   Data format, either json or csv.
   *
   * @return array
   *   File array containing the name and handle.
   */
  private function preparePagingFile($format) {
    rest_utils::purgeOldFiles('download', 3600);
    $uniqId = uniqid('', TRUE);
    $filename = "download-$uniqId.$format";
    // Reopen file for appending.
    $handle = fopen(DOCROOT . "download/$filename", "w");
    fwrite($handle, $this->getEsOutputHeader($format));
    return [
      'uniq_id' => $uniqId,
      'filename' => $filename,
      'handle' => $handle,
      'done' => 0,
    ];
  }

  /**
   * Proxies the current request to a provided URL.
   *
   * Eg. used when proxying to an Elasticsearch instance.
   *
   * @param string $url
   *   URL to proxy to.
   * @param object $requestBody
   *   Request data.
   * @param string $format
   *   Response format, e.g. 'csv', 'json'.
   * @param bool $ret
   *   Set to TRUE if the response should be returned rather than echoed.
   * @param bool $requestIsRawString
   *   Set to TRUE if the request is a raw string to be sent as-is rather than
   *   an object to be encoded as a string.
   */
  private function proxyToEs($url, $requestBody, $format, $ret, $requestIsRawString) {
    if ($requestIsRawString) {
      $postData = $requestBody;
    }
    else {
      $this->getPagingMode($format);
      $this->getColumnsTemplate($requestBody);
      $file = NULL;
      if ($this->pagingModeState === 'initial') {
        // First iteration of a scrolled request, so prepare an output file.
        $file = $this->preparePagingFile($format);
      }
      elseif ($this->pagingModeState === 'nextPage') {
        $file = $this->openPagingFile($format);
      }
      else {
        echo $this->getEsOutputHeader($format);
      }
      $postData = $this->getEsPostData($requestBody, $format, $file, preg_match('/\/_search/', $url));
    }
    // If request is in debug mode, then return query (for Unit testing).
    if (!empty($_GET['debug']) && $_GET['debug'] === 'true') {
      echo json_encode($postData);
      return;
    }
    $actualUrl = $this->getEsActualUrl($url);
    $session = curl_init($actualUrl);
    if (!empty($postData) && $postData !== '{}') {
      curl_setopt($session, CURLOPT_POST, 1);
      curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      curl_setopt($session, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
    }
    curl_setopt($session, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $headers = curl_getinfo($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
      $responseDecoded = json_decode($response, TRUE);
      if ($responseDecoded['error']['root_cause'][0]['reason'] ?? NULL) {
        $error = $responseDecoded['error']['root_cause'][0]['reason'];
      }
      else {
        $error = curl_error($session);
      }
      if (substr($error, 0, 21) === 'Failed to parse query') {
        $httpStatus = ['Bad Request', 400];
      }
      else {
        $httpStatus = ['Internal Server Error', 500];
      }
      kohana::log('error', 'ES proxy request failed: ' . $error);
      kohana::log('error', 'URL: ' . $actualUrl);
      kohana::log('error', 'Query: ' . $postData);
      kohana::log('error', 'Response: ' . $response);
      if (!empty(RestObjects::$clientSystemId)) {
        kohana::log('error', 'ClientSystemId: ' . RestObjects::$clientSystemId);
      }
      if (!empty(RestObjects::$clientUserId)) {
        kohana::log('error', 'ClientUserId: ' . RestObjects::$clientUserId);
      }
      if (!empty(RestObjects::$clientWebsiteId)) {
        kohana::log('error', 'ClientWebsiteId: ' . RestObjects::$clientWebsiteId);
      }
      RestObjects::$apiResponse->fail($httpStatus[0], $httpStatus[1], $error);
    }
    curl_close($session);
    // Will need decoded data for processing CSV.
    if ($format === 'csv') {
      $data = json_decode($response, TRUE);
      if (!empty($data['error'])) {
        kohana::log('error', 'Bad ES Rest query response: ' . json_encode($data['error']));
        kohana::log('error', 'Query: ' . $postData);
        RestObjects::$apiResponse->fail('Bad request', 400, json_encode($data['error']));
      }
      // Find the list of documents or aggregation output to add to the CSV.
      $itemList = $this->pagingMode === 'composite'
        ? $data['aggregations']['_rows']['buckets']
        : $data['hits']['hits'];
      if ($this->pagingMode === 'composite' && !empty($data['aggregations']['_count'])) {
        $file['total'] = $data['aggregations']['_count']['value'];
      }
    }
    // First response from a scroll, need to grab the scroll ID.
    if ($this->pagingMode === 'scroll' && $this->pagingModeState === 'initial') {
      $file['scroll_id'] = $data['_scroll_id'];
      // ES6/7 tolerance.
      $file['total'] = $data['hits']['total']['value'] ?? $data['hits']['total'];
    }
    elseif ($this->pagingMode === 'scroll' && $this->pagingModeState === 'nextPage') {
      $file['scroll_id'] = $_GET['scroll_id'];
    }

    if ($this->pagingMode === 'off') {
      switch ($format) {
        case 'csv':
          header('Content-type: text/csv');
          $out = fopen('php://output', 'w');
          $this->esToCsv($itemList, $out);
          fclose($out);
          break;

        case 'json':
          if (array_key_exists('charset', $headers)) {
            $headers['content_type'] .= '; ' . $headers['charset'];
          }
          header('Content-type: ' . $headers['content_type']);
          if ($ret) {
            return $response;
          }
          echo $response;
          break;

        default:
          throw new exception("Invalid format $format");
      }
    }
    else {
      switch ($format) {
        case 'csv':
          $this->esToCsv($itemList, $file['handle']);
          break;

        case 'json':
          // Append a separator to the output file.
          fwrite($file['handle'], "\n~~~\n");
          break;

        default:
          throw new exception("Invalid format $format");
      }
      fclose($file['handle']);
      unset($file['handle']);
      $done = FALSE;
      if ($this->pagingMode === 'scroll') {
        $file['done'] = min($file['total'], $file['done'] + MAX_ES_SCROLL_SIZE);
        $done = $file['done'] >= $file['total'];
      }
      elseif ($this->pagingMode === 'composite') {
        if ($format === 'csv') {
          $file['done'] = $file['done'] + count($itemList);
        }
        $data = json_decode($response, TRUE);
        // If we know the total, use that to set done state, otherwise wait for empty response.
        if (isset($file['total'])) {
          $done = $file['done'] >= $file['total'];
        }
        else {
          $done = count($data['aggregations']['_rows']['buckets']) === 0;
        }
        if (empty($data['aggregations']['_rows']['after_key'])) {
          unset($file['after_key']);
        }
        else {
          $file['after_key'] = $data['aggregations']['_rows']['after_key'];
        }
      }
      $file['state'] = $done ? 'done' : 'nextPage';
      $cache = Cache::instance();
      if ($done) {
        $cache->delete("es-paging-$file[uniq_id]", $file);
        unset($file['scroll_id']);
        $this->zip($file);
      }
      else {
        $cache->set("es-paging-$file[uniq_id]", $file);
      }
      $file['filename'] = url::base() . 'download/' . $file['filename'];
      header('Content-type: application/json');
      // Allow for a JSONP cross-site request.
      if (array_key_exists('callback', $_GET)) {
        echo $_GET['callback'] . "(" . json_encode($file) . ")";
      }
      else {
        echo json_encode($file);
      }
    }
    return NULL;
  }

  /**
   * Zip the CSV file ready for download.
   *
   * @param array $file
   *   File details. The filename will be modified to reflect the zip file name.
   */
  private function zip(array &$file) {
    $zip = new ZipArchive();
    $zipFile = DOCROOT . 'download/' . basename($file['filename'], '.csv') . '.zip';
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
      throw new exception("Cannot create zip file $zipFile.");
    }
    $zip->addFile(DOCROOT . 'download/' . $file['filename'], $file['filename']);
    $zip->close();
    unlink(DOCROOT . 'download/' . $file['filename']);
    $file['filename'] = basename($file['filename'], '.csv') . '.zip';
  }

}
