/**
 * Indicia, the OPAL Online Recording Toolkit.
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

superSampleLocStyleMap = new OpenLayers.StyleMap({"default": new OpenLayers.Style({pointRadius: 10, strokeColor: "Yellow",fillOpacity: 0,strokeWidth: 4})});
superSampleLocationLayer = new OpenLayers.Layer.Vector('SuperSample',{styleMap: superSampleLocStyleMap,displayInLayerSwitcher: false});
defaultoccurrenceStyle = new OpenLayers.Style({pointRadius: 6,fillColor: "Red",fillOpacity: 0.3,strokeColor: "Red",strokeWidth: 1});
selectoccurrenceStyle = new OpenLayers.Style({pointRadius: 6,fillColor: "Blue",fillOpacity: 0.3,strokeColor: "Yellow",strokeWidth: 2});
occurrenceStyleMap = new OpenLayers.StyleMap({"default": defaultoccurrenceStyle, "select": selectoccurrenceStyle});
occurrencePointLayer = new OpenLayers.Layer.Vector('Site Points',{styleMap: occurrenceStyleMap});
selectOccurrenceStyleHash={pointRadius:6,fillColor:'Fuchsia',fillOpacity:0.3,strokeColor:'Fuchsia',strokeWidth:1};

/**
 * Helper methods for additional JavaScript functionality required by the species_checklist control.
 * formatter - The taxon label template, OR a JavaScript function that takes an item returned by the web service 
 * search for a species when adding rows to the grid, and returns a formatted taxon label. Overrides the label 
 * template and controls the appearance of the species name both in the autocomplete for adding new rows, plus for 
  the newly added rows.
 */
var scRow = 0;

var _setHighlight = function(myRow) {
  myRow.closest('table').find('tr').removeClass('highlight');
  var map = jQuery('#map2');
  if(map.length==0) return; // no map
  map = map[0];
  if(map.children.length==0) return; // not created yet.
  var firstRow = myRow;
  myRow.addClass('highlight');
  while(!myRow.hasClass('last')) {
    myRow = myRow.next();
    if(myRow.length==0) break;
    myRow.addClass('highlight');
    if(myRow.find('[id$=imp-sref]').length>0)  map.settings.srefId=myRow.find('[id$=imp-sref]').attr('id').replace(/:/g,'\\:');
    if(myRow.find('[id$=imp-srefX]').length>0) map.settings.srefLatId=myRow.find('[id$=imp-srefX]').attr('id').replace(/:/g,'\\:');
    if(myRow.find('[id$=imp-srefY]').length>0) map.settings.srefLongId=myRow.find('[id$=imp-srefY]').attr('id').replace(/:/g,'\\:');
    if(myRow.find('[id$=imp-geom]').length>0)  map.settings.geomId=myRow.find('[id$=imp-geom]').attr('id').replace(/:/g,'\\:');
  };
  map.map.editLayer.destroyFeatures();
  occurrencePointLayer.removeAllFeatures();
  myRow.closest('table').find('.first').each(function(idx, elem){
    if(jQuery(elem).data('feature')!=null){
      if(firstRow[0]==elem){
        jQuery(elem).data('feature').style=selectOccurrenceStyleHash;
        map.map.editLayer.addFeatures([jQuery(elem).data('feature').clone()]); // add a clone as the editlayer features will be destroyed.
      }else{
        jQuery(elem).data('feature').style=null;
        occurrencePointLayer.addFeatures([jQuery(elem).data('feature')]);
      }
    }
  });
  if(map.map.editLayer.features.length>0){
    // keep zoom same, just move to centre location we are intested in
    var bounds=map.map.editLayer.features[0].geometry.bounds.clone();
    map.map.setCenter(bounds.getCenterLonLat());
  }
  if(jQuery('.sideMap-container').length>0){
    var offset = jQuery('#map2').parent().parent().offset().top;
    offset = (myRow.offset().top+firstRow.offset().top+myRow.height())/2 - offset - jQuery('#map2').height()/2;
    if(offset<0) offset=0;
    jQuery('#map2').parent().css("margin-top", offset+"px"); 
    map.map.events.triggerEvent('zoomend');
  }
};

