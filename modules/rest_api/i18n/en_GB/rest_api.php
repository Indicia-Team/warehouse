<?php

  $lang = array
  (
    'title' => 'Indicia RESTful API',
    'introduction' => 'Provides RESTful access to data in the Indicia warehouse database.',
    'authenticationTitle' => 'Authentication',
    'authIntroduction' => 'For information on authentication, see the ' .
        '<a href="http://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/authentiction.html">' .
        'authentication documentation.</a> The available authentication options are described in the table below.',
    'resourcesTitle' => 'Resources',
    'authMethods' => 'Allowed authentication methods',
    'oauth2User' => 'oAuth2 as warehouse user',
    'oauth2UserHelp' => 'Use oAuth2 password flow to authenticate as a warehouse user',
    'hmacClient' => 'HMAC as client system',
    'hmacClientHelp' => 'Use HMAC to authenticate as a configured client system.',
    'hmacWebsite' => 'HMAC as website',
    'hmacWebsiteHelp' => 'Use HMAC to authenticate as a website registered on the warehouse.',
    'directUser' => 'Direct authentication as warehouse user',
    'directUserHelp' => 'Directly pass the username and password of a warehouse user account.',
    'directClient' => 'Direct authentication as client system',
    'directClientHelp' => 'Directly pass the ID and secret of a configured client system.',
    'directWebsite' => 'Direct authentication as website',
    'directWebsiteHelp' => 'Directly pass the ID and password of a website registered on the warehouse.',
    'allowAuthTokensInUrl' => 'Tokens required for authorisation can be passed in the URL as query parameters or in ' .
        'the Authorization header of the request.',
    'dontAllowAuthTokensInUrl' => 'Tokens required for authorisation must be passed in the Authorization header of ' .
        'the request.',
    'onlyAllowHttps' => 'This authentication method requires you to access the web service via https',
    'onlyAllowFeaturedReports' => 'Only allows access to reports that have been vetted and flagged as featured.',
    'format_param_help' => 'Request a response in this format, either html or json (default).',
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
      '{report parameter}' => 'Supply report parameter values for filtering as defined by the report /params resource.'
    ),
    'reports/{report_path}.xml/params' => array(),
    'reports/{report_path}.xml/columns' => array()
  );
