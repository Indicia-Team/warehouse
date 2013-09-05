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

if(typeof OpenLayers != 'undefined'){
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

resize_radio_groups = function(selector){
  $(selector).find('.radio_group_container').each(function(idx,elem){
    var maxWidth = 0;
    $(elem).prev('label').width('auto');
    $(elem).find('div').each(function(index,div){
      if($(div).width()>maxWidth) maxWidth = $(div).width();
    });
    $(elem).find('div').not(':last').css('min-width', maxWidth);
    var required = $(elem).next('.deh-required');
    if(required.length>0) $(elem).append(required);
  });
}

set_up_relationships = function(startAttr, parent, setval, duplicates){
  var resetChildValue = function(child){
    if (child.length==1 && child.is('select')){
      var options = child.find('option').not('[value=]').not('[disabled]');
      if (options.length==1)
        child.val(options.val());
      else child.val('');
    } else {
      var options = child.not('[disabled]');
      if (options.length==1)
        options.attr('checked','checked');
      else child.removeAttr('checked');
    }
  };
  var scanForSelector = function(firstRow, selector){
    var children = [];
    for( ; children.length == 0 && firstRow.length > 0; firstRow = firstRow.next().not('.first')){
      children = firstRow.find(selector);
    }
    return children.length > 0 ? children : false;
  }
  var scanForAttr = function(firstRow, attrID){
    return(scanForSelector(firstRow, '[name$=occAttr\:'+attrID+'],[name*=occAttr\:'+attrID+'\:]'));
  }
  var getDisableableElements = function(attr){
    return (attr.length==1 && attr.is('select') ? attr.find('option').not('[value=]') : attr);
  }
  var getAttrVal = function(attr){
    if (typeof attr == "undefined") return '';
    if (attr.length==1 && attr.is('select'))
      return attr.val();
    var checked = attr.filter(':checked');
    if(checked.length == 0) return '';
    return checked.val();
  }
  // parent holds the item that has changed.
  start=false;
  var myParentRow = jQuery(parent).closest('tr');
  for( myParentRow = jQuery(myParentRow[0]); !myParentRow.hasClass('first') ; myParentRow = myParentRow.prev() );
  for(var i=1; i < attrRestrictionsProcessOrder.length - (duplicates ? 1 : 0); i++){ // don't do first as has no parent, and when duplicate checking don't do last attribute - this is handled later.
    if(start || startAttr==attrRestrictionsProcessOrder[i]){ // skip through list until we reach the attr to start with.
      start=true; // bubble - process all subsequent attributes as well.
      var child = scanForAttr(myParentRow, attrRestrictionsProcessOrder[i]);
      if(!child) break;
      var childOptions = getDisableableElements(child);
      var childVal = getAttrVal(child);
      var resetChild=false; // this is if the current value of the child is no longer valid at the end.
      if(getAttrVal(parent) == '') { // immediate parent has been cleared so disable everything.
        childOptions.attr('disabled','disabled'); // this leaves the blank.
        if(setval) resetChild=true;
      } else {
        childOptions.removeAttr('disabled'); // initialise everything as enabled.
        for(var j=0; j < relationships.length; j++){ 
          if(relationships[j].child == attrRestrictionsProcessOrder[i]){ // scan through all relationships which feature the child attribute as the child.
            var relParent = scanForAttr(myParentRow, relationships[j].parent);
            if(relParent.length==0) break;
            var relParentVal = getAttrVal(relParent);
            for(var k=0; k < relationships[j].values.length; k++){
              if(relParentVal == relationships[j].values[k].value) {
                childOptions.each(function(index, Element){
                  for(var m=0; m < relationships[j].values[k].list.length; m++){
                    if(relationships[j].values[k].list[m] == $(this).val()){
                      if($(this).val() == childVal && setval) resetChild=true;
                      $(this).attr('disabled','disabled');
                    }}
                });
              }}}}
       }
       if(childVal=='' && setval) resetChild=true;
       if(resetChild) resetChildValue(child);
    }
  }
  if(!duplicates) return;
  // something has changed: now need to go through ALL rows final field, not just ours, and eliminate options which would cause a duplicate.
  // but some of those may have been re-added by the change so have to reset all options!
  var tableRows = jQuery(parent).closest('table').find('.first');
  var lastAttr = attrRestrictionsProcessOrder.length-1;
  jQuery('[name$=occAttr\:'+attrRestrictionsProcessOrder[lastAttr]+'],[name*=occAttr\:'+attrRestrictionsProcessOrder[lastAttr]+'\:]').removeAttr('disabled').find('option').removeAttr('disabled');
  // now separate into groups: the attributes may be on different rows with in a group
  groups=[];
  tableRows.each(function(index, Row){
    var group = {attrs:{}, firstRow:$(Row)};
    var classList = $(Row).attr('class').split(/\s+/);
    jQuery.each( classList, function(index, item){ 
      var parts= item.split(/-/);
      if(parts[0]=='scMeaning') group.species = item;
    });
    var location = scanForSelector($(Row), '.imp-srefX');
    if(location && location.length>0) group.X = location;
    var location = scanForSelector($(Row), '.imp-srefY');
    if(location && location.length>0) group.Y = location;
    for(var j=0; j < attrRestrictionsDuplicateAttrList.length; j++){
      var child = scanForAttr($(Row), attrRestrictionsDuplicateAttrList[j]);
      if(child && child.length>0) group.attrs[attrRestrictionsDuplicateAttrList[j]] = child;
    }
    groups.push(group);
  });
  // first do all the final field relationships.
  $.each(groups, function(index, group){
    var child = group.attrs[attrRestrictionsProcessOrder[lastAttr]];
    var childOptions = getDisableableElements(child);
    var childVal = getAttrVal(child);
    var resetChild=false;
    if(getAttrVal(group.attrs[attrRestrictionsProcessOrder[lastAttr-1]]) == '') {
      childOptions.attr('disabled','disabled'); // immediate parent is empty so all disabled.
      if(setval && myParentRow[0]==group.firstRow[0]) resetChild=true;
    } else {
      for(var j=0; j < relationships.length; j++){
        if(relationships[j].child == attrRestrictionsProcessOrder[lastAttr]){
          var relParentVal = getAttrVal(group.attrs[relationships[j].parent]);
	      for(var k=0; k < relationships[j].values.length; k++){
	        if(relParentVal == relationships[j].values[k].value) {
	          childOptions.each(function(index, Element){
	            for(var m=0; m < relationships[j].values[k].list.length; m++){
	              if(relationships[j].values[k].list[m] == $(this).val()){
	                if($(this).val() == childVal && setval && myParentRow[0]==group.firstRow[0]) resetChild=true;
	                $(this).attr('disabled','disabled');
	            }}
	          });
	        }
	      }
        }
      }
    }
    if(childVal=='' && setval && myParentRow[0]==group.firstRow[0]) resetChild=true;
    if(resetChild) resetChildValue(child);
  });
  // now check for duplicates.
  $.each(groups, function(index, group){
    var resetChild=false;
    var myLastVal = getAttrVal(group.attrs[attrRestrictionsProcessOrder[lastAttr]]);
    var childOptions = getDisableableElements(group.attrs[attrRestrictionsProcessOrder[lastAttr]]);
    $.each(groups, function(idx, innerGroup){
      var same=true;
      if(idx == index) return; // don't compare myself to myself
      if(group.species == innerGroup.species) {
        if(typeof group.X != 'undefined' && typeof innerGroup.X != 'undefined' && group.X.val() != innerGroup.X.val())
          same = false;
        else if(typeof group.Y != 'undefined' && typeof innerGroup.Y != 'undefined' && group.Y.val() != innerGroup.Y.val())
          same = false;
        else for(var j=0; j < attrRestrictionsDuplicateAttrList.length; j++){ 
          if(attrRestrictionsDuplicateAttrList[j] == attrRestrictionsProcessOrder[lastAttr]) continue; // don't check last attribute.
          var myVal = getAttrVal(group.attrs[attrRestrictionsDuplicateAttrList[j]]);
          var otherVal = getAttrVal(innerGroup.attrs[attrRestrictionsDuplicateAttrList[j]]);
          if(myVal != otherVal) same=false;
        }
      } else
        same = false;
      var otherVal = getAttrVal(innerGroup.attrs[attrRestrictionsProcessOrder[lastAttr]]);
      // where all the other parents in the relationships are the same on this row, and the value is not empty
      // and we have changed a value in a row (ie myParentRow), then that row is the one that gets reset if a duplicate row is created.
      // ie myParentRow is one that will have option removed, not Row
      if(same && (myLastVal!=otherVal || myParentRow[0]==group.firstRow[0])){
        if(setval && otherVal == myLastVal) resetChild=true;
        if(otherVal!='')
          childOptions.each(function(index, Element){
            if(otherVal == $(this).val()){
              if($(this).val() == myLastVal && setval && myParentRow[0]==group.firstRow[0]) resetChild=true;
              $(this).attr('disabled','disabled');
            }});
      }
    });
    if(getAttrVal(group.attrs[attrRestrictionsProcessOrder[lastAttr]]) =='' && setval && myParentRow[0]==group.firstRow[0]) resetChild=true;
    if(resetChild) resetChildValue(group.attrs[attrRestrictionsProcessOrder[lastAttr]]);
  });
};

set_up_last_relationship = function(child, childAttrID, parentAttrID, setval){
  var scanForAttr = function(firstRow, attrID){
    var children = [];
    for( ; children.length == 0 && firstRow.length > 0; firstRow = firstRow.next().not('.first')){
      children = firstRow.find('[name$=occAttr\:'+attrID+'],[name*=occAttr\:'+attrID+'\:]');
    }
    return children.length > 0 ? children : false;
  }
  var myParentRow =$(child).closest('tr');
  for( myParentRow = jQuery(myParentRow[0]); !myParentRow.hasClass('first') ; myParentRow = myParentRow.prev() );
  var parent = scanForAttr(myParentRow, parentAttrID);
  set_up_relationships(childAttrID, parent, setval, attrRestrictionsDuplicates);
};

var _setHighlight = function(myRow) {
  if(jQuery(myRow).hasClass('highlight')) return;
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
    // keep zoom same, just move to centre location we are intested in, if feature is not already onscreen.
    if(!map.map.editLayer.features[0].onScreen()){
      var bounds=map.map.editLayer.features[0].geometry.bounds.clone();
      map.map.setCenter(bounds.getCenterLonLat());
    }
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
      var parser = new OpenLayers.Format.WKT(); // should already be in map projection
      var feature = parser.read(wkt);
      feature.attributes.type = 'enteredSref'; // so outside polygon is done.
      layer.removeAllFeatures();
      layer.addFeatures([feature]);
      var highlighted = jQuery('.highlight').filter('.first');
      if(highlighted.length>0) // a clone of the feature added to the layer is stored.
        highlighted.data('feature',feature.clone());
      // keep zoom same, just move to centre location we are intested in, if feature is not already onscreen.
      if(!feature.onScreen()){
        var bounds=feature.geometry.bounds.clone();
        layer.map.setCenter(bounds.getCenterLonLat());
      }
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
    function setRowControl(value,row){
      for(var i = 0; i < options.rowControl.controls.length; i++){
        for(var j = 0; j < options.rowControl.controls[i].rows.length; j++){
          var considerRow = $('.group-'+row+'-'+options.rowControl.controls[i].rows[j]);
          if(value== options.rowControl.controls[i].meaning_id){
            considerRow.find('input,select').removeAttr('disabled');
            considerRow.find('label').css('opacity','');
            considerRow.find('.deh-required').show();
            considerRow.find('.XrequiredX').addClass('required').removeClass('XrequiredX');
          } else {
            considerRow.find('input,select').attr('disabled','disabled');
            considerRow.find('label').css('opacity',0.25);
            considerRow.find('.deh-required').hide();
            considerRow.find('select,:text').val('');
            considerRow.find(':radio').removeAttr('checked');
            considerRow.find('.required').addClass('XrequiredX').removeClass('required');
            considerRow.find('.ui-state-error').removeClass('ui-state-error');
            considerRow.find('.inline-error').remove();
          }
        }
      };
    };
    var control = row.find('.'+options.rowControl.selector).find('input');
    if(control.length > 0){
      // for radio buttons, the change is fired on the new value only.
      (function(control,rownum){control.change(function(){setRowControl($(this).val(),rownum)})})(control,rowNum);
      control = control.filter(':checked');
      setRowControl(control.length>0 ? control.val() : -1,rowNum);
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
          var shown = jQuery(this).find('option').filter(':selected')[0].text;
          if(shown=='' || shown == 'm2')
            jQuery(this).closest('tr').find('.scNumber').removeClass('integer').attr('min',0);
          else 
            jQuery(this).closest('tr').find('.scNumber').addClass('integer').attr('min',1).valid();
        });
      }
    } else {
      row.find('.scNumber').addClass('integer').attr('min',1);
      row.find('.scUnits').find('option').each(function(index, elem){
        if(elem.text == 'm2' || elem.value == '') jQuery(elem).remove();
      }); // may need relationship rebuild
    }
  }
}

