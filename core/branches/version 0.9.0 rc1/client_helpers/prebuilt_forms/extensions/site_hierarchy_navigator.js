
jQuery(document).ready(function($) {
  //When the user returns to the homepage from a link, we get the locationid and location type id of the location we
  //are zooming to from php (these are calculated from the location id supplied in the URL)
  var preloadBreadcrumbFeatureId = null;
  var preloadBreadcrumbFeatureLocationTypeId = null;
  //setup default styling for the feature points. The type of icon to show is supplied in the report
  //in a column called 'graphic'.
  //Annotations have their own style as they also have labels
  var defaultStyle = new OpenLayers.Style({
    'pointRadius': 15,
    'externalGraphic': indiciaData.imagesPath + 'warning.png',
    'graphicWidth': 16,
    'graphicHeight': 16,
    'graphicOpacity': 0.8,
    'fillOpacity': 0,
    'strokeColor': '#0000ff',
    'strokeOpacity': 1,
    'strokeWidth': 2,
    'strokeDashstyle': 'dash'
  }), selectStyle = new OpenLayers.Style({
    'strokeColor': '#ff0000',
    'strokeDashstyle': 'dot'
  }, defaultStyle), annotationStyle = new OpenLayers.Style({
    'strokeDashstyle': 'solid',
    'label': '${name}',
    'fontSize': '15px',
    'labelOutlineColor': 'white',
    'labelOutlineWidth': '10'
  }, defaultStyle);
  var s = new OpenLayers.StyleMap({
    'default': defaultStyle, 
    'select': selectStyle,
    'annotation': annotationStyle
  });
  indiciaData.reportlayer = new OpenLayers.Layer.Vector('Report output', {styleMap: s});
  //Need seperate layer to display parent location feature as we don't want it clickable
  indiciaData.clickedParentLayer = new OpenLayers.Layer.Vector('Report output', {styleMap: s});
  mapInitialisationHooks.push(function (div) {
    "use strict";
    //Put into indicia data so we can see the map div elsewhere
    indiciaData.mapdiv = div;
    //Set the click "?" map control to be the default one
    $.each(div.map.controls, function(idx, ctrl) {
      if (ctrl.CLASS_NAME==="OpenLayers.Control") {
        ctrl.activate();
      } else if (ctrl.CLASS_NAME==="OpenLayers.Control.Navigation") {
        ctrl.deactivate();
      }
    });
    //if preloadBrumb is populated, it indicates the user is returning to the page from another page
    //and we need to set the map up so it is zoomed into the location the user has requested. This includes
    //setting up the breadcrumb trail correctly.
    if (indiciaData.preloadBreadcrumb) {
      //The ids required to rebuild the breadcrumb are a held as a location id and location type id.
      //These are calculated from the location id in the URL.
      //The values are process in the same way as if we had got the id and location type id by clicking on the map itself.
      var idAndLocationType = indiciaData.preloadBreadcrumb.split(',');
      preloadBreadcrumbFeatureId = idAndLocationType[0];
      preloadBreadcrumbFeatureLocationTypeId = idAndLocationType[1];
    }
    //Firstly we need to call the code which works out the map hierarchy to the current location (region, site, count unit)
    //that the user has selected.
    get_map_hierarchy_for_current_position(preloadBreadcrumbFeatureId,preloadBreadcrumbFeatureLocationTypeId);
  });  
});

/* 
 * Simple method that starts off the process of loading a new map layer.
 * This is the function that is run when map is clicked. 
 * This then calls another function that takes a feature.id and feature.location_type_id of the
 * feature the user clicked on. By taking these parameters instead of a features object, this function is able to be called 
 * from lots of places including from html.
 */
function move_to_new_layer(features) {
  if (features.length>0 && indiciaData.layerLocationTypes.length > 0) {
    get_map_hierarchy_for_current_position(features[0].id,features[0].attributes.location_type_id);
  }
}

/*
 * Function which works out the map hierarchy to the current location (region, site, count unit) that the user has selected.
 */
