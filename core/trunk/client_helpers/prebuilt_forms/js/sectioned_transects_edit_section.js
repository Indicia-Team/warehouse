$(document).ready(function() {

  var mapdiv;
  
  mapInitialisationHooks.push(function(div) {
    mapdiv = div;
    
    div.map.editLayer.style = null;
    div.map.editLayer.styleMap = new OpenLayers.StyleMap({
      'default':{
        pointRadius: 5,
        strokeColor: "#0000FF",
        strokeWidth: 2,
        fontSize: "16px",
        fontFamily: "Verdana, Arial, Helvetica,sans-serif",
        fontWeight: "bold",
        fontColor: "#FF0000",
        labelAlign: "cm",
        strokeDashstyle: "solid"
      }, 'select':{
        pointRadius: 5,
        strokeColor: "#00FFFF",
        strokeWidth: 5,
        label : "${section}",
        fontSize: "16px",
        fontFamily: "Verdana, Arial, Helvetica,sans-serif",
        fontWeight: "bold",
        fontColor: "#FF0000",
        labelAlign: "cm",
        strokeDashstyle: "solid"
      }
    });

    // add the loaded section geoms to the map. Do this before hooking up to the featureadded event.
    var f = [], feature, selected;
    var currentLocCode = $('#location_code').val();
    $.each($('#section-geoms input'), function(idx, input) {
      feature = new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT($(input).val()), {section:input.id});
      f.push(feature);
      if (input.id.toLowerCase()===currentLocCode.toLowerCase()) {
        selected=feature;
      }
    });
    div.map.editLayer.addFeatures(f);
    if (typeof selected!=="undefined") {
      selected.renderIntent = 'select';
      div.map.zoomToExtent(selected.geometry.getBounds());
    } else if (f.length>0) {
      div.map.zoomToExtent(div.map.editLayer.getDataExtent());
    } else {
      // first section in the transect so we have nothing to zoom to except the parent geom
      div.map.zoomToExtent(OpenLayers.Geometry.fromWKT($('#parent-geom').val()).getBounds());
    }
    div.map.editLayer.redraw();
    $.each(div.map.controls, function(idx, control) {
      if (control.CLASS_NAME==='OpenLayers.Control.ModifyFeature') {
        control.standalone = true;
        control.events.on({'activate':function() {
          control.selectFeature(selected);
        }});
      }
    });
    div.map.editLayer.events.on({'featuremodified': function(evt) {
      $('#'+currentLocCode).val(evt.feature.geometry.toString());
      $('#boundary_geom').val(evt.feature.geometry.toString());
      evt.feature.renderIntent = 'select';
      div.map.editLayer.redraw();
    }});
    div.map.editLayer.events.on({'featureadded': function(evt) {
      var oldSection = [];
      $.each(evt.feature.layer.features, function(idx, feature) {
        if (feature.attributes.section==currentLocCode && feature != evt.feature) {
          oldSection.push(feature);
        }
      });
      if (oldSection.length>0) {
        if (!confirm('Would you like to replace the existing section with the new one?')) {
          evt.feature.layer.removeFeatures([evt.feature], {});
          return;
        } else {
          evt.feature.layer.removeFeatures(oldSection, {});
        }
      }
      evt.feature.attributes = {'section':currentLocCode};
      evt.feature.renderIntent = 'select';
      selected = evt.feature;
      // store the geom for form posting
      $('#'+currentLocCode).val(evt.feature.geometry.toString());
      $('#boundary_geom').val(evt.feature.geometry.toString());
      div.map.editLayer.redraw();
    }});
  });

});