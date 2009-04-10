<?php
require '../data_entry_config.php';
$googleApiKey = $config['google_api_key'];
$multimapApiKey = $config['multimap_api_key'];
$geoserverUrl = $config['geoserver_url'];
$featureType = $config['feature_type'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
<title>Distribution Map</title>
<link rel="stylesheet" href="advanced.css" type="text/css" media="screen" />
<link rel="stylesheet" href="../../../media/css/default_site.css" type="text/css" media="screen">
<script type='text/javascript' src='../../../media/js/jquery.js' ></script>
<script type='text/javascript' src='../../../media/js/json2.js' ></script>
<script type='text/javascript' src='../../../media/js/OpenLayers.js' ></script>
<script type='text/javascript' src='../../../media/js/jquery.indiciaMap.js' ></script>
<script type='text/javascript' src='../../../media/js/jquery.indiciaMap.js' ></script>
<script type='text/javascript' src='../../../media/js/jquery.indiciaMap.edit.js' ></script>
<script type='text/javascript' src='../../../media/js/jquery.indiciaMap.edit.locationFinder.js' ></script>
<script src="http://maps.google.com/maps?file=api&v=2&key=<?php echo $googleApiKey; ?>" type="text/javascript"></script>
<script type="text/javascript" src="http://developer.multimap.com/API/maps/1.2/<?php echo $multimapApiKey; ?>" ></script>
<script type='text/javascript'>
(function($){
$(document).ready(function()
{
$('#map').indiciaMap({
    presetLayers : ['multimap_landranger', 'google_physical', 'google_satellite'],
    indiciaGeoSvc: '<?php echo $geoserverUrl; ?>',
    indiciaWMSLayers : {'Occurrences' : '<?php echo $featureType; ?>'},
    width: "700px",
    height: "700px",
    initial_zoom: 6,
    initial_lat: 7260000
  });
});
})(jQuery);
</script>
</head>
<body>
<div id="wrap">
<h1>Distribution Map</h1>
<div id="map"></div>
</div>
</body>
</html>