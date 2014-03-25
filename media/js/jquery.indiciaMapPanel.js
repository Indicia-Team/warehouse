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
 * Add functions to this array for them to be called a location is georeferenced.
 */
mapGeoreferenceHooks = [];

/**
 * Add functions to this array for them to be called when a location is picked in an input control.
 */
mapLocationSelectedHooks = [];

/**
* Class: indiciaMapPanel
* JavaScript & OpenLayers based map implementation class for Indicia data entry forms.
* This code file supports read only maps. A separate plugin will then run on top of this to provide editing support
* and can be used in a chainable way. Likewise, another plugin will provide support for finding places.
*/

(function($) {
  $.fn.indiciaMapPanel = function(options, olOptions) {

    // The ghost grid square drawn when hovering
    var ghost=null;
    
    var plusKeyDown=false, minusKeyDown=false, overMap=false, currentMousePixel=null;
    
    /**
     * Adds the distribution point indicated by a record object to a list of features.
     */
    function addPt(features, record, wktCol, opts, id) {
      if (record[wktCol]!==null) {
        // if an int supplied instead of a geom, this must be an index into the indiciaData.geoms array.
        if (!isNaN(record[wktCol])) {
          record[wktCol] = indiciaData.geoms[record[wktCol]];
        }
        var feature, geom=OpenLayers.Geometry.fromWKT(record[wktCol]);
        if (this.map.projection.getCode() != this.indiciaProjection.getCode()) {
          geom.transform(this.indiciaProjection, this.map.projection);
        }
        delete record[wktCol];
        if (typeof opts.type!=="undefined" && opts.type!=='vector') {
          // render a point for symbols
          geom = geom.getCentroid();
        }
        feature = new OpenLayers.Feature.Vector(geom, record);
        if (typeof id!=='undefined') {
          // store a supplied identifier against the feature
          feature.id=id;
        }
        features.push(feature);
        return feature;
      }
      return feature;
    }

    /**
     * Remove all features of a specific type or not of a specific type
     * This functionality allows a location to havea centroid and separate boundary.
     * Note that inverse mode does not interfere with annotations mode as this is a seperate mode added after code was originally created.
     */
    function removeAllFeatures(layer, type, inverse) {
      var toRemove = [];
      if (typeof inverse==="undefined") {
        inverse=false;
      }
      $.each(layer.features, function(idx, feature) {
        //Annotations is a special seperate mode added after original code was written, so do not interfere with annotations even in inverse mode.
        if ((!inverse && feature.attributes.type===type) || (inverse && feature.attributes.type!==type && feature.attributes.type!=='annotation')) {
          toRemove.push(feature);
        }
      });
      layer.removeFeatures(toRemove, {});
    }
    
    /**
     * A public method that can be fired when a location is selected in an input control, to load the location's
     * boundary onto the map. Automatic for #imp-location, but can be attached to other controls as well.
     */
    function locationSelectedInInput(div, val) {
      if (div.map.editLayer) {
        div.map.editLayer.destroyFeatures();
      }
      var intValue = parseInt(val);
      if (!isNaN(intValue)) {
        // Change the location control requests the location's geometry to place on the map.
        $.getJSON(div.settings.indiciaSvc + "index.php/services/data/location/"+val +
          "?mode=json&view=detail" + div.settings.readAuth + "&callback=?", function(data) {
            // store value in saved field?
            if (data.length>0) {
              // TODO not sure best way of doing this using the services, we don't really want
              // to use the proj4 client transform until its issues are sorted out, but have little choice here as
              // the wkt for a boundary could be too big to send to the services on the URL
              var geomwkt = data[0].boundary_geom || data[0].centroid_geom;
              if(_diffProj(div.indiciaProjection, div.map.projection)){
                // NB geometry may not be a point (especially if a boundary!)
                var parser = new OpenLayers.Format.WKT();
                var feature = parser.read(geomwkt);
                geomwkt = feature.geometry.transform(div.indiciaProjection, div.map.projection).toString();
              }
              _showWktFeature(div, geomwkt, div.map.editLayer, null, true, 'boundary');

              if (typeof indiciaData.searchUpdatesSref !== "undefined" && indiciaData.searchUpdatesSref) {
                // The location search box must fill in the sample sref box
                $('#'+div.settings.srefId).val(data[0].centroid_sref);
                $('#'+div.settings.srefSystemId).val(data[0].centroid_sref_system);
                $('#'+div.settings.geomId).val(data[0].centroid_geom);
                // If the sref is in two parts, then we might need to split it across 2 input fields for lat and long
                if (data[0].centroid_sref.indexOf(' ')!==-1) {
                  var parts=$.trim(data[0].centroid_sref).split(' ');
                  // part 1 may have a comma at the end, so remove
                  var part1 = parts.shift().split(',')[0];
                  $('#'+div.settings.srefLatId).val(part1);
                  $('#'+div.settings.srefLongId).val(parts.join(''));
                }
              }
              $.each(mapLocationSelectedHooks, function(idx, hook) {
                hook(div, data);
              });
            }
          }
        );
      }
    }

    /**
     * Variant of getFeatureById which allows for the features being checked being a comma
     * separated list of values, against any field.
     */
    function getFeaturesByVal(layer, value, field) {
      var features = [], ids, val;
      for(var i=0, len=layer.features.length; i<len; ++i) {
        if (typeof field!=="undefined" && typeof layer.features[i]['attributes'][field+'s']!=="undefined") {
          ids=layer.features[i]['attributes'][field+'s'].split(',');
          if ($.inArray(value, ids)>-1) {
            features.push(layer.features[i]);
          }
        } else {
          featureVal=(typeof field==="undefined" ? layer.features[i]['id'] : layer.features[i]['attributes'][field]);
          if (featureVal == value) {
            features.push(layer.features[i]);
          }
        }
      }
      return features;
    }

    /**
     * Convert any projection representation to a system string.
     */
    function _projToSystem(proj, convertGoogle) {
    	var system;
    	if(typeof proj != "string") { // assume a OpenLayers Projection Object
    		system = proj.getCode();
    	} else {
    		system = proj;
    	}
    	if(system.substring(0,5)=='EPSG:'){
    		system = system.substring(5);
    	}
    	if(convertGoogle && system=="900913"){
    		system="3857";
    	}
    	return system;
    }

    /**
     * Compare 2 projection representations.
     */
    function _diffProj(proj1, proj2) {
    	return (_projToSystem(proj1, true) != _projToSystem(proj2, true));
    }

    /**
     * Adds a buffer around a boundary so you can zoom to the boundary without zooming too tight.
     */
    function _extendBounds(bounds, buffer) {
      var dy = Math.max(50, (bounds.top-bounds.bottom) * buffer);
      var dx = Math.max(50, (bounds.right-bounds.left) * buffer);
      bounds.top = bounds.top + dy;
      bounds.bottom = bounds.bottom - dy;
      bounds.right = bounds.right + dx;
      bounds.left = bounds.left - dx;
      return bounds;
    }

    /**
     * Add a well known text definition of a feature to the map.
     * WKT is assumed to be in map projection, unless transform is set to true
     * in which case it is transformed from the indicia projection to map projection.
     * @access private
     */
    function _showWktFeature(div, wkt, layer, invisible, temporary, type, panzoom, transform) {
      var parser = new OpenLayers.Format.WKT(), bounds = new OpenLayers.Bounds(), geometry;
      var features = [];
      // This replaces other features of the same type
      removeAllFeatures(layer, type);
      if(wkt){
        var feature = parser.read(wkt);
        // this could be an array of features for a GEOMETRYCOLLECTION
        if ($.isArray(feature)===false) {
          feature = [feature];
        }
        var styletype = (typeof type !== 'undefined') ? styletype = type : styletype = 'default';
        $.each(feature, function(idx, feat){
          if (typeof transform!=="undefined" && transform && div.map.projection.getCode() != div.indiciaProjection.getCode()) {
            feat.geometry.transform(div.indiciaProjection, div.map.projection);
          }
          feat.style = new style(styletype);
          feat.attributes.type = type;
          if (temporary) {
            feat.attributes.temp = true;
          }
          features.push(feat);
          // get max extent of just the features we are adding.
          geometry = feat.geometry;
          if (geometry) {
            bounds.extend(geometry.getBounds());
          }
        });        
      }

      if(invisible !== null){
        //there are invisible features that define the map extent
        $.each(invisible, function(i,corner){
          feature = parser.read(corner);
          feature.style = new style('invisible');
          //give the invisible features a type so that they are replaced too
          feature.attributes.type = type;
          if (temporary) {
            feature.attributes.temp=true;
          }
          features.push(feature);
          bounds.extend(feature.geometry);
        });
      }
      if(features.length == 0) return false;
      layer.addFeatures(features);

      if(invisible === null) {
        // extend the boundary to include a buffer, so the map does not zoom too tight.
        bounds = _extendBounds(bounds, div.settings.maxZoomBuffer);
      }
      if (typeof panzoom==="undefined" || panzoom) {
        if (div.map.getZoomForExtent(bounds) > div.settings.maxZoom) {
          // if showing something small, don't zoom in too far
          div.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
        }
        else {
          // Set the default view to show something a bit larger than the size of the grid square
          div.map.zoomToExtent(bounds);
        }
      }
      if(feature.length == 1) return feature[0];
      return feature;
    }

    /*
     * An OpenLayers vector style object
     */
    function style(styletype) {
      styletype = (typeof styletype !== 'undefined') ? styletype : 'default';

      this.fillColor = opts.fillColor;
      this.fillOpacity = opts.fillOpacity;
      this.hoverFillColor = opts.hoverFillColor;
      this.hoverFillOpacity = opts.hoverFillOpacity;
      this.hoverStrokeColor = opts.hoverStrokeColor;
      this.hoverStrokeOpacity = opts.hoverStrokeOpacity;
      this.hoverStrokeWidth = opts.hoverStrokeWidth;
      this.strokeColor = opts.strokeColor;
      this.strokeOpacity = opts.strokeOpacity;
      this.strokeWidth = opts.strokeWidth;
      this.strokeLinecap = opts.strokeLinecap;
      this.strokeDashstyle = opts.strokeDashstyle;

      this.pointRadius = opts.pointRadius;
      this.hoverPointRadius = opts.hoverPointRadius;
      this.hoverPointUnit = opts.hoverPointUnit;
      this.pointerEvents = opts.pointerEvents;
      this.cursor = opts.cursor;

      switch(styletype) {
        case "georef":
          this.fillColor = opts.fillColorSearch;
          this.fillOpacity = opts.fillOpacitySearch;
          this.strokeColor = opts.strokeColorSearch;
          break;
        case "ghost":
          this.fillColor = opts.fillColorGhost;
          this.fillOpacity= opts.fillOpacityGhost;
          this.strokeColor = opts.strokeColorGhost;
          this.strokeOpacity = opts.strokeOpacityGhost;
          this.strokeDashstyle = opts.strokeDashstyleGhost;
          break;
        case "boundary":
          this.fillColor = opts.fillColorBoundary;
          this.fillOpacity = opts.fillOpacityBoundary;
          this.strokeColor = opts.strokeColorBoundary;
          this.strokeWidth = opts.strokeWidthBoundary;
          this.strokeDashstyle = opts.strokeDashstyleBoundary;
          break;
        case "invisible":
          this.pointRadius = 0;
          break;
      }
    }

    /**
     * Use jQuery selectors to locate any other related controls on the page which need to have events
     * bound to them to associate them with the map.
     */
    function _bindControls(div) {

      // If clickForPlot then do not bind to spatial ref input as currently it will 
      // do the wrong thing.
      if (opts.clickForPlot) {
        // Disable the spatial ref input so users do not think they can enter a value
        var version = $().jquery;
        var aryVersion = version.split('.');
        if (aryVersion[0] == 1 && aryVersion[1] < 6 ) {
          $('#'+opts.srefId).attr('readonly', true);
        } else {
          $('#'+opts.srefId).prop('readonly', true);
        }        
      } else if (opts.clickForSpatialRef) {
        // If the spatial ref input control exists, bind it to the map, so entering a ref updates the map
        $('#'+opts.srefId).change(function() {
          _handleEnteredSref($(this).val(), div);
        });
        // If the spatial ref latitude or longitude input control exists, bind it to the map, so entering a ref updates the map
        $('#'+opts.srefLatId).change(function() {
          // Only do something if both the lat and long are populated
          if ($.trim($(this).val())!='' && $.trim($('#'+opts.srefLongId).val())!='') {
            // copy the complete sref into the sref field
            $('#'+opts.srefId).val($.trim($(this).val()) + ', ' + $.trim($('#'+opts.srefLongId).val()));
            _handleEnteredSref($('#'+opts.srefId).val(), div);
          }
        });
        $('#'+opts.srefLongId).change(function() {
          // Only do something if both the lat and long are populated
          if ($.trim($('#'+opts.srefLatId).val())!='' && $.trim($(this).val())!='') {
            // copy the complete sref into the sref field
            $('#'+opts.srefId).val($.trim($('#'+opts.srefLatId).val()) + ', ' + $.trim($(this).val()));
            _handleEnteredSref($('#'+opts.srefId).val(), div);
          }
        });
      }

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
      if ($('#imp-location').length) {
        var locChange = function() {locationSelectedInInput(div, $('#imp-location').val());};
        $('#imp-location').change(locChange);
        // trigger change event, incase imp-location was already populated when the map loaded
        locChange();
      }
    }
    
    
     
    function _getPrecisionHelp(div, value) {
      var helptext = [],info;
      if (div.settings.helpToPickPrecisionMin && typeof indiciaData.srefHandlers!=="undefined" &&
          typeof indiciaData.srefHandlers[_getSystem().toLowerCase()]!=="undefined" &&
          $.inArray('precisions', indiciaData.srefHandlers[_getSystem().toLowerCase()].returns) !== -1) {
        info = indiciaData.srefHandlers[_getSystem().toLowerCase()].sreflenToPrecision(value.length);
        if (info.metres > div.settings.helpToPickPrecisionMin) {
          helptext.push(div.settings.hlpImproveResolution1.replace('{size}', info.display));
        } else if (info.metres > div.settings.helpToPickPrecisionMax) {
          helptext.push(div.settings.hlpImproveResolution2.replace('{size}', info.display));
        } else {
          helptext.push(div.settings.hlpImproveResolution3.replace('{size}', info.display));
        }
        // switch layer?
        if (div.settings.helpToPickPrecisionSwitchAt && info.metres<=div.settings.helpToPickPrecisionSwitchAt) {
          $.each(div.map.layers, function(idx, layer) {
            if (layer.isBaseLayer && layer.name.indexOf('Satellite')!==-1 && div.map.baseLayer!==layer) {
              div.map.setBaseLayer(layer);
              helptext.push(div.settings.hlpImproveResolutionSwitch);
            }
          });
        }
        var features=getFeaturesByVal(div.map.editLayer, 'clickPoint', 'type'),
            bounds = features[0].geometry.getBounds();
        bounds = _extendBounds(bounds, div.settings.maxZoomBuffer);
        if (div.map.getZoomForExtent(bounds) > div.settings.maxZoom) {
          // if showing something small, don't zoom in too far
          div.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
        }
        else {
          // Set the default view to show something triple the size of the grid square
          div.map.zoomToExtent(bounds);
        }
      }
      return helptext.join(' ');
    }

    function _handleEnteredSref(value, div) {
      if (value!='') {
        $.ajax({
          dataType: "jsonp",
          url: div.settings.indiciaSvc + "index.php/services/spatial/sref_to_wkt",
          data:"sref=" + value +
            "&system=" + _getSystem() +
            "&mapsystem=" + _projToSystem(div.map.projection, false), 
          success: function(data) {
            if(typeof data.error != 'undefined')
              if(data.code === 4001)
                alert(div.settings.msgSrefNotRecognised);
              else
                alert(data.error);
            else {
              // data should contain 2 wkts, one in indiciaProjection which is stored in the geom field,
              // and one in mapProjection which is used to draw the object.
              if (div.map.editLayer) {
                _showWktFeature(div, data.mapwkt, div.map.editLayer, null, false, "clickPoint");
              }
              $('#'+opts.geomId).val(data.wkt);
            }
          },
          error: function(data) {
            var response = JSON.parse(data.response.replace(/^jsonp\d+\(/, '').replace(/\)$/, ''));
            if(response.code === 4001) {
              alert(div.settings.msgSrefNotRecognised);
            } else {
              alert(response.error);
            }
          }
        });
      }
    }

    /**
     * Having clicked on the map, and asked warehouse services to transform this to a WKT, 
     * add the feature to the map editlayer. If the feature is a plot, enable dragging and
     * rotating. Finally add relevant help.
     */
    function _setClickPoint(data, div) {
      // data holds the sref in _getSystem format, wkt in indiciaProjection, optional mapwkt in mapProjection
      var feature, helptext=[], helpitem;
      // Update the spatial reference control
      $('#' + opts.srefId).val(data.sref);
      // If the sref is in two parts, then we might need to split it across 2 input fields for lat and long
      if (data.sref.indexOf(' ')!==-1) {
        var parts=$.trim(data.sref).split(' ');
        // part 1 may have a comma at the end, so remove
        var part1 = parts.shift().split(',')[0];
        $('#' + opts.srefLatId).val(part1);
        $('#' + opts.srefLongId).val(parts.join(''));
      }
      if ($('#annotations-mode-on').length && $('#annotations-mode-on').val()==='yes') { 
        //When in annotations mode, if the user sets the centroid on the map, we only want the previous centroid point to be removed.
        removeAllFeatures(div.map.editLayer, 'clickPoint');
      } else {
        removeAllFeatures(div.map.editLayer, 'boundary', true);
        removeAllFeatures(div.map.editLayer, 'ghost');
      }
      ghost=null;
      $('#' + opts.geomId).val(data.wkt);
      var parser = new OpenLayers.Format.WKT();
      var feature;
      // If mapwkt not provided, calculate it
      if (typeof data.mapwkt === "undefined") {
        if (div.indiciaProjection.getCode() === div.map.projection.getCode()) {
          data.mapwkt = data.wkt;
        } else {
          feature = parser.read(data.wkt);
          data.mapwkt = feature.geometry.transform(div.indiciaProjection, div.map.projection).toString();
        }
      }
      feature = parser.read(data.mapwkt);
      feature.attributes = {type: "clickPoint"};
      feature.style = new style('default');
      div.map.editLayer.addFeatures([feature]);
      
      if (div.settings.clickForPlot) {
        // if adding a plot, select it for modification
        div.map.plotModifier.selectFeature(feature);
      }
      
      if (div.settings.helpDiv) {
        // Output optional help and zoom in if more precision needed
        helpitem = _getPrecisionHelp(div, data.sref);
        if (helpitem !== '') {
          $('#' + div.settings.helpDiv).html(helpitem);
        } else {
          helptext.push(div.settings.hlpClickAgainToCorrect);
          // Extra help for grid square precision, as long as the precision is not fixed.
          if (feature.geometry.CLASS_NAME !== 'OpenLayers.Geometry.Point' && (div.settings.clickedSrefPrecisionMin===''
            || div.settings.clickedSrefPrecisionMin !== div.settings.clickedSrefPrecisionMax)) {
            helptext.push(div.settings.hlpZoomChangesPrecision);
          }
          $('#' + div.settings.helpDiv).html(helptext.join(' '));
        }
        $('#' + div.settings.helpDiv).show();
     } else if (div.settings.click_zoom) {
        // Optional zoom in after clicking when helpDiv not in use.
        var bounds = div.map.editLayer.features[0].geometry.getBounds();
        bounds = _extendBounds(bounds, div.settings.maxZoomBuffer);
        if (div.map.getZoomForExtent(bounds) > div.settings.maxZoom) {
          // if showing something small, don't zoom in too far
          div.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
        } else {
          // Set the default view to show something triple the size of the grid square
          div.map.zoomToExtent(bounds);
        }
        // Optional switch to satellite layer when using click_zoom
        if (div.settings.helpToPickPrecisionSwitchAt && data.sref.length >= div.settings.helpToPickPrecisionSwitchAt) {
          $.each(div.map.layers, function(idx, layer) {
            if (layer.isBaseLayer && layer.name.indexOf('Satellite') !== -1 && div.map.baseLayer !== layer) {
              div.map.setBaseLayer(layer);
            }
          });
        }
      }
      showGridRefHints(div);
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
        } else {
          div.georefInProgress = false;
        }
      }
    }

    /**
     * Convert a georeferenced place into a display place name.
     */
    function _getPlacename(place) {
      var placename=(place.display===undefined ? place.name : place.display);
      if (place.placeTypeName!==undefined) {
        placename = placename+' (' + place.placeTypeName + ')';
      }
      if (place.admin1!==undefined && place.admin1!='') {
        placename = placename + ', '+place.admin1;
      }
      if (place.admin2!==undefined && place.admin2!='') {
        placename = placename + '\\' + place.admin2;
      }
      return placename;
    }

    /**
     * Callback function, called by the georeferencer driver when it has found the results of a place
     * search.
     */
    function _displayGeorefOutput(div, places) {
      if (places.length>0) {
        var ref, corner1, corner2, obj, name,
            epsg = (places[0].epsg === undefined ? 4326 : places[0].epsg);
        if (places.length == 1 &&
          places[0].name.toLowerCase().replace('.','') == $('#' + div.georefOpts.georefSearchId).val().toLowerCase().replace('.','')) {
          // one place found that matches (ignoring case and full stop) e.g. 'st albans' matches 'St. Albans'
          ref=places[0].centroid.y + ', ' + places[0].centroid.x;
          name=places[0].name;
          corner1=places[0].boundingBox.northEast.y + ', ' + places[0].boundingBox.northEast.x;
          corner2=places[0].boundingBox.southWest.y + ', ' + places[0].boundingBox.southWest.x;
          obj = typeof places[0].obj==="undefined" ? {} : places[0].obj;
          _displayLocation(div, ref, corner1, corner2, epsg, name, obj);
        } else if (places.length !== 0) {
          // one inexact match or multiple matches
          $('<p>'+opts.msgGeorefSelectPlace+'</p>')
                  .appendTo('#'+div.georefOpts.georefOutputDivId);
          var ol=$('<ol>'), placename;
          $.each(places, function(i,place){
            ref= place.centroid.y + ', ' + place.centroid.x;
            corner1=place.boundingBox.northEast.y + ', ' + place.boundingBox.northEast.x;
            corner2=place.boundingBox.southWest.y + ', ' + place.boundingBox.southWest.x;
            placename= _getPlacename(place);

            obj = typeof place.obj==="undefined" ? {} : place.obj;

            ol.append($("<li>").append(
              $("<a href='#'>" + placename + "</a>")
                .click(function(e) {e.preventDefault();})
                .click((
                  // use closures to persist the values of ref, corner1, etc, admin1, admin2
                  function(ref, corner1, corner2, epsg, placename, obj){
                    return function() {
                      _displayLocation(div, ref, corner1, corner2, epsg, placename, obj);
                    };
                  }
                )(ref, corner1, corner2, epsg, placename, obj))
            ));
          });

          ol.appendTo('#'+div.georefOpts.georefOutputDivId);
          $('#'+div.georefOpts.georefDivId).show("fast", function() {div.map.updateSize();});
        }
      } else {
        // no matches found
        $('<p>'+opts.msgGeorefNothingFound+'</p>').appendTo('#'+div.georefOpts.georefOutputDivId);
        $('#'+div.georefOpts.georefDivId).show("fast", function() {div.map.updateSize();});
      }
      div.georefInProgress = false;
    }

    /**
    * After georeferencing a place, display a point on the map representing that place.
    * @access private
    */
    function _displayLocation(div, ref, corner1, corner2, epsgCode, name, obj)
    {
      // TODO either confirm that transform is OK or convert srefs using services.
      var epsg=new OpenLayers.Projection("EPSG:"+epsgCode);
      var refxy = ref.split(', ');
      var dataref = new OpenLayers.Geometry.Point(refxy[1],refxy[0]).transform(epsg, div.map.projection).toString();
      var corner1xy = corner1.split(', ');
      var datac1 = new OpenLayers.Geometry.Point(corner1xy[1],corner1xy[0]).transform(epsg, div.map.projection).toString();
      var corner2xy = corner2.split(', ');
      var datac2 = new OpenLayers.Geometry.Point(corner2xy[1],corner2xy[0]).transform(epsg, div.map.projection).toString();
      _showWktFeature(div, div.settings.searchDisplaysPoint ? dataref : false, div.map.searchLayer, [datac1, datac2], true, 'georef');
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
                  var parts=$.trim(data.sref).split(' ');
                  // part 1 may have a comma at the end, so remove
                  var part1 = parts.shift().split(',')[0];
                  $('#'+opts.srefLatId).val(part1);
                  $('#'+opts.srefLongId).val(parts.join(''));
                }
            }
           }
          );
        } else {
          $('#'+opts.srefId).val(ref);
          $('#'+opts.srefLatId).val($.trim(refxy[0]));
          $('#'+opts.srefLongId).val($.trim(refxy[1]));
        }
      } else {
        // clear the sref so the user doesn't accidentally submit an old one.'
        $('#'+opts.srefId).val('');
        $('#'+opts.srefLatId).val('');
        $('#'+opts.srefLongId).val('');
        $('#'+opts.geomId).val('');
      }
      // call any hooks that need to know about georeferences
      $.each(mapGeoreferenceHooks, function(i, fn) {
        fn(div, ref, corner1, corner2, epsgCode, name, obj);
      });
      if (div.georefOpts.autoCollapseResults) {
        $('#'+div.georefOpts.georefDivId).hide('fast', function() {div.map.updateSize();});
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
        openlayers_wms : function() {return new OpenLayers.Layer.WMS('OpenLayers WMS', 'http://labs.metacarta.com/wms/vmap0', {layers: 'basic'}, {'sphericalMercator': true});},
        nasa_mosaic : function() {return new OpenLayers.Layer.WMS('NASA Global Mosaic', 'http://t1.hypercube.telascience.org/cgi-bin/landsat7', {layers: 'landsat7'}, {'sphericalMercator': true});},
        // legacy support only
        virtual_earth: function() {return new OpenLayers.Layer.Bing({name: 'Bing Aerial', 'type': 'Aerial', 'key': settings.bing_api_key, 'sphericalMercator': true});},
        bing_aerial : function() {return new OpenLayers.Layer.Bing({name: 'Bing Aerial', 'type': 'Aerial', 'key': settings.bing_api_key, 'sphericalMercator': true});},
        bing_hybrid : function() {return new OpenLayers.Layer.Bing({name: 'Bing Hybrid', 'type': 'AerialWithLabels', 'key': settings.bing_api_key, 'sphericalMercator': true});},
        bing_shaded : function() {return new OpenLayers.Layer.Bing({name: 'Bing Shaded', 'type': 'road', 'key': settings.bing_api_key, 'sphericalMercator': true});},
        // multimap layers are no longer provided, so map any requests to OSM for backwards compatibility.
        multimap_default : function() {return new OpenLayers.Layer.OSM();},
        multimap_landranger : function() {return new OpenLayers.Layer.OSM();},
        osm : function() {return new OpenLayers.Layer.OSM();}, // default OpenStreetMap Mapnik layer
        osm_th : function() {return new OpenLayers.Layer.OSM("OpenStreetMap Tiles@Home", "http://tah.openstreetmap.org/Tiles/tile/${z}/${x}/${y}.png");} // OpenStreetMap Tiles@Home
      };
      // To protect ourselves against exceptions because the Google script would not link up, we
      // only enable these layers if the Google constants are available. We separately check for google V2 and V3 layers
      // to maintain backwards compatibility
      if (typeof G_PHYSICAL_MAP != "undefined") {
        r.google_physical =
            function() {return new OpenLayers.Layer.Google('Google Physical', {type: G_PHYSICAL_MAP, 'sphericalMercator': true});};
        r.google_streets =
            function() {return new OpenLayers.Layer.Google('Google Streets', {numZoomLevels : 20, 'sphericalMercator': true});};
        r.google_hybrid =
            function() {return new OpenLayers.Layer.Google('Google Hybrid', {type: G_HYBRID_MAP, numZoomLevels: 20, 'sphericalMercator': true});};
        r.google_satellite =
            function() {return new OpenLayers.Layer.Google('Google Satellite', {type: G_SATELLITE_MAP, numZoomLevels: 20, 'sphericalMercator': true});};
      } else if (typeof google !== "undefined" && typeof google.maps !== "undefined") {
        r.google_physical =
            function() {return new OpenLayers.Layer.Google('Google Physical', {type: google.maps.MapTypeId.TERRAIN, 'sphericalMercator': true});};
        r.google_streets =
            function() {return new OpenLayers.Layer.Google('Google Streets', {numZoomLevels : 20, 'sphericalMercator': true});};
        r.google_hybrid =
            function() {return new OpenLayers.Layer.Google('Google Hybrid', {type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20, 'sphericalMercator': true});};
        r.google_satellite =
            function() {return new OpenLayers.Layer.Google('Google Satellite', {type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 20, 'sphericalMercator': true});};
      }
      return r;
    }
    
    /**
     * Converts a bounds to a point or polygon geom.
     */
    function boundsToGeom(position, div) {
      var geom, bounds, xy, minXY, maxXY
      if (position.left===position.right && position.top===position.bottom) {
        // point clicked
        xy = div.map.getLonLatFromPixel(
            new OpenLayers.Pixel(position.left, position.bottom)
        );
        geom = new OpenLayers.Geometry.Point(xy.lon,xy.lat);
      } else {
        // bounding box dragged
        minXY = div.map.getLonLatFromPixel(
            new OpenLayers.Pixel(position.left, position.bottom)
        );
        maxXY = div.map.getLonLatFromPixel(
            new OpenLayers.Pixel(position.right, position.top)
        );
        bounds = new OpenLayers.Bounds(
            minXY.lon, minXY.lat, maxXY.lon, maxXY.lat
        );
        geom = bounds.toGeometry();
      }
      return geom;
    }

    /*
     * Selects the features in the contents of a bounding box
     */
    function selectBox(position, layers, div) {
      var testGeom, tolerantGeom, layer, tolerance, testGeoms={},
          getRadius, getStrokeWidth, radius, strokeWidth, match;
      if (position instanceof OpenLayers.Bounds) {
        testGeom=boundsToGeom(position, div);        
        for(var l=0; l<layers.length; ++l) {
          // set defaults
          getRadius=null;
          getStrokeWidth=null;
          radius=6;
          strokeWidth=1;
          layer = layers[l];
          // when testing a click point, use a circle drawn around the click point so the
          // click does not have to be exact. At this stage, we just look for the layer default
          // pointRadius and strokeWidth, so we can calculate the geom size to test.
          if (testGeom.CLASS_NAME==='OpenLayers.Geometry.Point') {
            if (typeof layer.styleMap.styles['default'].defaultStyle.pointRadius!=="undefined") {
              radius = layer.styleMap.styles['default'].defaultStyle.pointRadius;
              if (typeof radius === "string") {
                // A setting {n} means we use n to get the pointRadius per feature (either a field or a context func)
                match=radius.match(/^\${(.+)}/);
                if (match!==null && match.length>1) {
                  getRadius=layer.styleMap.styles['default'].context[match[1]];
                  if (getRadius===undefined) {
                    // the context function is missing, so must be a field name
                    getRadius=match[1];
                  }
                }
              }
            }
            if (typeof layer.styleMap.styles['default'].defaultStyle.strokeWidth!=="undefined") {
              strokeWidth=layer.styleMap.styles['default'].defaultStyle.strokeWidth;
              if (typeof strokeWidth === "string") {
                // A setting {n} means we use n to get the strokeWidth per feature (either a field or a context func)
                match=strokeWidth.match(/^\${(.+)}/);
                if (match!==null && match.length>1) {
                  getStrokeWidth=layer.styleMap.styles['default'].context[match[1]];
                  if (getStrokeWidth===undefined) {
                    // the context function is missing, so must be a field name
                    getStrokeWidth=match[1];
                  }
                }
              }
            }
          }
          var featuresToSelect = [];
          for(var i=0, len = layer.features.length; i<len; ++i) {
            var feature = layer.features[i];
            // check if the feature is displayed
            if (!feature.onScreen()) {
              continue;
            }
            pointGeom=feature.geometry.getCentroid();
            if (getRadius!==null) {
              // getRadius might be a string (fieldname) or a context function, so overwrite the layer default
              if (typeof getRadius==='string') {
                radius=feature.attributes[getRadius];
              } else {
                radius = getRadius(feature);
              }
            }
            if (getStrokeWidth!==null) {
              // getStrokeWidth might be a string (fieldname) or a context function, so overwrite the layer default
              if (typeof getStrokeWidth==='string') {
                strokeWidth=feature.attributes[getStrokeWidth];
              } else {
                strokeWidth = getStrokeWidth(feature);
              }
            }
            tolerance = div.map.getResolution() * (radius + (strokeWidth/2));
            tolerance=Math.round(tolerance);
            // keep geoms we create so we don't keep rebuilding them
            if (typeof testGeoms['geom-'+Math.round(tolerance/100)]!=="undefined") {
              tolerantGeom = testGeoms['geom-'+Math.round(tolerance/100)];
            } else {
              tolerantGeom = OpenLayers.Geometry.Polygon.createRegularPolygon(testGeom, tolerance, 8, 0);
              testGeoms['geom-'+Math.round(tolerance/100)] = tolerantGeom;
            }
            if ((tolerantGeom.intersects(feature.geometry) || testGeom.intersects(feature.geometry))
                && $.inArray(feature, layer.selectedFeatures)===-1) {
              featuresToSelect.push(feature);
            }
          }
          layer.map.setSelection(layer, featuresToSelect);
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
          } else if (item.CLASS_NAME==='OpenLayers.Layer.Vector' && $.inArray(item, clickableVectorLayers)===-1) {
            clickableVectorLayers.push(item);
          }
        });

        clickableWMSLayerNames = clickableWMSLayerNames.join(',');
        // Create a control that can handle both WMS and vector layer clicks.
        var infoCtrl = new OpenLayers.Control({
          displayClass: align + 'olControlSelectFeature',
          title: div.settings.reportGroup===null ? '' : div.settings.hintQueryDataPointsTool,
          lastclick: {},
          allowBox: clickableVectorLayers.length>0 && div.settings.allowBox===true,
          activate: function() {
            var handlerOptions = {
              'single': true,
              'double': false,
              'stopSingle': false,
              'stopDouble': true
            };
            if (clickableVectorLayers.length>0 && this.allowBox) {
              this.handlers = {box: new OpenLayers.Handler.Box(
                  this, {done: this.onGetInfo},
                  {boxDivClassName: "olHandlerBoxSelectFeature"}
                )
              };
              this.handlers.box.activate();
            } else {
              // allow click or bounding box actions
              this.handlers = {click: new OpenLayers.Handler.Click(this, {
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
            var bounds, xy, features, origFeatures;
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
              // build an array of all previuosly selected features in one
              origfeatures = [];
              $.each(clickableVectorLayers, function(idx, layer) {
                origfeatures = origfeatures.concat(layer.selectedFeatures);
              });
              // select all the features that were clicked or boxed.
              selectBox(bounds, clickableVectorLayers, div);
              // build an array of all newly selected features in one
              features = [];
              $.each(clickableVectorLayers, function(idx, layer) {
                features = features.concat(layer.selectedFeatures);
              });
              // now filter the report, highlight rows, or display output in a popup or div depending on settings.
              if (div.settings.clickableLayersOutputMode==='report' && div.settings.reportGroup!==null
                  && typeof indiciaData.reports!=="undefined") {
                // grab the feature ids
                var ids = [], len=0;
                $.each(features, function(idx, feature) {
                  if (len>1500) { // approaching 2K IE limit
                    alert('Too many records have been selected to show them all in the grid. Trying zooming in and selecting fewer records.');
                    return false;
                  }
                  if (typeof feature.attributes[div.settings.featureIdField]!=="undefined") {
                    ids.push(feature.attributes[div.settings.featureIdField]);
                    len += feature.attributes[div.settings.featureIdField].length;
                  } else if (typeof feature.attributes[div.settings.featureIdField+'s']!=="undefined") {
                    // allow for plural, list fields
                    ids.push(feature.attributes[div.settings.featureIdField+'s']);
                    len += feature.attributes[div.settings.featureIdField+'s'].length;
                  }
                });
                $('.'+div.settings.reportGroup+'-idlist-param').val(ids.join(','));
                // find the associated reports, charts etc and reload them to show the selected data. No need to if we started with no selection
                // and still have no selection.
                if (origfeatures.length!==0 || features.length!==0) {
                  $.each(indiciaData.reports[div.settings.reportGroup], function(name, report) {
                    report[0].settings.offset=0;
                    // force the param in, in case there is no params form.
                    report[0].settings.extraParams.idlist=ids.join(',');
                    report.reload(true);
                  });
                  $('table.report-grid tr').removeClass('selected');
                  $.each(ids, function(idx, id) {
                    $('table.report-grid tr#row'+id).addClass('selected');
                  });
                }
              } else if (div.settings.clickableLayersOutputMode==='reportHighlight'
                  && typeof indiciaData.reports!=="undefined") {
                // deselect existing selection in grid as well as on feature layer
                $('table.report-grid tr').removeClass('selected');
                // grab the features which should have an id corresponding to the rows to select
                $.each(features, function(idx, feature) {
                  $('table.report-grid tr#row'+feature.id).addClass('selected');
                });
              } else if (div.settings.clickableLayersOutputMode==='div') {
                $('#'+div.settings.clickableLayersOutputDiv).html(div.settings.clickableLayersOutputFn(features, div));
                //allows a custom function to be run when a user clicks on a map
              } else if (div.settings.clickableLayersOutputMode==='customFunction') {
                // features is already the list of clicked on objects, div.setting's.customClickFn must be a function passed to the map as a param.
                div.settings.customClickFn(features);
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

    /**
     * Converts a point to the required lat long notation.
     */
    function pointToLatLong(div, point) {
      var long_deg, long_min, long_sec, long_res, lat_deg, lat_min, lat_sec, lat_res, lat=Math.abs(point.y), lon=Math.abs(point.x);
      var TenToTheTwo=Math.pow(10,2), TenToTheFour=Math.pow(10,4), TenToTheFive=Math.pow(10,5);
      if (div.settings.latLongFormat == 'DMS') {
        long_deg = Math.floor(lon);
        long_min = Math.floor((lon-long_deg)*60);
        long_sec = Math.round((3600*(lon-long_deg)-long_min*60)*TenToTheTwo)/TenToTheTwo;
        long_res = long_deg+':'+long_min+':'+long_sec;
        lat_deg = Math.floor(lat);
        lat_min = Math.floor((lat-lat_deg)*60);
        lat_sec = Math.round((3600*(lat-lat_deg)-lat_min*60)*TenToTheTwo)/TenToTheTwo;
        lat_res = lat_deg+':'+lat_min+':'+lat_sec;
      } else if (div.settings.latLongFormat == 'DM') {
        long_deg = Math.floor(lon);
        long_min = Math.round((lon-long_deg)*60*TenToTheFour)/TenToTheFour;
        long_res = long_deg+':'+long_min;
        lat_deg = Math.floor(lat);
        lat_min = Math.round((lat-lat_deg)*60*TenToTheFour)/TenToTheFour;
        lat_res = lat_deg+':'+lat_min;
      }else {
        long_res = Math.round(lon*TenToTheFive)/TenToTheFive;
        lat_res = Math.round(lat*TenToTheFive)/TenToTheFive;
      }
      long_res += (point.x < 0 ? 'W' : 'E');
      lat_res += (point.y < 0 ? 'S' : 'N');
      return lat_res+' '+long_res;
    }

    /**
     * Gets the precision required for a grid square dependent on the map zoom.
     * Precision parameter is the optional default, overridden by the clickedSrefPrecisionMin and
     * clickedSrefPrecisionMax settings. Set accountForModifierKey to false to disable adjustments
     * made for the plus and minus key
     */
    function getPrecisionInfo(div, precision, accountForModifierKey) {
      if (typeof accountForModifierKey==="undefined") {
        accountForModifierKey=true;
      }
      // get approx metres accuracy we can expect from the mouse click - about 5mm accuracy.
      var metres = div.map.getScale() / 200;
      if (typeof precision === "undefined" || precision===null) {
        // now round to find appropriate square size
        if (metres < 3) {
          precision = 10;
        } else if (metres < 30) {
          precision = 8;
        } else if (metres < 300) {
          precision = 6;
        } else if (metres < 3000) {
          precision = 4;
        } else {
          precision = 2;
        }
      }
      if (accountForModifierKey) {
        // + and - keys can change the grid square precision
        precision = plusKeyDown ? precision + 2 : precision;
        precision = minusKeyDown ? precision - 2 : precision;
      }
      // enforce precision limits if specified in the settings
      if (div.settings.clickedSrefPrecisionMin !== '') {
        precision = Math.max(div.settings.clickedSrefPrecisionMin, precision);
      }
      if (div.settings.clickedSrefPrecisionMax !== '') {
        precision = Math.min(div.settings.clickedSrefPrecisionMax, precision);
      }
      return {precision: precision, metres: metres};
    }

    /**
     * Converts a point to a spatial reference, and also generates the indiciaProjection and mapProjection wkts.
     * The point should be a point geometry in the map projection or projection defined by pointSystem, system should hold the system we wish to
     * display the Sref. pointSystem is optional and defines the projection of the point if not the map projection.
     * Precision can be set to the number of digits in the grid ref to return or left for default which depends on the
     * map zoom.
     * We have consistency problems between the proj4 on the client and in the database, so go to the services
     * whereever possible to convert.
     * Callback gets called with the sref in system, and the wkt in indiciaProjection. These may be different.
     */
    function pointToSref(div, point, system, callback, pointSystem, precision) {
      if (typeof pointSystem==="undefined") {
        pointSystem=_projToSystem(div.map.projection, false);
      }
      // get precision required dependent on map zoom
      var precisionInfo=getPrecisionInfo(div, precision);
      if (typeof indiciaData.srefHandlers==="undefined" ||
          typeof indiciaData.srefHandlers[system.toLowerCase()]==="undefined" ||
          $.inArray('wkt', indiciaData.srefHandlers[_getSystem().toLowerCase()].returns)===-1||
          $.inArray('sref', indiciaData.srefHandlers[_getSystem().toLowerCase()].returns)===-1) {
        // next call also generates the wkt in map projection
        $.getJSON(opts.indiciaSvc + "index.php/services/spatial/wkt_to_sref"+
                "?wkt=" + point +
                "&system=" + system +
                "&wktsystem=" + pointSystem +
                "&mapsystem=" + _projToSystem(div.map.projection, false) +
                "&precision=" + precisionInfo.precision +
                "&metresAccuracy=" + precisionInfo.metres +
                "&output=" + div.settings.latLongFormat +
                "&callback=?", callback
        );
      } else {
        // passing a point in the mapSystem.
        var r, pt, feature, parser,
                ll = new OpenLayers.LonLat(point.x, point.y),
                proj=new OpenLayers.Projection('EPSG:'+indiciaData.srefHandlers[_getSystem().toLowerCase()].srid),
                precisionInfo=getPrecisionInfo(div);
        ll.transform(div.map.projection, proj);
        pt = {x:ll.lon, y:ll.lat};
        r=indiciaData.srefHandlers[_getSystem().toLowerCase()].pointToSref(pt, precisionInfo);
        parser = new OpenLayers.Format.WKT();
        feature = parser.read(r.wkt);
        r.wkt = feature.geometry.transform(proj, div.indiciaProjection).toString();
        callback(r);
      }
    }

    /**
     * Event handler for feature add/modify on the edit layer when polygon recording is enabled. Puts the geom in the hidden
     * input for the sample geom, plus sets the visible spatial ref control to the centroid in the currently selected system.
     */
    function recordPolygon(evt) {
      // track old features to replace
      var oldFeatures=[], map=this.map, div=map.div, separateBoundary=$('#' + map.div.settings.boundaryGeomId).length>0;
      evt.feature.attributes.type=div.settings.drawObjectType;
      //When drawing new features onto the map, we only ask the user
      //if they want to replace the previous feature when they have the same type.
      //This allows us to have multiple layers of different types that don't interfere with each other.
      $.each(evt.feature.layer.features, function(idx, feature) {
        // replace features of the same type, or allow a boundary to be replaced by a queryPolygon
        if (feature!==evt.feature && (feature.attributes.type===evt.feature.attributes.type || feature.attributes.type==='boundary')) {
          oldFeatures.push(feature);
        }
      });   
      if (oldFeatures.length>0) {
        if (confirm(div.settings.msgReplaceBoundary)) {
          evt.feature.layer.removeFeatures(oldFeatures, {});
        } else {
          evt.feature.layer.removeFeatures([evt.feature], {});
          return;
        }
      }
      if (div.settings.drawObjectType==="boundary"||div.settings.drawObjectType==="annotation") {
        geom = evt.feature.geometry.clone();
        if (map.projection.getCode() != div.indiciaProjection.getCode()) {
          geom.transform(map.projection, div.indiciaProjection);
        }
        if (separateBoundary) {
          $('#' + div.settings.boundaryGeomId).val(geom.toString());
          evt.feature.style = new style('boundary');
          if(this.map.div.settings.autoFillInCentroid) {
            var centroid = evt.feature.geometry.getCentroid();
            $('#imp-geom').val(centroid.toString());
            pointToSref(this.map.div, centroid, _getSystem(), function(data) {
              if (typeof data.sref !== "undefined") {
                $('#'+map.div.settings.srefId).val(data.sref);
              }
            });
          }
          map.editLayer.redraw();
        } else {
          $('#imp-geom').val(geom.toString());
          // as we are not separating the boundary geom, the geom's sref goes in the centroid
          pointToSref(div, geom.getCentroid(), _getSystem(), function(data) {
            if (typeof data.sref !== "undefined") {
              $('#'+div.settings.srefId).val(data.sref);
            }
          });
        }
      }
    }

    /**
     * Event handler for feature modify on the edit layer when clickForPlot is enabled. 
     * Puts the geom in the hidden input for the sample geom, plus sets the visible spatial 
     * ref control to the SW corner in the currently selected system.
     */
    function modifyPlot(evt) {
      var modifier = this;
      var feature = evt.feature;
      var map = modifier.map;
      var precision = map.div.settings.plotPrecision;
      
      var vertices = feature.geometry.getVertices();
      // Initialise swVertex to somewhere very northwest. 
      // This might need modifying for southern hemisphere.
      var swVertex = new OpenLayers.Geometry.Point(1e10, 1e10);
      $.each(vertices, function(i, vertex) {
        if ( (vertex.y < swVertex.y) || (vertex.y === swVertex.y && vertex.x < swVertex.x) ) {
          // Find the most southerly vertex and, of two equally southerly, take the
          // most westerly as our reference point.
          swVertex = vertex;
        }       
      });
      
      // Put the geometry in the input control 
      $('#imp-geom').val(feature.geometry.toString());
      // Get the sref of the swVertex and show in control
      pointToSref(map.div, swVertex, _getSystem(), function(data) {
        if (typeof data.sref !== "undefined") {
          $('#'+map.div.settings.srefId).val(data.sref);
        }
      }, undefined, precision);     
    }

    /**
     * Function called by the map click handler. Converts the point clicked to an sref then 
     * calls a callback to process it.
     * Callback is a function that accepts a data structure as returned by the warehouse 
     * conversion from Wkt to Sref. Should contain properties for sref & wkt, or error if failed.
     */
    function clickOnMap(xy, div, callback)
    {
      var lonlat = div.map.getLonLatFromPixel(xy);
      // This is in the SRS of the current base layer, which should but may not be the same projection 
      // as the map! Definitely not indiciaProjection!
      // Need to convert this map based Point to a _getSystem based Sref (done by pointToSref) and a
      // indiciaProjection based geometry (done by the callback)
      var point = new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat);
      var polygon;

      if (div.settings.clickForPlot) {
       // Clicking to locate a plot
       var plotShape = $('#' + div.settings.plotShapeId).val();
       if (plotShape === 'rectangle') {
         //create a rectangular polygon
          var width = parseFloat($('#' + div.settings.plotWidthId).val());
          var length = parseFloat($('#' + div.settings.plotLengthId).val());
          // Define a polygon the size of the plot with SW corner at the click point
          var linearRing = new OpenLayers.Geometry.LinearRing([
            new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat),
            new OpenLayers.Geometry.Point(lonlat.lon + width, lonlat.lat),
            new OpenLayers.Geometry.Point(lonlat.lon + width, lonlat.lat + length),
            new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat + length)
          ]);
          polygon = new OpenLayers.Geometry.Polygon([linearRing]);
        } else if (plotShape === 'circle') {
          // create a circular polygon
          var radius = parseFloat($('#' + div.settings.plotRadiusId).val());
          polygon = new OpenLayers.Geometry.Polygon.createRegularPolygon(point, radius, 20, 0);
        }
        var feature = new OpenLayers.Feature.Vector(polygon);
        var formatter = new OpenLayers.Format.WKT();
        // Store plot as WKT in map projection
        var plot = {};
        plot.mapwkt = formatter.write(feature);
        // Convert mapwkt to indicia wkt
        if (div.indiciaProjection.getCode() === div.map.projection.getCode()) {
          plot.wkt = plot.mapwkt;
        } else {
          plot.wkt = feature.geometry.transform(div.map.projection, div.indiciaProjection).toString();
        }           
        var precision = div.settings.plotPrecision;
        // Request sref of point that was clicked
        pointToSref(div, point, _getSystem(), function(data){
          plot.sref = data.sref;
          callback(plot);     
        }, undefined, precision);
      } 
      else 
      {
        // Clicking to locate an sref (eg an OSGB grid square)
        pointToSref(div, point, _getSystem(), function(data){
          callback(data);     
        });
      }      
    }
    
    function showGridRefHints(div) {
      if (div.settings.gridRefHint && typeof indiciaData.srefHandlers!=="undefined" &&
          typeof indiciaData.srefHandlers[_getSystem().toLowerCase()]!=="undefined") {
        var ll = div.map.getLonLatFromPixel(currentMousePixel), precisionInfo, 
              handler=indiciaData.srefHandlers[_getSystem().toLowerCase()], largestSrefLen, pt,
              proj, recalcGhost = ghost===null || !ghost.atPoint(ll, 0, 0), r;
        if ($.inArray('precisions', handler.returns)!==-1 && $.inArray('gridNotation', handler.returns)!==-1) {
          precisionInfo=getPrecisionInfo(div, null, false);
          proj=new OpenLayers.Projection('EPSG:'+indiciaData.srefHandlers[_getSystem().toLowerCase()].srid);
          ll.transform(div.map.projection, proj);
          pt = {x:ll.lon, y:ll.lat};
          largestSrefLen = precisionInfo.precision;
          $('.grid-ref-hint').removeClass('active');
          // If there are multiple precisions available using the +/- keys, activate the current one
          if (div.settings.clickForSpatialRef && handler.sreflenToPrecision(largestSrefLen+4).metres !== handler.sreflenToPrecision(largestSrefLen).metres) {
            if (minusKeyDown) {
              $('.hint-minus').addClass('active');
            } else if (plusKeyDown) {
              $('.hint-plus').addClass('active');
            } else {
              $('.hint-normal').addClass('active');
            }
          }
          // almost every mouse move causes the smallest + key square to change
          if (handler.sreflenToPrecision(largestSrefLen+4)!==false && 
                handler.sreflenToPrecision(largestSrefLen+4).metres !== handler.sreflenToPrecision(largestSrefLen+2).metres) {
            $('.hint-plus .label').html(handler.sreflenToPrecision(largestSrefLen+4).display + ':');
            $('.hint-plus .data').html(handler.pointToGridNotation(pt, largestSrefLen+2));
            $('.hint-plus').css('opacity', 1);
          } else {
            $('.hint-plus').css('opacity', 0);
          }
          // don't recalculate if mouse is still over the existing ghost                
          if (recalcGhost || $('.hint-normal').css('opacity')===0) {
            // since we've moved a square, redo the grid ref hints
            if (handler.sreflenToPrecision(largestSrefLen)!==false && 
                handler.sreflenToPrecision(largestSrefLen).metres !== handler.sreflenToPrecision(largestSrefLen+2).metres) {
              $('.hint-minus .label').html(handler.sreflenToPrecision(largestSrefLen).display + ':');
              $('.hint-minus .data').html(handler.pointToGridNotation(pt, largestSrefLen-2));
              $('.hint-minus').css('opacity', 1);
            } else {
              $('.hint-minus').css('opacity', 0);
            }
            $('.hint-normal .label').html(handler.sreflenToPrecision(largestSrefLen+2).display + ':');
            $('.hint-normal .data').html(handler.pointToGridNotation(pt, largestSrefLen));
            $('.hint-normal').css('opacity', 1);
          }
        }
      }
    }
    
    function clearGridRefHints() {
      $('.grid-ref-hint').css('opacity', 0);
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
      // expose public stuff
      this.settings = opts;
      this.pointToSref = pointToSref;
      this.addPt = addPt;
      this.getFeaturesByVal = getFeaturesByVal;
      this.removeAllFeatures = removeAllFeatures;
      this.locationSelectedInInput = locationSelectedInInput;
      // wrap the map in a div container
      $(this).wrap('<div id="map-container" style="width:'+opts.width+'" >');
      $(this).before('<div id="map-loading" class="loading-overlay"></div>');

      // if the validator exists, stop map clicks bubbling up to its event handler as IE can't
      // get the attributes of some map items and errors arise.
      if (typeof $.validator !== 'undefined') {
        $(this).parent().click(function(){
          return false;
        });
      }

      if (this.settings.toolbarDiv != 'map' && (opts.toolbarPrefix !== '' || opts.toolbarSuffix !== '')) {
        var toolbar = '<div id="map-toolbar-outer" class="ui-helper-clearfix">' + opts.toolbarPrefix + '<div class="olControlEditingToolbar" id="map-toolbar"></div>' + opts.toolbarSuffix + '</div>';
        if (this.settings.toolbarDiv == 'top') {
          $(this).before(toolbar);
        } else if (this.settings.toolbarDiv == 'bottom') {
          $(this).after(toolbar);
        } else {
          $('#' + this.settings.toolbarDiv).html(toolbar);
        }
        this.settings.toolbarDiv = 'map-toolbar';
      }
      if (this.settings.helpDiv === 'bottom') {
        var helpbar, helptext = [];
        if ($.inArray('panZoom', this.settings.standardControls) ||
            $.inArray('panZoomBar', this.settings.standardControls)) {
          helptext.push(this.settings.hlpPanZoomButtons);
        } else {
          helptext.push(this.settings.hlpPanZoom);
        }
        if (this.settings.editLayer && this.settings.clickForSpatialRef) {
          helptext.push(this.settings.hlpClickOnceSetSref);
        }
        helpbar = '<div id="map-help" class="ui-widget ui-widget-content">'+helptext.join(' ')+'</div>';
        $(this).after(helpbar);
        this.settings.helpDiv='map-help';
      }

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
      div.indiciaProjection = new OpenLayers.Projection('EPSG:3857');
      olOptions.controls = [
            new OpenLayers.Control.Navigation({title: 'navigation'}),
            new OpenLayers.Control.ArgParser(),
            new OpenLayers.Control.Attribution()
      ];

      // Constructs the map
      div.map = new OpenLayers.Map($(this)[0], olOptions);
      
      // track plus and minus key presses, which influence selected grid square size
      $(document).keydown(function(evt) {
        var change=false;
        switch (evt.which) {
          
          case 61: case 107: case 187:
            if (overMap) {
              // prevent + affecting other controls
              evt.preventDefault();
            }
            // prevent some browsers autorepeating
            if (!plusKeyDown) {
              plusKeyDown = true;
              change=true;
            }
            break;
          case 173: case 109: case 189:
            if (overMap) {
              // prevent + affecting other controls
              evt.preventDefault();
            }
            if (!minusKeyDown) {
              minusKeyDown = true;
              change=true;
            }
            break;
        };
        if (change) {
          // force a square redraw when mouse moves
          removeAllFeatures(div.map.editLayer, 'ghost');
          ghost=null;
          showGridRefHints(div);
        }
      });
      $(document).keyup(function(evt) {
        var change=false;
        switch (evt.which) {
          case 61: case 107: case 187:
            // prevent some browsers autorepeating
            if (plusKeyDown) {
              plusKeyDown = false;
              change=true;
            }
            break;
          case 173: case 109: case 189:
            if (minusKeyDown) {
              minusKeyDown = false;
              change=true;
            }
            break;
        };
        if (change) {
          // force a square redraw when mouse moves
          removeAllFeatures(div.map.editLayer, 'ghost');
          ghost=null;
          evt.preventDefault();
          showGridRefHints(div);
        }
      });
      div.map.events.register('mousemove', null, function() {
        overMap = true;
      });
      div.map.events.register('mouseout', null, function(evt) {
        var testDiv=div.map.viewPortDiv;
        var target = (evt.relatedTarget) ? evt.relatedTarget : evt.toElement;
        if (typeof target!=="undefined") {
          // walk up the DOM tree.
          while (target != testDiv && target != null) {
            target = target.parentNode;
          }
          // if the target we stop at isn't the div, then we've left the div.
          if (target != testDiv) {
            clearGridRefHints();
            overMap = false;
          }
        }
      });

      // setup the map to save the last position
      if (div.settings.rememberPos && typeof $.cookie !== "undefined") {
        div.map.events.register('moveend', null, function() {
          $.cookie('mapzoom', div.map.zoom, {expires: 7});
          $.cookie('maplon', div.map.center.lon, {expires: 7});
          $.cookie('maplat', div.map.center.lat, {expires: 7});
          $.cookie('mapbase', div.map.baseLayer.name, {expires: 7})
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
        } else {
          alert('Requested preset layer ' + item + ' is not recognised.');
        }
      });

      // Convert indicia WMS/WFS layers into js objects
      $.each(this.settings.indiciaWMSLayers, function(key, value)
      {
        div.settings.layers.push(new OpenLayers.Layer.WMS(key, div.settings.indiciaGeoSvc + 'wms', {layers: value, transparent: true}, {singleTile: true, isBaseLayer: false, sphericalMercator: true}));
      });
      $.each(this.settings.indiciaWFSLayers, function(key, value)
      {
        div.settings.layers.push(new OpenLayers.Layer.WFS(key, div.settings.indiciaGeoSvc + 'wms', {typename: value, request: 'GetFeature'}, {sphericalMercator: true}));
      });

      div.map.addLayers(this.settings.layers);

      // Centre the map, using cookie if remembering position, otherwise default setting.
      var zoom = null, center = {"lat":null, "lon":null}, baseLayerName = null, added;
      if (typeof $.cookie !== "undefined" && div.settings.rememberPos!==false) {
        zoom = $.cookie('mapzoom');
        center.lon = $.cookie('maplon');
        center.lat = $.cookie('maplat');
        baseLayerName = $.cookie('mapbase')
     }

      // Missing cookies result in null variables
      if (zoom === null) {
        zoom = this.settings.initial_zoom;
      }
      if (center.lon !== null && center.lat !== null) {
        center = new OpenLayers.LonLat(center.lon, center.lat);
      } else {
        center = new OpenLayers.LonLat(this.settings.initial_long, this.settings.initial_lat);
        if (div.map.displayProjection.getCode()!=div.map.projection.getCode()) {
          center.transform(div.map.displayProjection, div.map.projection);
        }
      }
      div.map.setCenter(center, zoom);

      // Set the base layer using cookie if remembering
      if (baseLayerName !== null) {
        $.each(div.map.layers, function(idx, layer) {
          if (layer.isBaseLayer && layer.name == baseLayerName && div.map.baseLayer !== layer) {
            div.map.setBaseLayer(layer);
          }
        });
      }

      /**
       * Public function to change selection of features on a layer.
       */
      div.map.setSelection = function(layer, features) {
        $.each(layer.selectedFeatures, function(idx, feature) {
          feature.renderIntent='default';
        });
        layer.selectedFeatures = features;
        $.each(layer.selectedFeatures, function(idx, feature) {
          feature.renderIntent='select';
        });
        layer.redraw();
      }

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
        if (indiciaData.zoomid) {
          //Change the feature colour to make it a ghost when we are in add mode and zoomed into a location (as the location boundary isn't
          //used, it is only visual)
          var editLayer = new OpenLayers.Layer.Vector(
              this.settings.editLayerName,
              {style: new style('ghost'), 'sphericalMercator': true, displayInLayerSwitcher: this.settings.editLayerInSwitcher}
          );
        } else {
          // Add an editable layer to the map
          var editLayer = new OpenLayers.Layer.Vector(
              this.settings.editLayerName,
              {style: new style('boundary'), 'sphericalMercator': true, displayInLayerSwitcher: this.settings.editLayerInSwitcher}
          );
        }
        div.map.editLayer = editLayer;
        div.map.addLayer(div.map.editLayer);

        if (this.settings.initialFeatureWkt === null && $('#'+this.settings.geomId).length>0) {
          // if no initial feature specified, but there is a populated imp-geom hidden input,
          // use the value from the hidden geom
          this.settings.initialFeatureWkt = $('#'+this.settings.geomId).val();
        }
        if (this.settings.initialBoundaryWkt === null && $('#'+this.settings.boundaryGeomId).length>0) {
          // same again for the boundary
          added=this.settings.initialBoundaryWkt = $('#'+this.settings.boundaryGeomId).val();
          added.style = new style('boundary');
        }
        
        // Draw the feature to be loaded on startup, if present
        var zoomToCentroid = (this.settings.initialBoundaryWkt) ? false : true;
        if (this.settings.initialFeatureWkt) {
          _showWktFeature(this, this.settings.initialFeatureWkt, div.map.editLayer, null, false, "clickPoint", zoomToCentroid, true);
        }
        if (this.settings.initialBoundaryWkt) {
          var featureType;
          //If the map is zoomed in add mode, then the featuretype is nothing as the boundary should act as a "ghost" that isn't used for 
          //anything other than zooming.
          if (indiciaData.zoomid) {
            featureType="";
          } else if ($('#annotations-mode-on').val()==='yes') {
            featureType="annotation";
          } else {
            featureType="boundary";
          }
          _showWktFeature(this, this.settings.initialBoundaryWkt, div.map.editLayer, null, false, featureType, true, true);
        }

        if (div.settings.clickForSpatialRef || div.settings.gridRefHint) {
          div.map.events.register('mousemove', null, function(evt) {
            currentMousePixel = evt.xy;
            showGridRefHints(div);
            if (typeof div.map.editLayer.clickControl!=="undefined" && div.map.editLayer.clickControl.active) {
              if (div.map.dragging) {
                removeAllFeatures(div.map.editLayer, 'ghost');
              } else {
                if (typeof indiciaData.srefHandlers!=="undefined" &&
                    typeof indiciaData.srefHandlers[_getSystem().toLowerCase()]!=="undefined" &&
                    $.inArray('wkt', indiciaData.srefHandlers[_getSystem().toLowerCase()].returns)!==-1) {
                  var ll = div.map.getLonLatFromPixel(evt.xy),
                      handler=indiciaData.srefHandlers[_getSystem().toLowerCase()], pt,
                      proj, recalcGhost = ghost===null || !ghost.atPoint(ll, 0, 0), precisionInfo;
                  if (recalcGhost) {
                    precisionInfo=getPrecisionInfo(div);
                    proj=new OpenLayers.Projection('EPSG:'+indiciaData.srefHandlers[_getSystem().toLowerCase()].srid);
                    ll.transform(div.map.projection, proj);
                    pt = {x:ll.lon, y:ll.lat};
                    // If we have a client-side handler for this system which can return the wkt then we can
                    // draw a ghost of the proposed sref if they click
                    var r, feature, parser;
                    r=handler.pointToSref(pt, precisionInfo);
                    if (typeof r.error!=="undefined") {
                      removeAllFeatures(div.map.editLayer, 'ghost');
                    } else {
                      parser = new OpenLayers.Format.WKT();
                      feature = parser.read(r.wkt);
                      r.wkt = feature.geometry.transform(proj, div.map.projection).toString();
                      ghost=_showWktFeature(div, r.wkt, div.map.editLayer, null, true, 'ghost', false);
                    }
                  } else if (parseInt(_getSystem())==_getSystem()) {
                    // also draw a selection ghost if using a point ref system we can simply transform client-side
                    var ll = div.map.getLonLatFromPixel({x: evt.layerX, y: evt.layerY}),
                        proj=new OpenLayers.Projection('EPSG:'+_getSystem());
                    //ll.transform(div.map.projection, proj);
                    ghost=_showWktFeature(div, 'POINT('+ll.lon+' '+ll.lat+')', div.map.editLayer, null, true, 'ghost', false);
                  }
                }
              }
            }
          });
          $('#map').mouseleave(function(evt) {
            // clear ghost hover markers when mouse leaves the map
            removeAllFeatures(div.map.editLayer, 'ghost');
          });
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
        var layer, locLayerSettings = {
            layers: div.settings.locationLayerName,
            transparent: true
        };
        if (div.settings.locationLayerFilter!=='') {
          locLayerSettings.cql_filter=div.settings.locationLayerFilter;
        }
        var layer = new OpenLayers.Layer.WMS('Locations', div.settings.indiciaGeoSvc + 'wms', locLayerSettings, {
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
                X: Math.round(e.xy.x),
                Y: Math.round(e.xy.y),
                INFO_FORMAT: 'application/vnd.ogc.gml',
                LAYERS: div.settings.locationLayerName,
                QUERY_LAYERS: div.settings.locationLayerName,
                CQL_FILTER: div.settings.locationLayerFilter,
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
              $('#imp-location').val(response.features[0].data.id).change();
              $('#imp-location\\:name').val(response.features[0].data.name);
            }
          }
        });

        div.map.addControl(infoCtrl);
        infoCtrl.activate();
      }

      if (div.settings.editLayer && (div.settings.clickForSpatialRef || div.settings.clickForPlot)) {
        // Setup a click event handler for the map
        OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
          defaultHandlerOptions: {'single': true, 'double': false, 'pixelTolerance': 0, 'stopSingle': false, 'stopDouble': false},
          title: div.settings.hintClickSpatialRefTool,
          trigger: function(e) {
            clickOnMap(e.xy, div, function(data)
              {
                if(typeof data.error !== 'undefined') {
                  if(data.error == 'The spatial reference system is not set.') {
                      alert(div.settings.msgSrefSystemNotSet);
                  } else {
                    // We can switch to lat long if the system is available for selection
                    var system=$('#'+opts.srefSystemId+' option[value=4326]');
                    if (system.length===1) {
                      var lonlat=div.map.getLonLatFromPixel(e.xy);
                      $('#'+opts.srefSystemId).val('4326');
                      pointToSref(div, new OpenLayers.Geometry.Point(lonlat.lon, lonlat.lat), '4326', function(data) {
                        _setClickPoint(data, div); // data sref in 4326, wkt in indiciaProjection, mapwkt in mapProjection
                      });
                    } else {
                      alert(div.settings.msgSrefOutsideGrid);
                    }
                  }
                }
                else
                  _setClickPoint(data, div); // data sref in _getSystem, wkt in indiciaProjection, mapwkt in mapProjection
              }
            );
          },
          initialize: function(options)
          {
            this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
            OpenLayers.Control.prototype.initialize.apply(this, arguments);
            this.handler = new OpenLayers.Handler.Click( this, {'click': this.trigger}, this.handlerOptions );
          }
        });
      }
      if (div.settings.editLayer && div.settings.allowPolygonRecording) {   
        div.map.editLayer.events.on({'featuremodified': function(evt) {
          if ($('#' + div.settings.boundaryGeomId).length>0) {
            $('#' + div.settings.boundaryGeomId).val(evt.feature.geometry.toString());
            if(div.settings.autoFillInCentroid) {
              var centroid = evt.feature.geometry.getCentroid();
              $('#imp-geom').val(centroid.toString());
              pointToSref(div, centroid, _getSystem(), function(data) {
                if (typeof data.sref !== "undefined") {
                  $('#'+div.settings.srefId).val(data.sref);
                }
              });
            }
          }
        }});
      }
      var ctrl, hint, pushDrawCtrl = function(c) {
        toolbarControls.push(c);
        if (div.settings.editLayer && div.settings.allowPolygonRecording) {
          c.events.register('featureadded', c, recordPolygon);
        }
      }, drawStyle=new style('boundary');
      var ctrlObj;
      $.each(div.settings.standardControls, function(i, ctrl) {
        ctrlObj=null;
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
          hint = div.settings.hintDrawPolygonHint;
          if (div.settings.reportGroup!==null) {
            hint += ' ' + div.settings.hintDrawForReportingHint;
          }
          ctrlObj = new OpenLayers.Control.DrawFeature(div.map.editLayer,
              OpenLayers.Handler.Polygon,
              {'displayClass': align + 'olControlDrawFeaturePolygon', 'title':hint, handlerOptions:{style:drawStyle}});
          pushDrawCtrl(ctrlObj);
        } else if (ctrl=='drawLine' && div.settings.editLayer) {
          hint = div.settings.hintDrawLineHint;
          if (div.settings.reportGroup!==null) {
            hint += ' ' + div.settings.hintDrawForReportingHint;
          }
          ctrlObj = new OpenLayers.Control.DrawFeature(div.map.editLayer,
              OpenLayers.Handler.Path,
              {'displayClass': align + 'olControlDrawFeaturePath', 'title':hint, handlerOptions:{style:drawStyle}});
          pushDrawCtrl(ctrlObj);
        } else if (ctrl=='drawPoint' && div.settings.editLayer) {
          hint = div.settings.hintDrawPointHint;
          if (div.settings.reportGroup!==null) {
            hint += ' ' + div.settings.hintDrawForReportingHint;
          }
          ctrlObj = new OpenLayers.Control.DrawFeature(div.map.editLayer,
              OpenLayers.Handler.Point,
              {'displayClass': align + 'olControlDrawFeaturePoint', 'title':hint, handlerOptions:{style:drawStyle}});
          pushDrawCtrl(ctrlObj);
        } else if (ctrl=='selectFeature' && div.settings.editLayer) {
          ctrlObj = new OpenLayers.Control.SelectFeature(div.map.editLayer);
          toolbarControls.push(ctrlObj);
        } else if (ctrl=='hoverFeatureHighlight' && div.settings.editLayer) {
          ctrlObj = new OpenLayers.Control.SelectFeature(div.map.editLayer, {hover: true, highlightOnly: true});
          div.map.addControl(ctrlObj);
        } else if (ctrl=='clearEditLayer' && div.settings.editLayer) {
          toolbarControls.push(new OpenLayers.Control.ClearLayer([div.map.editLayer],
              {'displayClass': align + ' olControlClearLayer', 'title':div.settings.hintClearSelection, 'clearReport':true}));
        } else if (ctrl=='modifyFeature' && div.settings.editLayer) {
          ctrlObj = new OpenLayers.Control.ModifyFeature(div.map.editLayer,
              {'displayClass': align + 'olControlModifyFeature', 'title':div.settings.hintModifyFeature});
          toolbarControls.push(ctrlObj);
        } else if (ctrl=='graticule') {
          ctrlObj = new OpenLayers.Control.IndiciaGraticule({projection: div.settings.graticuleProjection, bounds: div.settings.graticuleBounds});
          div.map.addControl(ctrlObj);
          if ($.inArray(ctrl, div.settings.activatedStandardControls)===-1) {
            // if this control is not active, also need to reflect this in the layer.
            ctrlObj.gratLayer.setVisibility(false);
          }
        }
        // activate the control if available and in the config settings. A null control cannot be activated.
        if (ctrlObj!==null && $.inArray(ctrl, div.settings.activatedStandardControls)>-1) {
          ctrlObj.activate();
        }
      });
      if (div.settings.editLayer && (div.settings.clickForSpatialRef || div.settings.clickForPlot)) {
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
        var nav=new OpenLayers.Control.Navigation({displayClass: align + "olControlNavigation", "title":div.settings.hintNavigation+((!this.settings.scroll_wheel_zoom || this.settings.scroll_wheel_zoom==="false")?'':div.settings.hintScrollWheel)});
        toolbar.addControls([nav]);
        toolbar.addControls(toolbarControls);
        div.map.addControl(toolbar);
        if (typeof click!=="undefined") {
          click.activate();
        } 
        else {
          nav.activate();
        }
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
        if (div.settings.editLayer && div.settings.clickForPlot) {
          // When clickForPlot is true add a ModifyFeature control to the map
          // so that the plot can be dragged and rotated
          var modifier = new OpenLayers.Control.ModifyFeature(div.map.editLayer, {
            standalone: true,
            mode: OpenLayers.Control.ModifyFeature.DRAG | OpenLayers.Control.ModifyFeature.ROTATE
          });
          div.map.addControl(modifier);
          div.map.editLayer.events.register('featuremodified', modifier, modifyPlot);
          modifier.activate();
          // Store a reference to the control
          div.map.plotModifier = modifier;
        }
      }

      // Disable the scroll wheel from zooming if required
      if (!this.settings.scroll_wheel_zoom || this.settings.scroll_wheel_zoom==="false") {
        $.each(div.map.controls, function(i, control) {
          if (control instanceof OpenLayers.Control.Navigation) {
            control.disableZoomWheel();
          }
        });
      }
      _bindControls(this);
      // keep a handy reference
      indiciaData.mapdiv=div;
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
jQuery.fn.indiciaMapPanel.defaults = {
    indiciaSvc : '',
    indiciaGeoSvc : '',
    readAuth : '',
    height: "600",
    width: "470",
    initial_lat: 55.1,
    initial_long: -2,
    initial_zoom: 5,
    scroll_wheel_zoom: true,
    click_zoom: false, // zoom in and recentre on grid square after clicking map
    bing_api_key: '',
    proxy: '',
    presetLayers: [],
    tilecacheLayers: [],
    indiciaWMSLayers: {},
    indiciaWFSLayers : {},
    layers: [],
    clickableLayers: [],
    clickableLayersOutputMode: 'popup', // options are popup, div or customFunction
    clickableLayersOutputFn: format_selected_features,
    clickableLayersOutputDiv: '',
    clickableLayersOutputColumns: [],
    allowBox: true, // can disable drag boxes for querying info, so navigation works
    featureIdField: '',
    clickPixelTolerance: 5,
    reportGroup: null, // name of the collection of report outputs that this map is linked to when doing dashboard reporting
    locationLayerName: '', // define a feature type that can be used to auto-populate the location control when clicking on a location
    locationLayerFilter: '', // a cql filter that can be used to limit locations shown on the location layer
    controls: [],
    standardControls: ['layerSwitcher','panZoom'],
    activatedStandardControls: ["hoverFeatureHighlight","graticule"],
    toolbarDiv: 'map', // map, top, bottom, or div ID
    toolbarPrefix: '', // content to prepend to the toolbarDiv content if not on the map
    toolbarSuffix: '', // content to append to the toolbarDiv content if not on the map
    helpDiv: false,
    editLayer: true,
    clickForSpatialRef: true, // if true, then enables the click to get spatial references control
    clickForPlot: false, // if true, overrides clickForSpatialRef to locate a plot instead of a grid square.
    allowPolygonRecording: false,
    autoFillInCentroid: false, // if true will automatically complete the centroid and Sref when polygon recording.
    editLayerName: 'Selection layer',
    editLayerInSwitcher: false,
    searchLayer: false, // determines whether we have a separate layer for the display of location searches, eg georeferencing. Defaults to editLayer.
    searchUpdatesSref: false,
    searchDisplaysPoint: true,
    searchLayerName: 'Search layer',
    searchLayerInSwitcher: false,
    initialFeatureWkt: null,
    initialBoundaryWkt: null,
    defaultSystem: 'OSGB',
    latLongFormat: 'D',
    srefId: 'imp-sref',
    srefLatId: 'imp-sref-lat',
    srefLongId: 'imp-sref-long',
    srefSystemId: 'imp-sref-system',
    geomId: 'imp-geom',
    plotShapeId: 'attr-shape', // html id of plot shape control. Can be 'rectangle' or 'circle'.
    plotWidthId: 'attr-width', // html id of plot width control for plotShape = 'rectangle'
    plotLengthId: 'attr-length', // html id of plot length control for plotShape = 'rectangle'
    plotRadiusId: 'attr-radius', // html id of plot radius control for plotShape = 'circle'
    boundaryGeomId: 'imp-boundary-geom',
    clickedSrefPrecisionMin: '2', // depends on sref system, but for OSGB this would be 2,4,6,8,10 etc = length of grid reference
    clickedSrefPrecisionMax: '10',
    plotPrecision: '10', // when clickForPlot is true, the precision of grid ref associated with plot.
    msgGeorefSelectPlace: 'Select from the following places that were found matching your search, then click on the map to specify the exact location:',
    msgGeorefNothingFound: 'No locations found with that name. Try a nearby town name.',
    msgGetInfoNothingFound: 'No occurrences were found at the location you clicked.',
    msgSrefOutsideGrid: 'The position is outside the range of the selected map reference system.',
    msgSrefNotRecognised: 'The map reference is not recognised.',
    msgSrefSystemNotSet: 'The spatial reference system is not set.',
    msgReplaceBoundary: 'Would you like to replace the existing boundary with the new one?',
    maxZoom: 19, //maximum zoom when relocating to gridref, postcode etc.
    maxZoomBuffer: 0.67, //margin around feature when relocating to gridref
    drawObjectType: 'boundary',

    //options for OpenLayers. Feature. Vector. style
    fillColor: '#ee9900',
    fillOpacity: 0.4,
    strokeColor: '#ee9900',
    strokeOpacity: 1,
    strokeWidth: 1,
    strokeLinecap: 'round',
    strokeDashstyle: 'solid',
    hoverFillColor: 'white',
    hoverFillOpacity: 0.8,
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
    // Additional options for OpenLayers.Feature.Vector.style for the ghost
    fillColorGhost: '#777777',
    fillOpacityGhost: 0.3,
    strokeColorGhost: '#ee9900',
    strokeOpacityGhost: 1,
    strokeDashstyleGhost: 'dash',
    // Additional options for OpenLayers.Feature.Vector.style for a boundary
    fillColorBoundary: '#0000FF',
    fillOpacityBoundary: 0.1,
    strokeColorBoundary: '#FF0000',
    strokeWidthBoundary: 2,
    strokeDashstyleBoundary: 'dash',
    // hint for the grid ref you are over
    gridRefHint: false,

    // Are we using the OpenLayers defaults, or are they all provided?
    useOlDefaults: true,
    rememberPos: false, // set to true to enable restoring the map position when the page is reloaded. Requires jquery.cookie plugin.
    hintNavigation: 'Select this tool navigate around the map by dragging, or double clicking to centre the map.',
    hintScrollWheel: ' Using the scroll bar whilst over the map will zoom in and out.',
    hintClickSpatialRefTool: 'Select this tool to enable clicking on the map to set your location',
    hintQueryDataPointsTool: 'Select this tool then click on or drag a box over data points on the map to view the underlying records.',
    hintDrawPolygonHint: 'Select this tool to draw a polygon, clicking on the map to draw the shape and double clicking to finish.',
    hintDrawLineHint: 'Select this tool to draw a line, clicking on the map to draw the shape and double clicking to finish.',
    hintDrawPointHint: 'Select this tool to draw points by clicking on the map.',
    hintDrawForReportingHint: 'You can then filter the report for intersecting records.',
    hintClearSelection: 'Clear the edit layer',
    hintModifyFeature: 'Modify the selected feature. Grab and drag the handles or double click on lines to add new handles.',
    hlpClickOnceSetSref: 'Click once on the map to set your location.',
    hlpClickAgainToCorrect: 'Click on the map again to correct your position if necessary.',
    hlpPanZoom: 'Pan and zoom the map to the required place by dragging the map and double clicking or Shift-dragging to zoom.',
    hlpPanZoomButtons: 'Pan and zoom the map to the required place using the navigation buttons or '+
        'by dragging the map and double clicking or Shift-dragging to zoom.',
    hlpZoomChangesPrecision: 'By zooming the map in or out before clicking you can alter the precision of the '+
        'selected grid square.',
    helpToPickPrecisionMin: false,
    helpToPickPrecisionMax: 10,
    helpToPickPrecisionSwitchAt: false,
    hlpImproveResolution1: "{size} square selected. Please click on the map again to provide a more accurate location.",
    hlpImproveResolution2: "Good. {size} square selected.",
    hlpImproveResolution3: "Excellent! {size} square selected. If your position is wrong, either click your actual position again or zoom out until your position comes to view, then retry.",
    hlpImproveResolutionSwitch: "We've switched to a satellite view to allow you to locate your position even better."

};

/**
 * Default options to pass to the openlayers map constructor
 */
jQuery.fn.indiciaMapPanel.openLayersDefaults = {
    projection: 3857,
    displayProjection: 4326,
    units: "m",
    numZoomLevels: 18,
    maxResolution: 156543.0339,
    maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34)
};


/**
 * Settings for the georeference lookup.
 */
jQuery.fn.indiciaMapPanel.georeferenceLookupSettings = {
    georefSearchId: 'imp-georef-search',
    georefSearchBtnId: 'imp-georef-search-btn',
    georefCloseBtnId: 'imp-georef-close-btn',
    georefOutputDivId: 'imp-georef-output-div',
    georefDivId: 'imp-georef-div'
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
