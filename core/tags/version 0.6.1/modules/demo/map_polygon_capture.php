<?php
require '../../client_helpers/data_entry_helper.php';
require 'data_entry_config.php';
$googleApiKey = helper_config::$google_api_key;
$base_url = helper_config::$base_url;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Map helper test</title>
<link rel="stylesheet" href="demo.css" type="text/css" media="screen">
<script type='text/javascript' src='../../media/js/jquery.js' ></script>
<script type='text/javascript' src='../../media/js/json2.js' ></script>
<script type='text/javascript' src='../../media/js/OpenLayers.js' ></script>
<script type='text/javascript' src='../../media/js/jquery.indiciaMapPanel.js' ></script>
<script type='text/javascript'>
(function($){
$(document).ready(function()
{
  // Create a vector layer to capture polygons onto, and a control to do the drawing
  polygonLayer = new OpenLayers.Layer.Vector("Polygon Layer");
  drawControl = new OpenLayers.Control.DrawFeature(polygonLayer, OpenLayers.Handler.Polygon);
  // trap when the polygon drawing is finished
  drawControl.events.register('featureadded', drawControl, FeatureAdded);
});
})(jQuery);

// Called when a polygon is added to the map
function FeatureAdded(control) {
  var geom=control.feature.geometry;
  // Store the geometry of the polygon to the database via the hidden geom field
  $('#imp-geom').attr("value", geom);
  // Also store the centroid as the spatial reference visible to the user
  var lonlat = geom.getBounds().getCenterLonLat();
  // get approx metres accuracy we can expect from the mouse click - about 5mm accuracy.
  var precision = this.map.getScale()/200;
  // now round to find appropriate square size
  if (precision<30) {
    precision=8;
  } else if (precision<300) {
    precision=6;
  } else if (precision<3000) {
    precision=4;
  } else {
    precision=2;
  }
  $.getJSON("<?php echo $base_url; ?>/index.php/services/spatial/wkt_to_sref"+
    "?wkt=POINT(" + lonlat.lon + "  " + lonlat.lat + ")"+
    "&system=" + $('#imp-sref-system').val() +
    "&precision=" + precision +
    "&callback=?", function(data)
    {
      $('#imp-sref').attr('value', data.sref);
    }
  );
}
</script>
</head>
<body>
<div id="wrap">
<h1>Polygon Capture Map</h1>
<p>Click on the map to define a polygon. Double click to finish.</p>
<?php
echo data_entry_helper::sref_and_system(array('label'=>'Spatial ref'));
echo data_entry_helper::map_panel(array('controls'=>array('drawControl'), 'layers'=>array('polygonLayer')));
echo data_entry_helper::dump_javascript();
?>
<script type='text/javascript'>
$(document).ready(function() {
  drawControl.activate();
});
</script>
</body>
</html>
