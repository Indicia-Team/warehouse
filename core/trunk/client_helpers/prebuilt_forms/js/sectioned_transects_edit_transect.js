function selectSection() {
  var current = $('#current-section').val();
  indiciaData.selectFeature.unselectAll();
  if (current!=='') {
    $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
      if (feature.attributes.section==current && feature.renderIntent != 'select') {
        indiciaData.selectFeature.select(feature);
      }
    });
    var link = $('#section-edit').attr('href');
    link = link.replace(/[0-9]+$/, indiciaData.sections['s'+current].id);
    $('#section-edit').attr('href',link);
    $('#section-edit').show();
  } else {
    $('#section-edit').hide();
  }
  indiciaData.mapdiv.map.editLayer.redraw();
}

$(document).ready(function() {

  var doingSelection=false; 
  
  // trap drawing of any vector onto the layer
  mapInitialisationHooks.push(function(div) {
    // find the selectFeature control so we can interact with it later
    $.each(div.map.controls, function(idx, control) {
      if (control.CLASS_NAME==='OpenLayers.Control.SelectFeature') {
        indiciaData.selectFeature = control;
        div.map.editLayer.events.on({'featureselected': function(evt) {
          if ($('#current-section').val() != evt.feature.attributes.section) {
            $('#current-section').val(evt.feature.attributes.section);
            selectSection();
          }
        }});
      }
    });
    indiciaData.mapdiv = div;
    
    div.map.editLayer.style = null;
    div.map.editLayer.styleMap = new OpenLayers.StyleMap({
      'default': {
        strokeColor: "#0000FF",
        strokeWidth: 3,
        // label with \n linebreaks
        label : "S${section}",
        fontSize: "16px",
        fontFamily: "Verdana, Arial, Helvetica,sans-serif",
        fontWeight: "bold",
        fontColor: "#FF0000",
        labelAlign: "cm",
        strokeDashstyle: "dash"
      }, 'select':{
        strokeColor: "#00FFFF",
        strokeWidth: 5,
        // label with \n linebreaks
        label : "S${section}",
        fontSize: "16px",
        fontFamily: "Verdana, Arial, Helvetica,sans-serif",
        fontWeight: "bold",
        fontColor: "#FF0000",
        labelAlign: "cm",
        strokeDashstyle: "dash"
      }
    });
    // add the loaded section geoms to the map. Do this before hooking up to the featureadded event.
    var f = [];
    $.each(indiciaData.sections, function(idx, section) {
      f.push(new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(section.geom), {section:idx.substr(1), type:"boundary"}));
    });
    div.map.editLayer.addFeatures(f);
    if (f.length>0) {
      div.map.zoomToExtent(div.map.editLayer.getDataExtent());
    }
  });
  
  $('#add-user').click(function(evt) {
    var user=($('#cmsUserId')[0]).options[$('#cmsUserId')[0].selectedIndex];
    if ($('#user-'+user.value).length===0) {
      $('#user-list').append('<tr><td id="user-'+user.value+'"><input type="hidden" name="locAttr:'+indiciaData.locCmsUsrAttr+'::'+user.value+'" value="'+user.value+'"/>'+
          user.text+'</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>');
    }
  });
  
  $('.remove-user').live('click', function(evt) {
    $(evt.target).parents('tr').css('text-decoration','line-through');
    $(evt.target).parents('tr').addClass('ui-state-disabled');
    // clear the underlying value
    $(evt.target).parents('tr').find('input').val('');
  });

});