var selectedFeature = null;
var sectionDetailsChanged = false;

function clearSection() {
  $('#section-location-id').val('');
  var nameparts;
  // loop through form controls to make sure they do not have the value id (as these will be new values)
  $.each($('#section-form').find(':input'), function(idx, ctrl) {
    nameparts = $(ctrl).attr('name').split(':');
    if (nameparts[0]==='locAttr') {
      if (nameparts.length===3) {
        $(ctrl).attr('name', nameparts[0] + ':' + nameparts[1]);
      }
      // clear the control's value
      if (typeof ctrl.checked === "undefined") {
        $(ctrl).val('');
      } else {
        $(ctrl).attr('checked', false);
      }
    }
  });
}

function loadSectionDetails(section) {
  clearSection();
  if (typeof indiciaData.sections[section]!=="undefined") {
    $('#section-location-id').val(indiciaData.sections[section].id);
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location_attribute_value?location_id=" + indiciaData.sections[section].id +
        "&mode=json&view=list&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
        function(data) {
          var attrname;
          $.each(data, function(idx, attr) {
            attrname = 'locAttr:'+attr.location_attribute_id;
            if (attr.id!==null) {
              attrname += ':'+attr.id;
            }
            // special handling for checking radios
            if ($('input:radio#locAttr\\:'+attr.location_attribute_id+'\\:0').length>0) {
              var radioidx=0;
              // name the radios with the existing value id
              while ($('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).length>0) {
                $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).attr('name',attrname);
                radioidx++;
              }
              radioidx=0;
              // check the correct radio
              while ($('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).length>0 && 
                  $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).val()!==attr.raw_value) {
                radioidx ++;
              }
              if ($('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).length>0 && 
                  $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).val()===attr.raw_value) {
                $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).attr('checked', true);
              }
            } else {
              $('#section-form #locAttr\\:'+attr.location_attribute_id).val(attr.raw_value);              
              $('#section-form #locAttr\\:'+attr.location_attribute_id).attr('name',attrname);
            }
          });
        }
    );
  }
}

function confirmSelectSection(section, doFeature, withCancel) {
  var buttons =  { 
	  "Yes": function() {
          dialog.dialog('close');
          $('#section-form').submit(); // this is synchronous
          selectSection(section, doFeature);
          $(this).unbind(event);
        },
      "No":  function() {
          dialog.dialog('close');
          selectSection(section, doFeature)
        }
     };
  if(withCancel)
    buttons.Cancel = function() { dialog.dialog('close'); };

  if(sectionDetailsChanged == true) {
    var dialog = $('<p>'+indiciaData.sectionChangeConfirm+'</p>').dialog({ title: "Save Data?", buttons: buttons });
  } else selectSection(section, doFeature);
}

function selectSection(section, doFeature) {
  sectionDetailsChanged = false;
  $('.section-select li').removeClass('selected');
  $('#section-select-route-'+section).addClass('selected');
  $('#section-select-'+section).addClass('selected');
  // don't select the feature if this was triggered by selecting the feature (as opposed to the button) otherwise we recurse.
  if (typeof indiciaData.mapdiv !== "undefined") {
    if(typeof indiciaData.modifyFeature !== "undefined") indiciaData.modifyFeature.deactivate();
    if (doFeature && typeof indiciaData.selectFeature !== "undefined") {
      indiciaData.selectFeature.unselectAll();
      $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
        if (feature.attributes.section===section && feature.renderIntent !== 'select') {
          indiciaData.selectFeature.select(feature);
          selectedFeature = feature;
        }
      });
    }
    if (indiciaData.mapdiv.map.editLayer.selectedFeatures.length===0 && typeof indiciaData.drawFeature !== "undefined") {
      indiciaData.drawFeature.activate();
    }
    indiciaData.mapdiv.map.editLayer.redraw();
  }
  if (indiciaData.currentSection!==section) {
    loadSectionDetails(section);
    indiciaData.currentSection=section;
  }
}

