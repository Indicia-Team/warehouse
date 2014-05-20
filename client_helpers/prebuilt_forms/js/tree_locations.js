

var selectedFeature = null;
var treeDetailsChanged = false;
var clearTree, loadTreeDetails, confirmSelectTree, selectTree, asyncPost,
    deleteLocation, deleteTrees, deleteTree;

(function ($) {

clearTree = function() {
  $('#tree-location-id').attr('disabled',true).val('');
  // parent_id, location_type_id, website_id stay the same
  $('#tree-form [name=location\\:name]').val('');
  $('#tree-form [name=location\\:comment]').val('');
  $('#locations-website-website-id').removeAttr('disabled');
  $('#imp-sref-tree,#imp-geom-tree').val(''); // leave the system unchanged.
  // remove exiting errors:
  $('#tree-form').find('label.error').remove();
  $('#tree-form').find('.error').removeClass('error');
  clearTreeAttributes(true);
  // TODO reset display features.
  $('form#tree-form [name=sample\\:id],[name=occurrence\\:id]').val('').attr('disabled',true);
  $('form#tree-form [name=sample\\:location_name],[name=occurrence\\:taxa_taxon_list_id]').val('');
  $('form#tree-form [name=sample\\:date]').val('');
  // sample_method_id is constant
  // remove photos
  $('form#tree-form .filelist').empty();
};

clearTreeAttributes = function(setDefaults) {
  var nameparts;
  var attrList = $('#tree-form [name^=locAttr\\:]');
  // loop through form attribute controls clear them.
  attrList.filter('.multiselect').remove(); // remove hidden entry for checkboxes.
  $.each(attrList, function(idx, ctrl) {
    nameparts = $(ctrl).attr('name').split(':');
    if(nameparts[1].indexOf('[]') > 0){
      nameparts[1] = nameparts[1].substr(0, nameparts[1].indexOf('[]'));
      $(ctrl).attr('name', nameparts[0] + ':' + nameparts[1]);
    } else if (nameparts.length===3) {
      $(ctrl).attr('name', nameparts[0] + ':' + nameparts[1]);
    }
  });
  attrList.filter('select,:text').val('');
  attrList.filter(':checkbox,:radio').removeAttr('checked');
  // rename checkboxes to add square brackets
  attrList.filter(':checkbox').each(function(idx,elem){
    var name = jQuery(elem).attr('name').split(':');
    var value = jQuery(elem).val().split(':');
    var similar = jQuery('[name=locAttr\\:'+name[1]+'],[name=locAttr\\:'+name[1]+'\\[\\]]').filter(':checkbox'); // [] may have been added on previous iteration of loop
    if(similar.length > 1) // only do this for checkbox groups.
      jQuery(this).attr('name', name[0]+':'+name[1]+'[]');
    if(value.length > 1)
        jQuery(this).val(value[0]);
  });
  if(setDefaults)
    $('#tree-form #locAttr\\:'+indiciaData.assignedRecorderID).val(indiciaData.assignedUsers);
  check_attrs();
}

loadTreeAttributes = function(tree){
  $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location_attribute_value?location_id=" + tree +
        "&mode=json&view=list&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
      function(data) {
          // because of async issues and validation, clear values now
          clearTreeAttributes(false);
          $('#tree-form #locAttr\\:'+indiciaData.assignedRecorderID).val('');
          var attrname, checkboxes; // clearTreeAttributes has reset all values and names to defaults.
          $.each(data, function(idx, attr) {
            if (attr.id===null) return;
            attrname = 'locAttr:'+attr.location_attribute_id+':'+attr.id;
            // Ignore Heirarchy select at moment.
            // special handling for checking radios: note ids are not same as names.
            var checkboxes = $('#tree-form input:checkbox[name=locAttr\\:'+attr.location_attribute_id+'],input:checkbox[name=locAttr\\:'+attr.location_attribute_id+'\\[\\]]');
            if ($('input:radio#locAttr\\:'+attr.location_attribute_id+'\\:0').length>0) {
              $('#tree-form [id^=locAttr\\:'+attr.location_attribute_id+'\\:]').attr('name',attrname);
              $('#tree-form [id^=locAttr\\:'+attr.location_attribute_id+'\\:]').filter('[value='+attr.raw_value+']').attr('checked', true);
            } else if (checkboxes.length > 0) {
              checkboxes.filter('[value='+attr.raw_value+']').attr('name',attrname).attr('checked', true).before('<input class="multiselect" type="hidden" name="'+attrname+'" value="" />');
            } else {              
              $('#tree-form #locAttr\\:'+attr.location_attribute_id).val(attr.raw_value).attr('name',attrname);
            }
          });
          check_attrs();
        }
    );
}

loadTreeDetails = function(location_id) {
  clearTree();
  if (typeof indiciaData.trees[location_id]!=="undefined") {
    $('#tree-location-id').removeAttr('disabled').val(location_id);
    $('#locations-website-website-id').attr('disabled','disabled');
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location/" + location_id +
            "?mode=json&view=detail&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
            function(data) {
              // TODO confirm location type & parent_id
              $('form#tree-form [name=location\\:name]').val(data[0].name);
              $('form#tree-form [name=location\\:centroid_geom]').val(data[0].centroid_geom);
              $('form#tree-form [name=location\\:centroid_sref]').val(data[0].centroid_sref);
              $('form#tree-form [name=location\\:centroid_sref_system]').val(data[0].centroid_sref_system);
              $('form#tree-form [name=location\\:comment]').val(data[0].comment);
              $('form#tree-form [name=sample\\:location_name]').val(data[0].name);
            }
        );
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/sample?location_id=" + location_id + "&sample_method_id=" + indiciaData.treeSampleMethodID +
            "&mode=json&view=detail&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
            function(data) {
              //TODO add error return check
              if(data.length>0){
                $('form#tree-form [name=sample\\:id]').val(data[0].id).removeAttr('disabled');
                // assume date is in in YYYY/MM/DD[+Time] format: convert to DD/MM/YYYY format.
                $('form#tree-form [name=sample\\:date]').val(data[0].date_start.slice(8,10)+'/'+data[0].date_start.slice(5,7)+'/'+data[0].date_start.slice(0,4));
                // sample_method_id is constant
                $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/occurrence?sample_id=" + data[0].id +
                        "&mode=json&view=detail&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
                        function(odata) {
                          //TODO add error return check
                          if(odata.length>0){
                            $('form#tree-form [name=occurrence\\:id]').val(odata[0].id).removeAttr('disabled');
                            $('form#tree-form [name=occurrence\\:taxa_taxon_list_id]').val(odata[0].taxa_taxon_list_id);
                          }
                        }
                    );
              }
            }
        );
    loadTreeAttributes(location_id);
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location_image?location_id=" + location_id +
            "&deleted=f&mode=json&view=list&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
            function(data) {
    	// TODO add error check
              $.each(data, function(idx, file) {
                var existing, uniqueId, thumbnailfilepath, div, count;
                div = $('#container-location_medium-default')[0]; // TODO add error check.
                count = $('.filelist .photo img').length;
                uniqueId = 'existing-image-'+count;
                existing = div.settings.file_box_initial_file_infoTemplate.replace(/\{id\}/g, uniqueId)
                           .replace(/\{filename\}/g, div.settings.msgExistingImage)
                           .replace(/(\{filesize\})/g, '')
                           .replace(/\{imagewidth\}/g, div.settings.imageWidth);
                $('#' + div.id.replace(/:/g,'\\:') + ' .filelist').append(existing);
                $('#' + uniqueId + ' .progress').remove();
                // TODO need to sort out saving on the Warehouse server....
                if (file.id==='') {
                  thumbnailfilepath = div.settings.destinationFolder + file.path;
                } else {
                  thumbnailfilepath = div.settings.finalImageFolder + file.path;
                }
                var origfilepath = div.settings.finalImageFolder + file.path;
                $('#' + uniqueId + ' .photo-wrapper').append(div.settings.file_box_uploaded_imageTemplate
                           .replace(/\{id\}/g, uniqueId)
                           .replace(/\{thumbnailfilepath\}/g, thumbnailfilepath)
                           .replace(/\{origfilepath\}/g, origfilepath)
                           .replace(/\{imagewidth\}/g, div.settings.imageWidth)
                           .replace(/\{captionField\}/g, div.settings.table + ':caption:' + count)
                           .replace(/\{captionValue\}/g, file.caption===null?'':file.caption.replace(/\"/g, '&quot;'))
                           .replace(/\{typeValue\}/g, file.media_type_id)
                           .replace(/\{typeField\}/g, div.settings.table + ':media_type_id:' + count)
                           .replace(/\{typeNameValue\}/g, file.media_type)
                           .replace(/\{typeNameField\}/g, div.settings.table + ':media_type:' + count)
                           .replace(/\{pathField\}/g, div.settings.table + ':path:' + count)
                           .replace(/\{pathValue\}/g, file.path)
                           .replace(/\{deletedField\}/g, div.settings.table + ':deleted:' + count)
                           .replace(/\{deletedValue\}/g, 'f')
                           .replace(/\{isNewField\}/g, 'isNew-' + uniqueId)
                           .replace(/\{isNewValue\}/g, 'f')
                           .replace(/\{idField\}/g, div.settings.table + ':id:' + count) 
                           .replace(/\{idValue\}/g, file.id) // If ID is set, the picture is uploaded to the server
                );
              });
            })
  }
};

