

var selectedFeature = null;
var sectionDetailsChanged = false;
var clearSection, loadSectionDetails, confirmSelectSection, selectSection, asyncPost, deleteWalks,
    deleteLocation, deleteSections, deleteSection;

(function ($) {

clearSection = function() {
  $('#section-location-id').val('');
  $('#section-location-sref').val('');
  $('#section-location-system,#section-location-system-select').val('');
  // remove exiting errors:
  $('#section-form').find('.inline-error').remove();
  $('#section-form').find('.ui-state-error').removeClass('ui-state-error');
  var nameparts;
  // loop through form controls to make sure they do not have the value id (as these will be new values)
  $.each($('#section-form').find(':input[name]'), function(idx, ctrl) {
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
    } else if ($(ctrl).hasClass('hierarchy-select')) {
      $(ctrl).val('');
    }
  });
};

loadSectionDetails = function(section) {
  clearSection();
  if (typeof indiciaData.sections[section]!=="undefined") {
    $('#section-location-id').val(indiciaData.sections[section].id);
    // if the systems on the section and main location do not match, copy the the system and sref from the main site.
    if(indiciaData.sections[section].system !== $('#imp-sref-system').val()) {
        $('#section-location-sref').val($('#imp-sref').val());
        $('#section-location-system,#section-location-system-select').val($('#imp-sref-system').val());
    } else {
        $('#section-location-sref').val(indiciaData.sections[section].sref);
        $('#section-location-system,#section-location-system-select').val(indiciaData.sections[section].system);
    }
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
                radioidx++;
              }
              if ($('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).length>0 && 
                  $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).val()===attr.raw_value) {
                $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).attr('checked', true);
              }
            } else if ($('#section-form #fld-locAttr\\:'+attr.location_attribute_id).length>0) {
              // a hierarchy select outputs a fld control, which needs a special case
              $('#section-form #fld-locAttr\\:'+attr.location_attribute_id).val(attr.raw_value);              
              $('#section-form #fld-locAttr\\:'+attr.location_attribute_id).attr('name',attrname);
              // check the option is already in the drop down.
              if ($('#section-form #locAttr\\:'+attr.location_attribute_id + " option[value='"+attr.raw_value+"']").length===0) {
                // no - we'll just put it in at the top level
                // @todo - should really now fetch the top level in the hierarchy then select that.
                $('#section-form #locAttr\\:'+attr.location_attribute_id).append('<option value="' + 
                    attr.raw_value + '">' + attr.value + '</option>');
              }
              $('#section-form #locAttr\\:'+attr.location_attribute_id).val(attr.raw_value);
            } else {              
              $('#section-form #locAttr\\:'+attr.location_attribute_id).val(attr.raw_value);              
              $('#section-form #locAttr\\:'+attr.location_attribute_id).attr('name',attrname);
            }
          });
        }
    );
  }
};

confirmSelectSection = function(section, doFeature, withCancel) {
  var buttons =  { 
    "Yes": function() {
          dialog.dialog('close');
          $('#section-form').submit(); // this is synchronous
          selectSection(section, doFeature);
          $(this).unbind(event);
        },
      "No":  function() {
          dialog.dialog('close');
          selectSection(section, doFeature);
        }
     };
  if(withCancel) {
    buttons.Cancel = function() { dialog.dialog('close'); };
  }

  if(sectionDetailsChanged === true) {
    var dialog = $('<p>'+indiciaData.sectionChangeConfirm+'</p>').dialog({ title: "Save Data?", buttons: buttons });
  } else {
    selectSection(section, doFeature);
  }
};

