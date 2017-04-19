<?php

  $lang = array
  (
    'introduction' => 'Provides the following list of RESTful resources. For information on ' .
        'authentication, see the ' .
        '<a href="http://indicia-docs.readthedocs.io/en/latest/developing/rest-web-services/authentiction.html">' .
        'authentication documentation</a>.',
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
      'reports/{report_path}-xml/params' => 'Get metadata about the list of parameters available for this report.',
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
      '{report parameter}' => 'Supply report parameter values for filtering as defined by the report /params resource.'
    ),
    'reports/{report_path}.xml/params' => array(),
    'reports/{report_path}.xml/columns' => array()
  );