function _addNewSpeciesGridRow(data,options){
  var map = jQuery('#map2')[0];
  scRow++;
  $('#'+options.gridId).find('.ui-state-error').removeClass('.ui-state-error');
  $('#'+options.gridId).find('.inline-error').remove();
  var rows=$('#'+options.gridId + '-scClonable > tbody > tr');
  // Replace the tags in the row template with the taxa_taxon_list_ID
  rows.each(function(i, row) {
    var newRow = $(row).clone(true);
    newRow.addClass('added-row').removeClass('scClonableRow').attr('id','').addClass('scMeaning-'+data.taxon_meaning_id);;
    $.each(newRow.children(), function(j, cell) {
      cell.innerHTML = cell.innerHTML.replace(/--TTLID--/g, data.id).replace(/--GroupID--/g, scRow).replace(/--SampleID--/g, '').replace(/--OccurrenceID--/g, '');
    }); 
    newRow.addClass('group-'+scRow).addClass('group-'+scRow+'-'+i).appendTo('#'+options.gridId);
  });
  // sc:--GroupID--:--SampleID--:--TTLID--:--OccurrenceID--
  var newRows = $('.group-'+scRow);
  var taxonCell=$(newRows[0]).find('td:eq(1)');
  $(newRows[0]).find('.scPresenceCell input').attr('name', 'sc:'+scRow+'::' + data.id + '::present').val('true');
  $(newRows[0]).data('feature',null);
  // now bolt all functionality in: deliberately separated from above so all rows are deployed into table first.
  newRows.each(function(i, row){
    if(typeof indiciaData.speciesListInTextSelector != "undefined" && $(row).find(indiciaData.speciesListInTextSelector).length > 0)
      bindSupportingSpeciesAutocomplete($(row).find(indiciaData.speciesListInTextSelector)[0], options);
  });
  newRows.each(function(i, row){
    _bindSpeciesGridControls($(row),scRow,options);
    if(indiciaData.resizeSpeciesRadioGroup)
      resize_radio_groups($(row));
  });
  if(typeof attrRestrictionsProcessOrder != 'undefined' && attrRestrictionsProcessOrder.length > 1){
    newRows.each(function(i, row){
      // first set up initial state by triggering first - this will bubble through
      var parent = $(row).find('[name$=occAttr\:'+attrRestrictionsProcessOrder[0]+'],[name*=occAttr\:'+attrRestrictionsProcessOrder[0]+'\:]');
      if(parent.length>0)
        set_up_relationships(attrRestrictionsProcessOrder[1], parent, false, attrRestrictionsDuplicates);
      // Set up what happens when fields are changed - do last one separately.
      for(var i = 0; i < attrRestrictionsProcessOrder.length-1; i++){
        var chngFunc = function(i, me){return function(){
        	set_up_relationships(attrRestrictionsProcessOrder[i+1], $(me), true, attrRestrictionsDuplicates);
        }};
        $(row).find('[name$=occAttr\:'+attrRestrictionsProcessOrder[i]+'],[name*=occAttr\:'+attrRestrictionsProcessOrder[i]+'\:]').each(
          function(idx,elem){
            $(elem).change(
              chngFunc(i, elem)
            );
          }
        );
      }
      // last is special - only updates similar on other rows.
      $(row).find('[name$=occAttr\:'+attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-1]+'],[name*=occAttr\:'+attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-1]+'\\:]').change(function(){
        set_up_last_relationship(this, attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-1], attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-2], true);
      });
      // for duplicate checks have to trigger on all duplicate based fields.
      if(typeof attrRestrictionsDuplicateSelector != "undefined"){
        $(row).find(attrRestrictionsDuplicateSelector).change(function(){
          set_up_last_relationship(this, attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-1], attrRestrictionsProcessOrder[attrRestrictionsProcessOrder.length-2], true);
        });
      }
    });
  }
  // Allow forms to hook into the event of a new row being added
  if (typeof hook_species_grid_changed !== "undefined") {
    hook_species_grid_changed();
  }
  options.formatter(data,taxonCell);
  _setHighlight($(newRows[0]));
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
    row.addClass('group-'+index).addClass('group-'+index+'-'+i);
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
  e.preventDefault();
  // Allow forms to hook into the event of a row being deleted, most likely use would be to have a confirmation dialog
  // This allows language independance.
  if (typeof hook_species_checklist_pre_delete_row !== "undefined") {
    if(!hook_species_checklist_pre_delete_row(e)) return;
  }
  // @TBD unbind all event handlers
  // row is first in group, as this holds the delete button.
  var row = $(e.target.parentNode);
  var numRows = $(e.target).attr('rowspan');

  var map2 = jQuery('#map2');
  if(map2.length>0) {
    map2 = map2[0];
    if(row.data('feature')!=null){
      if(row.data('feature').layer==occurrencePointLayer)
        occurrencePointLayer.destroyFeatures([row.data('feature')]);
      else if(row.data('feature').layer==map2.map.editLayer)
        map2.map.editLayer.destroyFeatures([row.data('feature')]);
    }
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
      considerRow.addClass('deleted-row').hide();
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
  		  if(typeof a1.feature.attributes.type == 'undefined' || (a1.feature.attributes.type != 'clickPoint' && a1.feature.attributes.type != 'enteredSref')) return;
  		  var highlighted = jQuery('.highlight').filter('.first');
  		  if(highlighted.length>0){ // a clone of the feature added to the layer is stored.
  			  highlighted.data('feature',a1.feature.clone());
  			  if(superSampleLocationLayer.features.length > 0){
  			    var inside = true;
  			    // when dealing with a multiple polygon site, it must be outside all polygons - i.e. inside any one.
  			    for(var i = 0; i< superSampleLocationLayer.features.length; i++){
                  // TODO extend to allow buffer
                  if(superSampleLocationLayer.features[i].geometry.CLASS_NAME == 'OpenLayers.Geometry.Polygon'){
                    inside = superSampleLocationLayer.features[i].geometry.containsPoint(a1.feature.geometry);
                  } else if(superSampleLocationLayer.features[i].geometry.CLASS_NAME == 'OpenLayers.Geometry.MultiPolygon'){
                    for(var j = 0, inside = false; j< superSampleLocationLayer.features[i].geometry.components.length; j++){
                      inside = inside || superSampleLocationLayer.features[i].geometry.components[j].containsPoint(a1.feature.geometry);
                    }
                  }  // any non polygon geometries > not a valid check as always outside.
                  if(inside) break;
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
  		if($('#mousePos').length>0){
  	  		jQuery('#map2 .olControlEditingToolbar').append('<span id=\"mousePos2\"></span>');
  	  		var mousePosCtrl = new OpenLayers.Control.MousePosition({
  			  div: document.getElementById('mousePos2'),
  			  prefix: 'LUREF:',
  			  displayProjection: new OpenLayers.Projection('EPSG:2169'),
  			  emptyString: '',
  			  numDigits: 0 
  			});
  			mapdiv.map.addControl(mousePosCtrl);
  		}
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
    if(indiciaData.speciesListInTextMax != '' && container.find('.Speciesname').length >= indiciaData.speciesListInTextMax){
      // use jQuery dialog as it does not stop processing.
      var dialog = $('<p>Warning: You have reached the limit on the number of supporting species you may add.</p>').dialog({ title: "Too Many Supporting Species", buttons: { "OK": function() { dialog.dialog('close'); }}});
      $(event.target).val('');
      return;
    }
    var found=false;
    cell.find('.Speciesname').each(function(idx,elem){ found = found || (elem.innerHTML == data.taxon) });
    if(!found) container.append('<span class="SpeciesNameGroup" ><br /><div class="ui-state-default remove-button"> </div><span class="Speciesname">'+data.taxon+'</span></span>');
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
      container.append('<span class="SpeciesNameGroup" ><br /><div class="ui-state-default remove-button"> </div><span class="Speciesname">'+item+'</span></span>');
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

}
