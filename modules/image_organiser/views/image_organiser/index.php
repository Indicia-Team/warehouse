<?php

warehouse::loadHelpers(['data_entry_helper']);
helper_base::add_resource('indiciaFns');
helper_base::$indiciaData['siteUrl'] = url::site();

echo data_entry_helper::select([
  'label' => 'Select image type',
  'fieldname' => 'entity',
  'lookupValues' => [
    'occurrence' => 'Occurrences',
    'sample' => 'Samples',
    'location' => 'Locations',
    'taxon' => 'Taxon',
    'survey' => 'Survey',
  ],
  'helpText' => 'Select which type of images to process',
  'blankText' => '- Please select -',
]);

echo data_entry_helper::dump_javascript();

?>

<p class="alert alert-info">The following button can be used to initiate copying images from the
  root of the /upload folder on the warehouse to sub-folders based on the image created timestamp.
  The path information in the database is updated but the original copies are left in place,
  allowing time for client sites to update cached versions of the image paths before the original
  images are actually deleted.
</p>
<button type="button" class="btn btn-primary" id="move-batch">Begin relocation</button>

<p class="alert alert-info">Once images have been relocated and client sites given a chance to
  update their caches, use the following button to remove the original copies of any files that
  have been successfully copied to a new location.
</p>
<button type="button" class="btn btn-primary" id="delete-batch">Delete relocated images</button>

<div class="alert alert-info" id="current-status"></div>

<textarea class="form-control" id="output"></textarea>
