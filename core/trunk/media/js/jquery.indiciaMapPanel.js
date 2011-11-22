

/* Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 */

/**
 * Add functions to this array for them to be called when the map settings are
 * available, allowing any setting to be overridden..
 */
mapSettingsHooks = [];

/**
 * Add functions to this array for them to be called when the map initialises.
 */
mapInitialisationHooks = [];

/**
* Class: indiciaMapPanel
* JavaScript & OpenLayers based map implementation class for Indicia data entry forms.
* This code file supports read only maps. A separate plugin will then run on top of this to provide editing support
* and can be used in a chainable way. Likewise, another plugin will provide support for finding places.
*/

(function($) {
  $.fn.indiciaMapPanel = function(options, olOptions) {
    /**
     * Enable a multimap landranger preference.
     */
    function _enableMMLandranger() {
      // Don't do this if the MM script is not linked up properly, otherwise we get a JS
      // exception and the other scripts stop running
      if (typeof MMDataResolver !== "undefined") {
        var landrangerData = 904;
        var prefs = MMDataResolver.getDataPreferences(MM_WORLD_MAP);

        // Remove the landranger data where it is present
        for (i=0; i<prefs.length; i++) {
          if (landrangerData == prefs[i]) {
            prefs.splice(i, 1);
          }
        }
        // Add to beginning of array (highest priority)
        prefs.unshift(landrangerData);

        MMDataResolver.setDataPreferences( MM_WORLD_MAP, prefs );
      }
    }
    
    /**
     * Remove all features except a boundary feature which is not controlled by the click point.
     * This functionality allows a location to havea centroid and separate boundary.
     */
    function _removeAllFeaturesExcept(layer, type) {
      var toRemove = [];
      $.each(layer.features, function(idx, feature) {
        if (feature.attributes.type!=="boundary") {
          toRemove.push(feature);
        }
      });
      layer.removeFeatures(toRemove, {});
    }

    /**
     * Add a well known text definition of a feature to the map.
     * @access private
     */
    function _showWktFeature(div, wkt, layer, invisible) {
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read(wkt);
      feature.style = new style(opts.searchLayer && layer == div.map.searchLayer);
      _removeAllFeaturesExcept(layer, 'boundary');
      var features = [feature];

      if(invisible !== null){
        //there are invisible features that define the map extent
        $.each(invisible, function(i,corner){
          feature = parser.read(corner);
          feature.style = new style(true);
          feature.style.pointRadius = 0;
          features.push(feature);
        });
      }
      layer.addFeatures(features);
      var bounds=layer.getDataExtent();

      if(invisible === null) {
        // extend the boundary to include a buffer, so the map does not zoom too tight.
        var dy = (bounds.top-bounds.bottom) * div.settings.maxZoomBuffer;
        var dx = (bounds.right-bounds.left) * div.settings.maxZoomBuffer;
          bounds.top = bounds.top + dy;
          bounds.bottom = bounds.bottom - dy;
          bounds.right = bounds.right + dx;
          bounds.left = bounds.left - dx;
      }

      if (div.map.getZoomForExtent(bounds) > div.settings.maxZoom) {
        // if showing something small, don't zoom in too far
        div.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
      }
      else {
        // Set the default view to show something triple the size of the grid square
        div.map.zoomToExtent(bounds);
      }
    }

    /*
     * An OpenLayers vector style object
     */
    function style(isSearch) {
      this.fillColor = isSearch ? opts.fillColorSearch : opts.fillColor;
      this.fillOpacity = isSearch ? opts.fillOpacitySearch : opts.fillOpacity;
      this.hoverFillColor = opts.hoverFillColor;
      this.hoverFillOpacity = opts.hoverFillOpacity;
      this.strokeColor = isSearch ? opts.strokeColorSearch : opts.strokeColor;
      this.strokeOpacity = opts.strokeOpacity;
      this.strokeWidth = opts.strokeWidth;
      this.strokeLinecap = opts.strokeLinecap;
      this.strokeDashstyle = opts.strokeDashstyle;
      this.hoverStrokeColor = opts.hoverStrokeColor;
      this.hoverStrokeOpacity = opts.hoverStrokeOpacity;
      this.hoverStrokeWidth = opts.hoverStrokeWidth;
      this.pointRadius = opts.pointRadius;
      this.hoverPointRadius = opts.hoverPointRadius;
      this.hoverPointUnit = opts.hoverPointUnit;
      this.pointerEvents = opts.pointerEvents;
      this.cursor = opts.cursor;
    }

    /**
     * Use jQuery selectors to locate any other related controls on the page which need to have events
     * bound to them to associate them with the map.
     */
    function _bindControls(div) {

      // If the spatial ref input control exists, bind it to the map, so entering a ref updates the map
      $('#'+opts.srefId).change(function() {
        _handleEnteredSref($(this).val(), div);
      });
      // If the spatial ref latitude or longitude input control exists, bind it to the map, so entering a ref updates the map
      $('#'+opts.srefLatId).change(function() {
        // Only do something if the long is also populated
        if ($('#'+opts.srefLongId).val()!='') {
          // copy the complete sref into the sref field
          $('#'+opts.srefId).val($(this).val() + ', ' + $('#'+opts.srefLongId).val());
          _handleEnteredSref($('#'+opts.srefId).val(), div);
        }
      });
      $('#'+opts.srefLongId).change(function() {
        // Only do something if the long is also populated
        if ($('#'+opts.srefLatId).val()!='') {
          // copy the complete sref into the sref field
          $('#'+opts.srefId).val($('#'+opts.srefLatId).val() + ', ' + $(this).val());
          _handleEnteredSref($('#'+opts.srefId).val(), div);
        }
      });

      // If a place search (georeference) control exists, bind it to the map.
      $('#'+div.georefOpts.georefSearchId).keypress(function(e) {
        if (e.which==13) {
          _georeference(div);
          return false;
        }
      });

      $('#'+div.georefOpts.georefSearchBtnId).click(function() {
        _georeference(div);
      });

      $('#'+div.georefOpts.georefCloseBtnId).click(function(e) {
        $('#'+div.georefOpts.georefDivId).hide('fast', function() {div.map.updateSize();});
        e.preventDefault();
      });

      $('#imp-location').change(function()
      {
        div.map.editLayer.destroyFeatures();
        if (this.value!=='') {
          // Change the location control requests the location's geometry to place on the map.
          $.getJSON(div.settings.indiciaSvc + "index.php/services/data/location/"+this.value +
            "?mode=json&view=detail" + div.settings.readAuth + "&callback=?", function(data) {
              // store value in saved field?
              if (data.length>0) {
                _showWktFeature(div, data[0].boundary_geom || data[0].centroid_geom, div.map.editLayer, null);
              }
            }
          );
        }
      });
    }

    function _handleEnteredSref(value, div) {
      if (value!='') {
        $.getJSON(div.settings.indiciaSvc + "index.php/services/spatial/sref_to_wkt"+
            "?sref=" + value +
            "&system=" + _getSystem() +
            "&callback=?", function(data) {
              if(typeof data.error != 'undefined')
                if(data.error == 'Spatial reference is not a recognisable grid square.')
                  alert(div.settings.msgSrefNotRecognised);
                else
                  alert(data.error);
              else {
                // store value in saved field?
                if (div.map.editLayer) {
                  _showWktFeature(div, data.wkt, div.map.editLayer, null);
                }
                $('#'+opts.geomId).val(data.wkt);
              }
            }
        );
      }
    }

    /**
     * Having clicked on the map, and asked warehouse services to transform this to a WKT, add the feature to the map editlayer.
     */
    function _setClickPoint(data, div) {
      $('#'+opts.srefId).val(data.sref);
      // If the sref is in two parts, then we might need to split it across 2 input fields for lat and long
      if (data.sref.indexOf(' ')!==-1) {
        var parts=data.sref.split(' ');
        // part 1 may have a comma at the end, so remove
        parts[0]=parts[0].split(',')[0];
        $('#'+opts.srefLatId).val(parts[0]);
        $('#'+opts.srefLongId).val(parts[1]);
      }
      _removeAllFeaturesExcept(div.map.editLayer, 'boundary');
      $('#'+opts.geomId).val(data.wkt);
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read(data.wkt);
      feature.attributes = {type:"clickPoint"};
      feature.style = new style(false);
      if (div.map.projection.getCode() != div.indiciaProjection.getCode()) {
        feature.geometry.transform(div.indiciaProjection, div.map.projection);
      }
      div.map.editLayer.addFeatures([feature]);
    }

    function _georeference(div) {
      if (!div.georefInProgress) {
        div.georefInProgress = true;
        $('#' + div.georefOpts.georefDivId).hide();
        div.map.updateSize();
        $('#' + div.georefOpts.georefOutputDivId).empty();
        var searchtext = $('#' + div.georefOpts.georefSearchId).val();
        if (searchtext != '') {
          // delegate the service lookup task to the georeferencer driver that is loaded.
          div.georeferencer.georeference(searchtext);
        }
      }
    }

    /**
     * Callback function, called by the georeferencer driver when it has found the results of a place
     * search.
     */
    function _displayGeorefOutput(div, places) {
      if (places.length>0) {
        var ref;
        var corner1;
        var corner2;
        var epsg = (places[0].epsg === undefined ? 4326 : places[0].epsg);
        if (places.length==1 && places[0].name.toLowerCase()==$('#' + div.georefOpts.georefSearchId).val().toLowerCase()) {
          ref=places[0].centroid.y + ', ' + places[0].centroid.x;
          corner1=places[0].boundingBox.northEast.y + ', ' + places[0].boundingBox.northEast.x;
          corner2=places[0].boundingBox.southWest.y + ', ' + places[0].boundingBox.southWest.x;
          _displayLocation(div, ref, corner1, corner2, epsg);
        } else if (places.length!==0) {
          $('<p>'+opts.msgGeorefSelectPlace+'</p>')
                  .appendTo('#'+div.georefOpts.georefOutputDivId);
          var ol=$('<ol>'), placename;
          $.each(places, function(i,place){
            ref= place.centroid.y + ', ' + place.centroid.x;
            corner1=place.boundingBox.northEast.y + ', ' + place.boundingBox.northEast.x;
            corner2=place.boundingBox.southWest.y + ', ' + place.boundingBox.southWest.x;
            placename= (place.display===undefined ? place.name : place.display);
            if (place.placeTypeName!==undefined) {
              placename = placename+' (' + place.placeTypeName + ')';
            }
            if (place.admin1!==undefined && place.admin1!='') {
              placename = placename + ', '+place.admin1;
            }
            if (place.admin2!==undefined && place.admin2!='') {
              placename = placename + '\\' + place.admin2;
            }

            ol.append($("<li>").append(
              $("<a href='#'>" + placename + "</a>")
                .click(function(e) {e.preventDefault();})
                .click((
                  // use closures to persist the values of ref, corner1, corner 2
                  function(ref, corner1, corner2, epsg){
                    return function() {
                      _displayLocation(div, ref, corner1, corner2, epsg);
                    };
                  }
                )(ref, corner1, corner2, epsg))
            ));
          });

          ol.appendTo('#'+div.georefOpts.georefOutputDivId);
          $('#'+div.georefOpts.georefDivId).show("fast", function() {div.map.updateSize();});
        }
      } else {
        $('<p>'+opts.msgGeorefNothingFound+'</p>').appendTo('#'+div.georefOpts.georefOutputDivId);
        $('#'+div.georefOpts.georefDivId).show("fast", function() {div.map.updateSize();});
      }
      div.georefInProgress = false;
    }

    /**
    * After georeferencing a place, display a point on the map representing that place.
    * @access private
    */
    function _displayLocation(div, ref, corner1, corner2, epsgCode)
    {
      var epsg=new OpenLayers.Projection("EPSG:"+epsgCode);
      var refxy = ref.split(', ');
      var dataref = new OpenLayers.Geometry.Point(refxy[1],refxy[0]).transform(epsg, div.map.projection).toString();
      var corner1xy = corner1.split(', ');
      var datac1 = new OpenLayers.Geometry.Point(corner1xy[1],corner1xy[0]).transform(epsg, div.map.projection).toString();
      var corner2xy = corner2.split(', ');
      var datac2 = new OpenLayers.Geometry.Point(corner2xy[1],corner2xy[0]).transform(epsg, div.map.projection).toString();
      _showWktFeature(div, dataref, div.map.searchLayer, [datac1, datac2]);
      if(div.settings.searchUpdatesSref && !div.settings.searchLayer){ // if no separate search layer, ensure sref matches feature in editlayer, if requested.
          $('#'+opts.geomId).val(dataref);
          // Unfortunately there is no guarentee that the georeferencer will return the sref in the system required: eg it will usually be in
          // Lat/Long EPSG:4326 WGS 84, but the sref system being used to record the data (which may not even be selectable by the user)
          // may be eg 27700 (British National Grid) or 2169 (Luxembourg), or 27572 (French).
          // We need to convert to the required system if the systems differ - the wkt is always in 900913.
          if(_getSystem() != epsgCode){
            $.getJSON(opts.indiciaSvc + "index.php/services/spatial/wkt_to_sref"+
                  "?wkt=" + dataref +
                  "&system=" + _getSystem() +
                  "&precision=8" +
                  "&output=" + div.settings.latLongFormat +
                  "&callback=?", function(data)
             {
              if(typeof data.error != 'undefined') {
                if(data.error == 'wkt_to_sref translation is outside range of grid.')
                  alert(div.settings.msgSrefOutsideGrid);
                else
                  alert(data.error);
              } else {
                  $('#'+opts.srefId).val(data.sref);
                  // If the sref is in two parts, then we might need to split it across 2 input fields for lat and long
                  if (data.sref.indexOf(' ')!==-1) {
                    var parts=data.sref.split(' ');
                    // part 1 may have a comma at the end, so remove
                    parts[0]=parts[0].split(',')[0];
                    $('#'+opts.srefLatId).val(parts[0]);
                    $('#'+opts.srefLongId).val(parts[1]);
                  }
              }
             }
            );
          } else {
            $('#'+opts.srefId).val(ref);
            $('#'+opts.srefLatId).val(refxy[0]);
            $('#'+opts.srefLongId).val(refxy[1]);
          }
      }
    }

    /**
     * Return the system, by loading from the system control. If not present, revert to the default.
     */
    function _getSystem() {
      var selector=$('#'+opts.srefSystemId);
      if (selector.length===0) {
        return opts.defaultSystem;
      }
      else {
        return selector.val();
      }
    }

    /**
    * Some pre-configured layers that can be added to the map.
    */
    function _getPresetLayers(settings) {
      var r={
        openlayers_wms : function() { return new OpenLayers.Layer.WMS('OpenLayers WMS', 'http://labs.metacarta.com/wms/vmap0', {layers: 'basic'}, {'sphericalMercator': true}); },
        nasa_mosaic : function() { return new OpenLayers.Layer.WMS('NASA Global Mosaic', 'http://t1.hypercube.telascience.org/cgi-bin/landsat7', {layers: 'landsat7'}, {'sphericalMercator': true}); },
        virtual_earth : function() { return new OpenLayers.Layer.VirtualEarth('Virtual Earth', {'type': VEMapStyle.Aerial, 'sphericalMercator': true}); },
        bing_aerial : function() { return new OpenLayers.Layer.Bing({name: 'Bing Aerial', 'type': 'Aerial', 'key': settings.bing_api_key, 'sphericalMercator': true}); },
        bing_hybrid : function() { return new OpenLayers.Layer.Bing({name: 'Bing Hybrid', 'type': 'AerialWithLabels', 'key': settings.bing_api_key, 'sphericalMercator': true}); },
        bing_shaded : function() { return new OpenLayers.Layer.Bing({name: 'Bing Shaded', 'type': 'road', 'key': settings.bing_api_key, 'sphericalMercator': true}); },
        multimap_default : function() { return new OpenLayers.Layer.MultiMap('MultiMap', {sphericalMercator: true}); },
        multimap_landranger : function() { return new OpenLayers.Layer.MultiMap('Multimap OS Landranger', {sphericalMercator: true}); },
        osm : function() { return new OpenLayers.Layer.OSM(); }
      };
      // To protect ourselves against exceptions because the Google script would not link up, we
      // only enable these layers if the Google constants are available. We separately check for google V2 and V3 layers
      // to maintain backwards compatibility
      if (typeof G_PHYSICAL_MAP != "undefined") {
        r.google_physical =
            function() { return new OpenLayers.Layer.Google('Google Physical', {type: G_PHYSICAL_MAP, 'sphericalMercator': true}); };
        r.google_streets =
            function() { return new OpenLayers.Layer.Google('Google Streets', {numZoomLevels : 20, 'sphericalMercator': true}); };
        r.google_hybrid =
            function() { return new OpenLayers.Layer.Google('Google Hybrid', {type: G_HYBRID_MAP, numZoomLevels: 20, 'sphericalMercator': true}); };
        r.google_satellite =
            function() { return new OpenLayers.Layer.Google('Google Satellite', {type: G_SATELLITE_MAP, numZoomLevels: 20, 'sphericalMercator': true}); };
      } else if (typeof google !== "undefined" && typeof google.maps !== "undefined") {
        r.google_physical =
            function() { return new OpenLayers.Layer.Google('Google Physical', {type: google.maps.MapTypeId.TERRAIN, 'sphericalMercator': true}); };
        r.google_streets =
            function() { return new OpenLayers.Layer.Google('Google Streets', {numZoomLevels : 20, 'sphericalMercator': true}); };
        r.google_hybrid =
            function() { return new OpenLayers.Layer.Google('Google Hybrid', {type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20, 'sphericalMercator': true}); };
        r.google_satellite =
            function() { return new OpenLayers.Layer.Google('Google Satellite', {type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 20, 'sphericalMercator': true}); };
      }
      return r;
    }
    
    /*
     * Selects the features in the contents of a bounding box
     */
    function selectBox(position, layers, div) {
      if (position instanceof OpenLayers.Bounds) {
        var minXY = div.map.getLonLatFromPixel(
            new OpenLayers.Pixel(position.left, position.bottom)
        );
        var maxXY = div.map.getLonLatFromPixel(
            new OpenLayers.Pixel(position.right, position.top)
        );
        var bounds = new OpenLayers.Bounds(
            minXY.lon, minXY.lat, maxXY.lon, maxXY.lat
        );
        var layer;
        for(var l=0; l<layers.length; ++l) {
          layer = layers[l];
          $.each(layer.selectedFeatures, function(idx, feature) {
            layer.drawFeature(feature, 'default');
          });
          layer.selectedFeatures = [];
          for(var i=0, len = layer.features.length; i<len; ++i) {
            var feature = layer.features[i];
            // check if the feature is displayed
            if (!feature.getVisibility()) {
              continue;
            }
            if (bounds.toGeometry().intersects(feature.geometry)) {
              if (OpenLayers.Util.indexOf(layer.selectedFeatures, feature) == -1) {
                layer.selectedFeatures.push(feature);
                layer.drawFeature(feature, 'select');
              }
            }
          }
        }
      }
    }
    
    /** 
     * Create tools required to click on features to drill into the data etc.
     */
    function getClickableLayersControl(div, align) {
      if (div.settings.clickableLayers.length!==0) {
        var clickableWMSLayerNames = [], clickableVectorLayers = [], wmsUrl='';
        // find out which of the clickable layers are WMS or Vector, since we handle them differently.
        $.each(div.settings.clickableLayers, function(i, item) {
          if (item.CLASS_NAME==='OpenLayers.Layer.WMS') {
            if (wmsUrl==='') {
              // store just the first wms layer's URL since all clickable layers must be from the same url.
              wmsUrl = item.url;
            }
            clickableWMSLayerNames.push(item.params.LAYERS);
          } else if (item.CLASS_NAME==='OpenLayers.Layer.Vector')
            clickableVectorLayers.push(item);           
        });
        
        clickableWMSLayerNames = clickableWMSLayerNames.join(',');
        // Create a control that can handle both WMS and vector layer clicks.
        var infoCtrl = new OpenLayers.Control({
          displayClass: align + 'olControlSelectFeature',
          lastclick: {},
          allowBox: false,
          activate: function() {
            var handlerOptions = {
              'single': true,
              'double': false,
              'stopSingle': false,
              'stopDouble': true
            };
            if (clickableVectorLayers.length>0) {
              this.handlers = { box: new OpenLayers.Handler.Box(
                  this, {done: this.onGetInfo},
                  {boxDivClassName: "olHandlerBoxSelectFeature"}
                )
              };
              this.handlers.box.activate();
              this.allowBox = true;
            } else {
              // allow click or bounding box actions
              this.handlers = { click: new OpenLayers.Handler.Click(this, {
                  'click': this.onGetInfo
                }, handlerOptions)
              };
              this.handlers.click.activate();
            }
            // create a protocol for the WMS getFeatureInfo requests if we need to
            if (wmsUrl!=='') {
              this.protocol = new OpenLayers.Protocol.HTTP({
                url: wmsUrl,
                format: new OpenLayers.Format.WMSGetFeatureInfo()
              });
            }
            OpenLayers.Control.prototype.activate.call(this);
          },
          // handler for the click or bounding box action
          onGetInfo: function(position) {
            var bounds, xy;
            // we could have a point or a bounds
            if (position instanceof OpenLayers.Bounds) {
              bounds = position;
              // use box centre as click point
              this.lastclick.x = (position.left + position.right) / 2;
              this.lastclick.y = (position.bottom + position.top) / 2;
            } else {
              // create a bounds from the click point. It may have xy if from WMS click, or not from Vector click
              if (typeof position.xy!=="undefined") {
                this.lastclick = position.xy;
              } else {
                this.lastclick = position;
              }
              bounds = new OpenLayers.Bounds(this.lastclick.x, this.lastclick.y, this.lastclick.x, this.lastclick.y);
            }
            if (clickableWMSLayerNames!=='') {
              // Do a WMS request
              var params={
                  REQUEST: "GetFeatureInfo",
                  EXCEPTIONS: "application/vnd.ogc.se_xml",
                  VERSION: "1.1.0",
                  STYLES: '',
                  BBOX: div.map.getExtent().toBBOX(),
                  X: Math.round(this.lastclick.x),
                  Y: Math.round(this.lastclick.y),
                  INFO_FORMAT: 'application/vnd.ogc.gml',
                  LAYERS: clickableWMSLayerNames,
                  QUERY_LAYERS: clickableWMSLayerNames,
                  WIDTH: div.map.size.w,
                  HEIGHT: div.map.size.h,
                  SRS: div.map.projection,
                  BUFFER: div.settings.clickPixelTolerance
              };
              if (div.settings.clickableLayers[0].params.CQL_FILTER!==undefined) {
                if (div.settings.clickableLayers.length>1) {
                  alert('Multiple layers are clickable with filters defined. This is not supported at present');
                  exit;
                }
                params.CQL_FILTER = div.settings.clickableLayers[0].params.CQL_FILTER;
              }
              // hack: Because WMS layers don't support the proxyHost setting in OL, but we need to, WMS layers will have
              // the proxy URL built into their URL. But OL will use proxyHost for a protocol request. Therefore temporarily 
              // disable proxyHost during this request.
              var oldPh = OpenLayers.ProxyHost;
              if (wmsUrl.substr(0, OpenLayers.ProxyHost.length)===OpenLayers.ProxyHost) {
                OpenLayers.ProxyHost = "";
              }
              try {
                this.protocol.read({
                  params: params,
                  callback: this.onResponse,
                  scope: this
                });
              } finally {
                OpenLayers.ProxyHost = oldPh;
              }
            }
            // now handle any vector clickable layers
            if (clickableVectorLayers.length>0) {
              // select all the features that were clicked or boxed.
              selectBox(bounds, clickableVectorLayers, div);
              // build an array of all selected features in one
              features = [];
              $.each(clickableVectorLayers, function(idx, layer) {
                features = features.concat(layer.selectedFeatures);
              });
              // now filter the report, or display output in a popup or div depending on settings.
              if (div.settings.clickableLayersOutputMode==='report' && div.settings.reportGroup!==null) {
                // grab the feature ids
                ids = [];
                $.each(features, function(idx, feature) {
                  ids.push(feature.attributes[div.settings.featureIdField]);
                });
                $('.'+div.settings.reportGroup+'-idlist-param').val(ids.join(','));
                // find the associated reports, charts etc and reload them to show the selected data
                $.each(indiciaData.reports[div.settings.reportGroup], function(name, report) {
                  report[0].settings.recordCount = features.length;
                  report[0].settings.offset=0;
                  report.reload();
                });
              } else if (div.settings.clickableLayersOutputMode==='div') {
                $('#'+div.settings.clickableLayersOutputDiv).html(div.settings.clickableLayersOutputFn(features, div));
              } else {
                for (var i=0; i<div.map.popups.length; i++) {
                  div.map.removePopup(div.map.popups[i]);
                }
                div.map.addPopup(new OpenLayers.Popup.FramedCloud(
                    "popup",
                    div.map.getLonLatFromPixel(this.lastclick),
                    null,
                    div.settings.clickableLayersOutputFn(features, div),
                    null,
                    true
                ));
              }
            }
          },
          // handler for response from a WMS call.
          // todo: support div or report filters.
          onResponse: function(response) {
            if (div.settings.clickableLayersOutputMode==='popup') {
              for (var i=0; i<div.map.popups.length; i++) {
                div.map.removePopup(div.map.popups[i]);
              }
              div.map.addPopup(new OpenLayers.Popup.FramedCloud(
                  "popup",
                  div.map.getLonLatFromPixel(this.lastclick),
                  null,
                  div.settings.clickableLayersOutputFn(response.features, div),
                  null,
                  true
              ));
            } else {
              $('#'+div.settings.clickableLayersOutputDiv).html(div.settings.clickableLayersOutputFn(response.features, div));
            }
          }
          
        });

        return infoCtrl;
      } else {
        return null;
      }
    }

    // Extend our default options with those provided, basing this on an empty object
    // so the defaults don't get changed.
    var opts = $.extend({}, $.fn.indiciaMapPanel.defaults, options);
    // call any hooks that update the settings
    $.each(mapSettingsHooks, function(i, fn) {
      fn(opts);
    });
    if (opts.useOlDefaults) {
      olOptions = $.extend({}, $.fn.indiciaMapPanel.openLayersDefaults, olOptions);
    }

    olOptions.projection = new OpenLayers.Projection("EPSG:"+olOptions.projection);
    olOptions.displayProjection = new OpenLayers.Projection("EPSG:"+olOptions.displayProjection);
    if ((typeof olOptions.maxExtent !== "undefined") && (olOptions.maxExtent instanceof Array)) {
      // if the maxExtent is passed as an array, it could be from JSON on a Drupal settings form. We need an Ol bounds object.
      olOptions.maxExtent = new OpenLayers.Bounds(olOptions.maxExtent[0], olOptions.maxExtent[1],
            olOptions.maxExtent[2], olOptions.maxExtent[3]);
    }
    // set the image path otherwise Drupal js optimisation can move the script relative to the images.
    if (!OpenLayers.ImgPath && opts.jsPath) {
      OpenLayers.ImgPath=opts.jsPath + 'img/';
    }
    return this.each(function() {
      this.settings = opts;
      // wrap the map in a div container
      $(this).wrap('<div id="map-container" style="width:'+opts.width+'" >');
      
      if (this.settings.toolbarDiv!='map' && (opts.toolbarPrefix!=='' || opts.toolbarSuffix!=='')) {
        var toolbar='<div id="map-toolbar-outer" class="ui-helper-clearfix">' + opts.toolbarPrefix + '<div class="olControlEditingToolbar" id="map-toolbar"></div>' + opts.toolbarSuffix + '</div>';
        if (this.settings.toolbarDiv=='top') {
          $(this).before(toolbar);
        } else if (this.settings.toolbarDiv=='bottom') {
          $(this).after(toolbar);
        } else {
          $('#' + this.settings.toolbarDiv).html(toolbar);
        }
        this.settings.toolbarDiv='map-toolbar';
      }
      
      this.settings.boundaryStyle=new style();

      // Sizes the div. Width sized by outer div.
      $(this).css('height', this.settings.height);
      $(this).css('width', '100%');

      // If we're using a proxy
      if (this.settings.proxy)
      {
        OpenLayers.ProxyHost = this.settings.proxy;
      }

      // Keep a reference to this, to simplify scoping issues.
      var div = this;

      // Create a projection to represent data in the Indicia db
      div.indiciaProjection = new OpenLayers.Projection('EPSG:900913');
      olOptions.controls = [
            new OpenLayers.Control.Navigation(),
            new OpenLayers.Control.ArgParser(),
            new OpenLayers.Control.Attribution()
      ];

      // Constructs the map
      div.map = new OpenLayers.Map($(this)[0], olOptions);
      
      // setup the map to save the last position
      if (typeof $.cookie !== "undefined") {
        div.map.events.register('moveend', null, function() {
          $.cookie('mapzoom', div.map.zoom, {expires: 7});
          $.cookie('maplon', div.map.center.lon, {expires: 7});
          $.cookie('maplat', div.map.center.lat, {expires: 7});
        });
      }
      
      // and prepare a georeferencer
      div.georefOpts = $.extend({}, $.fn.indiciaMapPanel.georeferenceDriverSettings, $.fn.indiciaMapPanel.georeferenceLookupSettings);
      if (typeof Georeferencer !== "undefined") {
        div.georeferencer = new Georeferencer(div, _displayGeorefOutput);
      }

      // Add any tile cache layers
      var tcLayer;
      $.each(this.settings.tilecacheLayers, function(i, item) {
        tcLayer = new OpenLayers.Layer.TileCache(item.caption, item.servers, item.layerName, item.settings);
        div.map.addLayer(tcLayer);
      });

      // Iterate over the preset layers, adding them to the map
      var presetLayers=_getPresetLayers(this.settings);
      $.each(this.settings.presetLayers, function(i, item)
      {
        // Check whether this is a defined layer
        if (presetLayers.hasOwnProperty(item))
        {
          var layer = presetLayers[item]();
          div.map.addLayer(layer);
          if (item=='multimap_landranger') {
            // Landranger is not just a simple layer - need to set a Multimap option
            _enableMMLandranger();
          }
        } else {
          alert('Requested preset layer ' + item + ' is not recognised.');
        }
      });

      // Convert indicia WMS/WFS layers into js objects
      $.each(this.settings.indiciaWMSLayers, function(key, value)
      {
        div.settings.layers.push(new OpenLayers.Layer.WMS(key, div.settings.indiciaGeoSvc + 'wms', { layers: value, transparent: true }, { singleTile: true, isBaseLayer: false, sphericalMercator: true}));
      });
      $.each(this.settings.indiciaWFSLayers, function(key, value)
      {
        div.settings.layers.push(new OpenLayers.Layer.WFS(key, div.settings.indiciaGeoSvc + 'wms', { typename: value, request: 'GetFeature' }, { sphericalMercator: true }));
      });

      div.map.addLayers(this.settings.layers);

      // Centre the map, using cookie if remembering position, otherwise default setting.
      var zoom=null;
      var center = {};
      if (typeof $.cookie !== "undefined" && div.settings.rememberPos!==false) {
        zoom=$.cookie('mapzoom');
        center.lon = $.cookie('maplon');
        center.lat = $.cookie('maplat');
        if (center.lon!==null && center.lat!==null) {
          center = new OpenLayers.LonLat(center.lon, center.lat);
        } else {
          center = {};
        }
      }
      if (zoom===null) {
        zoom = this.settings.initial_zoom;
      }
      if (typeof center.lat === "undefined") {
        center = new OpenLayers.LonLat(this.settings.initial_long, this.settings.initial_lat);
        if (div.map.displayProjection.getCode()!=div.map.projection.getCode()) {
          center.transform(div.map.displayProjection, div.map.projection);
        }
      }
      div.map.setCenter(center, zoom);

      // This hack fixes an IE8 bug where it won't display Google layers when switching using the Layer Switcher.
      div.map.events.register('changebaselayer', null, function(e) {
        // trigger layer redraw by changing the map size
        div.style.height = (parseInt(div.style.height)-1) + 'px';
        // keep a local reference to the map div, so we can access it from the timeout
        tmp=div;
        // after half a second, reset the map size
        setTimeout("tmp.style.height = (parseInt(tmp.style.height) + 1) + 'px'", 500);
      });

      if (this.settings.editLayer) {
        // Add an editable layer to the map
        var editLayer = new OpenLayers.Layer.Vector(
            this.settings.editLayerName,
            {style: this.settings.boundaryStyle, 'sphericalMercator': true, displayInLayerSwitcher: this.settings.editLayerInSwitcher}
        );
        div.map.editLayer = editLayer;
        div.map.addLayer(div.map.editLayer);

        if (this.settings.initialFeatureWkt === null ) {
          // if no initial feature specified, but there is a populated imp-geom hidden input,
          // use the value from the hidden geom
          this.settings.initialFeatureWkt = $('#imp-geom').val();
        }

        // Draw the feature to be loaded on startup, if present
        if (this.settings.initialFeatureWkt)
        {
          _showWktFeature(this, this.settings.initialFeatureWkt, div.map.editLayer, null);
        }
      }
      if (this.settings.searchLayer) {
          // Add an editable layer to the map
          var searchLayer = new OpenLayers.Layer.Vector(this.settings.searchLayerName, {style: this.settings.searchStyle, 'sphericalMercator': true, displayInLayerSwitcher: this.settings.searchLayerInSwitcher});
          div.map.searchLayer = searchLayer;
          div.map.addLayer(div.map.searchLayer);
      } else {
        div.map.searchLayer = div.map.editLayer;
      }
      // Add any map controls
      $.each(this.settings.controls, function(i, item) {
        div.map.addControl(item);
      });      
      // specify a class to align edit buttons left if they are on a toolbar somewhere other than the map.
      var align = (div.settings.toolbarDiv=='map') ? '' : 'left ';
      var toolbarControls = [];
      var clickInfoCtrl = getClickableLayersControl(div, align);

      if (div.settings.locationLayerName) {
        var layer = new OpenLayers.Layer.WMS('Locations', div.settings.indiciaGeoSvc + 'wms', {
            layers: div.settings.locationLayerName,
            transparent: true
          }, {
            singleTile: true,
            isBaseLayer: false,
            sphericalMercator: true,
            opacity: div.settings.fillOpacity/2
        });
        div.settings.layers.push(layer);
        div.map.addLayers([layer]);

        var infoCtrl = new OpenLayers.Control({
          activate: function() {
            var handlerOptions = {
              'single': true,
              'double': false,
              'pixelTolerance': 0,
              'stopSingle': false,
              'stopDouble': false
            };
            this.handler = new OpenLayers.Handler.Click(this, {
              'click': this.onClick
            }, handlerOptions);
            this.protocol = new OpenLayers.Protocol.HTTP({
              url: div.settings.indiciaGeoSvc + 'wms',
              format: new OpenLayers.Format.WMSGetFeatureInfo()
            });
            OpenLayers.Control.prototype.activate.call(this);
          },

          onClick: function(e) {
            var params={
                REQUEST: "GetFeatureInfo",
                EXCEPTIONS: "application/vnd.ogc.se_xml",
                VERSION: "1.1.0",
                STYLES: '',
                BBOX: div.map.getExtent().toBBOX(),
                X: e.xy.x,
                Y: e.xy.y,
                INFO_FORMAT: 'application/vnd.ogc.gml',
                LAYERS: div.settings.locationLayerName,
                QUERY_LAYERS: div.settings.locationLayerName,
                WIDTH: div.map.size.w,
                HEIGHT: div.map.size.h,
                SRS: div.map.projection
            };
            this.protocol.read({
              params: params,
              callback: this.onResponse,
              scope: this
            });
          },

          onResponse: function(response) {
            if (response.features.length>0) {
              $('#imp-location').val(response.features[0].data.id);
              $('#imp-location\\:name').val(response.features[0].data.name);
            }
          }
        });

        div.map.addControl(infoCtrl);
        infoCtrl.activate();
      }
      
      if (div.settings.editLayer && div.settings.clickForSpatialRef) {
        // Setup a click event handler for the map
        OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
          defaultHandlerOptions: { 'single': true, 'double': false, 'pixelTolerance': 0, 'stopSingle': false, 'stopDouble': false },
          initialize: function(options)
          {
            this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
            OpenLayers.Control.prototype.initialize.apply(this, arguments);
            this.handler = new OpenLayers.Handler.Click( this, {'click': this.trigger}, this.handlerOptions );
          },

          trigger: function(e)
          {
            var lonlat = div.map.getLonLatFromPixel (e.xy);
            // get approx metres accuracy we can expect from the mouse click - about 5mm accuracy.
            var precision, metres = div.map.getScale()/200;
            // now round to find appropriate square size
            if (metres<30) {
              precision=8;
            } else if (metres<300) {
              precision=6;
            } else if (metres<3000) {
              precision=4;
            } else {
              precision=2;
            }
            // enforce precision limits if specified in the settings
            if (div.settings.clickedSrefPrecisionMin!=='') {
              precision=Math.max(div.settings.clickedSrefPrecisionMin, precision);
            }
            if (div.settings.clickedSrefPrecisionMax!=='') {
              precision=Math.min(div.settings.clickedSrefPrecisionMax, precision);
            }
            var sref, wkt, outputSystem = _getSystem();
            if ('EPSG:' + outputSystem == div.map.projection.getCode()) {
              // no transform required
              if (div.map.getUnits()=='m') {
              // in metres, so we can round (no need for sub-metre precision)
                sref = Math.round(lonlat.lon) + ', ' + Math.round(lonlat.lat);
              } else {
                sref = lonlat.lat + ', ' + lonlat.lon;
              }
              if (outputSystem != '900913') {
                lonlat.transform(div.map.projection, div.indiciaProjection);
              }
              wkt = "POINT(" + lonlat.lon + "  " + lonlat.lat + ")";
              _setClickPoint({
                'sref' : sref,
                'wkt' : wkt
              }, div);
            } else {
              if (div.map.projection.getCode() != div.indiciaProjection.getCode()) {
                // Indicia expects the WKT in 900913 (it's internal format)
                lonlat.transform(div.map.projection, div.indiciaProjection);
              }
              wkt = "POINT(" + lonlat.lon + "  " + lonlat.lat + ")";
              $.getJSON(opts.indiciaSvc + "index.php/services/spatial/wkt_to_sref"+
                      "?wkt=" + wkt +
                      "&system=" + outputSystem +
                      "&precision=" + precision +
                      "&metresAccuracy=" + metres +
                      "&output=" + div.settings.latLongFormat +
                      "&callback=?", function(data)
                {
                  if(typeof data.error != 'undefined')
                    if(data.error == 'wkt_to_sref translation is outside range of grid.')
                      alert(div.settings.msgSrefOutsideGrid);
                    else
                      alert(data.error);
                  else
                    _setClickPoint(data, div);
                }
              );
            }
          }
        });
      }
      $.each(div.settings.standardControls, function(i, ctrl) {
        // Add a layer switcher if there are multiple layers
        if (ctrl=='layerSwitcher') {
          div.map.addControl(new OpenLayers.Control.LayerSwitcher());
        } else if (ctrl=='zoomBox') {
          div.map.addControl(new OpenLayers.Control.ZoomBox());
        } else if (ctrl=='panZoom') {
          div.map.addControl(new OpenLayers.Control.PanZoom());
        } else if (ctrl=='panZoomBar') {
          div.map.addControl(new OpenLayers.Control.PanZoomBar());
        } else if (ctrl=='drawPolygon' && div.settings.editLayer) {
          toolbarControls.push(new OpenLayers.Control.DrawFeature(div.map.editLayer,
              OpenLayers.Handler.Polygon,
              {'displayClass': align + 'olControlDrawFeaturePolygon', 'title':'Draw polygons by clicking on the then double click to finish'}));
        } else if (ctrl=='drawLine' && div.settings.editLayer) {
          toolbarControls.push(new OpenLayers.Control.DrawFeature(div.map.editLayer,
              OpenLayers.Handler.Path,
              {'displayClass': align + 'olControlDrawFeaturePath', 'title':'Draw lines by clicking on the then double click to finish'}));
        } else if (ctrl=='drawPoint' && div.settings.editLayer) {
          toolbarControls.push(new OpenLayers.Control.DrawFeature(div.map.editLayer,
              OpenLayers.Handler.Point,
              {'displayClass': align + 'olControlDrawFeaturePoint', 'title':'Draw points by clicking on the map'}));
        } else if (ctrl=='selectFeature' && div.settings.editLayer) {
          toolbarControls.push(new OpenLayers.Control.SelectFeature(div.map.editLayer));
        } else if (ctrl=='hoverFeatureHighlight' && div.settings.editLayer) {
          var highlighter = new OpenLayers.Control.SelectFeature(div.map.editLayer, {hover: true, highlightOnly: true});
          div.map.addControl(highlighter);
          highlighter.activate();
        } else if (ctrl=='clearEditLayer' && div.settings.editLayer) {
          toolbarControls.push(new OpenLayers.Control.ClearLayer([div.map.editLayer],
              {'displayClass': align + ' olControlClearLayer', 'title':'Clear selection'}));
        }  else if (ctrl=='modifyFeature' && div.settings.editLayer) {
          toolbarControls.push(new OpenLayers.Control.ModifyFeature(div.map.editLayer,
              {'displayClass': align + 'olControlModifyFeature', 'title':'Modify a feature'}));
        } else if (ctrl=='graticule') {
          var graticule = new OpenLayers.Control.IndiciaGraticule({projection: div.settings.graticuleProjection, bounds: div.settings.graticuleBounds});
          div.map.addControl(graticule);
          graticule.activate();
        }
      });
      if (div.settings.editLayer && div.settings.clickForSpatialRef) {
        var click = new OpenLayers.Control.Click({'displayClass':align + 'olControlClickSref'});
        div.map.editLayer.clickControl = click;
      }
      if (clickInfoCtrl !== null) {
        // When using a click for info control, if it allows boxes then it needs to go on the toolbar so it can be disabled.
        // This is because the bounding boxes break the navigation (you can't pan the map).
        if (clickInfoCtrl.allowBox || toolbarControls.length>0) {
          toolbarControls.push(clickInfoCtrl);
        }
      }
      if (toolbarControls.length>0) {
        // Add the click control to the toolbar alongside the other controls.
        if (typeof click!=="undefined") {
          toolbarControls.push(click);
        }
        var toolbarOpts = {
           displayClass: 'olControlEditingToolbar'
        };
        if (div.settings.toolbarDiv!='map') {
          toolbarOpts.div = document.getElementById(div.settings.toolbarDiv);
        }
        var toolbar = new OpenLayers.Control.Panel(toolbarOpts);
        // add a nav control to the toolbar
        var nav=new OpenLayers.Control.Navigation({displayClass: align + "olControlNavigation"});
        toolbar.addControls([nav]);
        toolbar.addControls(toolbarControls);
        div.map.addControl(toolbar);
        nav.activate();
        // as these all appear on the toolbar, don't need to worry about activating individual controls, as user will pick which one they want.
      } else {
        // no other selectable controls, so no need for a click button on toolbar
        if (typeof click!=="undefined") {
          div.map.addControl(click);
          click.activate();
        }
        if (clickInfoCtrl !== null) {
          div.map.addControl(clickInfoCtrl);
          clickInfoCtrl.activate();
        }
      }

      // Disable the scroll wheel from zooming if required
      if (!this.settings.scroll_wheel_zoom) {
        $.each(div.map.controls, function(i, control) {
          if (control instanceof OpenLayers.Control.Navigation) {
            control.disableZoomWheel();
          }
        });
      }
      _bindControls(this);
      // call any post initialisation hooks
      $.each(mapInitialisationHooks, function(i, fn) {
        fn(div);
      });
    });

  };

})(jQuery);