confirmSelectTree = function(tree, doFeature, withCancel) {
  var buttons =  { 
    "Yes": function() {
          dialog.dialog('close');
          $('#tree-form').submit(); // this is synchronous
          selectTree(tree, doFeature);
        },
    "No":  function() {
          var current = $('#tree-select li.selected');
          if(current.attr('id')==""){ // New tree does not have id.
            current.remove();
          }
          dialog.dialog('close');
          selectTree(tree, doFeature);
        }
     };

  var current = $('#tree-select li.selected');
  if(current.length==0) { // nothing selected, so can just select straight away
    selectTree(tree, doFeature);
    return;
  }
  if(withCancel) {
    buttons.Cancel = function() { dialog.dialog('close'); }; // this will allow user to abort without selecting.
  }
  // for new trees and a position is selected after data is entered: this is fired
  // also when changing location of an existing tree.
  if(tree === 'new' && $('#tree-location-id').val() == '') return;
  if(tree !== 'new' && $('#tree-location-id').val() == tree) return;
  if(treeDetailsChanged === true) {
    var dialog = $('<p>'+indiciaData.treeChangeConfirm+'</p>').dialog({ title: "Save Data?", buttons: buttons });
  } else {
    selectTree(tree, doFeature);
  }
};

selectTree = function(tree, doFeature) {
  treeDetailsChanged = false;
  $('.tree-select li').removeClass('selected');
  if (doFeature && typeof indiciaData.selectFeature !== "undefined") {
    if(indiciaData.selectFeature.layer.selectedFeatures.length > 0)
      indiciaData.selectFeature.unselectAll();
    selectedFeature = null;
  }
  $('#tree-'+tree).addClass('selected');
  // don't select the feature if this was triggered by selecting the feature (as opposed to the button) otherwise we recurse.
  if (typeof indiciaData.mapdiv !== "undefined") {
    if (doFeature && typeof indiciaData.selectFeature !== "undefined") {
      $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
        if (feature.attributes.id===tree) {
          indiciaData.selectFeature.select(feature);
          selectedFeature = feature;
          if(!feature.onScreen()){
            var bounds=feature.geometry.bounds.clone();
            indiciaData.mapdiv.map.setCenter(bounds.getCenterLonLat());
          }
        }
      });
    }
    if (indiciaData.mapdiv.map.editLayer.selectedFeatures.length===0 && typeof indiciaData.drawFeature !== "undefined") {
      indiciaData.drawFeature.activate();
    }
    indiciaData.mapdiv.map.editLayer.redraw();
  }
  if (indiciaData.currentTree!=tree) {
    loadTreeDetails(tree);
    indiciaData.currentTree=tree;
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

deleteLocation = function(ID) {
  var data = {
    'location:id':ID,
    'location:deleted':'t',
    'website_id':indiciaData.website_id
  };
  asyncPost(indiciaData.ajaxFormPostLocationUrl, data);
};

// delete a set of trees.
deleteTrees = function(treeIDs) {
  $.each(treeIDs, function(i, treeID) {
    $('#delete-site').html('Deleting Trees ' + (Math.round(100*i/(treeIDs.length))+'%'));
    deleteTree(treeID);
  });
  $('#delete-site').html('Deleting Trees 100%');
};

//delete a tree
deleteTree = function(tree) {
  var data;
  $('.remove-tree').addClass('waiting-button');
  // if it has been saved, delete any samples lodged against it.
  if(typeof indiciaData.trees[tree] !== "undefined"){
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/sample?location_id=" + tree +
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
    // then delete the tree record itself
    data = {'location:id':tree,'location:deleted':'t','website_id':indiciaData.website_id};
    $.post(indiciaData.ajaxFormPostLocationUrl,
          data,
          function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
          'json');
  } 
  $('#tree-'+tree).remove();
  var remove = [];
  if(indiciaData.selectFeature.layer.selectedFeatures.length > 0)
      indiciaData.selectFeature.unselectAll();
  selectedFeature = null;
  indiciaData.mapdiv.map.editLayer.clickControl.deactivate();
  indiciaData.selectFeature.activate();
  $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
      if (feature.attributes.id===tree)
        remove.push(feature);
    });
  indiciaData.mapdiv.map.editLayer.removeFeatures(remove);
  clearTree();
  $('.remove-tree').removeClass('waiting-button');
};

