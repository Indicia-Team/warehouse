<?php

  $lang = [
    'title' => 'Indicia RESTful API',
    'introduction' => 'Provides RESTful access to data in the Indicia warehouse database.',
    'authenticationTitle' => 'Authentication',
    'authIntroduction' => 'For information on authentication, see the ' .
        '<a href="http://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/authentication.html">' .
        'authentication documentation.</a> The available authentication options are described in the table below.',
    'resourcesTitle' => 'Resources',
    'authMethods' => 'Allowed authentication methods',
    'oauth2User' => 'oAuth2 as warehouse user',
    'oauth2UserHelp' => 'Use oAuth2 password flow to authenticate as a warehouse user',
    'jwtUser' => 'JWT as warehouse user',
    'jwtUserHelp' => 'Use JWT access token to authenticate as a warehouse user',
    'hmacClient' => 'HMAC as client system',
    'hmacClientHelp' => 'Use HMAC to authenticate as a configured client system.',
    'hmacClientHelpHeader' => 'Set the authorisation header to <em>USER:[client system ID]:HMAC:[hmac]</em>',
    'hmacWebsite' => 'HMAC as website',
    'hmacWebsiteHelp' => 'Use HMAC to authenticate as a website registered on the warehouse.',
    'hmacWebsiteHelpHeader' => 'Set the authorisation header to <em>WEBSITE_ID:[website ID]:HMAC:[hmac]</em>',
    'directUser' => 'Direct authentication as warehouse user',
    'directUserHelp' => 'Directly pass the username and password of a warehouse user account.',
    'directClientHelpHeader' => 'Set the authorisation header to <em>USER_ID:[user ID]:WEBSITE_ID:[website id]:SECRET:[user warehouse password]</em>',
    'directClientHelpUrl' => 'Add the following to the URL: <em>?user_id=[user ID]&website_id=[website ID]&secret=[user warehouse password]</em>',
    'directClient' => 'Direct authentication as client system',
    'directClientHelp' => 'Directly pass the ID and secret of a configured client system.',
    'directClientHelpHeader' => 'Set the authorisation header to <em>USER:[client system ID]:SECRET:[secret]</em>',
    'directClientHelpUrl' => 'Add the following to the URL: <em>?user=[client system ID]&secret=[secret]</em>',
    'directWebsite' => 'Direct authentication as website',
    'directWebsiteHelp' => 'Directly pass the ID and password of a website registered on the warehouse.',
    'directWebsiteHelpHeader' => 'Set the authorisation header to <em>WEBSITE_ID:[website ID]:SECRET:[password]</em>',
    'directWebsiteHelpUrl' => 'Add the following to the URL: <em>?website_id=[website ID]&secret=[password]</em>',
    'genericHelpHeader' => 'Specify an authorisation header with a list of token name/value pairs, using colons as a ' .
      'separator, for example <em>TOKEN1:value1:TOKEN2:value2</em>.',
    'genericHelpUrl' => 'Add the tokens to the URL as parameters, using lowercase token names',
    'authMethodsHelpHeader' => 'Provide the authentication tokens using one of the following methods:',
    'allowAuthTokensInUrl' => 'Tokens required for authorisation can be passed in the URL as query parameters or in ' .
        'the Authorization header of the request.',
    'dontAllowAuthTokensInUrl' => 'Tokens required for authorisation must be passed in the Authorization header of ' .
        'the request.',
    'onlyAllowHttps' => 'This authentication method requires you to access the web service via https',
    'resourceOptionInfo' => 'The %s resource',
    'resourceOptionInfo-featured' => 'is limited to reports which have been vetted and flagged as featured',
    'resourceOptionInfo-summary' => 'is limited to reports which show summary data',
    'resourceOptionInfo-cached' => 'shows cached data which may be slightly out of date',
    'resourceOptionInfo-limit_to_own_data' => 'is limited to data entered by you',
    'format_param_help' => 'Request a response in this format, either html or json (default). You can also set the ' .
        'response format using the Accept http header, setting it to text/html or application/json as required.',
    'resources' => array(
      'projects' => 'Retrieve a list of projects available to this client system ID. Only available ' .
          'when authenticating as a client system defined in the REST API\'s configuration file.',
      'projects/{project ID}' => 'Retrieve the details of a single project where {project id} is ' .
          'replaced by the project ID as retreived from an earlier request to /projects.',
      'taxon-observations' => 'Retrieve a list of taxon-observations available to this client ID for a ' .
          'project indicated by a supplied proj_id parameter.',
      'taxon-observations/{taxon-observation ID}' => 'Retrieve the details of a single taxon-observation where ' .
          '{taxon-observation ID} is replaced by the observation ID. A proj_id parameter must be provided and the ' .
          'observation should be available within that project\'s records.',
      'annotations' => 'Retrieve a list of annotations available to this client ID.',
      'annotations/{annotation ID}' => 'Retrieve the details of a single annotation where ' .
          '{annotation ID} is replaced by the observation ID.',
      'taxa' => 'Base resource for taxon interactions. Not currently implemented.',
      'taxa/search' => 'Search resource for taxa. Perform full text searches against the taxonomy information held ' .
          'in the warehouse.',
      'reports' => 'Retrieves the contents of the top level of the reports directory on ' .
          'the warehouse. Can retrieve the output for a subfolder in the directory or ' .
          'a specific report by appending the path to the resource URL.',
      'reports/{report_path}-xml' => 'Access the output for a report specified by the supplied path.',
      'reports/{report_path}-xml/params' => 'Get metadata about the list of parameters available to filter this ' .
            'report by.',
      'reports/{report_path}-xml/columns' => 'Get metadata about the list of columns available for this report.'
    ),
    'projects' => array(
    ),
    'taxon-observations' => array(
      'proj_id' => 'Required when authenticated using a client system. Identifier for the project ' .
          'that contains the observations the client is requesting.',
      'filter_id' => 'Optional when authenticated as a warehouse user. Must point to the ID of a ' .
          'filter in the filters table which has defines_permissions set to true and is linked to ' .
          'the authenticated user. When used, switches the set of records that are accessible from ' .
          'those created by the current user to the set of records identified by the filter.',
      'page' => 'The page of records to retrieve when there are more records available than page_size. The first '.
          'page is page 1. Defaults to 1 if not provided.',
      'page_size' => 'The maximum number of records to retrieve. Defaults to 100 if not provided.',
      'edited_date_from' => 'Restricts the records to those created or edited on or after the date provided. ' .
          'Format yyyy-mm-dd.',
      'edited_date_to' => 'Restricts the records to those created or edited on or before the date provided. ' .
          'Format yyyy-mm-dd.'
    ),
    'annotations' => array(
      'proj_id' => 'Required when authenticated using a client system. Identifier for the project ' .
        'that contains the observations the client is requesting.',
      'filter_id' => 'Optional when authenticated as a warehouse user. Must point to the ID of a ' .
        'filter in the filters table which has defines_permissions set to true and is linked to ' .
        'the authenticated user. When used, switches the set of records that are accessible from ' .
        'those created by the current user to the set of records identified by the filter.',
      'page' => 'The page of records to retrieve when there are more records available than page_size. The first '.
        'page is page 1. Defaults to 1 if not provided.',
      'page_size' => 'The maximum number of records to retrieve. Defaults to 100 if not provided.',
      'edited_date_from' => 'Restricts the annotations to those created or edited on or after the date provided. ' .
        'Format yyyy-mm-dd.',
      'edited_date_to' => 'Restricts the annotations to those created or edited on or before the date provided. ' .
        'Format yyyy-mm-dd.'
    ),
    'taxa' => array(
      'taxon_list_id' => 'ID or list o IDs of taxon list to search against.',
      'searchQuery' => 'Search text which will be used to look up species and taxon names.',
      'taxon_group_id' => 'ID or array of IDs of taxon groups to limit the search to.',
      'taxon_group' => 'Taxon group name or array of taxon group names to limit the search to, an alternative to ' .
          'using taxon_group_id.',
      'taxon_meaning_id' => 'ID or array of IDs of taxon meanings to limit the search to.',
      'taxa_taxon_list_id' => 'ID or array of IDs of taxa taxon list records to limit the search to',
      'preferred_taxa_taxon_list_id' => 'ID or array of IDs of taxa taxon list records to limit the search to, using ' .
          'the preferred name\'s ID to filter against, therefore including synonyms and common names in the search.',
      'preferred_taxon' => 'Preferred taxon name or array of preferred names to limit the search to (e.g. limit to a ' .
          'list of species names). Exact matches required.',
      'external_key' => 'External key or array of external keys to limit the search to (e.g. limit to a list of TVKs).',
      'parent_id' => 'ID of a taxa_taxon_list record limit the search to children of, e.g. a species when searching ' .
          'the subspecies. May be set to null to force top level species only.',
      'language' => 'Languages of names to include in search results. Pass a 3 character iso code for the ' .
          'language, e.g. "lat" for Latin names or "eng" for English names. Alternatively set this to "common" to ' .
          'filter for all common names (i.e. non-Latin names).',
      'preferred' => 'Set to true to limit to preferred names, false to limit to non-preferred names.',
      'commonNames' => 'Set to true to limit to common names, false to exclude common names.',
      'synonyms' => 'Set to true to limit to syonyms, false to exclude synonyms.',
      'abbreviations' => 'Set to false to disable searching 2+3 character species name abbreviations.',
      'marine_flag' => 'Set to true for only marine associated species, false to exclude marine-associated species.',
      'searchAuthors' => 'Set to true to include author strings in the searched text.',
      'wholeWords' => 'Set to true to only search whole words in the full text index, otherwise searches the start ' .
          'of words.',
      'min_taxon_rank_sort_order' => 'Set to the minimum sort order of the taxon ranks to include in the results.',
      'max_taxon_rank_sort_order' => 'Set to the maximum sort order of the taxon ranks to include in the results.',
      'limit' => 'Limit the number of records in the response.',
      'offset' => 'Offset from the start of the dataset that the response will start.',
      'include' => 'Defines which parts of the response structure to include. If the count and paging data are not ' .
          'required then exclude them for better performance.',
    ),
    'reports' => array(
      'featured_folder_description' => 'Provides a list of well maintained reports which are ' .
        'recommended as a starting point when exploring the library of reports.',
      'filter_id' => 'Optional when authenticated as a warehouse user. Must point to the ID of a ' .
        'filter in the filters table which has defines_permissions set to true and is linked to ' .
        'the authenticated user. When used, switches the set of records that are accessible from ' .
        'those created by the current user to the set of records identified by the filter.',
      'limit' => 'Limit the number of records in the response.',
      'offset' => 'Offset from the start of the dataset that the response will start.',
      'sortby' => 'The field to sort by. Must be compatible with the SQL generated for the report.',
      'sortdir' => 'Direction of sort, ASC or DESC',
      'columns' => 'Comma separated list of column fieldnames to include in the report output. Default is all ' .
          'available in the report.',
      'cached' => 'Set to true to enable server side caching of the report output. Repeated requests with for the ' .
          'same report and parameters will be fast but data will not be fully up to date.',
      '{report parameter}' => 'Supply report parameter values for filtering as defined by the report /params resource.',
    ),
    'reports/{report_path}.xml/params' => [],
    'reports/{report_path}.xml/columns' => [],
  ];