/**
 * Main default options for the map
 */
$.fn.indiciaMapPanel.defaults = {
    indiciaSvc : '',
    indiciaGeoSvc : '',
    readAuth : '',
    height: "600",
    width: "470",
    initial_lat: 55.1,
    initial_long: -2,
    initial_zoom: 5,
    scroll_wheel_zoom: true,
    bing_api_key: '',
    proxy: '',
    presetLayers: [],
    tilecacheLayers: [],
    indiciaWMSLayers: {},
    indiciaWFSLayers : {},
    layers: [],
    clickableLayers: [],
    clickableLayersOutputMode: 'popup', // options are popup or div
    clickableLayersOutputFn: format_selected_features,
    clickableLayersOutputDiv: '',
    clickableLayersOutputColumns: [],
    featureIdField: '',
    clickPixelTolerance: 5,
    reportGroup: null, // name of the collection of report outputs that this map is linked to when doing dashboard reporting
    locationLayerName: '', // define a feature type that can be used to auto-populate the location control when clicking on a location
    controls: [],
    standardControls: ['layerSwitcher','panZoom'],
    toolbarDiv: 'map', // map or div ID
    toolbarPrefix: '', // content to prepend to the toolbarDiv content if not on the map
    toolbarSuffix: '', // content to append to the toolbarDiv content if not on the map
    editLayer: true,
    clickForSpatialRef: true, // if true, then enables the click to get spatial references control
    editLayerName: 'Selection layer',
    editLayerInSwitcher: false,
    searchLayer: false, // determines whether we have a separate layer for the display of location searches, eg georeferencing. Defaults to editLayer.
    searchUpdatesSref: false,
    searchLayerName: 'Search layer',
    searchLayerInSwitcher: false,
    initialFeatureWkt: null,
    defaultSystem: 'OSGB',
    latLongFormat: 'D',
    srefId: 'imp-sref',
    srefLatId: 'imp-sref-lat',
    srefLongId: 'imp-sref-long',
    srefSystemId: 'imp-sref-system',
    geomId: 'imp-geom',
    clickedSrefPrecisionMin: '', // depends on sref system, but for OSGB this would be 2,4,6,8,10 etc = length of grid reference
    clickedSrefPrecisionMax: '',
    msgGeorefSelectPlace: 'Select from the following places that were found matching your search, then click on the map to specify the exact location:',
    msgGeorefNothingFound: 'No locations found with that name. Try a nearby town name.',
    msgGetInfoNothingFound: 'No occurrences were found at the location you clicked.',
    msgSrefOutsideGrid: 'The position is outside the range of the selected grid reference system.',
    msgSrefNotRecognised: 'The grid reference is not recognised.',
    maxZoom: 19, //maximum zoom when relocating to gridref, postcode etc.
    maxZoomBuffer: 0.67, //margin around feature when relocating to gridref

    //options for OpenLayers. Feature. Vector. style
    fillColor: '#ee9900',
    fillOpacity: 0.4,
    hoverFillColor: 'white',
    hoverFillOpacity: 0.8,
    strokeColor: '#ee9900',
    strokeOpacity: 1,
    strokeWidth: 1,
    strokeLinecap: 'round',
    strokeDashstyle: 'solid',
    hoverStrokeColor: 'red',
    hoverStrokeOpacity: 1,
    hoverStrokeWidth: 0.2,
    pointRadius: 6,
    hoverPointRadius: 1,
    hoverPointUnit: '%',
    pointerEvents: 'visiblePainted',
    cursor: '',
    graticuleProjection: 'EPSG:27700',
    graticuleBounds: [0,0,700000,1300000],

    /* Intention is to also implement hoveredSrefPrecisionMin and Max for a square size shown when you hover, and also a
     * displayedSrefPrecisionMin and Mx for a square size output into a list box as you hover. Both of these could either be
     * absolute numbers, or a number preceded by - or + to be relative to the default square size for this zoom level. */
    // Additional options for OpenLayers.Feature.Vector.style on the search layer.
    fillColorSearch: '#ee0000',
    fillOpacitySearch: 0.5,
    strokeColorSearch: '#ee0000',

    // Are we using the OpenLayers defaults, or are they all provided?
    useOlDefaults: true,
    rememberPos: false // set to true to enable restoring the map position when the page is reloaded. Requires jquery.cookie plugin.

};