var _bindSpeciesGridControls = function(row,rowNum,options){

  function handleFocus(event, data) {
    var myRow = $(event.target).closest('tr');
    while(!myRow.hasClass('first')) {
      myRow = myRow.prev();
    }
    _setHighlight(myRow);
  };

  function _handleEnteredSref(value, div) {

    function _projToSystem(proj, convertGoogle) {
      var system = ((typeof proj != "string") ? proj.getCode() : proj);
      if(system.substring(0,5)=='EPSG:') system = system.substring(5);
      if(convertGoogle && system=="900913") system="3857";
      return system;
    }
    function _getSystem() {
      var selector=$('#'+map.settings.srefSystemId);
      if (selector.length===0)
        return map.settings.defaultSystem;
      else
        return selector.val();
    }
    function _showWktFeature(div, wkt, layer) {
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read(wkt);
      layer.removeAllFeatures();
      layer.addFeatures([feature]);
      var bounds=layer.getDataExtent();
      div.map.setCenter(bounds.getCenterLonLat());
    }

    if (value!='')
      $.getJSON(div.settings.indiciaSvc + "index.php/services/spatial/sref_to_wkt?sref=" + value +
          "&system=" + _getSystem() + "&mapsystem=" + _projToSystem(div.map.projection, false) + "&callback=?", function(data) {
        if(typeof data.error != 'undefined')
          alert(data.error);
        else {
          if (div.map.editLayer)
            _showWktFeature(div, data.mapwkt, div.map.editLayer);
          $('#'+div.settings.geomId).val(data.wkt);
        }
      });
  }

  if(row.find('[id$=imp-srefX]').length>0)
    row.find('[id$=imp-srefX]').change(function() {
      var map2 = jQuery('#map2');
      if(map2.length==0) return;
      // Only do something if the long is also populated
      if ($('#'+map2[0].settings.srefLongId).val()!='') {
        // copy the complete sref into the sref field
        $('#'+map2[0].settings.srefId).val($(this).val() + ', ' + $('#'+map2[0].settings.srefLongId).val());
        _handleEnteredSref($('#'+map2[0].settings.srefId).val(), map2[0]);
      }
    });
  if(row.find('[id$=imp-srefY]').length>0)
    row.find('[id$=imp-srefY]').change(function() {
      var map2 = jQuery('#map2');
      if(map2.length==0) return;
      // Only do something if the lat is also populated
      if ($('#'+map2[0].settings.srefLatId).val()!='') {
        // copy the complete sref into the sref field
        $('#'+map2[0].settings.srefId).val($('#'+map2[0].settings.srefLatId).val() + ', ' + $(this).val());
        _handleEnteredSref($('#'+map2[0].settings.srefId).val(), map2[0]);
      }
    });
  row.find('.scCommentLabelCell').each(function(idx,elem){
      jQuery(this).css('width',jQuery(this).find('label').css('width'));
  });
  // normal validation is taken from the database.
  row.find('input,select').bind('focus', handleFocus);
  if(typeof options.rowControl != 'undefined'){
    function setControl(control,row,j){
        control.change(function(){
          if(jQuery(this).filter(':checked').length>0){
            $('.group-'+row+'-'+j).find('input,select').removeAttr('disabled');
            $('.group-'+row+'-'+j).find('label').css('opacity','');
            $('.group-'+row+'-'+j).find('.deh-required').show();
            $('.group-'+row+'-'+j).find('.required').addClass('XrequiredX').removeClass('required');
          } else {
            $('.group-'+row+'-'+j).find('input,select').attr('disabled','disabled');
            $('.group-'+row+'-'+j).find('label').css('opacity',0.25);
            $('.group-'+row+'-'+j).find('.deh-required').hide();
            $('.group-'+row+'-'+j).find('.XrequiredX').addClass('required').removeClass('XrequiredX');
            $('.group-'+row+'-'+j).find('select,:text').val('');
            $('.group-'+row+'-'+j).find(':radio').removeAttr('checked');
            $('.group-'+row+'-'+j).find('.ui-state-error').removeClass('ui-state-error');
            $('.group-'+row+'-'+j).find('.inline-error').remove();
          }
        });
        control.change();
    };
    for(var i = 0; i< options.rowControl.length; i++){
      var control = row.find('.'+options.rowControl[i].selector).filter(':checkbox');
      if(control.length > 0){
        for(var j=0; j< options.rowControl[i].rows.length; j++){
          setControl(control,rowNum,options.rowControl[i].rows[j]);
        }
      }
    }
  }
  if(typeof options.unitSpeciesMeaning != 'undefined'){
    if(row.hasClass('scMeaning-'+options.unitSpeciesMeaning)){
      var units = row.find('.scUnits');
      if(units.length > 0){
        // initially units will not be m2,  but set min to 0: will be set correctly when units selected.
        row.find('.scNumber').attr('min',0);
        units.change(function(){
          jQuery('.ui-state-error').removeClass('ui-state-error');
          jQuery('.inline-error').remove();
          if(jQuery(this).find('option').filter(':selected')[0].text=='m2')
            jQuery(this).closest('tr').find('.scNumber').removeClass('integer').attr('min',0);
          else
            jQuery(this).closest('tr').find('.scNumber').addClass('integer').attr('min',1);
        });
      }
    } else {
      row.find('.scNumber').addClass('integer');
      row.find('.scUnits').find('option').each(function(index, elem){
        if(elem.text == 'm2' || elem.value == '') jQuery(elem).remove();
      });
    }
  }
}