selectSection = function(section, doFeature) {
  sectionDetailsChanged = false;
  // if the modify control is active, save any changes, unselect any currently selected feature
  // do this before changing the selection so that the previous selection is tidied up properly.
  if (typeof indiciaData.mapdiv !== "undefined") {
    if(typeof indiciaData.modifyFeature !== "undefined") {
      indiciaData.modifyFeature.deactivate();
    }
    if (doFeature && typeof indiciaData.selectFeature !== "undefined") {
      indiciaData.selectFeature.unselectAll();
    }
  }
  $('.section-select li').removeClass('selected');
  $('#section-select-route-'+section).addClass('selected');
  $('#section-select-'+section).addClass('selected');
  // don't select the feature if this was triggered by selecting the feature (as opposed to the button) otherwise we recurse.
  if (typeof indiciaData.mapdiv !== "undefined") {
    if (doFeature && typeof indiciaData.selectFeature !== "undefined") {
      $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
        if (feature.attributes.section===section) {
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
};

asyncPost = function(url, data) {
  $.ajax({
    type: 'POST',
    url: url,
    data: data,
    success: function(data) {
      if (typeof(data.error)!=="undefined") {
        alert(data.error);
      }
    },
    dataType: 'json',
    // cannot be synchronous otherwise we navigate away from the page too early
    async: false
  });
};

deleteWalks = function(walkIDs) {
  $.each(walkIDs, function(i, walkID) {
    $('#delete-transect').html('Deleting Walks ' + (Math.round(i/walkIDs.length*100)+'%'));
    var data = {
      'sample:id':walkID,
      'sample:deleted':'t',
      'website_id':indiciaData.website_id
    };
    asyncPost(indiciaData.ajaxFormPostSampleUrl, data);
  });
  $('#delete-transect').html('Deleting Walks 100%');
};

deleteLocation = function(ID) {
  var data = {
    'location:id':ID,
    'location:deleted':'t',
    'website_id':indiciaData.website_id
  };
  asyncPost(indiciaData.ajaxFormPostUrl, data);
};

// delete a set of sections. Does not re-index the other section codes.
deleteSections = function(sectionIDs) {
  $.each(sectionIDs, function(i, sectionID) {
    $('#delete-transect').html('Deleting Sections ' + (Math.round(i/sectionIDs.length*100)+'%'));
    deleteLocation(sectionID);
  });
  $('#delete-transect').html('Deleting Sections 100%');
};

//delete a section
deleteSection = function(section) {
  var data;
  // section comes in like "S1"
  // TODO Add progress bar
  $('.remove-section').addClass('waiting-button');
  // if it has been saved, delete any subsamples lodged against it.
  if(typeof indiciaData.sections[section] !== "undefined"){
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/sample?location_id=" + indiciaData.sections[section].id +
            "&mode=json&view=detail&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
      function(sdata) {
        if (typeof sdata.error==="undefined") {
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
    data = {'location:id':indiciaData.sections[section].id,'location:deleted':'t','website_id':indiciaData.website_id};
    $.post(indiciaData.ajaxFormPostUrl,
          data,
          function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
          'json');
  }
  // loop through all the subsections with a greater section number
  // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
  // Update the code and the name for the locations.
  // Note that the subsections may not have been saved, so may not exist.
  var numSections = parseInt($('[name='+indiciaData.numSectionsAttrName.replace(/:/g,'\\:')+']').val(),10);
  for(var i = parseInt(section.substr(1))+1; i <= numSections; i++){
    if(typeof indiciaData.sections['S'+i] !== "undefined"){
      data = {'location:id':indiciaData.sections['S'+i].id,
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
  data = {'location:id':$('#location\\:id').val(), 'website_id':indiciaData.website_id};
  data[indiciaData.numSectionsAttrName] = ''+(numSections-1);
  // reload the form when all ajax done.
  $('.remove-section').ajaxStop(function(event){    
    window.location = window.location.href.split('#')[0]; // want to GET even if last was a POST. Plus don't want to go to the tab bookmark after #
  });
  $.post(indiciaData.ajaxFormPostUrl,
          data,
          function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
          'json');
};

//insert a section
insertSection = function(section) {
  var data;
  // section comes in like "S1"
  // TODO Add progress bar
  $('.insert-section').addClass('waiting-button');
  // loop through all the subsections with a greater section number
  // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
  // Update the code and the name for the locations.
  // Note that the subsections may not have been saved, so may not exist.
  var numSections = parseInt($('[name='+indiciaData.numSectionsAttrName.replace(/:/g,'\\:')+']').val(),10);
  for(var i = parseInt(section.substr(1))+1; i <= numSections; i++){
    if(typeof indiciaData.sections['S'+i] !== "undefined"){
      data = {'location:id':indiciaData.sections['S'+i].id,
                  'location:code':'S'+(i+1),
                  'location:name':$('#location\\:name').val() + ' - ' + 'S'+(i+1),
                  'website_id':indiciaData.website_id};
      $.post(indiciaData.ajaxFormPostUrl,
            data,
            function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
            'json');
    }
  }
  // update the attribute value for number of sections.
  data = {'location:id':$('#location\\:id').val(), 'website_id':indiciaData.website_id};
  data[indiciaData.numSectionsAttrName] = ''+(numSections+1);
  // reload the form when all ajax done.
  $('.insert-section').ajaxStop(function(event){    
    window.location = window.location.href.split('#')[0]; // want to GET even if last was a POST. Plus don't want to go to the tab bookmark after #
  });
  $.post(indiciaData.ajaxFormPostUrl,
          data,
          function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
          'json');
};

$(document).ready(function() {

  var doingSelection=false; 
  
  $('#section-form').ajaxForm({
    // must be synchronous, otherwise currentCell could change.
    async: false,
    dataType:  'json',
    complete: function() {
      // 
    },
    success: function(data) {
      // remove exiting errors:
      $('#section-form').find('.inline-error').remove();
      $('#section-form').find('.ui-state-error').removeClass('ui-state-error');
      if(typeof data.errors !== "undefined"){
        for(field in data.errors){
          var elem = $('#section-form').find('[name='+field+']');
          var label = $("<label/>")
					.attr({"for":  elem[0].id, generated: true})
					.addClass('inline-error')
					.html(data.errors[field]);
	      var elementBefore = $(elem).next().hasClass('deh-required') ? $(elem).next() : elem;
          label.insertAfter(elementBefore);
          elem.addClass('ui-state-error');
        }
      } else {
        var current = $('#section-select li.selected').html();
        // store the Sref...
        indiciaData.sections[current].sref = $('#section-location-sref').val();
        indiciaData.sections[current].system = $('#section-location-system-select').val();
        alert('The section information has been saved.');
        sectionDetailsChanged = false;
      }
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
      $('.insert-section').click(function(evt) {
        var current = $('#section-select-route li.selected').html();
        if(confirm(indiciaData.sectionInsertConfirm + ' ' + current + '?')) insertSection(current);
      });
      $('.erase-route').click(function(evt) {
        var current = $('#section-select-route li.selected').html(),
            oldSection = [];
        // If the draw feature control is active unwind it one point at a time.
        for(var i = div.map.controls.length-1; i>=0; i--)
            if(div.map.controls[i].CLASS_NAME == 'OpenLayers.Control.DrawFeature' && div.map.controls[i].active) {
              if(div.map.controls[i].handler.line){
                if(div.map.controls[i].handler.line.geometry.components.length == 2) // start point plus current unselected position)
                  div.map.controls[i].cancel();
                else 
                  div.map.controls[i].undo();
                return;
              }
            }
        current = $('#section-select-route li.selected').html();
        // label a new feature properly (and remove the undefined that appears)
        $.each(div.map.editLayer.features, function(idx, feature) {
          if (feature.attributes.section===current) {
            oldSection.push(feature);
          }
        });
        if (oldSection.length>0 && oldSection[0].geometry.CLASS_NAME==="OpenLayers.Geometry.LineString") {
          if (!confirm('Do you wish to erase the route for this section?')) {
            return;
          } else {
            div.map.editLayer.removeFeatures(oldSection, {});
          }
        } else return; // no existing route to clear
        if (typeof indiciaData.sections[current]=="undefined") {
          return; // not currently stored in database
        }
        // have to leave the location in the website (data may have been recorded against it), but can't just empty the geometry
        var data = {
          'location:boundary_geom':'',
          'location:centroid_geom':oldSection[0].geometry.getCentroid().toString(),
          'location:id':indiciaData.sections[current].id,
          'website_id':indiciaData.website_id
        };
        $.post(
          indiciaData.ajaxFormPostUrl,
          data,
          function(data) {
            if (typeof(data.error)!=="undefined") {
              alert(data.error);
            } else {
              // Better way of doing this?
              $('#section-select-route-'+current).addClass('missing');
              $('#section-select-'+current).addClass('missing');
            }
          },
          'json'
        );

      });
      div.map.parentLayer = new OpenLayers.Layer.Vector('Transect Square', {style: div.map.editLayer.style, 'sphericalMercator': true, displayInLayerSwitcher: true});
      div.map.addLayer(div.map.parentLayer);
      // If there are any features in the editLayer without a section number, then this is the transect square feature, so move it to the parent layer,
      // otherwise it will be selectable and will prevent the route features being clicked on to select them. Have to do this each time the tab is displayed
      // else any changes in the transect will not be reflected here.
      function copy_over_transects()
      {
        $.each(div.map.editLayer.features, function(idx, elem){
          if(typeof elem.attributes.section == "undefined"){
            div.map.parentLayer.destroyFeatures();
            div.map.editLayer.removeFeatures([elem]);
            div.map.parentLayer.addFeatures([elem]);
          }
        });
      }
      copy_over_transects();
      $('.ui-tabs').bind('tabsshow', function(event, ui) {
        function _extendBounds(bounds, buffer) {
            var dy = (bounds.top-bounds.bottom) * buffer;
            var dx = (bounds.right-bounds.left) * buffer;
            bounds.top = bounds.top + dy;
            bounds.bottom = bounds.bottom - dy;
            bounds.right = bounds.right + dx;
            bounds.left = bounds.left - dx;
            return bounds;
        }

        var div, target = ui.panel;
        if((div = $('#'+target.id+' #route-map')).length > 0){
          copy_over_transects();
          div = div[0];
          // when the route map is initially created it is hidden, so is not rendered, and the calculations of the map size are wrong
          // (Width is 100 rather than 100%), so any initial zoom in to the transect by the map panel is wrong.
          var bounds = div.map.parentLayer.getDataExtent();
          if(div.map.editLayer.features.length>0)
            bounds.extend(div.map.editLayer.getDataExtent());
          _extendBounds(bounds,div.settings.maxZoomBuffer);
          div.map.zoomToExtent(bounds);
        }
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
          // make sure the feature is selected: this ensures that it can be modified straight away
          // note that selecting or unselecting the feature triggers the afterfeaturemodified event
          if(selectedFeature != evt.feature) {
            indiciaData.selectFeature.select(evt.feature);
            selectedFeature = evt.feature;
            div.map.editLayer.redraw();
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
          if (indiciaData.defaultSectionGridRef==='parent') {
            // initially set the section Sref etc to match the parent. Geom will be auto generated on the server
            indiciaData.sections[current] = {sref : $('#imp-sref').val(),	system : $('#imp-sref-system').val()};
          } else if (indiciaData.defaultSectionGridRef.match(/^section(Centroid|Start)100$/)) {
            if (typeof indiciaData.srefHandlers!=="undefined" &&
                typeof indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()]!=="undefined") {
              var handler = indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()], pt, sref;
              if (indiciaData.defaultSectionGridRef==='sectionCentroid100') {
                pt = selectedFeature.geometry.getCentroid(true); // must use weighted to accurately calculate
              } else {
                pt = jQuery.extend({}, selectedFeature.geometry.components[0]);
              }
              sref=handler.pointToGridNotation(pt.transform(indiciaData.mapdiv.map.projection, 'EPSG:'+handler.srid), 6);
              indiciaData.sections[current] = {sref : sref,	system : $('#imp-sref-system').val()};
            }
          }
          indiciaData.sections[current].geom = evt.feature.geometry.toString();
          data['location:centroid_sref']=indiciaData.sections[current].sref;
          data['location:centroid_sref_system']=indiciaData.sections[current].system;
          // autocalc section length
          if (indiciaData.autocalcSectionLengthAttrId) {
            data[$('#locAttr\\:'+indiciaData.autocalcSectionLengthAttrId).attr('name')] = Math.round(selectedFeature.geometry.clone().transform(indiciaData.mapdiv.map.projection, 'EPSG:27700').getLength());
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
                indiciaData.sections[current].id = id;
                $('#section-location-id').val(id);
                $('#section-select-route-'+current).removeClass('missing');
                $('#section-select-'+current).removeClass('missing');
                loadSectionDetails(current);
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
    $(evt.target).closest('tr').css('text-decoration','line-through');
    $(evt.target).closest('tr').addClass('ui-state-disabled');
    // clear the underlying value
    $(evt.target).closest('tr').find('input').val('');
  });

  $('#add-branch-coord').click(function(evt) {
    var coordinator=($('#branchCmsUserId')[0]).options[$('#branchCmsUserId')[0].selectedIndex];
    if ($('#branch-coord-'+coordinator.value).length===0) {
      $('#branch-coord-list').append('<tr><td id="branch-coord-'+coordinator.value+'">' +
          '<input type="hidden" name="locAttr:'+indiciaData.locBranchCmsUsrAttr+'::'+coordinator.value+'" value="'+coordinator.value+'"/>'+coordinator.text+'</td>'+
          '<td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>');
    }
  });

});

}(jQuery));