/**
 * Settings for the georeference lookup.
 */
$.fn.indiciaMapPanel.georeferenceLookupSettings = {
  georefSearchId: 'imp-georef-search',
  georefSearchBtnId: 'imp-georef-search-btn',
  georefCloseBtnId: 'imp-georef-close-btn',
  georefOutputDivId: 'imp-georef-output-div',
  georefDivId: 'imp-georef-div'
};

/**
 * Default options to pass to the openlayers map constructor
 */
$.fn.indiciaMapPanel.openLayersDefaults = {
    projection: 900913,
    displayProjection: 4326,
    units: "m",
    numZoomLevels: 18,
    maxResolution: 156543.0339,
    maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34)
};

/**
 * A utility function to convert an OpenLayers filter into text, which can be supplied to a WMS filter call to GeoServer.
 */
$.fn.indiciaMapPanel.convertFilterToText = function(filter) {
  // First, get the filter as XML DOM
  var dom = new OpenLayers.Format.Filter.v1_0_0().write(filter);
  // Now, convert the XML to text
  var serializer, serialized;
  try {
    // XMLSerializer exists in current Mozilla browsers
    serializer = new XMLSerializer();
    serialized = serializer.serializeToString(dom);
  }
  catch (e) {
    // Internet Explorer has a different approach to serializing XML
    serialized = dom.xml;
  }
  return serialized;
};

