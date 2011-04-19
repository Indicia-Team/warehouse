<?php
$allowed = array(
  'data_entry/test_data_entry.php',
  'data_entry/species_checklist.php',
  'data_entry/basic_data_entry_tutorial.php',
  'data_entry/flickr.php',
  'data_entry/file_upload.php',
  'map_default.php',
  'map_modular.php',
  'map_tilecache.php',
  'occurrence_grid.php',
  'occurrence.php',
  'map_polygon_capture.php',
  'valid.php',
  'advanced_data_entry/header.php',
  'advanced_data_entry/list.php',
  'advanced_data_entry/save.php',
  'advanced_data_entry/success.php',
  'advanced_data_entry/map.php',
  'reports/accessing_report_data.php',
  'reports/report_1.php',
  'reports/reports_and_charts.php',
  'login_control.php',
  'forgotten_password_control.php',
);

if ($_GET['file']) {
  if (in_array($_GET['file'], $allowed)) {
    highlight_file($_GET['file']);
  } else {
    echo "file not in list of allowed source code files";
  }
}
?>
