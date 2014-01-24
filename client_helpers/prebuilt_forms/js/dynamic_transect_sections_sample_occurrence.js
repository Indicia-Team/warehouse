var setSectionDropDown;

jQuery(document).ready(function($) {

  indiciaData.siteChanged = false;

  /**
   * When viewing the species input tab, if the seelcted site has been changed, then update the sections input controls for 
   * each row to a drop down with the available subsites.
   */
  setSectionDropDown = function(event, ui) {
    if (indiciaData.siteChanged || $('input.scSectionID').length>0) {
      var html, instance, val, id;
      html = '<select id="{id}" name="{name}"><option value="">&lt;Please select&gt;</option>';
      $.each(indiciaData.subsites, function(idx, subsite) {
        html += '<option value="' + subsite.id + '">' + subsite.name + '</option>';
      });  
      html += '</select>';  
      $('.scSectionID').each(function(idx, ctrl) {
        val=ctrl.value, id=ctrl.id;
        instance = $(html.replace('\{id\}', id).replace('\{name\}', ctrl.name));
        $(ctrl).replaceWith(instance);
        instance.val(val);
      });
    }
  };
  
  /**
   * A public method that can be fired when a location is selected in an input control, to load the location's
   * boundary onto the map.
   */
  function locationSelectedInInput(div, val) {
    var intValue = parseInt(val);
    if (!isNaN(intValue)) {
      // Change the location control requests the location's geometry to place on the map.
      $.getJSON(indiciaData.read.url + "index.php/services/data/location?parent_id="+val +
        "&mode=json&view=detail&auth_token=" + indiciaData.read.auth_token + '&nonce=' + indiciaData.read.nonce + "&callback=?", function(data) {
        indiciaData.subsites = data;
        $('#subsites').val(JSON.stringify(data));
        $.each(data, function(idx, subsite) {
          indiciaData.siteChanged = true;
          var geomwkt = subsite.boundary_geom || data[0].centroid_geom;
          var parser = new OpenLayers.Format.WKT();
          var feature = parser.read(geomwkt);
          if (indiciaData.mapdiv.indiciaProjection.projCode!==indiciaData.mapdiv.map.projection.projCode){
            geomwkt = feature.geometry.transform(div.indiciaProjection, indiciaData.mapdiv.map.projection).toString();
          }
          indiciaData.mapdiv.map.editLayer.addFeatures([feature]);
        });
        indiciaData.mapdiv.map.zoomToExtent(indiciaData.mapdiv.map.editLayer.getDataExtent());
      });
    }
  }
  
  mapInitialisationHooks.push(function() {
    if ($('#imp-location').length) {
      var locChange = function() {locationSelectedInInput(indiciaData.mapdiv, $('#imp-location').val());};
      $('#imp-location').change(locChange);
      // trigger change event, incase imp-location was already populated when the map loaded
      locChange();
    }
  });
  
});