function deleteWalks(walkIDs) {
  for(var i = o; i< walkIDs.length; i++){
    var data = {
      'sample:id':walkIDs[i],
      'sample:deleted':'t',
      'website_id':indiciaData.website_id
    };
    $.post(
      indiciaData.ajaxFormPostSampleUrl, data,
      function(data) {
        if (typeof(data.error)!=="undefined") {
          alert(data.error);
        }
      },
      'json'
    );
  }
}

//delete a section
function deleteSection(section) {
  // section comes in like "S1"
  // TODO Add progress bar
  $('.remove-section').addClass('waiting-button');
  // if it has been saved, delete any subsamples lodged against it.
  if(typeof indiciaData.sections[section] !== "undefined"){
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/sample?location_id=" + indiciaData.sections[section].id +
            "&mode=json&view=detail&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
      function(sdata) {
        if (typeof sdata.error =="undefined") {
          $.each(sdata, function(idx, sample) {
            var postData = {'sample:id':sample.id,'sample:deleted':'t','website_id':indiciaData.website_id};
            $.post(indiciaData.ajaxFormPostSampleUrl, postData,
              function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
              'json');
          });
        }
      }
    );
    // then delete the section record itself
    var data = {'location:id':indiciaData.sections[section].id,'location:deleted':'t','website_id':indiciaData.website_id};
    $.post(indiciaData.ajaxFormPostUrl,
          data,
          function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
          'json');
  }
  // loop through all the subsections with a greater section number
  // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
  // Update the code and the name for the locations.
  // Note that the subsections may not have been saved, so may not exist.
  var i;
  var numSections = $('[name='+indiciaData.numSectionsAttrName.replace(/:/g,'\\:')+']').val();
  for(i = parseInt(section.substr(1))+1; i <= numSections; i++){
    if(typeof indiciaData.sections['S'+i] != "undefined"){
      var data = {'location:id':indiciaData.sections['S'+i].id,
                  'location:code':'S'+(i-1),
                  'location:name':$('#location\\:name').val() + ' - ' + 'S'+(i-1),
                  'website_id':indiciaData.website_id};
      $.post(indiciaData.ajaxFormPostUrl,
            data,
            function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
            'json');
    }
  }
  // update the attribute value for number of sections.
  var data = {'location:id':$('#location\\:id').val(), 'website_id':indiciaData.website_id};
  data[indiciaData.numSectionsAttrName] = ''+(numSections-1);
  $.post(indiciaData.ajaxFormPostUrl,
          data,
          function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
          'json');
  // reload the form.
  $('.remove-section').ajaxStop(function(event){
    location.href = location.href; // want to GET even if last was a POST.
  });
}