/**
 * Function that formats the response from a SelectFeature action.
 * Can be replaced through the setting clickableLayersOutputFn.
 */
function format_selected_features(features, div) {
  if (features.length===0) {
    return div.settings.msgGetInfoNothingFound;
  } else {
    var html='<table><thead><tr>', keepVagueDates = typeof features[0].attributes.date === "undefined";
    // use normal for (in) to get object properties
    for(var attr in features[0].attributes) {
      // skip vague date component columns if we have a standard date
      if (keepVagueDates || attr.substr(0, 5)!=='date_') {
        if (div.settings.clickableLayersOutputColumns.length===0) {
          html += '<th>' + attr + '</th>';
        } else if (div.settings.clickableLayersOutputColumns[attr]!=undefined) {
          html += '<th>' + div.settings.clickableLayersOutputColumns[attr] + '</th>';
        }
      }
    };
    html += '</tr></thead><tbody>';
    $.each(features, function(i, item) {
      html += '<tr>';
      for(var attr in item.attributes) {
        if ((keepVagueDates || attr.substr(0, 5)!=='date_') && (div.settings.clickableLayersOutputColumns.length===0 || div.settings.clickableLayersOutputColumns[attr]!=undefined)) {
          html += '<td>' + item.attributes[attr] + '</td>';
        }
      };
      html += '</tr>';
    });
    html += '</tbody></table>';
    return html;
  }
  
};