function _addNewSpeciesGridRow(data,options){
  var map = jQuery('#map2')[0];
  var rows=$('#'+options.gridId + '-scClonable > tbody > tr');
  var newRows=[];
  rows.each(function(){newRows.push($(this).clone(true))})
  var taxonCell=newRows[0].find('td:eq(1)');
  scRow++;
  // Replace the tags in the row template with the taxa_taxon_list_ID
  $.each(newRows, function(i, row) {
    row.addClass('added-row').removeClass('scClonableRow').attr('id','').addClass('scMeaning-'+data.taxon_meaning_id);;
    $.each(row.children(), function(j, cell) {
      cell.innerHTML = cell.innerHTML.replace(/--TTLID--/g, data.id).replace(/--GroupID--/g, scRow).replace(/--SampleID--/g, '').replace(/--OccurrenceID--/g, '');
    }); 
    row.addClass('group-'+scRow+'-'+i).appendTo('#'+options.gridId);
  });
  // sc:--GroupID--:--SampleID--:--TTLID--:--OccurrenceID--
  newRows[0].find('.scPresenceCell input').attr('name', 'sc:'+scRow+'::' + data.id + '::present').val('true');
  newRows[0].data('feature',null);
  // now bolt all functionality in: deliberately separated from above so all rows are deployed into table first.
  $.each(newRows, function(i, row){
    if(typeof indiciaData.speciesListInTextSelector != "undefined" && $(row).find(indiciaData.speciesListInTextSelector).length > 0)
      bindSupportingSpeciesAutocomplete($(row).find(indiciaData.speciesListInTextSelector)[0], options);
  });
  $.each(newRows, function(i, row){
    _bindSpeciesGridControls(row,scRow,options);
  });
  // Allow forms to hook into the event of a new row being added
  if (typeof hook_species_grid_changed !== "undefined") {
    hook_species_grid_changed();
  }
  options.formatter(data,taxonCell);
  _setHighlight(newRows[0]);
};

function _addExistingSpeciesGridRow(index,row,options){
  var map = jQuery('#map2')[0];
  var rows=[];
  var myRow = $(row);
  while(!myRow.hasClass('last')) {
    rows.push(myRow);
    myRow = myRow.next();
    if(myRow.length==0) break;
  };
  // now bolt all functionality in: deliberately separated
  $.each(rows, function(i, row){
    row.addClass('group-'+index+'-'+i);
    if(typeof indiciaData.speciesListInTextSelector != "undefined" && $(row).find(indiciaData.speciesListInTextSelector).length > 0)
      bindSupportingSpeciesAutocomplete($(row).find(indiciaData.speciesListInTextSelector)[0], options);
  });
  $.each(rows, function(i, row){
    _bindSpeciesGridControls(row,index,options);
  });
};
	
function bindSpeciesButton(options){
  $('#' + options.selectorID).click(function(){
    _addNewSpeciesGridRow(options.speciesData, options)
  });
  $('#'+options.gridId+' tbody').find('.first').each(function(idx,elem){_addExistingSpeciesGridRow(idx+1,elem,options);});
}

