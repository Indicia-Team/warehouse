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

var enableBuffering;

(function ($) {

/**
 * Method which copies the features on a layer into a WKT in a form input.
 */
function storeGeomsInHiddenInput(layer, inputId) {
  "use strict";
  var geoms=[], featureClass='', geom;
  $.each(layer.features, function(i, feature) {
    // ignore features with a special purpose, e.g. the selected record when verifying
    if (typeof feature.tag==="undefined") {
      if (feature.geometry.CLASS_NAME.indexOf('Multi')!==-1) {
        geoms = geoms.concat(feature.geometry.components);
      } else {
        geoms.push(feature.geometry);
      }
    }
  });
  if (geoms.length===0) {
    $('#'+inputId).val('');
  } else {
    if (geoms[0].CLASS_NAME === 'OpenLayers.Geometry.Polygon') {
      geom = new OpenLayers.Geometry.MultiPolygon(geoms);
    } else if (geoms[0].CLASS_NAME === 'OpenLayers.Geometry.LineString') {
      geom = new OpenLayers.Geometry.MultiLineString(geoms);
    } else if (geoms[0].CLASS_NAME === 'OpenLayers.Geometry.Point') {
      geom = new OpenLayers.Geometry.MultiPoint(geoms);
    }
    if (layer.map.projection.getCode() != 'EPSG:3857') {
      geom.transform(layer.map.projection, new OpenLayers.Projection('EPSG:3857'));
    }
    $('#'+inputId).val(geom.toString());
  }
}

function storeGeomsInForm(div) {
  if (typeof bufferLayer==="undefined") {
    storeGeomsInHiddenInput(div.map.editLayer, 'hidden-wkt');
  } else {
    storeGeomsInHiddenInput(div.map.editLayer, 'orig-wkt');
    storeGeomsInHiddenInput(bufferLayer, 'hidden-wkt');
  }
}

function bufferFeature(div, feature) {
  var storeBuffer = function(buffer) {
    feature.buffer = buffer;
    bufferLayer.addFeatures([buffer]);
    indiciaData.buffering = false;
    if (typeof indiciaData.submitting!=="undefined" && indiciaData.submitting) {
      storeGeomsInForm(div);
      $('#run-report').parents('form')[0].submit();
    }
  }
  
  if (typeof feature.geometry!=="undefined" && feature.geometry!==null) {
    if ($('#geom_buffer').val()==='0') {
      storeBuffer(new OpenLayers.Feature.Vector(feature.geometry));
    }
    else {
      indiciaData.buffering = true;
      var geom = feature.geometry.clone();
      // remove unnecessary precision, as we can't sent much data via GET
      if (geom.CLASS_NAME=="OpenLayers.Geometry.LineString") {
        for(var j=0;j<geom.components.length;j++) {
          geom.components[j].x = Math.round(geom.components[j].x);
          geom.components[j].y = Math.round(geom.components[j].y);
        }
      }
      else if(geom.CLASS_NAME=="Polygon") {
        var objFpt = geom.components[0].components;
        for(var i=0;i<objFpt.length;i++) {
          objFpt[i].x = Math.round(objFpt[i].x);
          objFpt[i].y = Math.round(objFpt[i].y);
        }
        objFpt[i-1].x = objFpt[0].x;
      }
      $.ajax({
        url: indiciaData.mapdiv.settings.indiciaSvc + 'index.php/services/spatial/buffer?callback=?',
        data: {'wkt':geom.toString(),'buffer':$('#geom_buffer').val()},
        success: function(buffered) {
          var buffer = new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(buffered.response));
          storeBuffer(buffer);
        },
        dataType: 'jsonp'
      });
    }
  }
}

function rebuildBuffer(div) {
  if (!$('#geom_buffer').val().match(/^\d+$/)) {
    $('#geom_buffer').val(0);
  }
  bufferLayer.removeAllFeatures();
  // re-add each object from the edit layer using the spatial buffering service
  $.each(div.map.editLayer.features, function(idx, feature) {
    bufferFeature(div, feature);
  });
}

enableBuffering = function() {
  // add a mapinitialisation hook to add a layer for buffered versions of polygons
  mapInitialisationHooks.push(function(div) {
    var style = $.extend({}, div.settings.boundaryStyle);
    style.strokeDashstyle = 'dash';
    style.strokeColor = '#ff7777';
    style.fillOpacity = 0.2;
    style.fillColor = '#777777';
    style.pointRadius = 6;
    bufferLayer = new OpenLayers.Layer.Vector(
        'buffer outlines',
        {style: style, 'sphericalMercator': true, displayInLayerSwitcher: false}
    );
    div.map.addLayer(bufferLayer);
    div.map.editLayer.events.register('featureadded', div.map.editLayer, function(evt) {
      // don't buffer special polygons
      if (typeof evt.feature.tag==="undefined") {
        bufferFeature(div, evt.feature);
      }
    });
    div.map.editLayer.events.register('featuresremoved', div.map.editLayer, function(evt) {
      buffers = [];
      $.each(evt.features, function(idx, feature) {
        if (typeof feature.buffer!=="undefined") {
          buffers.push(feature.buffer);
        }
      });
      bufferLayer.removeFeatures(buffers);
    });
    // When exiting the buffer input, recreate all the buffer polygons.
    $('#geom_buffer').blur(function() {rebuildBuffer(div);});
    $('#run-report').click(function(evt) {
      // rebuild the buffer if the user is changing it.
      if (document.activeElement.id==='geom_buffer') {
        rebuildBuffer(div);
      }
      if (typeof indiciaData.buffering!=="undefined" && indiciaData.buffering) {
        // when the buffering response comes back, submit the form
        indiciaData.submitting=true;
        evt.preventDefault();
      } else {        
        storeGeomsInForm(div);
      }
    });
  });
};

}(jQuery));