function get_map_hierarchy_for_current_position(clickedFeatureId,clickedFeatureLocationTypeId) {
  //copy array by value. This is a list of the location types for the different layers.
  var locationLayerTypeIds = indiciaData.layerLocationTypes.slice();
  if (!clickedFeatureId) {
    //If there is no clicked layer, then we are dealing with the first layer.
    add_new_layer_controller(null,[],clickedFeatureLocationTypeId);
  } else {
    var SupportedLocationTypeIdsAsString;
    var i=-1;
    //Cycle round the list of all Location Types that can be displayed on the homepage map in order.
    //Then stop when we reach the location type that is the same as the location we have clicked on. This gives us a list of location
    //types up until that point.
    do {
      i++;
      if (SupportedLocationTypeIdsAsString) {
        SupportedLocationTypeIdsAsString=SupportedLocationTypeIdsAsString+','+indiciaData.layerLocationTypes[i];
      } else {
        SupportedLocationTypeIdsAsString=indiciaData.layerLocationTypes[i];
      }
    } while (clickedFeatureLocationTypeId != locationLayerTypeIds[i] &&
             i < indiciaData.layerLocationTypes.length-1) 
    //Use a report to get a list of locations that match the different layer location types and also intersect the location we are interested in.
    reportRequest = indiciaData.breadcrumbReportRequest+'&location_id='+clickedFeatureId+'&location_type_ids='+SupportedLocationTypeIdsAsString;
    $.getJSON(reportRequest,
      null,
      function(response, textStatus, jqXHR) { 
        //We need to reorder the breadcrumb as the sql doesn't know about the order of indiciaData.layerLocationTypes
        var breadcrumbHierarchy = reorderBreadcrumbHierarchy(response);
        //Function that gets the feature that the user has clicked on (from the map or select list or homepage link from
        //another page).
        //This then calls add_new_layer_controller which controls setting up the rest of the layer.
        get_clicked_feature(clickedFeatureId,breadcrumbHierarchy,clickedFeatureLocationTypeId);
      }
    );
  }
}

/*
 * Once we have worked out a hierarchy of locations list to the currently selected location, we need to re-order
 * the list so it matches the ordering of the layers on the page.
 * This is because the sql report that returns the list doesn't know about the location type layer configuation (indiciaData.layerLocationTypes)
 * which is supplied on the edit tab by the user.
 */
function reorderBreadcrumbHierarchy(breadcrumbHierarchy) {
  var orderedBreadcrumbHierarchy = [];
  $.each(indiciaData.layerLocationTypes, function (idx, locationTypeLayerId) {
    //breadcrumb hierarchy is the unordered hierarchy returned by the report.
    $.each(breadcrumbHierarchy, function (idx, breadcrumbHierarchyItem) {
      if (locationTypeLayerId==breadcrumbHierarchyItem.location_type_id) {
        orderedBreadcrumbHierarchy.push(breadcrumbHierarchyItem);
      }
    });
  });
  return orderedBreadcrumbHierarchy;
}

/*
 * Function that gets the feature that the user has clicked on (from the map or select list or homepage link from
 * another page).
 * This then calls add_new_layer_controller which controls setting up the rest of the layer.
 */
function get_clicked_feature(clickedFeatureId,breadcrumbHierarchy,clickedFeatureLocationTypeId) {
  var clickedFeature;
  var features=[];
  clickedFeature = indiciaData.reportlayer.getFeatureById(clickedFeatureId);
  //clickedFeature might still be empty if the user clicks on the breadcrumb as the feature isn't already on the child layer
  //so we can't collect it by using indiciaData.reportlayer.getFeatureById, we need to collect it from a report
  if (!clickedFeature) {
    reportRequest = indiciaData.layerReportRequest + '&location_type_id='+clickedFeatureLocationTypeId+'&parent_id='+null+
    '&deactivate_site_attribute_id='+indiciaData.deactivateSiteAttributeId+'&location_id='+clickedFeatureId;
    $.getJSON(reportRequest,
      null,
      function(reportdata, textStatus, jqXHR) {     
        features = get_map_features_from_report_data(reportdata,[]);
        clickedFeature = features[0];
        add_new_layer_controller(clickedFeature,breadcrumbHierarchy,clickedFeatureLocationTypeId);
      }
    );
  } else {
    add_new_layer_controller(clickedFeature,breadcrumbHierarchy,clickedFeatureLocationTypeId);
  }
}

