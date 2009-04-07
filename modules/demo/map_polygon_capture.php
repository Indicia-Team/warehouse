<?php include '../../client_helpers/helper_config.php';
$base_url = helper_config::$base_url;
?>
<html>
<head>
<title>Map helper test</title>
<script type='text/javascript' src='../../media/js/jquery.js' ></script>
<script type='text/javascript' src='../../media/js/json2.js' ></script>
<script type='text/javascript' src='../../media/js/OpenLayers.js' ></script>
<script type='text/javascript' src='../../media/js/jquery.indiciaMap.js' ></script>
<script type='text/javascript' src='../../media/js/jquery.indiciaMap.js' ></script>
<script type='text/javascript' src='../../media/js/jquery.indiciaMap.edit.js' ></script>
<script src="http://maps.google.com/maps?file=api&v=2&key=null" type="text/javascript"></script>
<script type='text/javascript'>

(function($){
$(document).ready(function()
{
	// Create a vector layer to capture polygons onto, and a control to do the drawing
	var polygonLayer = new OpenLayers.Layer.Vector("Polygon Layer");
	var drawControl = new OpenLayers.Control.DrawFeature(polygonLayer, OpenLayers.Handler.Polygon);
	$('#map').indiciaMap({presetLayers : ['google_physical', 'google_hybrid'], layers : [polygonLayer], controls: [drawControl]}).indiciaMapEdit();
	// trap when the polygon drawing is finished
	drawControl.events.register('featureadded', drawControl, FeatureAdded)
	drawControl.activate();

});
})(jQuery);

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
	$.getJSON("<?php echo $base_url; ?>/index.php/services/spatial/wkt_to_sref"+
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
<div id='map' />
</body>
</html>
