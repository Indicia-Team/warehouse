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