/*
 * Main method that handles setting up a new layer when the user clicks on the map (or uses the select list).
 * This calls the report that get the features to display in the parent location.
 * It also calls methods that handle setting up the layer in various ways
 */
function add_new_layer_controller(clickedFeature,breadcrumbHierarchy,clickedFeatureLocationTypeId) {
  //Get id and name of the location clicked on
  var parentId,parentName
  if (clickedFeature) {
    parentId = clickedFeature.id
    parentName = clickedFeature.attributes.name;
  } else {
    parentId=null;
    parentName=null;
  }
  var reportRequest,childLocationTypesToReport,i;
  //We need the the location type that is next in the list of indiciaData.layerLocationTypes
  //along from the location type we clicked on (this is the location type of the child features we are drawing.
  if (!clickedFeature) {
    childLocationTypesToReport = indiciaData.layerLocationTypes[0];
  } else {
    for (i=0; i<indiciaData.layerLocationTypes.length; i++) {
      if (indiciaData.layerLocationTypes[i]==clickedFeatureLocationTypeId) {
        childLocationTypesToReport = indiciaData.layerLocationTypes[i+1];
      }
    }
  }
  //If the parent is acount unit, then that is also the last location type listed in indiciaData.layerLocationTypes.
  //That means that childLocationTypesToReport will be empty (as it will be assigned to location type with the last index + 1), 
  //so we need to detect this and set it to the all the location types for annotations so these can be displayed on the last layer.
  if (!childLocationTypesToReport) {
    childLocationTypesToReport = indiciaData.annotationTypeIds;
  }
  //Link to Count unit Information sheet if we detect a Count Unit has been clicked on/selected
  //and it is the final layer. 
  //The final layer is different as it is the parent that is clickable (count unit), and it is the 
  //child annotations layer that is not cliackable.
  if (clickedFeature && clickedFeature.attributes.clickableParent) {
    location = indiciaData.informationSheetLink+clickedFeature.attributes.parent_id;
  } else {
    //If the user has specified a layer must also display count units, then add them to the report parameters
    if (inArray(childLocationTypesToReport,indiciaData.showCountUnitsForLayers)) {
      reportRequest = indiciaData.layerReportRequest + '&location_type_id='+childLocationTypesToReport+','+indiciaData.layerLocationTypes[indiciaData.layerLocationTypes.length-1]+ '&parent_id='+parentId+
                      '&deactivate_site_attribute_id='+indiciaData.deactivateSiteAttributeId+'&location_id=null';
    } else {
      reportRequest = indiciaData.layerReportRequest + '&location_type_id='+childLocationTypesToReport+'&parent_id='+parentId+
                      '&deactivate_site_attribute_id='+indiciaData.deactivateSiteAttributeId+'&location_id=null';
    }
    //Get the locations to displayed that are within the parent location (or all locations matching the first location type if it is the first layer)
    $.getJSON(reportRequest,
        null,
        function(response, textStatus, jqXHR) {
          if (response.length>0 || clickedFeature) {           
            var currentLayerLocationNames = [], features=[],feature,getMapFeaturesFromReportDataResult,mainCurrentLayerLocationTypeName;
            //If the user has specified that a layer must also display count units, then get the Count Unit location type name (we don't want to hard code it so collect from database)
            if (inArray(childLocationTypesToReport,indiciaData.showCountUnitsForLayers)) {
              currentLayerLocationNames = get_names_from_location_types(childLocationTypesToReport+','+indiciaData.layerLocationTypes[indiciaData.layerLocationTypes.length-1]);
            } else {
              currentLayerLocationNames = get_names_from_location_types(childLocationTypesToReport);
            }
            //The main mainCurrentLayerLocationTypeName variable is the name of the location type of the layer we are currently looking at,
            //this is currently used to make the Add Site button's label change depending on the location type to be added.
            //Note this is different to the currentLayerLocationNames which might include Count Unit if the user has set the option
            //to add Count Units to a particular layer.
            mainCurrentLayerLocationTypeName = currentLayerLocationNames.split(',')[0];
            //Get the child features for the layer
            features = get_map_features_from_report_data(response,currentLayerLocationNames);
            if (indiciaData.useBreadCrumb) {
              //create the map breadcrumb itself
              breadcrumb(parentId,parentName,currentLayerLocationNames,breadcrumbHierarchy);
            }
            //Add the features to the child (clickable) or parent (non-clickable layers) as appropriate.
            add_features_to_layers(features,clickedFeature,currentLayerLocationNames);
            //Add other controls like Add Site or location drop-down to the page
            setup_additional_controls(childLocationTypesToReport,parentId, parentName,clickedFeature,features,mainCurrentLayerLocationTypeName,breadcrumbHierarchy)
            //Finally zoom in to the features
            zoom_to_area(features,clickedFeature)
          }
        }
    );
  }
}

