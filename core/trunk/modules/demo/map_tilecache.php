<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<html>
<head>
<title>Map helper test</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<link rel="stylesheet" href="../../media/css/default_site.css" type="text/css" media="screen">
</head>
<body>
<div id="wrap">
<h1>Default Map</h1>
<?php
include '../../client_helpers/data_entry_helper.php';
require 'data_entry_config.php';

/*
 * Output a map. Don't use any preset base layers, but define a tile cache layer pointing to the OpenLayers
 * demo tilecache. Note that the list of resolutions must match the tilecache.
 */
echo data_entry_helper::map_panel(array(
  'presetLayers' => array(),
  'tilecacheLayers' => array(
    array(
      'caption' => 'My Tile Cache', 
      'servers' => array("http://c0.labs.metacarta.com/wms-c/cache/",
                 "http://c1.labs.metacarta.com/wms-c/cache/",
                 "http://c2.labs.metacarta.com/wms-c/cache/",
                 "http://c3.labs.metacarta.com/wms-c/cache/",
                 "http://c4.labs.metacarta.com/wms-c/cache/"
      ),
      'layerName' => 'basic',
      'settings' => array('serverResolutions' => array(0.703125, 0.3515625, 0.17578125, 0.087890625, 
                                        0.0439453125, 0.02197265625, 0.010986328125, 
                                        0.0054931640625, 0.00274658203125, 0.001373291015625, 
                                        0.0006866455078125, 0.00034332275390625, 0.000171661376953125, 
                                        0.0000858306884765625, 0.00004291534423828125, 0.000021457672119140625)
      )
    )
  )
), array(
  'projection' => 4326,
  'displayProjection' => 4326,
  'resolutions' => array(0.087890625, 0.0439453125, 0.02197265625, 0.010986328125)
));

echo data_entry_helper::dump_javascript();
?>
</div>
</body>
</html>