function bindSpeciesAutocomplete(options){
    // Attach auto-complete code to the input
  var handleSelectedTaxon = function(event, data) {
    _addNewSpeciesGridRow(data, options)
    $(event.target).val('');
  };

  ctrl = $('#' + options.selectorID).autocomplete(options.url+'/taxa_taxon_list', {
      extraParams : {
        view : 'detail',
        orderby : 'taxon',
        mode : 'json',
        qfield : 'taxon',
        auth_token: options.auth_token,
        nonce: options.nonce,
        taxon_list_id: options.lookupListId
      },
      max : options.max,
      parse: function(data) {
        var results = [];
        jQuery.each(data, function(i, item) {
          results[results.length] =
          {
            'data' : item,
            'result' : item.taxon,
            'value' : item.id
          };
        });
        return results;
      },
      formatItem: function(item) {
        return item.taxon;
      }
  });
  ctrl.bind('result', handleSelectedTaxon);
  setTimeout(function() { $('#' + ctrl.attr('id')).focus(); });

  $('#'+options.gridId+' tbody').find('.first').each(function(idx,elem){_addExistingSpeciesGridRow(idx+1,elem,options);});
}

$('.remove-row').live('click', function(e) {
  var map2 = jQuery('#map2');
  if(map2.length==0) return;
  map2 = map2[0];
  e.preventDefault();
  // Allow forms to hook into the event of a row being deleted, most likely use would be to have a confirmation dialog
  // This allows language independance.
  if (typeof hook_species_checklist_pre_delete_row !== "undefined") {
    if(!hook_species_checklist_pre_delete_row(e)) return;
  }
  // @TBD unbind all event handlers
  var row = $(e.target.parentNode);
  var numRows = $(e.target).attr('rowspan');
  // row is first in group, as this holds the delete button.
  if(row.data('feature')!=null){
    if(row.data('feature').layer==occurrencePointLayer)
      occurrencePointLayer.destroyFeatures([row.data('feature')]);
    else
      map2.map.editLayer.destroyFeatures([row.data('feature')]);
  }

  if (row.hasClass('added-row')) {
    for(var i=1;i<numRows;i++) row.next().remove();
    row.remove();
  } else {
    // This was a pre-existing occurrence so we can't just delete the row from the grid. Grey it out
    // Use the presence checkbox to remove the taxon, even if the checkbox is hidden.
    // Hide the checkbox so this can't be undone
    row.find('.scPresence').val('false').css('display','none');
    var considerRow = row;
    for(var i=0;i<numRows;i++){
      // disable or remove all other active controls from the row.
      // Do NOT disable the presence checkbox or the container td, otherwise it is not submitted.
      considerRow.addClass('deleted-row').css('opacity',0.25);
      considerRow.find('*:not(.scPresence,.scPresenceCell)').attr('disabled','disabled').removeClass('required ui-state-error').filter('input,select').val('').width('');
      considerRow.find('a').remove();
      considerRow.find('.deh-required,.inline-error').remove();
      considerRow= considerRow.next();
    }
  }
  if (typeof hook_species_grid_changed !== "undefined") {
	  hook_species_grid_changed();
  }
});
// Two places an editlayer feature can be added:
// 1) clicking on the map: We want to store a clone of the feature in the row.
// 2) loading an existing feature. Our code in this case will have cloned the row feature, so do nothing.
mapInitialisationHooks.push(function(mapdiv) {
	// try to identify if this map is the secondary small one
  	if(mapdiv.id=='map2'){
  		var _featureAdded = function(a1){
  		  if(typeof a1.feature.attributes.type == 'undefined' || a1.feature.attributes.type != 'clickPoint') return;
  		  var highlighted = jQuery('.highlight').filter('.first');
  		  if(highlighted.length>0){ // a clone of the feature added to the layer is stored.
  			  highlighted.data('feature',a1.feature.clone());
  			  if(superSampleLocationLayer.features.length > 0){
  			    var inside = null;
  			    for(var i = 0; i< superSampleLocationLayer.features.length; i++){
  			      if(superSampleLocationLayer.features[i].geometry.CLASS_NAME == 'OpenLayers.Geometry.Polygon'){
  			        if(inside === null) inside = false;
  			        // TODO extend to allow buffer
  			        inside = inside || superSampleLocationLayer.features[i].geometry.containsPoint(a1.feature.geometry);
  			      }
  			    }
  			    if(inside===false)
  			      // use jQuery dialog as it does not stop processing.
  			      var dialog = $('<p>Warning: The point you have selected is outside the limits of the site.</p>').dialog({ title: "Outside Site", buttons: { "OK": function() { dialog.dialog('close'); }}});
  			  }
  		  }
  		};

		var ZoomToParent = function(){
		  if(superSampleLocationLayer.features.length > 0) superSampleLocationLayer.map.zoomToExtent(superSampleLocationLayer.getDataExtent());
		};

  		mapdiv.map.editLayer.events.on({featureadded: _featureAdded});
  		Map2Toolbar=OpenLayers.Class(OpenLayers.Control.Panel,
  			{initialize:function(layer,options){
  						OpenLayers.Control.Panel.prototype.initialize.apply(this,[options]);
  						this.addControls([new OpenLayers.Control.Button({displayClass: 'olControlZoomToSite',
  							trigger: ZoomToParent,
  							title: 'Zoom to site'})]);
  				},
  				CLASS_NAME:'Map2Toolbar'});
  		var editControl = new Map2Toolbar(superSampleLocationLayer, {allowDepress: false, 'displayClass':'olControlEditingToolbar'});
  		mapdiv.map.addControl(editControl);
  		editControl.activate();
	}
  	// TBD load existing features
});

