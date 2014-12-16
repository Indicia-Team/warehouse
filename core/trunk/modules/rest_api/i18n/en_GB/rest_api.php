<?php

  $lang = array
  (
    'resources' => array(
      'projects' => 'Retrieve a list of projects available to this client ID.',
      'projects/{project ID}' => 'Retrieve the details of a single project where {project id} is ' .
          'replaced by the project ID.',
      'taxon_observations' => 'Retrieve a list of taxon-observations available to this client ID for a ' .
          'project indicated by a supplied proj_id parameter.',
      'taxon_observations/{taxon observation ID}' => 'Retrieve the details of a single taxon-observation where ' .
          '{taxon-observation ID} is replaced by the observation ID. A proj_id parameter must be provided and the ' .
          'observation should be available within that project\'s records.',
      'annotations' => 'Retrieve a list of annotations available to this client ID.',
      'annotations/{annotation ID}' => 'Retrieve the details of a single annotation where ' .
          '{annotation ID} is replaced by the observation ID.',
    ),
    'taxon_observations' => array(
      'proj_id' => 'Identifier for the projet that contains the observations the client is requesting.'
    )
  );

?>
