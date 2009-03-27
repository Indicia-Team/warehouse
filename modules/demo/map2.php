<html>
<head>
<title>Map helper test</title>
<script type='text/javascript' src='../../media/js/jquery.js' ></script>
<script type='text/javascript' src='../../media/js/json2.js' ></script>
<script type='text/javascript' src='../../media/js/OpenLayers.js' ></script>
<script type='text/javascript' src='../../media/js/jquery.indiciaMap.js' ></script>
<script src='http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1'></script>
<script src="http://maps.google.com/maps?file=api&v=2&key=null" type="text/javascript"></script>
<script type='text/javascript'>
(function($){ 
$(document).ready(function()
{
$('#map').indiciaMap();
});
})(jQuery);
</script>
</head>
<body>
<div id='map' />
</body>
</html>
