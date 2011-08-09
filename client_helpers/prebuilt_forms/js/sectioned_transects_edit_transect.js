$(document).ready(function() {

  var mapdiv, selectFeature, doingSelection=false;
  
  function selectSection() {
    console.log('in select section');
    var current = $('#current-section').val();
    selectFeature.unselectAll();
    if (current!=='') {
      $.each(mapdiv.map.editLayer.features, function(idx, feature) {
        if (feature.attributes.section==current && feature.renderIntent != 'select') {
          selectFeature.select(feature);
        }
      });
      var link = $('#section-edit').attr('href');
      link = link.replace(/[0-9]+$/, $('#id-s'+current).val());
      $('#section-edit').attr('href',link);
      $('#section-edit').show();
    } else {
      $('#section-edit').hide();
    }
    mapdiv.map.editLayer.redraw();
  }
  
  $('#current-section').live('change', selectSection);
  
  // trap drawing of any vector onto the layer
  mapInitialisationHooks.push(function(div) {
    // find the selectFeature control so we can interact with it later
    $.each(div.map.controls, function(idx, control) {
      if (control.CLASS_NAME==='OpenLayers.Control.SelectFeature') {
        selectFeature = control;
        div.map.editLayer.events.on({'featureselected': function(evt) {
          console.log('in feature selected');
          if ($('#current-section').val() != evt.feature.attributes.section) {
            console.log('switching to '+evt.feature.attributes.section);
            $('#current-section').val(evt.feature.attributes.section);
            selectSection();
          }
        }});
      }
    });
    mapdiv = div;
    
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
    $.each($('#section-geoms input'), function(idx, input) {
      f.push(new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT($(input).val()), {section:input.id.substr(1), type:"boundary"}));
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
          user.text+'</td><td><span class="remove-user ui-state-default ui-corner-all">x</span></td></tr>');
    }
  });
  
  $('.remove-user').live('click', function(evt) {
    $(evt.target).parents('tr').css('text-decoration','line-through');
    $(evt.target).parents('tr').addClass('ui-state-disabled');
    // clear the underlying value
    $(evt.target).parents('tr').find('input').val('');
  });

});