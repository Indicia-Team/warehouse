jQuery(document).ready(function($) {

  // Retrieves a jQuery element from the PHP string representation of the ID.
  function getEl(id) {
    return $('#' + id.replace(/:/g, '\\:'));
  }

  /**
   * Initialise the photo upload control for the habitat identified by the index.
   * @param habitatIndex
   */
  function initHabitatPhotoUploads(habitatIndex, existing) {
    var options = {
      id : 'sample_medium-sample_medium-' + habitatIndex,
      upload : '1',
      maxFileCount : '4',
      autoupload : '1',
      msgUploadError : 'An error occurred uploading the file.',
      msgFileTooBig : 'The image file cannot be uploaded because it is larger than the maximum file size allowed.',
      runtimes : 'html5,flash,silverlight,html4',
      imageWidth : '200',
      uploadScript : indiciaData.uploadSettings.uploadScript,
      destinationFolder : '/sites/all/modules/iform/client_helpers/upload/',
      finalImageFolder : indiciaData.warehouseUrl + 'upload/',
      jsPath : '/sites/all/modules/iform/media/js/',
      buttonTemplate : '<button id="{id}" type="button" title="{title}"{class}>{caption}</button>',
      table : 'sample_medium' + habitatIndex,
      maxUploadSize : '4194304',
      codeGenerated : 'all',
      mediaTypes : ["Image:Local"],
      fileTypes : {"image":["jpg","gif","png","jpeg"],"audio":["mp3","wav"]},
      imgPath : '/sites/all/modules/iform/media/images/',
      addBtnCaption : 'Add {1}',
      msgPhoto : 'photo',
      msgFile : 'file',
      msgLink : 'link',
      msgNewImage : 'New {1}',
      msgDelete : 'Delete this item',
      msgUseAddFileBtn : 'Use the Add file button to select a file from your local disk. Files of type {1} are allowed.',
      msgUseAddLinkBtn : 'Use the Add link button to add a link to information stored elsewhere on the internet. You can enter links from {1}.',
      caption : 'Habitat photos',
      resizeWidth: 1500,
      resizeHeight: 1500
    };
    if (existing.length) {
      options.existingFiles = existing;
    }
    $('#container-sample_medium' + habitatIndex + '-default').uploader(options);
  }

  // On change of the habitat count control, or initial loading of the form, set up all the controls required
  // for data entry of the habitat data.
  function setHabitatCount(targetCount) {
    var currentCount = $('#habitat-blocks').children('fieldset').length, addingHabitatIdx,
        block, blockHtml, attrType, tbody, existingSubsampleData, attrId, valId, valueData, regexp,
        data, validationClass, value, checkedBoxes,
        // find the min and max depths allowed for the whole dive, so we can apply them to the habitat depth field validation
       minDepth = $('#' + indiciaData.depthMinLimitAttrNames[0].replace(':', '\\:')).val(),
       maxDepth = $('#' + indiciaData.depthMaxLimitAttrNames[0].replace(':', '\\:')).val();
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
        existingSubsampleData={"values":{},"media":{}};
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
        initHabitatPhotoUploads(addingHabitatIdx, existingSubsampleData.media);

        // Insert columns into the quantitative data grids on the next tab.
        // First put the habitat ID column title in place.
        $('table#depth-limits,table#substratum,table#features').find('thead tr:first-child').append('<th class="habitat-title-' + (currentCount+1) + '">' + (currentCount+1) + '</th>');
        // now inject <td> elements containing the appropriate control type
        $('table#depth-limits,table#substratum,table#features').find('tbody tr:not(.checkboxes) td.label').before(
            '<td class="input"><input type="text" class="control-width-2"/></td>'
        );
        $('table#depth-limits,table#substratum,table#features').find('tbody tr.checkboxes td.label').before(
            '<td class="input"><input type="hidden" value="0"/><input type="checkbox"/></td>'
        );
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
            $(this).attr('tabindex', addingHabitatIdx);
            // Apply depth range validation for the whole dive to the habitat depths
            if (attrId==indiciaData.habitatMinDepthSLAttrId || attrId==indiciaData.habitatMaxDepthSLAttrId) {
              if (minDepth) {
                validationClass = validationClass.replace('}', ', min:'+minDepth+'}');
              }
              if (maxDepth) {
                validationClass = validationClass.replace('}', ', max:'+maxDepth+'}');
              }
              $(this).change(correctHabitatDepthFields);

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
        addHabitatColToSpeciesGrid(addingHabitatIdx);
        currentCount++;
      }
    }
    else if (targetCount < currentCount) {
      // too many habitat blocks, so remove some. Should never happen.
    }
    // set the column unit title colspan
    $('table#depth-limits,table#substratum,table#features').find('thead tr:last-child th:first-child').attr('colspan', targetCount);
  }

  /**
   * @todo Naming and value of cloned selects for existing records
   * @param habitatIdx
   */
  function addHabitatColToSpeciesGrid(habitatIdx) {
    var habitatName = 'Habitat '+habitatIdx, tokens, select;
    // Work out the name of the habitat. Could be set by the habitat's name control, or use a default.
    $.each($('.habitat-name'), function() {
      tokens = $(this).attr('name').split(':');
      if (parseInt(tokens[tokens.length-1])===habitatIdx) {
        if ($(this).val().trim()!=='') {
          habitatName = $(this).val();
        }
        return false; // from $.each
      }
    })
    // First habitat will already have the abundance control present, so don't need to create new column. Just label the
    // header and set a class so that habitat name updates will be reflected in it.
    if (habitatIdx===1) {
      var headers = $('table.sticky-header #species-grid-comment-0,table.species-grid #species-grid-comment-0').prev();
      headers.html(habitatName);
      headers.addClass('habitat-title-' + habitatIdx);
    }
    else {
      // add headers to the species grid for the new habitat. Also include the Drupal sticky table header.
      $('table.sticky-header #species-grid-comment-0,table.species-grid #species-grid-comment-0').before(
        '<th class="habitat-title-' + habitatIdx + '">'+habitatName+'</th>');
    }
    var processRow = function(row, idx) {
      if (habitatIdx===1) {
        select = $(row).find('.scSACFORPCell select');
      }
      else {
        select = $(row).find('.scSACFORPCell select').clone();
        $(row).find('.scCommentCell').before($('<td class="scOccAttrCell ui-widget-content scSACFORPCell"></td>').append(select));
      }
      $(select).attr('id', 'species-grid-'+idx+':habitat-'+habitatIdx);
      $(select).attr('name', 'species-grid-'+idx+':habitat-'+habitatIdx);
      $(select).val('');
    }
    // now process the data rows - either modifying the existing attribute control (habitat 1) or inserting new habitat cells
    $.each($('table#species-grid tbody tr'), function(idx) {
      processRow(this, idx);
    });
    processRow($('#species-grid-scClonableRow'), '-idx-');
  }

  $('#add-habitat').click(function() {
    setHabitatCount($('#habitat-blocks').children('fieldset').length+1);
  });
  setHabitatCount(indiciaData.initialHabitatCount);

  indiciaFns.on('change', '.habitat-name', {}, function(e) {
    var habitatIdx, tokens;
    tokens = $(this).attr('id').split(':');
    // last part of the field ID is the habitat index
    habitatIdx = tokens.pop();
    if ($(e.currentTarget).val().trim() !== '') {
      $('.habitat-title-' + habitatIdx).html($(e.currentTarget).val().trim());
    }
    else {
      $('.habitat-title-' + habitatIdx).html('Habitat ' + habitatIdx);
    }
  });

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



  /* Some business logic for chart datum */
  function correctDiveDepthFields() {
    // Find the chart datum correction
    var cd=getEl(indiciaData.depthCDAttrName).val(), bsl;
    // Apply this correction to the 2 dive depth fields
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

  function correctHabitatDepthFields() {
    var habitatCount=$('#habitat-count').val(), $table = $('#depth-limits'), i,
      $minRowSL = $table.find('tr[data-attrid=' + indiciaData.habitatMinDepthSLAttrId + ']'),
      $maxRowSL = $table.find('tr[data-attrid=' + indiciaData.habitatMaxDepthSLAttrId + ']'),
      $minRowCD = $table.find('tr[data-attrid=' + indiciaData.habitatMinDepthCDAttrId + ']'),
      $maxRowCD = $table.find('tr[data-attrid=' + indiciaData.habitatMaxDepthCDAttrId + ']'),
      cd = getEl(indiciaData.depthCDAttrName).val();
    for (i=1; i<=habitatCount; i++) {
      if (cd && $minRowSL.find('td:nth-child(' + i + ') input').val()) {
        $minRowCD.find('td:nth-child(' + i + ') input').val($minRowSL.find('td:nth-child(' + i + ') input').val() - cd);
      } else {
        $minRowCD.find('td:nth-child(' + i + ') input').val('');
      }
      if (cd && $maxRowSL.find('td:nth-child(' + i + ') input').val()) {
        $maxRowCD.find('td:nth-child(' + i + ') input').val($maxRowSL.find('td:nth-child(' + i + ') input').val() - cd);
      } else {
        $maxRowCD.find('td:nth-child(' + i + ') input').val('');
      }
    }
  }

  /**
   * Takes a single habitat depth control and applies the min and max validation rules for the entire dive depth.
   * @param input The control element
   * @param min The minimum uncorrected dive depth value, can be '' if not specified
   * @param max The maximum uncorrected dive depth value, can be '' if not specified
   */
  function updateMinMaxValidation(input, min, max) {
    $(input).rules('remove', 'min max');
    if (min!=='') {
      $(input).rules('add', {min: min});
    }
    if (max!=='') {
      $(input).rules('add', {max: max});
    }
  }

  function updateHabitatDepthValidation() {
    var habitatCount=$('#habitat-count').val(), i,
      $table = $('#depth-limits'),
      $minRowSL = $table.find('tr[data-attrid=' + indiciaData.habitatMinDepthSLAttrId + ']'),
      $maxRowSL = $table.find('tr[data-attrid=' + indiciaData.habitatMaxDepthSLAttrId + ']'),
      cd = getEl(indiciaData.depthCDAttrName).val(), input,
      validation = ['number:true'],
      min = getEl(indiciaData.depthMinLimitAttrNames[0]).val(),
      max = getEl(indiciaData.depthMaxLimitAttrNames[0]).val();
    if (min!=='') {
      validation.push('min:'+min);
    }
    if (max!=='') {
      validation.push('max:'+max);
    }
    for (i=1; i<=habitatCount; i++) {
      input = $minRowSL.find('td:nth-child(' + i + ') input');
      updateMinMaxValidation(input, min, max);
      input = $maxRowSL.find('td:nth-child(' + i + ') input');
      updateMinMaxValidation(input, min, max);
    }
  }

  function cdCorrectionUpdated() {
    correctDiveDepthFields();
    correctHabitatDepthFields();
  }

  function diveDepthUpdated() {
    correctDiveDepthFields();
    updateHabitatDepthValidation();
  }

  getEl(indiciaData.depthCDAttrName).change(cdCorrectionUpdated);
  getEl(indiciaData.depthMinLimitAttrNames[0]).change(diveDepthUpdated);
  getEl(indiciaData.depthMaxLimitAttrNames[0]).change(diveDepthUpdated);

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

  // Unless editing, we don't need the existing records grid
  if ($('#edit-species-grid tbody tr').not('.scClonableRow').length===0) {
    $('#edit-species').hide();
    $('#create-species h3').hide();
  }
  // The edit grid does not need the row at the bottom for adding species.
  $('#edit-species-grid .scClonableRow').hide();
  // The edit grid also needs a column for indicating the habitat.
  $('#edit-species-grid thead th:first-child').after('<th>Habitat</th>');
  var habitatIdx;
  $.each($('#edit-species-grid tbody tr'), function() {
    habitatIdx = parseInt($(this).find('input.scSample').val()) + 1;
    $(this).find('.scTaxonCell').after('<td class="habitat-title-' + habitatIdx + '">Habitat ' + habitatIdx + '</td>');
  });

  // enable jQuery UI tooltips
  $(document).tooltip();
  mapInitialisationHooks.push(addDorisLayers);
});