//insert a tree
insertTree = function() {
  $('#tree-select li').removeClass('selected');
  if($('#tree-new').length>0) {// new tree already exists
    // TODO add dialog to say to save previous created new tree before next one created.
  } else {
    $('#tree-select').append('<li id="tree-new" class="missing">New Tree</li>');
    $('#tree-new').click(function(evt) {
        var parts = evt.target.id.split('-');
        confirmSelectTree(parts[parts.length-1], true, true); // click on control
    });
  }
  confirmSelectTree('new', true, true); 
};

var errorPos = null;
$(document).ready(function() {
  
  $('#tree-form').ajaxForm({
    async: false,
    dataType:  'json',
    beforeSubmit:   function(data, obj, options){
      $('#tree-form').find('label.error').remove();
      $('#tree-form').find('.error').removeClass('error');
      $('#tree-form').find('label.inline-error').remove();
      $('#tree-form').find('.ui-state-error').removeClass('ui-state-error');
      var valid = true;
      var validator = $('#tree-form').validate({});
      validator.settings.ignoreTitle = true; // some of the 
      if (!$('#tree-form input').valid()) { valid = false; }
      if (!$('#tree-form select').valid()) { valid = false; }
      if(!valid)
        alert('A validation error has occurred with the data you have entered: Please correct the highlighted error then attempt to resubmit.');
      return valid;
    },
    complete: function() {
      // 
    },
    success: function(data) {
      if(typeof data.errors !== "undefined"){
        for(field in data.errors){
          var fieldname = field.replace(/:/g,'\\:');
          var elem = $('#tree-form').find('[name='+fieldname+']');
          var label = $("<label/>")
					.attr({"for":  elem[0].id, generated: true})
					.addClass('error')
					.html(data.errors[field]);
	      var elementBefore = $(elem).next().hasClass('deh-required') ? $(elem).next() : elem;
          label.insertAfter(elementBefore);
          elem.addClass('error');
        }
      } else {
        // this comes back as a sample with a parent location, and child occurrence.
        var location_id = data.struct.parents[0].id;
        var first_date = $('#tree-form [name=sample\\:date]').val();
        var sample_id = data.outer_id;
        var occurrence_id = data.struct.children[0].id;
        var isnew = $('#tree-form [name=location\\:id]').attr('disabled');
        if(typeof isnew == 'undefined') isnew=false;
        $('#tree-form [name=location\\:id]').removeAttr('disabled').val(location_id);
        $('#locations-website-website-id').attr('disabled',true);
        $('#tree-form [name=sample\\:id]').removeAttr('disabled').val(sample_id);
        $('#tree-form [name=occurrence\\:id]').removeAttr('disabled').val(occurrence_id);
        if(typeof indiciaData.trees[location_id] == 'undefined') {
          indiciaData.trees[location_id] = {'id':location_id};
        }
        indiciaData.trees[location_id].name = $('#tree-form [name=location\\:name]').val();
//        indiciaData.trees[location_id].sref = $('#imp-sref-tree').val();
//        indiciaData.trees[location_id].system = $('#imp-sref-system-tree').val();
        $('#tree-select li.selected').html($('#tree-form [name=location\\:name]').val()).attr('id','tree-'+location_id).removeClass('missing');
        selectedFeature.attributes.name = $('#tree-form [name=location\\:name]').val();
        selectedFeature.attributes.id = location_id;
        selectedFeature.layer.redraw();
        treeDetailsChanged = false;
        indiciaData.currentTree = location_id;
        // need to reset the attribute fields: could have saved a new attribute or cleared an old one.
        loadTreeAttributes(location_id);
        $('.filelist .deleted-value[value=t]').closest('.photo').remove();
        // create a dialog
        var buttons =  { 
            "Yes: Create Visit Now": function() {
                  dialog.dialog('close');
                  $('#visit_link').attr('href', indiciaData.visitURL+location_id+(isnew?'&date='+first_date:'')+'&no_referer=1');
                  $('#visit_link').each(function(idex,elem){elem.click();});
                },
            "No Thanks":  function() {
                  dialog.dialog('close');
                }
             };
         var dialog = $('<p>'+(isnew ? indiciaData.newVisitDialog : indiciaData.existingVisitDialog)+'</p>').dialog({ title: "Create Visit Data?", buttons: buttons });
      }
    }
  });  
  
  $('#tree-select li').click(function(evt) {
    var parts = evt.target.id.split('-');
    confirmSelectTree(parts[parts.length-1], true, true); // click on control
  });
  $('#tree-form').find('input,textarea,select').change(function(evt) {
	  treeDetailsChanged = true;
  });

  mapInitialisationHooks.push(function(div) {
    if (div.id==='trees-map') {
      $('.remove-tree').click(function(evt) {
        var current = $('#tree-select li.selected').attr('id').split('-');
        current=current[current.length-1];
        // TODO add check if not selected
        var name = $('#tree-select li.selected').html();
        if(confirm(indiciaData.treeDeleteConfirm + ' ' + name + '?'))
          deleteTree(current);
      });
      $('.insert-tree').click(function(evt) {
        insertTree();
      });
      div.map.parentLayer = new OpenLayers.Layer.Vector('Main Sites', {style: div.map.editLayer.style, 'sphericalMercator': true, displayInLayerSwitcher: true});
      div.map.addLayer(div.map.parentLayer);
      // If there are any features in the editLayer without a tree number, then this is the site feature, so move it to the parent layer,
      // otherwise it will be selectable and will prevent the tree features being clicked on to select them. Have to do this each time the tab is displayed
      // else any changes in the site will not be reflected here.
      function copy_over_sites()
      {
        var i=0;
        while(i < div.map.editLayer.features.length) {
          var feature = div.map.editLayer.features[i];
          if(typeof feature.attributes.id == "undefined"){
            div.map.parentLayer.destroyFeatures();
            div.map.editLayer.removeFeatures([feature]); // this will change the editLayer list, so we need to do Loop, rather than each.
            div.map.parentLayer.addFeatures([feature]);
          } else i++; // only skip to next in list if haven't removed this element: otherwise the following items will have shuffled down.
        };
      }
      copy_over_sites();
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
        if((div = $('#'+target.id+' #trees-map')).length > 0){
          copy_over_sites();
          div = div[0];
          // when the tree map is initially created it is hidden, so is not rendered, and the calculations of the map size are wrong
          // (Width is 100 rather than 100%), so any initial zoom in to the site by the map panel is wrong.
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
        	  confirmSelectTree(evt.feature.attributes.id, false, false); // click on map
          }});
        }
      });
      
      div.map.editLayer.style = null;

      var baseStyle = {
        strokeWidth: 4
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
          label : "${name}",
          fontSize: "16px",
          fontFamily: "Verdana, Arial, Helvetica,sans-serif",
          fontWeight: "bold",
          fontColor: "#FF0000",
          labelAlign: "cm",
          labelYOffset: "-15"
        }
      });
      var defaultStyle = new OpenLayers.Style(), selectedStyle = new OpenLayers.Style();

      defaultStyle.addRules([defaultRule, labelRule]);
      selectedStyle.addRules([selectedRule, labelRule]);
      div.map.editLayer.styleMap = new OpenLayers.StyleMap({
        'default': defaultStyle,
        'select':selectedStyle
      });
      // add the loaded tree geoms to the map. Do this before hooking up to the featureadded event.
      var f = [];
      $.each(indiciaData.trees, function(idx, tree) {
        f.push(new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(tree.geom), {name:tree.name, id:tree.id, type:"boundary"}));
      });
      div.map.editLayer.addFeatures(f);
      // select the feature for any tree that is currently selected.