/*
 * The layer the user is looking at consists of at least one location type.
 * Get the names of those location types and return it in a comma seperated format
 */
function get_names_from_location_types(currentLayerIds) {
  //Get an array of the loction type ids relevant to the current layer
  var currentLayerIdsArray = [];
  if (typeof currentLayerIds == 'string' || currentLayerIds instanceof String) {
    currentLayerIdsArray = currentLayerIds.split(',');
  } else {
    currentLayerIdsArray = currentLayerIds;
  }
  //We are building the list of names from scratch so we start with empty list.
  var currentLayerNames = '';
  //Cycle through the location ids of the layer we are viewing
  $.each(currentLayerIdsArray, function (currentLayerListIndex, theCurrentIdToCheck) {
    //Try and find the same id in the list of layer location type ids sepcified by the user 
    $.each(indiciaData.layerLocationTypes, function (originalListIndex, originalLayerListId) {
      //Once we have a match then we know the position of the id in the list of layer location type ids originally specified by the user.
      //We already have a list of equivalent names in indiciaData.layerLocationTypesNames which is in the same order, so we can collect the name
      //from indiciaData.layerLocationTypesNames
      if (theCurrentIdToCheck===originalLayerListId) {
        if (currentLayerNames) {
        currentLayerNames = currentLayerNames + ',' + indiciaData.layerLocationTypesNames[originalListIndex];
        } else {
          currentLayerNames = indiciaData.layerLocationTypesNames[originalListIndex];
        }
      }
    });  
  });
  //If we haven't been able to locate the location type names by using the layer list supplied by the user, it means we are looking at the 
  //annotation layer
  if (!currentLayerNames) {
    currentLayerNames = 'annotation';
  }
  return currentLayerNames;
}

/*
 * Once we have returned the data of what locations we want to display on a map layer, we need to 
 * actually add those features to the map itself.
 */
function get_map_features_from_report_data(reportdata,currentLayerLocationNames) {
  var features = [];

  var mainCurrentLayerLocationTypeName;
  if (reportdata) {
    $.each(reportdata, function (idx, obj) {
      if (obj) {
        //Use boundary geom by default
        if (obj.boundary_geom) {               
          feature=indiciaData.mapdiv.addPt(features, obj, 'boundary_geom', {}, obj.id);
        } 
        else {
          //Else fall back on the centroid
          if (reportdata.length>0) {
            feature=indiciaData.mapdiv.addPt(features, obj, 'centroid_geom', {}, obj.id);
          }
        }
        //The rendering of the features for the last layer needs to be different as for annotations we also display
        //a label
        if (inArray(feature.attributes.location_type_id,indiciaData.annotationTypeIds)) {
          feature.renderIntent='annotation';
        }
      }
    });
  }
  return features;
}

/*
 * Most layers consist of a non-clickable location layer in addition to a clickable layer which displays
 * the locations within that location.
 * The exception is the last layer which has a clickable parent count unit plus non-clickable annotations.
 * So for the last layer we swap which layer is clickable.
 * This function call code which sets up each of the two layers, and if it detects the last layer it 
 * swaps which layer which is clickable.
 */
