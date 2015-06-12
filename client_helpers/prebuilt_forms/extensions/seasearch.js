var setClickedPosition;
jQuery(document).ready(function($) {

  /**
   * Utility method to retrieve a jQuery element using the ID.
   * @param id
   * @returns {*|jQuery|HTMLElement}
   */
  function getEl(id) {
    return $('#' + id.replace(/:/g, '\\:'));
  }

  /**
   * If any lat long data is entered, this overrides the grid ref.
   */
  $('table#position-data :input').change(function() {
    $('#input-os-grid').val('');
    updatePositionData();
  });

  /**
   * When the user changes the OS grid input control, the lat long boxes can be cleared. Also copies the grid ref to the
   * hidden fields used to post the form data.
   */
  $('#input-os-grid').change(function(e) {
    // entering an OS grid reference. So clear any data from the lat long grid.
    $('table#position-data :input').val('');
    // Copy the grid ref to the hidden input so that it gets posted and shown on the map
    $('#imp-sref').val($(e.currentTarget).val());
    $('#imp-sref-system').val('OSGB');
    $('#imp-sref').change();
  });

  /**
   * Switch between lat long (WGS84 or OSGB36) or osgb entry. If a lat long type is selected then the various input controls
   * for lat longs are shown and the grid ref control is hidden. Vice versa if OSGB is selected.
   */
  function showHidePositionEntryMethods() {
    if ($('#imp-sref-system').val()==='OSGB') {
      $('#input-os-grid-container').show();
      $('#input-ll-container').hide();
      // make lat long inputs not mandatory as not visible
      $.each($('#input-centre input'), function() {
        $(this)[0].className = $(this)[0].className.replace('required: true', 'required: false');
      });
      $('#input-centre input').rules('remove', 'required');
    } else {
      $('#input-os-grid-container').hide();
      $('#input-ll-container').show();
      // make lat long inputs not mandatory as not visible
      $.each($('#input-centre input'), function() {
        $(this)[0].className = $(this)[0].className.replace('required: false', 'required: true');
      });
    }
  }

  $('#imp-sref-system').change(showHidePositionEntryMethods);

  // Trigger the switch between lat long and OSGB on form load, in case there is existing data to load.
  showHidePositionEntryMethods();

  /**
   * Calculates the lat long point for the "from", "centre" or "to" position and returns as an array.
   */
  function buildPoint(position) {
    var qualifier, lat, long, minutes, decimalDegrees;
    qualifier = position==='centre' ? '' : '-' + position;
    minutes = $('#input-lat-min'+qualifier).val();
    decimalDegrees = minutes / 60;
    lat = parseInt($('#input-lat-deg'+qualifier).val()) + decimalDegrees;
    minutes = $('#input-long-min'+qualifier).val();
    decimalDegrees = minutes / 60;
    long = parseInt($('#input-long-deg'+qualifier).val()) + decimalDegrees;
    if ($('#e-w'+qualifier).val()==='W') {
      long = 0 - long;
    }
    return [long, lat];
  }

  /**
   * Draws the geometry for a drift dive from an array of points.
   * @param points
   */
  function drawDriftGeom(points) {
    var wkt='', feature, pointsList=[], parser = new OpenLayers.Format.WKT(),
      editLayer=indiciaData.mapdiv.map.editLayer;
    if (points.length===1) {
      wkt = 'POINT(' + points[0][0] + ' ' + points[0][1] + ')';
    } else if (points.length>1) {
      $.each(points, function() {
        pointsList.push(this[0] + ' ' + this[1]);
      });
      wkt = 'LINESTRING(' + pointsList.join(', ') + ')';
    }
    // zero points does nothing
    if (wkt!=='') {
      feature = parser.read(wkt);
      feature.geometry.transform('EPSG:' + $('#imp-sref-system').val(), indiciaData.mapdiv.map.projection);
      feature.attributes = {type: "driftLine"};
      indiciaData.mapdiv.removeAllFeatures(editLayer, 'driftLine');
      editLayer.addFeatures([feature]);
      zoom = Math.min(editLayer.getZoomForExtent(editLayer.getDataExtent()), indiciaData.mapdiv.settings.maxZoom);
      indiciaData.mapdiv.map.setCenter(editLayer.getDataExtent().getCenterLonLat(), zoom);
    }
  }

  /**
   * Recalculates the centre point for a drift dive, using the control values for the start and end of the drift. The
   * calculated values are stored in the form controls.
   */
  function calcCentreFromDrift() {
    var latFrom, latTo, longFrom, longTo, latCentre, longCentre, tokens;
    if ($('#input-drift-from :input[value=], #input-drift-to :input[value=]').length===0) {
      // If we have both start and end of a drift, we can calculate the centre
      latFrom = parseInt($('#input-lat-deg-from').val()) + $('#input-lat-min-from').val() / 60;
      latTo = parseInt($('#input-lat-deg-to').val()) + $('#input-lat-min-to').val() / 60;
      latCentre = (latFrom + latTo) / 2;
      tokens = latCentre.toString().split('.');
      $('#input-lat-deg').val(tokens[0]);
      $('#input-lat-min').val((('0.' + tokens[1])*60).toFixed(4));
      longFrom = parseInt($('#input-long-deg-from').val()) + $('#input-long-min-from').val() / 60;
      longTo = parseInt($('#input-long-deg-to').val()) + $('#input-long-min-to').val() / 60;
      if ($('#e-w-from').val()==='W') {
        longFrom = 0 - longFrom;
      }
      if ($('#e-w-to').val()==='W') {
        longTo = 0 - longTo;
      }
      longCentre = (longFrom + longTo) / 2;
      tokens = longCentre.toString().split('.');
      $('#e-w').val(tokens[0] < 0 ? 'W' : 'E');
      $('#input-long-deg').val(Math.abs(tokens[0]));
      $('#input-long-min').val((('0.' + tokens[1])*60).toFixed(4));
    }
  }

  /**
   * Method called when setting up or when any control containing position data changes. Ensures that the current state
   * of the position data is correctly reflected on the map.
   */
  function updatePositionData() {
    var latLong, $hiddenInput, updateGeom=false, points=[];
    calcCentreFromDrift();
    $hiddenInput = getEl(indiciaData.driftStartAttrFieldname);
    if ($('#input-drift-from').find(':input[value=]').length===0) {
      latLong = $('#input-lat-deg-from').val() + ':' + $('#input-lat-min-from').val() + 'N, ' +
      $('#input-long-deg-from').val() + ':' + $('#input-long-min-from').val() +
      $('#e-w-from').val();
      if ($hiddenInput.val()!==latLong) {
        $hiddenInput.val(latLong);
        updateGeom=true;
      }
      points.push(buildPoint('from'));
    } else if ($hiddenInput.val()!=='') {
      $hiddenInput.val('');
      updateGeom=true;
    }
    $hiddenInput = $('#imp-sref');
    if ($('#input-centre').find(':input[value=]').length===0) {
      // append all the boxes to make a lat long for the drift start. Use : to separate degrees from minutes.
      latLong = $('#input-lat-deg').val() + ':' + $('#input-lat-min').val() + 'N, ' +
      $('#input-long-deg').val() + ':' + $('#input-long-min').val() + $('#e-w').val();
      if ($hiddenInput.val()!==latLong) {
        $hiddenInput.val(latLong);
        $('#imp-sref').val(latLong);
        updateGeom=true;
      }
      points.push(buildPoint('centre'));
    } else if ($hiddenInput.val()!=='') {
      $hiddenInput.val('');
      updateGeom=true;
    }
    $hiddenInput = getEl(indiciaData.driftEndAttrFieldname);
    if ($('#input-drift-to').find(':input[value=]').length===0) {
      // append all the boxes to make a lat long for the drift end. Use : to separate degrees from minutes.
      latLong = $('#input-lat-deg-to').val() + ':' + $('#input-lat-min-to').val() + 'N, ' +
      $('#input-long-deg-to').val() + ':' + $('#input-long-min-to').val() + $('#e-w-to').val();
      if ($hiddenInput.val()!==latLong) {
        $hiddenInput.val(latLong);
        updateGeom=true;
      }
      points.push(buildPoint('to'));
    } else {
      $hiddenInput.val();
    }
    if (updateGeom) {
      drawDriftGeom(points);
    }
  }

  /**
   * Function called when the user clicks on the map. Reflects the click position into the appropriate lat long control.
   * @param data
   */
  setClickedPosition = function(data) {
    if ($('#imp-sref-system').val()==='OSGB') {
      $('#input-os-grid').val($('#imp-sref').val());
    }
    else {
      var ll = data.sref.split('N '), tokens, qualifier = '-' + $('input[name="which-point"]:checked').val();
      if (qualifier==="-centre") {
        qualifier='';
      }
      $('#e-w'+qualifier).val(ll[1].substr(ll[1].length-1, 1));
      ll[1] = ll[1].substring(0, ll[1].length-1);
      tokens = ll[0].split('.');
      $('#input-lat-deg'+qualifier).val(tokens[0]);
      $('#input-lat-min'+qualifier).val((('0.' + tokens[1])*60).toFixed(4));
      tokens = ll[1].split('.');
      $('#input-long-deg'+qualifier).val(tokens[0]);
      $('#input-long-min'+qualifier).val((('0.' + tokens[1])*60).toFixed(4));

      updatePositionData();
    }
  }

  mapInitialisationHooks.push(updatePositionData);
  mapClickForSpatialRefHooks.push(setClickedPosition);

});