/*      var current = $('#tree-select li.selected').attr('id').split('-');
      if(current.length==2 && typeof indiciaData.selectFeature !== "undefined"){
        current=current[current.length-1];
        if(indiciaData.selectFeature.layer.selectedFeatures.length > 0)
            indiciaData.selectFeature.unselectAll();
        selectedFeature = null;
        $.each(div.map.editLayer.features, function(idx, feature) {
          if (feature.attributes.id===current) {
            indiciaData.selectFeature.select(feature);
            selectedFeature = feature;
          }
        });
      } else { */
        indiciaData.mapdiv.map.editLayer.clickControl.deactivate();
        indiciaData.selectFeature.activate();
//      }

      function featureChangeEvent(evt) {
        if (evt.feature.attributes.type == 'clickPoint') {
          var current, name;
          current = (selectedFeature === null || typeof selectedFeature.attributes.id ==="undefined") ? 'new' : selectedFeature.attributes.id;
          // label a new feature properly (and remove the undefined that appears)
          name = (selectedFeature === null || typeof selectedFeature.attributes.name ==="undefined") ? $('#tree-select li.selected').html() : selectedFeature.attributes.name;
          evt.feature.attributes = {name:name, id:current, type:"boundary"};
          if (selectedFeature!==null) {
            if (!confirm('Would you like to replace the existing tree location with this new one?')) {
              evt.feature.layer.removeFeatures([evt.feature], {});
              return;
            }
            if(indiciaData.selectFeature.layer.selectedFeatures.length > 0)
                indiciaData.selectFeature.unselectAll();
            evt.feature.layer.removeFeatures([selectedFeature], {});
            selectedFeature=null;
          }
          // make sure the feature is selected: this ensures that it can be modified straight away
          // note that selecting or unselecting the feature triggers the afterfeaturemodified event
          evt.feature.style=null;
          indiciaData.selectFeature.select(evt.feature);
          selectedFeature = evt.feature;
          div.map.editLayer.redraw();
          $('#imp-geom-tree').val(evt.feature.geometry.toString());
//          if (typeof indiciaData.trees[current]!=="undefined") {
//            indiciaData.trees[current] = {sref : $('#imp-sref').val(),
//            		system : $('#imp-sref-system').val()};
//          }
//          indiciaData.trees[current].geom = evt.feature.geometry.toString();
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

});

}(jQuery));