function add_features_to_layers (features,clickedFeature,currentLayerLocationNames) { 
  //If the user has clicked on a count unit boundary or a count unit (in the case it doesn't have a boundary), 
  //then we know we are going to be looking at the Count Unit itself.
  if (clickedFeature && 
      (clickedFeature.attributes.location_type_id==indiciaData.countUnitBoundaryTypeId ||
      clickedFeature.attributes.location_type_id==indiciaData.layerLocationTypes[indiciaData.layerLocationTypes.length-1])) {
    //The clickableParent clickedFeature attribute is checked for by the code elsewhere in order for it to detect
    //we are looking at a count unit and annotations.
    //This is a special case as the child layer of annotations is not clickable, neither is it specified by the user in the configuration,
    //so we need a special way of detecting we are actually looking at a count unit and annotations.
    clickedFeature.attributes.clickableParent=1
    //On the last layer the non-clickable (parent) layer consists of all the main features (the annotations).
    if (clickedFeature) {
      setup_layer('parent',features,indiciaData.clickedParentLayer,clickedFeature.attributes.name + ' layer');
    }
    //On the last layer the clickable (child) layer consists the count unit the user clicked on.
    setup_layer('child',[clickedFeature],indiciaData.reportlayer,currentLayerLocationNames + ' layer');
  } else {
    if (clickedFeature) {
      //On a non-last layer, the non-clickable (parent) layer contains the clicked location.
      setup_layer('parent',[clickedFeature],indiciaData.clickedParentLayer,clickedFeature.attributes.name + ' layer');
    }
    //On a non-last layer, the clickable (child) layer consists of all the features for the location type we are viewing
    //that fall inside the parent location.
    setup_layer('child',features,indiciaData.reportlayer,currentLayerLocationNames + ' layer');
  }
}

/*
 * Adding features and layers to the map is repetative, so place some of the code in a function we can call several times.
 * There are two layers to add.
 */
function setup_layer(layerType,featuresForLayer,layerToAdd,nameForLayer) {
  //There are two types of layer, one is clickable, one isn't clickable
  if (layerType==='parent') {
    if (nameForLayer) {
      indiciaData.clickedParentLayer.setName(nameForLayer)
    }
    indiciaData.clickedParentLayer.removeAllFeatures();
    indiciaData.mapdiv.map.addLayer(layerToAdd);
    indiciaData.clickedParentLayer.addFeatures(featuresForLayer); 
  } else {
    if (nameForLayer) {
      indiciaData.reportlayer.setName(nameForLayer)
    }
    indiciaData.reportlayer.removeAllFeatures();
    indiciaData.mapdiv.map.addLayer(layerToAdd);
    indiciaData.reportlayer.addFeatures(featuresForLayer); 
  } 
}

/*
 * Add controls not related to the map to the page
 */
function setup_additional_controls(currentLayerLocationTypesId,parentId, parentName,clickedFeature,features,mainCurrentLayerLocationTypeName,breadcrumbHierarchy) {
  //This code gets the url id. This is used on the Cudi Information Sheet which also contains the map.
  if (indiciaData.preloadBreadcrumb) {
    var idAndLocationType = indiciaData.preloadBreadcrumb.split(',');
    var preloadBreadcrumbFeatureId = idAndLocationType[0];
  }
  //Don't show the control at all in the situation where the item that is being shown on the map in the same as the what is shown on the Cudi Information Sheet.
  //This avoids a link button being shown to the same Cudi Information Sheet as the user is already on. As the user moves to other Count Units on the map, then the link is shown again.
  //Note that breadcrumbHierarchy.length==0 is needed as a test as there is no breadcrumb hierarchy at the top level.
  //The preloadBreadcrumbFeatureId is the url id, breadcrumbHierarchy[breadcrumbHierarchy.length-1]['id'] is the id of the Count Unit being viewed,
  //(the clickedFeature id can't be used here as that would give us the boundary id, not the parent).
  if (indiciaData.useSelectList && (breadcrumbHierarchy.length==0 || preloadBreadcrumbFeatureId != breadcrumbHierarchy[breadcrumbHierarchy.length-1]['id'])) {
    if (clickedFeature && clickedFeature.attributes.clickableParent) {
      //If at bottom level of the tree, the select list would only have one item, so just use a button instead
      selectlistbutton([clickedFeature]);
    } else { 
      selectlist(features);
    }
  } else {
    $('#map-selectlist-div').hide();
  }
  //Get the link to the View Sites and Count Units report button
  if (indiciaData.useListReportLink) {
    list_report_link(currentLayerLocationTypesId,parentId, parentName);
  }     
  //Get the Add Count Unit button
  if (indiciaData.useAddCountUnit) {
    add_count_unit_link(currentLayerLocationTypesId,parentId);
  } 
  //Get the View Count Units Review button
  if (indiciaData.useCountUnitsReview) {
    count_units_review_link(currentLayerLocationTypesId);
  } 
  //Get the Add Site (location) button
  if (indiciaData.useAddSite) {
    add_site_link(currentLayerLocationTypesId,parentId, mainCurrentLayerLocationTypeName);
  }
  //Get the Edit Site button
  if (indiciaData.useEditSite) {
    edit_site_link(currentLayerLocationTypesId,parentId,parentName);
  }
}

