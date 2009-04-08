<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>OpenLayers Example</title>
    <link rel="stylesheet" href="../theme/default/style.css" type="text/css" />
    <link rel="stylesheet" href="style.css" type="text/css" />
    <script src="../../media/js/OpenLayers.js"></script>
    <script src='http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1'></script>
    <?php include 'data_entry_config.php'; ?>
  <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=<?php echo $config['google_api_key'] ?>"
      type="text/javascript"></script>
    <script type="text/javascript">
        // making this a global variable so that it is accessible for
        // debugging/inspecting in Firebug
        var map = null;
        var format = 'image/png';


        function init(){

      var options = {
        projection: new OpenLayers.Projection("EPSG:900913"),
        displayProjection: new OpenLayers.Projection("EPSG:4326"),
        units: "m",
        numZoomLevels: 18,
        maxResolution: 156543.0339,
        maxExtent: new OpenLayers.Bounds(
          -20037508, -20037508,
          20037508, 20037508.34)
      };

            map = new OpenLayers.Map('map', options);

            var gphy = new OpenLayers.Layer.Google(
      "Google Physical",
       {type: G_PHYSICAL_MAP, 'sphericalMercator': true}
      );
      var gmap = new OpenLayers.Layer.Google(
        "Google Streets", // the default
        {numZoomLevels: 20, 'sphericalMercator': true}
      );
      var ghyb = new OpenLayers.Layer.Google(
        "Google Hybrid",
        {type: G_HYBRID_MAP, numZoomLevels: 20, 'sphericalMercator': true}
      );
      var gsat = new OpenLayers.Layer.Google(
        "Google Satellite",
        {type: G_SATELLITE_MAP, numZoomLevels: 20, 'sphericalMercator': true}
      );

            var ol_wms = new OpenLayers.Layer.WMS(
                "OpenLayers WMS",
                "http://labs.metacarta.com/wms/vmap0",
                {layers: 'basic', 'sphericalMercator': true}
            );

            var jpl_wms = new OpenLayers.Layer.WMS(
                "NASA Global Mosaic",
                "http://t1.hypercube.telascience.org/cgi-bin/landsat7",
                {layers: "landsat7", 'sphericalMercator': true}
            );

            var velayer = new OpenLayers.Layer.VirtualEarth(
              "Virtual Earth",
              {'type': VEMapStyle.Aerial, 'sphericalMercator': true}
            );

      // Samples layer
            var samples = new OpenLayers.Layer.WMS(
                "Samples from Indicia", "http://192.171.199.208:8080/geoserver/wms",
                {
                    layers: 'opal:indicia_samples',
                    srs: 'EPSG:900913',
                    transparent: true,
                    format: format
                },
                {singleTile: true, ratio: 1,isBaseLayer:false, opacity:0.5}
            );

            map.addLayers([gphy, gmap, gsat, ghyb, velayer, jpl_wms, ol_wms, samples]);
            map.addControl(new OpenLayers.Control.LayerSwitcher());
            map.setCenter(new OpenLayers.LonLat(-100000,6700000),7);
        }
    </script>
  </head>

  <body onload="init()">
    <h1 id="title">Indicia Spatial Data Example</h1>
    <div id="tags"></div>
    <p id="shortdesc">
        This page demonstrates a simple map built using the OpenLayers JavaScript library and GeoServer. The data shown is contained in Indicia's
        samples table as PostGIS geometry data. Geoserver provides a link to this data in the form of an OGC WMS web service.
    </p>
    <div id="map" class="smallmap" style="width: 850px; height: 600px;"></div>
    <div id="docs"></div>
  </body>

</html>

