<p>The import has completed successfully.</p>
<?php
warehouse::loadHelpers(['data_entry_helper']);
echo data_entry_helper::textarea([
  'label' => 'Output log',
  'fieldname' => 'output',
  'default' => count($log) > 0 ? implode("\n", $log) : 'No action was taken',
  'rows' => 50,
]);