jQuery('.remove-button').live('click', function(){
  var cell = $(this).closest('td');
  var container = cell.find('.SpeciesNameList');
  var group = $(this).closest('.SpeciesNameGroup');
  group.remove();
  var names=[];
  cell.find('.Speciesname').each(function(idx,elem){ names.push(elem.innerHTML); });
  if(names.length){
    cell.find(indiciaData.speciesListInTextSelector).val(names.join('|'));
  } else {
    cell.find(indiciaData.speciesListInTextSelector).val('');
    container.empty().append('<label><i>'+indiciaData.None+'</i><label>');
  }
});

function bindSupportingSpeciesAutocomplete(field, options){
    // Attach auto-complete code to the input
  var handleSelectedTaxon = function(event, data) {
    var cell = $(event.target).closest('td');
    var container = cell.find('.SpeciesNameList');
    if(container.find('.Speciesname').length == 0) container.empty();
    container.append('<span class=\"SpeciesNameGroup\" ><br /><div class=\"ui-state-default remove-button\"> </div><span class=\"Speciesname\">'+data.taxon+'</span></span>');
    var names=[];
    cell.find('.Speciesname').each(function(idx,elem){ names.push(elem.innerHTML); });
    cell.find(indiciaData.speciesListInTextSelector).val(names.join('|'));
    $(event.target).val('');
  };

  // convert the attribute to special input control.
  $(field).hide();
  var cell= $(field).closest('td');
  var container = $('<span class="SpeciesNameList"></span>').appendTo(cell);
  if($(field).val() != ''){
    var vals = $(field).val().split('|');
    jQuery.each(vals, function(idx,item){
      container.append('<span><br /><div class="ui-state-default remove-button"> </div><span class="Speciesname">'+item+'</span></span>');
    });
  } else container.append('<label><i>'+indiciaData.None+'</i><label>')
  cell.append('<br /><label class="auto-width">'+indiciaData.speciesListInTextLabel+'</label> <input name="addSupportingSpeciesControl" >');
  // merge into following cell if empty, to give us more room
  var next = cell.next('td');
  if(next.length && next[0].innerHTML==''){
    cell.attr('colspan',next[0].colSpan+1);
    next.remove();
  }
  var ctrl = cell.find('[name=addSupportingSpeciesControl]')
  ctrl = ctrl.autocomplete(options.url+'/taxa_taxon_list', {
      extraParams : {
        view : 'detail',
        orderby : 'taxon',
        mode : 'json',
        qfield : 'taxon',
        auth_token: options.auth_token,
        nonce: options.nonce,
        taxon_list_id: indiciaData.speciesListInTextSpeciesList
      },
      max : options.max,
      parse: function(data) {
        var results = [];
        jQuery.each(data, function(i, item) { results[results.length] = {'data' : item, 'result' : item.taxon, 'value' : item.taxon }; });
        return results;
      },
      formatItem: function(item) { return item.taxon; }
  });
  ctrl.bind('result', handleSelectedTaxon);
  setTimeout(function() { ctrl.focus(); });
}

