<?php
session_start();
require '../../../client_helpers/data_entry_helper.php';
require '../data_entry_config.php';
$baseUrl = helper_config::$base_url;
$multimapApiKey = helper_config::$multimap_api_key;
$geoplanetApiKey = helper_config::$geoplanet_api_key;
$readAuth = data_entry_helper::get_read_auth($config['website_id'], $config['password']);
// As this is the first page of the wizard, clear the wizard content
data_entry_helper::clear_session();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>Set up the record card header</title>
<link rel="stylesheet" href="advanced.css" type="text/css" media="screen" />
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
<script type='text/javascript' src='<?php echo $baseUrl ?>/media/js/jquery.js' ></script>
<script type='text/javascript' src='<?php echo $baseUrl ?>/media/js/json2.js' ></script>
<script type='text/javascript' src='<?php echo $baseUrl ?>/media/js/OpenLayers.js' ></script>
<script type='text/javascript' src='<?php echo $baseUrl ?>/media/js/jquery.indiciaMap.js' ></script>
<script type='text/javascript' src='<?php echo $baseUrl ?>/media/js/jquery.indiciaMap.js' ></script>
<script type='text/javascript' src='<?php echo $baseUrl ?>/media/js/jquery.indiciaMap.edit.js' ></script>
<script type='text/javascript' src='<?php echo $baseUrl ?>/media/js/jquery.indiciaMap.edit.locationFinder.js' ></script>
<script src="http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false" type="text/javascript"></script>
<script type="text/javascript" src="http://developer.multimap.com/API/maps/1.2/<?php echo $multimapApiKey; ?>" ></script>
<script type="text/javascript" src="http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1"></script>
<script type='text/javascript'>
var polygonLayer;

(function($){
$(document).ready(function()
{
  // Create a vector layer to capture polygons onto, and a control to do the drawing
  polygonLayer = new OpenLayers.Layer.Vector("Polygon Layer");
  var drawControl = new OpenLayers.Control.DrawFeature(polygonLayer, OpenLayers.Handler.Polygon);
  var navigateControl = new OpenLayers.Control.Navigation();
  var trashButton = new OpenLayers.Control.Button({
    displayClass: "trashButton", trigger: TrashEdits
  });
  var mousePos = new OpenLayers.Control.MousePosition({
    div: document.getElementById("mousePos"),
    prefix: "Lat\\Long:"
  });
  var panel = new OpenLayers.Control.Panel({
    div: document.getElementById("panel")
  });
  panel.addControls([navigateControl, drawControl, trashButton]);
  $('#map').indiciaMap({
      width: "670px",
      presetLayers : ['multimap_landranger', 'google_physical', 'google_satellite', 'virtual_earth'],
      layers : [polygonLayer],
      controls: [panel, drawControl, navigateControl, mousePos]
      }).indiciaMapEdit().locationFinder({
        apiKey: '<?php echo $geoplanetApiKey; ?>'
      });
  // trap when the polygon drawing is finished
  drawControl.events.register('featureadded', drawControl, FeatureAdded);
});
})(jQuery);

// Called when the trashEdits button is clicked
function TrashEdits() {
  polygonLayer.destroyFeatures();
  $('#geom').attr('value', '');
}

// Called when a polygon is added to the map
function FeatureAdded(control) {
  var geom=control.feature.geometry;
  // Store the geometry of the polygon to the database via the hidden geom field
  $('#geom').attr("value", geom);
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
  $.getJSON("<?php echo $baseUrl; ?>/index.php/services/spatial/wkt_to_sref"+
    "?wkt=POINT(" + lonlat.lon + "  " + lonlat.lat + ")"+
    "&system=" + $('#entered_sref_system').val() +
    "&precision=" + precision +
    "&callback=?", function(data)
    {
      $('#entered_sref').attr('value', data.sref);
    }
  );
}
</script>
</head>
<body>
<div id="wrap">
<h1>Record Card Header</h1>

<p>Please enter summary information for your record card below.</p>

<form action="list.php" method="post">
<fieldset>
<legend>Card Header</legend>
<label for="date">Date:</label>
<?php echo data_entry_helper::date_picker('sample:date'); ?>
<br/>
<div id="map"></div>
<div id="panel" class="olControlPanel"></div><span id="mousePos"></span>
<label for="smpAttr:1">Weather:</label>
<input type="text" id="smpAttr:1" name="smpAttr:1" class="wide" />
<br/>
<label for="smpAttr:3">Surroundings:</label>
<?php echo data_entry_helper::select("smpAttr:3", 'termlists_term', 'term', 'id', $readAuth + array('termlist_id' => 2)); ?>
<br/>
<input type="submit" value="Next" class="auto" />
</fieldset>
</form>
</div>
<?php echo data_entry_helper::dump_javascript(); ?>
</body>
</html>