/*
 * Make the map breadcrumb (not to be confused with the homepage links found on some pages.)
 */
function breadcrumb(parentId,parentName,currentLayerLocationNames,breadcrumbHierarchy) {
  var i,breadcrumbPartFront,buildUpBreadCrumb;
  //The first item in the breadcrumb is for the top level, this needs to be treated seperately as it doesn't
  //have a parent location.
  breadcrumbPartFront = '<li id = "breadcrumb-part-'+0+'">';  
  //Get translatable label for top-level breadcrub item.
  buildUpBreadCrumb = breadcrumbPartFront + "<a onclick='get_map_hierarchy_for_current_position(null,null)'>"+ indiciaData.allSitesLabel + "</a></li>";
  //We need an item in the breadcrumb for each item in the location hierarchy to the location that is currently selected.
  for (i=1;i<=breadcrumbHierarchy.length;i++) {
    breadcrumbPartFront = '<li id = "breadcrumb-part-'+i+'">';
    buildUpBreadCrumb = buildUpBreadCrumb + breadcrumbPartFront + "<a onclick='get_map_hierarchy_for_current_position("+breadcrumbHierarchy[i-1].id+","+breadcrumbHierarchy[i-1].location_type_id+")'>"+ breadcrumbHierarchy[i-1].name + "</a></li>";
  }
  $('#map-breadcrumb').html(buildUpBreadCrumb);
  //Call this function to reset the map position as the breadcrumb moves the map which cause the features to become out of alignment
  //with the map itself. If we didn't call this function, the further the user clicks into the map layers, the more difficulty they will 
  //have with accurately clicking on features.
  indiciaData.mapdiv.map.updateSize();
}

/*
 * When the user moves layer, zoom the map.
 * This involves finding the most northerly, southerly, easterly and westerly boundaries of all the 
 * locations we are drawing as a whole. When we do this, we need to take into account both layers.
 */
function zoom_to_area(features,clickedFeature) {
  var featuresToZoom = [];
  featuresToZoom = features;
  //If the user clicks on a feature, that feature becomes the parent feature to display, so we need to include it when zooming.
  if (clickedFeature) {
    featuresToZoom.push(clickedFeature);
  }
              
  var bounds = new OpenLayers.Bounds();
  var boundsOfAllObjects = new OpenLayers.Bounds();
  
  //Each location is inside a square shape called the bounds.
  //There is an east,south,west,north side (right,bottom,left,top) side to each of the bounds
  //All we need to do is cycle through all the features are find the most northerly, easterly, westerly, and southerly
  //bounds. We then have a square containing all the features, we can then automatically zoom into this square.
  $.each(featuresToZoom, function (idx, feature) {
    bounds = feature.geometry.getBounds()
    //Store the left most boundary if it is further west than current stored furthest west boundary
    if (!boundsOfAllObjects.left || boundsOfAllObjects.left > bounds.left) {
      boundsOfAllObjects.left = bounds.left;
    }
    //As above for south
    if (!boundsOfAllObjects.bottom || boundsOfAllObjects.bottom > bounds.bottom) {
      boundsOfAllObjects.bottom = bounds.bottom;
    }
    //As above for east
    if (!boundsOfAllObjects.right || boundsOfAllObjects.right < bounds.right) {
      boundsOfAllObjects.right = bounds.right;
    }
    //As above for north
    if (!boundsOfAllObjects.top || boundsOfAllObjects.top < bounds.top) {
      boundsOfAllObjects.top = bounds.top;
  }   
  });
  //Zoom and center
  //if showing something small, don't zoom in too far
  if (indiciaData.mapdiv.map.getZoomForExtent(bounds) > indiciaData.mapdiv.settings.maxZoom) {  
    indiciaData.mapdiv.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
  } else {
    zoom = indiciaData.mapdiv.map.getZoomForExtent(boundsOfAllObjects);
    indiciaData.mapdiv.map.setCenter(boundsOfAllObjects.getCenterLonLat(), zoom); 
  }
}

