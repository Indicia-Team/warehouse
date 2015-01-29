jQuery(document).ready(function($) {

  // Retrieves a jQuery element from the PHP string representation of the ID.
  function getEl(id) {
    return $('#' + id.replace(/:/g, '\\:'));
  }

  // On change of the habitat count control, or initial loading of the form, set up all the controls required
  // for data entry of the habitat data.
  function setHabitatCount(targetCount) {
    var currentCount = $('#habitat-blocks').children('fieldset').length, addingHabitatIdx,
        block, blockHtml, attrType, tbody, existingSubsampleData, attrId, valId, valueData, regexp,
        data, validationClass, value, checkedBoxes;
    if (isNaN(targetCount) || targetCount === currentCount || targetCount<1) {
      return;
    }
    $('#habitat-count').val(targetCount);
    if (targetCount>=5) {
      $('#add-habitat').hide();
    }
    if (targetCount > currentCount) {
      // too few blocks, so add some
      while (currentCount < targetCount) {
        addingHabitatIdx = currentCount + 1;
        checkedBoxes = [];
        block = $('#habitat-block-template').clone();
        blockHtml = $(block).html().replace(/habitatIdx/g, addingHabitatIdx);
        existingSubsampleData={"values":{}};
        // for existing data, put a hidden which stores the subsample's sample ID.
        if (typeof indiciaData.existingSubsampleData!=="undefined" && typeof indiciaData.existingSubsampleData[currentCount]!=="undefined") {
          existingSubsampleData=indiciaData.existingSubsampleData[currentCount];
          $('#habitat-blocks').append('<input type="hidden" name="habitat_sample_id:'+addingHabitatIdx+'" value="'+
              existingSubsampleData.sample_id+'" />');
        }
        $(block).html(blockHtml);
        // clear the block ID so we don't duplicate
        $(block).attr('id', '');

        $('#habitat-blocks').append(block);

        // Insert columns into the quantitative data grids on the next tab.
        // First put the habitat ID column title in place.
        $('table#depth-limits,table#substratum,table#features').find('thead tr:first-child').append('<th>' + (currentCount+1) + '</th>');
        // now inject <td> elements containing the appropriate control type
        $('table#depth-limits,table#substratum,table#features').find('tbody tr:not(.checkboxes) td.label').before(
            '<td class="input"><input type="text" class="control-width-2"/></td>'
        );
        $('table#depth-limits,table#substratum,table#features').find('tbody tr.checkboxes td.label').before(
            '<td class="input"><input type="hidden" value="0"/><input type="checkbox"/></td>'
        );
        // find the min and max depths allowed for the whole dive, so we can apply them to the habitat depth field validation
        var minDepth = false, maxDepth = false, $el;
        $.each(indiciaData.depthMinLimitAttrNames, function() {
          $el = getEl(this);
          if ($el.val()!=='' && (minDepth===false || $el.val()<minDepth)) {
            minDepth = $el.val();
          }
        });
        $.each(indiciaData.depthMaxLimitAttrNames, function() {
          $el = getEl(this);
          if ($el.val()!=='' && (maxDepth===false || $el.val()>maxDepth)) {
            maxDepth = $el.val();
          }
        });
        // finally name the controls using the HTML5 data attribute in the row element to find the attribute ID.
        $.each($('table#depth-limits,table#substratum, table#features').find('tbody tr'), function() {
          attrId = $(this).attr('data-attrid');
          attrType = $(this).attr('data-attrtype');
          validationClass = $(this).attr('data-class');
          $.each($(this).find('td:nth-child('+(currentCount+1)+') input'), function() {
            if (typeof existingSubsampleData.values[attrId] === "undefined") {
              valId = '';
            } else {
              valueData = existingSubsampleData.values[attrId].split(':');
              valId = valueData[0];
            }
            $(this).attr('name', 'smpAttr:' + attrId + ':' + valId + ':' + addingHabitatIdx);
            // Apply depth range validation for the whole dive to the habitat depths
            if (attrId==indiciaData.habitatMinDepthSLAttr || attrId==indiciaData.habitatMaxDepthSLAttr) {
              if (minDepth!==false) {
                validationClass = validationClass.replace('}', ', min:'+minDepth+'}');
              }
              if (maxDepth!==false) {
                validationClass = validationClass.replace('}', ', max:'+maxDepth+'}');
              }
            }
            if (attrId==indiciaData.habitatMinDepthSLAttr) {
              $(this).change(function() {

              });

              /*bsl = $('#smpAttr\\:'+indiciaData.habitatMinDepthSLAttr).val();
              if (bsl.match(/^\d+(\.\d+)?$/)) {
                $('#smpAttr\\:'+indiciaData.habitatMinDepthCDAttr).val(bsl-cd);
              }
              bsl = $('#smpAttr\\:'+indiciaData.habitatMaxDepthSLAttr).val();
              if (bsl.match(/^\d+(\.\d+)?$/)) {
                $('#smpAttr\\:'+indiciaData.habitatMaxDepthCDAttr).val(bsl-cd);
              }*/
            }
            $(this).attr('class', validationClass);

          })
        });

        // copy the existing data values into the fields for this habitat
        $('textarea[name="sample:comment:' + addingHabitatIdx + '"]').text(existingSubsampleData.comment);
        $.each(existingSubsampleData.values, function(attrInfo, datastring) {
          attrId = attrInfo.split(':').pop();
          data = datastring.split(':');
          if (data[2]==='L') {
            $('input[name^="smpAttr:'+attrId+'::'+addingHabitatIdx+'\[\]"]').filter('[value="' + data[1] + '"]')
                .attr('checked', 'checked');
          } else if (data[2]==='B') {
            if (data[1]==='1') {
              $('input[name="smpAttr:'+attrId+'::'+addingHabitatIdx+'"]')
                  .attr('id', 'smpAttr:'+attrId+':'+data[0]+':'+addingHabitatIdx)
                  .attr('name', 'smpAttr:'+attrId+':'+data[0]+':'+addingHabitatIdx)
                  .attr('checked', 'checked');
            }
          } else {
            $('input[name="smpAttr:'+attrId+'::'+addingHabitatIdx+'"]')
                .attr('id', 'smpAttr:'+attrId+':'+data[0]+':'+addingHabitatIdx)
                .attr('name', 'smpAttr:'+attrId+':'+data[0]+':'+addingHabitatIdx)
                .val(data[1]);
          }
        });
        //addHabitatColToSpeciesGrid(addingHabitatIdx);
        currentCount++;
      }
    }
    else if (targetCount < currentCount) {
      // too many habitat blocks, so remove some. Should never happen.
    }
    // set the column unit title colspan
    $('table#depth-limits,table#substratum,table#features').find('thead tr:last-child th:first-child').attr('colspan', targetCount);

  }

  $('#add-habitat').click(function() {
    setHabitatCount($('#habitat-blocks').children('fieldset').length+1);
  });
  setHabitatCount(indiciaData.initialHabitatCount);

  // On load of existing data, ensure that the habitat indexes are loaded into the grid habitat controls properly.
  var habitatInput, row;
  $.each($('table.species-grid .scSampleCell input'), function() {
    row = $(this).closest('tr');
    if (!$(row.hasClass('scClonableRow'))) {
      habitatInput = row.find('.scHabitatCell input');
      $(habitatInput[0]).val(parseInt($(this).val()) + 1);
    }
  });

  getEl('smpAttr:' + indiciaData.driftAttrId).find('input').change(function() {
    if ($('label[for='+getEl('smpAttr:' + indiciaData.driftAttrId).find('input:checked').attr('id').replace(/:/g,'\\:')+']').html()==='Yes') {
      $('p.drift-only').css('opacity',1);
    } else {
      $('p.drift-only').css('opacity',0);
    }
  });

  /*
  If any position control input is changed:
  Build the drift from point. If it is different to the value saved in the hidden, then update the hidden and flag a change.
  Build the centre. If different to imp-sref, then update plus update sref-system. Flag a change.
  Build the drift to point. If it is different to the value saved in the hidden, then update the hidden and flag a change.
  If changes, then build the geom. Add to map. Centre on it.
   */

  $('#input-os-grid').change(function(e) {
    // entering an OS grid reference. So clear any data from the lat long grid.
    $('table#position-data :input').val('');
    // Copy the grid ref to the hidden input so that it gets posted and shown on the map
    $('#imp-sref').val($(e.currentTarget).val());
    $('#imp-sref-system').val('OSGB');
    $('#imp-sref').change();
  });

  // Switch between lat long or osgb entry
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

  showHidePositionEntryMethods();

  // calculates the lat long point for the from, centre or to spatial reference information and returns as an array.
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
      /*feature.style = new OpenLayers.Style({
       pointRadius: 5,
       strokeColor: "red",
       strokeWidth: 2
       });*/
      indiciaData.mapdiv.removeAllFeatures(editLayer, 'driftLine');
      editLayer.addFeatures([feature]);
      zoom = Math.min(editLayer.getZoomForExtent(editLayer.getDataExtent()), indiciaData.mapdiv.settings.maxZoom);
      indiciaData.mapdiv.map.setCenter(editLayer.getDataExtent().getCenterLonLat(), zoom);
    }
  }

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

  /* Some business logic for chart datum */
  function changeDepthField() {
    var cd=getEl(indiciaData.depthCDAttrName).val(), bsl;
    if (cd.match(/^\d+(\.\d+)?$/)) {
      bsl = getEl(indiciaData.depthMinLimitAttrNames[0]).val();
      if (bsl.match(/^\d+(\.\d+)?$/)) {
        getEl(indiciaData.depthMinLimitAttrNames[1]).val(bsl-cd);
      }
      bsl = getEl(indiciaData.depthMaxLimitAttrNames[0]).val();
      if (bsl.match(/^\d+(\.\d+)?$/)) {
        getEl(indiciaData.depthMaxLimitAttrNames[1]).val(bsl-cd);
      }
    }
  }
  getEl(indiciaData.depthCDAttrName).change(changeDepthField);
  getEl(indiciaData.depthMinLimitAttrNames[0]).change(changeDepthField);
  getEl(indiciaData.depthMaxLimitAttrNames[0]).change(changeDepthField);

  $('table#position-data :input').change(function() {
    // If any lat long data is entered, this overrides the grid ref
    $('#input-os-grid').val('');
    updatePositionData();
  });

  // Add the Dorset Integrated Seabed layers
  function addDorisLayers(div) {
    var seabed = new OpenLayers.Layer.XYZ(
      "Seabed",
      "http://doris.s3.amazonaws.com/bathyalltiles/${z}/${x}/${y}.png",
        { isBaseLayer: false, sphericalMercator: true }
    );
    var seanames = new OpenLayers.Layer.XYZ(
        "DORIS names",
        "http://doris.s3.amazonaws.com/seaplace/${z}/${x}/${y}.png",
        { isBaseLayer: false, sphericalMercator: true }
    );
    div.map.addLayers([seabed, seanames]);
    // ensure under the edit layer
    div.map.setLayerIndex(seabed, 0);
    div.map.setLayerIndex(seanames, 1);
  }

  function setClickedPosition(data) {
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

  // enable jQuery UI tooltips
  $(document).tooltip();

  mapInitialisationHooks.push(updatePositionData);
  mapInitialisationHooks.push(addDorisLayers);
  mapClickForSpatialRefHooks.push(setClickedPosition);
});