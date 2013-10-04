/*
 * Function that hides/shows the annotations or boundary fields when the user clicks to edit annotations or boundaries.
 */
function hide_boundary_annotations_panels() { 
  if ($('#tab-boundaries').css('display')==='block') {
    $('#toggle-annotations').attr('value','Add & Edit Boundaries');   
    $('#tab-boundaries').hide();
    $('#annotation-details').show();
    $('#annotations-mode-on').val('yes');
    indiciaData.mapdiv.settings.drawObjectType='annotation';
    //When the user adds a boundary in boundary mode, then this is saved into the imp-boundary-geom field. Any annotation the user has previously added will be
    //in the other-layer-boundary-geom-holder field. These are then processed on submission.
    //However when the user is in annotation mode and adds an annotation, this is saved into the imp-boundary-geom field and any previously drawn boundary is in the 
    //other-layer-boundary-geom-holder.
    //So when the user swaps between boundary and annotation modes, we need to swap the data in the two fields over, this operation
    //requires the use of a 3rd field to temporarily store some of the data so it isn't lost.
    //The system then checks $('#annotations-mode-on') on submission to see which field it needs to look at to get the values for the boundary or annotation.
    $('#boundary-geom-temp').val($('#imp-boundary-geom').val());    
    $('#imp-boundary-geom').val($('#other-layer-boundary-geom-holder').val());
    $('#other-layer-boundary-geom-holder').val($('#boundary-geom-temp').val());
  } else {
    $('#toggle-annotations').attr('value','Add & Edit Annotations');
    $('#tab-boundaries').show();
    $('#annotation-details').hide();
    $('#annotations-mode-on').val('');
    indiciaData.mapdiv.settings.drawObjectType='boundary';
    $('#boundary-geom-temp').val($('#imp-boundary-geom').val());
    $('#imp-boundary-geom').val($('#other-layer-boundary-geom-holder').val());
    $('#other-layer-boundary-geom-holder').val($('#boundary-geom-temp').val());
  }
}

/*
 * Function that populates the annotation fields when the user selects an existing annotation.
 */
function load_annotation() {
  var record;
  //Remove the annotation already on the map, if changing annotation to show
  indiciaData.mapdiv.removeAllFeatures(indiciaData.mapdiv.map.editLayer, 'annotation');
  $.each(indiciaData.existingannotations, function (idx,annotation) {
    if (annotation.id==$('#existing_annotations').val()) {
      $("#annotation\\:id").val(annotation.id);
      $("#annotation\\:name").val(annotation.name);
      $("#annotation\\:code").val(annotation.code);
      $("#annotation\\:location_type_id").val(annotation.location_type_id);
      $("#imp-boundary-geom").val(annotation.boundary_geom);
      record = annotation;
      
      //Convert geom from the database into polygon format we can work with in code
      var feature, geom=OpenLayers.Geometry.fromWKT($('#imp-boundary-geom').val());
      if (indiciaData.mapdiv.map.projection.getCode() != indiciaData.mapdiv.indiciaProjection.getCode()) {
          geom.transform(indiciaData.mapdiv.indiciaProjection, indiciaData.mapdiv.map.projection);
      }
      feature = new OpenLayers.Feature.Vector(geom, record);
      feature.attributes.type = 'annotation';
      indiciaData.mapdiv.map.editLayer.addFeatures([feature]);
    }
    
    if ($('#existing_annotations').val()==='(New Annotation)') {
      $("#annotation\\:id").val(null);
      $("#annotation\\:name").val(null);
      $("#annotation\\:code").val(null);
      $("#annotation\\:location_type_id").val(null);
      $("#imp-boundary-geom").val(null);
    }
  });
}

/*
 * Function that is run when the user clicks to add a Survey and Date combination using the Surveys multi-select.
 */
function select_survey_and_date() {
  //Don't run if the Please Select option is still selected
  if ($("#survey-select option:selected").attr('id') !== 'please-select-surveys-item') { 
    //If the option we are selecting is already on the grid, it means it was previously there but has been hidden as the user removed it, so
    //show the item again rather than add new one.
    if ($('#selected-survey-row-'+$('#survey-select option:selected').val()).length != 0) {
      $('#selected-survey-row-'+$('#survey-select option:selected').val()).show();
      //When we reshow the grid item, make sure we update the date to the user selected value
      $('#selected-survey-date-'+$('#survey-select option:selected').val()).val($('#survey\\:date').val());
      $('#selected-survey-deleted-'+$('#survey-select option:selected').val()).val('false');
    } else {
      //Add an item to the last row in the results grid.
      $("#surveys-table tr:last").after(
        //Note we use the Survey Id on the end of the various html ids. The fields that will be used in submission also have a 
        //"name" otherwise they won't show in the submission $values variable
        "<tr id='selected-survey-row-"+$('#survey-select option:selected').val()+"'>"+
          "<td>"+
            "<input style='border: none;' id='selected-survey-id-"+$('#survey-select option:selected').val()+"' name='selected-survey-id-"+$('#survey-select option:selected').val()+"' value='"+$('#survey-select').val()+"' readonly>"+
          "</td>"+
          "<td>"+
            "<input style='border: none;' id='selected-survey-name-"+$('#survey-select option:selected').val()+"' value='"+$('#survey-select option:selected').text()+"' readonly>"+
          "</td>" +
          "<td>" +
            "<input  id='selected-survey-date-"+$('#survey-select option:selected').val()+"' name='selected-survey-date-"+$('#survey-select option:selected').val()+"' value='"+$("#survey\\:date").val()+"'>"+
          "</td>"+
          "<td>"+
            "<img class='action-button' src='"+indiciaData.deleteImagePath+"' onclick=\"remove_survey_selection("+$('#survey-select option:selected').val()+",'"+$('#survey-select option:selected').text()+"');\" title=\"Delete Survey Selection\">" +
          "</td>"+ 
          "<td style='display:none;'>"+
            "<input id='selected-survey-existing-"+$('#survey-select option:selected').val()+"' name='selected-survey-existing-"+$('#survey-select option:selected').val()+"' value=''>"+
          "</td>"+
          "<td style='display:none;'>"+
            "<input id='selected-survey-deleted-"+$('#survey-select option:selected').val()+"' name='selected-survey-deleted-"+$('#survey-select option:selected').val()+"' value='false'>"+
          "</td>"+
        "</tr>"
      );
    }
    $("#survey-select option:selected").remove();
  }
}

/*
 * Function that is run when the user removes one of the surveys they have selected in the grid.
 */
function remove_survey_selection(id,title) {
  $('#selected-survey-deleted-'+id).val('true');
  $('#selected-survey-row-'+id).hide();
  //Put the option that was removed back into the Surveys drop-down so the user can reselect the option if they so wish.
  $('#survey-select').append('<option id="survey-select-'+id+'" value="'+id+'">'+title+'</option>');
}