/*
 * A select list that displays the same locations as on the map. Selecting a location
 * from the select list zooms in the same way map clicking does.
 */
function selectlist(featuresForSelectList) {
  //At the bottom level a button is used instead of a single-option drop-down, this button is only
  //ever used when the selectlistbutton function is called
  $('#map-selectlist-button-div').hide();
  $('#map-selectlist-div').show();
  var selectListOptions;
  selectListOptions = '<option value="">Please select a location</option>';
  $.each(featuresForSelectList, function (idx, featureForSelectList) {
    //Don't include annotations in the drop-down as we'll end up with multiple options
    //for the same count unit.
    if (!inArray(featureForSelectList.attributes.location_type_id,indiciaData.annotationTypeIds)) {
      //Need to include the location id and location type id in the html, as these are used by the code once an option is selected.
      selectListOptions += '<option value="'+featureForSelectList.attributes.name+'" featureid="'+featureForSelectList.id+'" featurelocationtypeid="'+featureForSelectList.attributes.location_type_id+'">'+featureForSelectList.attributes.name+'</option>';
    }
  });
  $('#map-selectlist').html(selectListOptions)
}

/*
 * When we reach the count unit level in the map hierarchy we 
 * use a button to link to the information sheet instead of a drop-down
 * as there is only ever one option
 */
function selectlistbutton(countUnitFeature) {
  $('#map-selectlist-button-div').show();
  $('#map-selectlist-div').hide();
  buttonHtml = '<input id="map-selectlist-bottom-level-button" type="button" value="'+'Information Sheet for '+countUnitFeature[0].attributes.name+'" featureid="'+countUnitFeature[0].id+'" featurelocationtypeid="'+countUnitFeature[0].attributes.location_type_id+'" onclick="get_map_hierarchy_for_current_position('+countUnitFeature[0].id+','+countUnitFeature[0].attributes.location_type_id+')">';
  $('#map-selectlist-button-div').html(buttonHtml)
}

/*
 * A control where we construct a button linking to a report page whose path and parameter are as per administrator supplied options.
 * The options format is comma seperated where the format of the elements is "location_type_id|report_path|report_parameter".
 * If an option is not found for the displayed layer's location type, then the report link button is hidden from view.
 */
function list_report_link(currentSiteType,parentId, parentName) {
  //construct a comma seperated list of location ids shown on the current layer 
  //which is then put in the report parameter in the url.
  //If the current layer location type is in the administrator specified options list, then we know to draw the report button
  for (i=0; i<indiciaData.locationTypesForListReport.length;i++) {
    if (indiciaData.locationTypesForListReport[i] === currentSiteType) {
      button = '<FORM>';
      button += "<INPUT TYPE=\"button\" VALUE=\"View Sites and Count Units Within "+parentName+"\"\n\
                    ONCLICK=\"window.location.href='"+indiciaData.reportLinkUrls[i]+parentId+"'\">";
      button += '</FORM>'; 
      return $('#map-listreportlink').html(button);
    }
  }
  return $('#map-listreportlink').html('');
}