$(document).ready(function() {

  var doingSelection=false; 
  
  jQuery('#section-form').ajaxForm({
    // must be synchronous, otherwise currentCell could change.
    async: false,
    dataType:  'json',
    complete: function() {
      // 
    },
    success: function(data) {
      alert('The section information has been saved.');
      sectionDetailsChanged = false;
    }
  });  
  
  $('#section-select li').click(function(evt) {
    var parts = evt.target.id.split('-');
    confirmSelectSection(parts[parts.length-1], true, true);
  });
  $('#section-form').find('input,textarea,select').change(function(evt) {
      sectionDetailsChanged = true;
  });

  mapInitialisationHooks.push(function(div) {
    if (div.id==='route-map') {
      $('#section-select-route li').click(function(evt) {
        var parts = evt.target.id.split('-');
        confirmSelectSection(parts[parts.length-1], true, false);
      });
      $('.remove-section').click(function(evt) {
        var current = $('#section-select-route li.selected').html();
        if(confirm(indiciaData.sectionDeleteConfirm + ' ' + current + '?')) deleteSection(current);
      });
      // find the selectFeature control so we can interact with it later
      $.each(div.map.controls, function(idx, control) {
        if (control.CLASS_NAME==='OpenLayers.Control.SelectFeature') {
          indiciaData.selectFeature = control;
          div.map.editLayer.events.on({'featureselected': function(evt) {
        	  confirmSelectSection(evt.feature.attributes.section, false, false);
          }});
        } else if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature') {
          indiciaData.drawFeature = control;
        } else if (control.CLASS_NAME==='OpenLayers.Control.ModifyFeature') {
          indiciaData.modifyFeature = control;
          control.standalone = true;
          control.events.on({'activate':function() {
            indiciaData.modifyFeature.selectFeature(selectedFeature);
            div.map.editLayer.redraw();
          }});
        }
      });
      
      div.map.editLayer.style = null;
      var baseStyle = {        
        strokeWidth: 4,
        strokeDashstyle: "dash"
      }, defaultRule = new OpenLayers.Rule({
        symbolizer: $.extend({strokeColor: "#0000FF"}, baseStyle)
      }), selectedRule = new OpenLayers.Rule({
        symbolizer: $.extend({strokeColor: "#FFFF00"}, baseStyle)
      });
      // restrict the label style to the type boundary lines, as this excludes the virtual edges created during a feature modify
      var labelRule = new OpenLayers.Rule({
        filter: new OpenLayers.Filter.Comparison({
            type: OpenLayers.Filter.Comparison.EQUAL_TO,
            property: "type",
            value: "boundary"
        }),
        symbolizer: {
          label : "${section}",
          fontSize: "16px",
          fontFamily: "Verdana, Arial, Helvetica,sans-serif",
          fontWeight: "bold",
          fontColor: "#FF0000",
          labelAlign: "cm"
        }
      });
      var defaultStyle = new OpenLayers.Style(), selectedStyle = new OpenLayers.Style();

      defaultStyle.addRules([defaultRule, labelRule]);
      selectedStyle.addRules([selectedRule, labelRule]);
      div.map.editLayer.styleMap = new OpenLayers.StyleMap({
        'default': defaultStyle,
        'select':selectedStyle
      });
      // add the loaded section geoms to the map. Do this before hooking up to the featureadded event.
      var f = [];
      $.each(indiciaData.sections, function(idx, section) {
        f.push(new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(section.geom), {section:'S'+idx.substr(1), type:"boundary"}));
      });
      div.map.editLayer.addFeatures(f);
      // select the first section
      confirmSelectSection('S1', true, false);
      if (f.length>0) {
        div.map.zoomToExtent(div.map.editLayer.getDataExtent());
      }
      
      function featureChangeEvent(evt) {
        // Only handle lines - as things like the sref control also trigger feature change events
        if (evt.feature.geometry.CLASS_NAME==="OpenLayers.Geometry.LineString") {
          var current, oldSection = [];
          // Find section attribute if existing, or selected section button if new
          current = (typeof evt.feature.attributes.section==="undefined") ? $('#section-select-route li.selected').html() : evt.feature.attributes.section;
          // label a new feature properly (and remove the undefined that appears)
          evt.feature.attributes = {section:current, type:"boundary"};
          evt.feature.renderIntent = 'select';        
          div.map.editLayer.redraw();
          $.each(evt.feature.layer.features, function(idx, feature) {
            if (feature.attributes.section===current && feature !== evt.feature) {
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
          // post the new or edited section to the db
          var data = {
            'location:code':current,
            'location:name':$('#location\\:name').val() + ' - ' + current,
            'location:parent_id':$('#location\\:id').val(),
            'location:boundary_geom':evt.feature.geometry.toString(),
            'location:location_type_id':indiciaData.sectionTypeId,
            'website_id':indiciaData.website_id
          };
          if (typeof indiciaData.sections[current]!=="undefined") {
            data['location:id']=indiciaData.sections[current].id;
          } else {
            data['locations_website:website_id']=indiciaData.website_id;
          }
          $.post(
            indiciaData.ajaxFormPostUrl,
            data,
            function(data) {
              if (typeof(data.error)!=="undefined") {
                alert(data.error);
              } else {
                // Better way of doing this?
                var id = data.outer_id;
                indiciaData.sections[current] = {id:id, geom: evt.feature.geometry.toString()};
                $('#section-location-id').val(id);
                $('#section-select-route-'+current).removeClass('missing');
                $('#section-select-'+current).removeClass('missing');
              }
            },
            'json'
          );
        }
      }
      
      div.map.editLayer.events.on({'featureadded': featureChangeEvent, 'afterfeaturemodified': featureChangeEvent}); 
      
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