<?php

  $lang = array
  (
    'resources' => array(
      'projects' => 'Retrieve a list of projects available to this client ID.',
      'projects/{project ID}' => 'Retrieve the details of a single project where {project id} is ' .
          'replaced by the project ID as retreived from an earlier request to /projects.',
      'taxon_observations' => 'Retrieve a list of taxon-observations available to this client ID for a ' .
          'project indicated by a supplied proj_id parameter.',
      'taxon_observations/{taxon-observation ID}' => 'Retrieve the details of a single taxon-observation where ' .
          '{taxon-observation ID} is replaced by the observation ID. A proj_id parameter must be provided and the ' .
          'observation should be available within that project\'s records.',
      'annotations' => 'Retrieve a list of annotations available to this client ID.',
      'annotations/{annotation ID}' => 'Retrieve the details of a single annotation where ' .
          '{annotation ID} is replaced by the observation ID.',
    ),
    'projects' => array(
      'client_id' => 'Your unique client identifier'
    ),
    'taxon_observations' => array(
      'client_id' => 'Your unique client identifier',
      'proj_id' => 'Identifier for the project that contains the observations the client is requesting.',
      'page' => 'The page of records to retrieve when the records available is greater than page_size. The first '.
          'page is page 1. Defaults to 1 if not provided.',
      'page_size' => 'The maximum number of records to retrieve. Defaults to 100 if not provided.',
      'edited_date_from' => 'Restricts the records to those created or edited on or after the date provided. ' .
          'Format yyyy-mm-dd.',
      'edited_date_to' => 'Restricts the records to those created or edited on or before the date provided. ' .
          'Format yyyy-mm-dd.'
    )
  );

?>