/*
 * Control button that takes user to Add Count Unit page whose path and parameter are as per administrator supplied options.
 * The parameter is used to automatically zoom the map to the area we want to add the count unit.
 * The options format is comma seperated where the format of the elements is "location_type_id|page_path|parameter_name".
 * If an option is not found for the displayed layer's location type, then the Add Count Unit button is hidden from view.
 */
function add_count_unit_link(currentSiteType,parentLocationId) {
  if (parentLocationId) {
    //If the current layer location type is in the administrator specified options list, then we know to draw the add count unit button
    for (i=0; i<indiciaData.locationTypesForAddCountUnits.length;i++) {
      if (indiciaData.locationTypesForAddCountUnits[i] === currentSiteType) {
        button = '<FORM>';
        button += "<INPUT TYPE=\"button\" VALUE=\"Add Count Unit\"\n\
                      ONCLICK=\"window.location.href='"+indiciaData.addCountUnitLinkUrls[i]+parentLocationId+"'\">";
        button += '</FORM>'; 
        return $('#map-addcountunit').html(button);
      }
    }
  }
  return $('#map-addcountunit').html('');
}

/*
 * Control button that takes user to the Count Units Review Report page whose path is per the administrator supplied option.
 * The options format is comma seperated where the format of the elements is "location_type_id|page_path".
 * If an option is not found for the displayed layer's location type, then the View Count Units Review button is hidden from view.
 */
function count_units_review_link(currentSiteType) {
  //If the current layer location type is in the administrator specified options list, then we know to draw the View Count Units Review button
  for (i=0; i<indiciaData.locationTypesFoCountUnitsReview.length;i++) {
    if (indiciaData.locationTypesFoCountUnitsReview[i] === currentSiteType) {
      button = '<FORM>';
      button += "<INPUT TYPE=\"button\" VALUE=\"View Count Units Review Report\"\n\
                    ONCLICK=\"window.location.href='"+indiciaData.countUnitsReviewLinkUrls[i]+"'\">";
      button += '</FORM>'; 
      return $('#map-viewcountunitsreview').html(button);
    }
  }
}

/*
 * Control button that takes user to Add Site page whose path and parameter are as per administrator supplied options.
 * The type location_type of the site we are adding is taken from the location layer we are viewing, so this button is
 * not just limited to the site location type if users want to use it to add other location types.
 * The parameter is used to automatically zoom the map to the region/site we want to add the new site to.
 * The options format is comma seperated where the format of the elements is "location_type_id|page_path|parameter_name".
 * If an option is not found for the displayed layer's location type, then the Add Site button is hidden from view.
 */
function add_site_link(currentSiteType,parentLocationId, location_type_of_viewing_layer) {
  if (parentLocationId) {
    //If the current layer location type is in the administrator specified options list, then we know to draw the add site button
    for (i=0; i<indiciaData.locationTypesForAddSites.length;i++) {
      if (indiciaData.locationTypesForAddSites[i] === currentSiteType) {
        button = '<FORM>';
        button += "<INPUT TYPE=\"button\" VALUE=\"Add "+location_type_of_viewing_layer+"\"\n\
                      ONCLICK=\"window.location.href='"+indiciaData.addSiteLinkUrls[i]+parentLocationId+'&location_type_id='+currentSiteType+"'\">";
        button += '</FORM>'; 
        return $('#map-addsite').html(button);
      }
    }
  }
  return $('#map-addsite').html('');
}

/*
 * Allow users to edit sites. When button is displayed, it allows the user to edit the parent site.
 */
function edit_site_link(currentSiteType, parentSiteId, parentSiteName) {
  if (parentSiteId) {
    for (i=0; i<indiciaData.locationTypesForEditSites.length;i++) {
      if (indiciaData.locationTypesForEditSites[i] === currentSiteType) {
        button = '<FORM>';
        button += "<INPUT TYPE=\"button\" VALUE=\"Edit "+parentSiteName+"\"\n\
                      ONCLICK=\"window.location.href='"+indiciaData.editSiteLinkUrls[i]+parentSiteId+"'\">";
        button += '</FORM>'; 
        return $('#map-editsite').html(button);
      }
    }
  }
  return $('#map-editsite').html('');
}

/*
 * Returns true if an item is found in an array
 */
function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}
