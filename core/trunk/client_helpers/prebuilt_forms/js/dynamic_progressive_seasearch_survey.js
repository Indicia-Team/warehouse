var setupHtmlForLinkingPhotosToHabitats, setupDroppableItemsForLinkingPhotosToHabitats,setupSubSampleAttrsForHabitat, createNewHabitat, setupAjaxPreSubmissionObject;
var setupAjaxPageSaving, setupClickEvents, inArray, current, next, hideOccurrenceAddphoto, disableTabContents,makeImageRowOrSpareRow,createNewHabitat;

jQuery(window).load(function($) {
  //Once page has finished loading we can hide loading panel
  jQuery('.loading-panel').hide();
  jQuery('#show').hide();
  //currently selected tab number
  current=parseInt(indiciaData.tabToReloadTo);
  //When linking photos to habitat we use drag and drop. Set this up.
  setupDroppableItemsForLinkingPhotosToHabitats();
  //Track progress through wizard by incrementing and decrementing tab number appropriately
  setupClickEvents();
  //On the page where we link photos to habitats we need to manipulate some of the html to setup the page.
  setupHtmlForLinkingPhotosToHabitats();
  //Some pages use a full reload, when the page reloads it needs to move back to the appropriate tab (held in the "current" variable)
  var a = jQuery('ul.ui-tabs-nav a')[current];
  jQuery(a).click();
  scrollTopIntoView(indiciaData.topSelector);
  //see detailed notes before method
  disableTabContents();
  //Stop the browser from warning the user each time a reload is done.
  window.onbeforeunload = confirmExit();
  function confirmExit()
  {
    return '';
  }
});


(function ($) {
  hideOccurrenceAddphoto = function hideOccurrenceAddphoto() {
    //Hide photos button on species tab as we don't need that.
    if (current==indiciaData.occTabIdx) {
      $('[id^="upload-select-btn-"]').hide();
      //Also hide the clonable row and the ability to add further photos.
      $('#third-level-smp-occ-grid').find('.scClonableRow').hide();
      $('.scAddMediaCell').hide();
    } else {
      $('[id^="upload-select-btn-"]').show(); 
      $('.scClonableRow').show();
      $('.scAddMediaCell').show();
    }
  }
  
  //Track progress through wizard by incrementing and decrementing tab number appropriately
  setupClickEvents = function setupClickEvents(getSampleId) {
    // Find the appropriate separator for AJAX url params - depends on clean urls setting.
    var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    //Parameter of the URL should include sample id if needed
    var param;
    if (indiciaData.sample_id) {
      param = 'sample_id=' + indiciaData.sample_id;
    } else {
      param = ''; 
    }
    //Hide the Add Photo button on the occurrences tab
    hideOccurrenceAddphoto();
    //Note for this wizard, we don't have a final submit, we save on everytime the user clicks next.
    $('.tab-next').click(function() {
      //Remove any drupal set message when we move page, apart from if there is only 1 habitat the allocated images
      //to habitat page will warn the user and we want this message to persist.
      if (current !==5) {
        $('#messages').hide();
      }
      //currently selected tab number need incrementing    
      current++;
      //Show Loading panel if we need to relod page
      if (inArray(current-1,indiciaData.reloadtabs)) {
        $('.loading-panel').show();
        $('#controls').hide();
      }
      $('.tab-next').each(function() {
        $(this).text('Please wait, saving previous page');
      });
      //Hide button when saving in progress
      $('.tab-prev').each(function() {
        $(this).hide();
      });
      //If finished wizard, then set in-progress attribute for sample to false.
      if (current>6) {
        $(indiciaData.inProgressAttrSelector).val(0);
      }
      hideOccurrenceAddphoto();
      //Setup saving without a full php post
      setupAjaxPageSaving(false);
      //see detailed notes before method
      disableTabContents();
      //Make sure the habitat tab has a habitat on it ready to fill in if there aren't any existing ones
      if (!indiciaData.existingHabitatSubSamplesIds && current===4) {
        createNewHabitat();
      }
    });  

    $('.tab-prev').click(function() {
      $('#messages').hide();
      //currently selected tab number needs decrementing     
      current--;
      //Show loading panel as we are reloading page.
      $('.loading-panel').show();
      $('#controls').hide();
      hideOccurrenceAddphoto();
      setupAjaxPageSaving(true);
      //see detailed notes before method
      disableTabContents();
    });
  }
  
  /*
   * Setup page saving. Some tabs can be saved as an ajax save instead of a page reload
   */
  setupAjaxPageSaving = function setupAjaxPageSaving(backButtonUsed) {
    var data = $('#entry_form').serialize();
    $.post(
      //Calls ajax_save function in php file.
      indiciaData.ajaxUrl + '/save/' + indiciaData.nid,
      data,
      function (response) { 
        var responseObject = $.parseJSON(response);
        //If the response is a fatal error message, then display it and reload the previous tab
        if (responseObject.fatalSubmissionError) {
          current--;
          alert(responseObject.fatalSubmissionError);
        } else {
          //If just a warning, then display it and continue as usual
          if (responseObject.submissionWarning) {
            alert(responseObject.submissionWarning);
          }
          var sampleIdToUse;
          //Get the sample id after save.
          var getSampleId = indiciaData.getSampleId;
          //In add mode get the sample id from the response, otherwise get from the URL.
          if (getSampleId) {
            sampleIdToUse=getSampleId;
          } else {
            sampleIdToUse=responseObject.outer_id;
          }
        }
        //Some tabs need a full reload (e.g. the next tab uses php for setup). When the reload takes place then we need to put
        //the sample id in the url and also the tab we need to return to.
        //"current" has already been incremented, so need to check the last tab to check if it is in the list of tabs to reload.
        //Always reload when going back in the wizard as some pages contain attributes that need reloading with sample_attribute_values.
        //Also need a reload if an fatal submission error message is shown and we want to return to previous tab.
        if (inArray(current-1,indiciaData.reloadtabs)||backButtonUsed===true||responseObject.fatalSubmissionError) { 
          //Ignore anything after # in the URL, like overlay contexts
          var url = window.location.href.toString().split('#');
          url=url[0];
          //Get the part of the URL before the parameters. The first params separator could be ? or & depending
          //on whether clean URLs are enabled.
          url = url.split(indiciaData.paramsSeparator);
          var urlWithoutParams = url[0];
          if (sampleIdToUse) {
              urlWithoutParams += indiciaData.paramsSeparator+'sample_id='+sampleIdToUse;
          }
          //Current has already been incremented, so we just need to load the new current tab
          urlWithoutParams += '&load_tab='+current;
          window.location=urlWithoutParams;
          window.location.href;
        }
        //Re-enable wizard buttons once saving is complete
        $('.tab-next').each(function() {
            $(this).text('Next step >');
        });
        $('.tab-prev').each(function() {
            $(this).show();
        });
      }
    );
  }
  
  //When creating a new habitat, we make a clone of a hidden cloneable habitat
  //Call the function that will setup the names of the attributes so they are ready for submission
  //Add a hidden field to allow the submission handler to know what the parent of the sub-sample is
  createNewHabitat = function() {
    var panelId='habitat-panel-'+indiciaData.nextHabitatNum;     
    $('#habitats-setup').append('<div id=\"'+panelId+'\" style=\"display:none;\">');
    $('#habitats-setup').append('<hr width=\"50%\">');
    $('.habitat-attr-cloneable').each(function(index) {
      $('#'+panelId).append($(this).clone().show().removeAttr('class'));
    });

    setupSubSampleAttrsForHabitat(indiciaData.nextHabitatNum,true, null);
    $('#habitat-panel'+'-'+indiciaData.nextHabitatNum).append('<input id=\"new_sample_sub_sample:'+indiciaData.nextHabitatNum+':sample:parent_id\" name=\"new_sample_sub_sample:'+indiciaData.nextHabitatNum+':sample:parent_id\" type=\"hidden\" value=\"'+indiciaData.mainSampleId+'\">');
    $('#habitat-panel'+'-'+indiciaData.nextHabitatNum).show();
    indiciaData.currentHabitatNum++;
    indiciaData.nextHabitatNum++;
  }
  
  /*
   * For all the attributes associated with a habitat, we need to place new_sample_sub_sample:<sub sample idx starting from 0> or existing_sample_sub_sample:<sub sample id in db> at the front of the names and ids so the submission
   * knows that these are to be placed into a sub-sample.
   */  
  setupSubSampleAttrsForHabitat = function setupSubSampleAttrsForHabitat(habitatNumToSetup,addNew, existingHabitatSampleId) {
    //Find all fields inside habitat panel
    $('#habitat-panel'+'-'+habitatNumToSetup).find('*').each(function() {
      //Only need to alter an element's attribute if it actually exists
      if ($(this).attr('id')) {
        if (addNew===true) {
          $(this).attr(
            'id','new_sample_sub_sample:'+habitatNumToSetup+':'+$(this).attr('id')
          );
        } else {
          $(this).attr(
            'id','existing_sample_sub_sample:'+existingHabitatSampleId+':'+$(this).attr('id')
          );
        }
      }
      if ($(this).attr('name')) {
        if (addNew===true) {
          $(this).attr(
            'name','new_sample_sub_sample:'+habitatNumToSetup+':'+$(this).attr('name')
          );
        } else {
          $(this).attr(
            'name','existing_sample_sub_sample:'+existingHabitatSampleId+':'+$(this).attr('name')
          );
        }
      }
    });
  }

  //On the page where we link photos to habitats we need to manipulate some of the html to setup the page.
  setupHtmlForLinkingPhotosToHabitats = function setupHtmlForLinkingPhotosToHabitats() {
    var mediaItemNumber,splitUpId;
    //Cycle through each photo in the standard photo control
    $("[name^='sample_medium\\:id']").each(function(i, obj) {
      mediaItemNumber = $('#media-item-for-habitat-'+$(this).val()).attr('number');
      if ($('#media-item-for-habitat-'+$(this).val()).length) {
        //Save a hidden field to hold the habitat sub-sample id the media item will now be associated with, however for now, this will not have an actual value until a drop is made.
        //This might already exist if reloading page when habitats have already been allocated, so check with jquery the hidden field doesn't already exist before adding.
        if (!$('#sub-sample-holder-for-media-number-'+mediaItemNumber).length) {
          $('#media-item-for-habitat-'+$(this).val()).append('<input type=\"hidden\" id=\"sub-sample-holder-for-media-number-'+mediaItemNumber+'\" name=\"sample_medium:'+$(this).val()+':sample_id\">');
        } else {
          if (!$('#sub-sample-holder-for-media-number-'+mediaItemNumber).attr('name')) {
            $('#sub-sample-holder-for-media-number-'+mediaItemNumber).attr('name','sample_medium:'+$(this).val()+':sample_id');
          }
        }
      }
    });
  }
  
  //When linking photos to habitat we use drag and drop. Set this up.
  setupDroppableItemsForLinkingPhotosToHabitats = function setupDroppableItemsForLinkingPhotosToHabitats() {   
    $('.droppable-splitter').droppable({ accept: '.habitat-dragzone',tolerance: 'touch',
      drop: function(event, ui) {
        var splitUpId=$(this).attr('id').split('-');
        //In between each photo is a splitter the user can drop a habitat onto.
        //The number of the splitter is held as part of its id
        var splitNumber=splitUpId[splitUpId.length-1];
        //Get the subSample id which is held as part of the photos container when loading in edit mode, the container contains the photo and splitter,
        //so we can get it by looking at the parent.
        var subSampleId=$(ui.draggable).parent().attr('id').split('-').pop();
        var colourToUse=$(ui.draggable).attr('color');
        //The are two kinds of habitat zones the user can drag from.
        //The override dragzone will always change the photo to be the habitat even 
        //if it has already been allocated in this session.
        //The other habitat draggable item will setup the photo to be associated with the habitat but also all
        //previous photos in the list providing they have not already been allocated. This way, the user can allocate
        //lots of photos to the same habitat all at once e.g if there are ten photos, then 1 to 5 can be allocated by dragging onto
        //splitter 5, then 6-10 can be allocated by dragging onto splitter 10 (as 1-5 won't be overridden unless using the other (override)
        //drag tool, which only does one at a time.
        if ($(ui.draggable).hasClass('habitat-override-dragzone')) {
          $('#sub-sample-holder-for-media-number-'+splitNumber).val(subSampleId);
          //Change the colour of the border
          $('[number=\"'+splitNumber+'\"]').css('border', '5px solid'+colourToUse);
          $(ui.draggable).css('border', '5px solid'+colourToUse);
        } else {
          //When we drag a habitat onto a photo, we are setting that photo as the last in the habitat, so set all the previous
          //photos to be in that habitat also.
          for (var i=splitNumber; i>-1; i--) {
            //Add the appropriate sub-sample id from the habitat into the hidden holder related to the media item.
            //Don't set the habitat on images that have already had the habitat set.
            if (!$('#sub-sample-holder-for-media-number-'+i).attr('allocated')) {
              $('#sub-sample-holder-for-media-number-'+i).val(subSampleId);
              //Change the colour of the border
              $('[number=\"'+i+'\"]').css('border', '5px solid'+colourToUse);
              $(ui.draggable).css('border', '5px solid'+colourToUse);
              $('#sub-sample-holder-for-media-number-'+i).attr('allocated',true);
            }
          } 
        }
      }
    });
  }
  
  //Some pages we can save without reloading. The problem with this is that the html on pages which contain attributes will not be setup 
  //with the sample_attribute_values like in edit mode, this means that when the user saves again on the following tabs a set of new sample_attribute_values
  //will be created instead of saving the existing ones. To get around that, we can remove the contents of the tab once saved until the tab
  //is reloaded correctly in edit mode (like if the user clicks back in the wizard, or reloads the page manually.)
  disableTabContents = function () {
    if (current>2) {
      $('#other-dive-info-not-date').remove();
    }

    if (current>3) {
      $('#tab-uploadplansandsketches').remove();
    }
  }
  
  //Based on the addRowToGrid.js makeSpareRow function (at the time of writing).
  //Slightly customised for seasearch, but changes not relevant to general code. 
  //To Do: I have only been partially successful at removing general code that might not be needed for Seasearch (without breaking it), however
  //that does not mean that further streamlining of this function is not possible.
  //Makes a row containing an image on the main occurrences grid that is preloaded from second level or third level sample.
  //On the extra species grid (non image grid), this also handles the addition of new rows to the grid (like makeSpareRow does)
  makeImageRowOrSpareRow = function(gridId, readAuth, lookupListId, url, evt, scroll, keycode, force, mediaId) {
    handleSelectedTaxon = function(event, data, value) {
      var taxonCell, checkbox, rowId, row, label, subSpeciesCellId, regex, deleteAndEditHtml;
      // on picking a result in the autocomplete, ensure we have a spare row
      // clear the event handlers
      $(event.target).unbind('result', handleSelectedTaxon);
      $(event.target).unbind('return', returnPressedInAutocomplete);
      taxonCell=event.target.parentNode; 
      //Create edit icons for taxon cells. Only add the edit icon if the user has this functionality available on the edit tab.
      //Also create Notes and Delete icons when required
      var linkPageIconSource = indiciaData.imagesPath + "nuvola/find-22px.png";
      if (indiciaData['editTaxaNames-'+gridId]==true) {
        deleteAndEditHtml = "<td class='row-buttons'>\n\
            <img class='action-button remove-row' src=" + indiciaData.imagesPath + "nuvola/cancel-16px.png>\n" 
        deleteAndEditHtml += "<img class='action-button edit-taxon-name' src=" + indiciaData.imagesPath + "nuvola/package_editors-16px.png>\n";
        if (indiciaData['includeSpeciesGridLinkPage-'+gridId]==true) {
          deleteAndEditHtml += '<img class="species-grid-link-page-icon" title="'+indiciaData.speciesGridPageLinkTooltip+'" alt="Notes icon" src=' + linkPageIconSource + '>';
        }
        deleteAndEditHtml += "</td>";
      } else {   
        deleteAndEditHtml = "<td class='row-buttons'>\n\
            <img class='action-button action-button remove-row' src=" + indiciaData.imagesPath + "nuvola/cancel-16px.png>\n";
        if (indiciaData['includeSpeciesGridLinkPage-'+gridId]==true) {
          deleteAndEditHtml += '<img class="species-grid-link-page-icon" title="'+indiciaData.speciesGridPageLinkTooltip+'" alt="Notes icon" src=' + linkPageIconSource + '>';
        }
        deleteAndEditHtml += "</td>";
      }
      //Put the edit and delete icons just before the taxon name
      $(taxonCell).before(deleteAndEditHtml);
      // Note case must be colSpan to work in IE!
      $(taxonCell).attr('colSpan',1);
      row=taxonCell.parentNode;
      //Only add this class if the user is adding new taxa, if they are editing existing taxa we don't add the class so that when the delete icon is used the
      //row becomes greyed out instead of deleted.
      if ($(row).hasClass('scClonableRow'))
        $(taxonCell).parent().addClass('added-row');
      $(taxonCell).parent().removeClass('scClonableRow');
      $(taxonCell).parent().find('input,select,textarea').removeClass('inactive');
      // Do we use a JavaScript fn, or a standard template, to format the species label?      
      if ($.isFunction(formatter)) {
        $(taxonCell).html(formatter(data));
      } else {
        // Just a simple PHP template
        label = formatter;
        // replace each field in the label template
        $.each(data, function(field, value) {
          regex = new RegExp('\\{' + field + '\\}', 'g');
          label = label.replace(regex, value === null ? '' : value);
        });
        $(taxonCell).html(label);
      }
      $(row).find('.id-diff').hover(indiciaFns.hoverIdDiffIcon);
      $(row).find('.species-checklist-select-species').hide();
      $(row).find('.add-media-link').show();
      // auto-check the row
      checkbox=$(row).find('.scPresenceCell input.scPresence');
      checkbox.attr('checked', 'checked');
      // store the ttlId 
      checkbox.val(data.id);
      // Finally, a blank row is added for the next record
      makeImageRowOrSpareRow(gridId, readAuth, lookupListId, url, null, true);
      // Allow forms to hook into the event of a new row being added
      $.each(hook_species_checklist_new_row, function(idx, fn) {
        fn(data, row);
      });
    };
    
    if (typeof formatter==="undefined" || !$.isFunction(formatter)) {
      // provide a default format function
      formatter = function(item) {
        return item.taxon;
      };
    }
    // only add a spare row if none already exist, or forced to do so
    if ($('table#'+gridId + ' tr.scClonableRow').length>=1 && !force) {
      return;
    }
    //If a media id is supplied, we will be preloading an image into the blank row. New for seasearch
    var extraParams, newRow, selectorId, speciesSelector, attrVal, ctrl;
    if (mediaId) {
      newRow = $('tr#'+gridId + '-scOccImageRow-'+mediaId).clone(true);
      //Remove row after cloning as we don't need it anymore and don't want to pass to submission builder
      $('tr#'+gridId + '-scOccImageRow-'+mediaId).remove();
    } else {    
      newRow = $('tr#'+gridId + '-scClonableRow').clone(true);
    }
    // build an auto-complete control for selecting the species to add to the bottom of the grid.
    // The next line gets a unique id for the autocomplete.
    selectorId = gridId + '-' + indiciaData['gridCounter-'+gridId];
    speciesSelector = '<input type="text" id="' + selectorId + '" class="grid-required {speciesMustBeFilled:true}" />';
    // put this inside the new row template in place of the species label.
    $(newRow).html($(newRow.html().replace('{content}', speciesSelector)));
    // Replace the tags in the row template with a unique row ID
    $.each($(newRow).children(), function(i, cell) {
      $.each($(cell).find('*'), function(idx, child) {
        attrVal = $(child).attr('name');
        if (typeof attrVal !== "undefined" && attrVal.indexOf('-idx-') !== -1) {
          $(child).attr('name', $(child).attr('name').replace(/-idx-/g, indiciaData['gridCounter-'+gridId]));
        }
        attrVal = $(child).attr('id');
        if (typeof attrVal !== "undefined" && attrVal.indexOf('-idx-') !== -1) {
          $(child).attr('id', $(child).attr('id').replace(/-idx-/g, indiciaData['gridCounter-'+gridId]));
        }
        attrVal = $(child).attr('for');
        if (typeof attrVal !== "undefined" && attrVal.indexOf('-idx-') !== -1) {
          $(child).attr('for', $(child).attr('for').replace(/-idx-/g, indiciaData['gridCounter-'+gridId]));
        }
      });
    });
    $(newRow).find("[name$='\:sampleIDX']").each(function(idx, field) {
      //Allows a sample to be generated for each occurrence in the grid if required.
      //For Seasearch I have removed the code that checks if we are in sub sample mode, as we will always be in sub-sample mode.
      var rowNumber=$(field).attr('name').replace('sc:'+gridId+'-','');
      rowNumber = rowNumber.substring(0,1);
      $(field).val(rowNumber);
    });
    // add the row to the bottom of the grid
    newRow.appendTo('table#' + gridId +' > tbody').removeAttr('id');
    extraParams = {
      mode : 'json',
      qfield : indiciaData.speciesGrid[gridId].cacheLookup ? 'searchterm' : 'taxon',
      auth_token: readAuth.auth_token,
      nonce: readAuth.nonce,
      taxon_list_id: lookupListId
    };
    if (indiciaData.speciesGrid[gridId].cacheLookup)
      extraParams.orderby = indiciaData.speciesGrid[gridId].selectMode ? 'original,preferred_taxon' : 'searchterm_length,original,preferred_taxon';
    else
      extraParams.orderby = 'taxon';
    if (typeof indiciaData['taxonExtraParams-'+gridId]!=="undefined") { 
      $.extend(extraParams, indiciaData['taxonExtraParams-'+gridId]);
      // a custom query on the list id overrides the standard filter..
      if (typeof extraParams.query!=="undefined" && extraParams.query.indexOf('taxon_list_id')!==-1) {
        delete extraParams.taxon_list_id;
      }
    }
    $(newRow).find('input,select').keydown(keyHandler);
    var autocompleteSettings = getAutocompleteSettings(extraParams, gridId);
    if ($('#' + selectorId).width()<200) {
      autocompleteSettings.width = 200;
    }
    // Attach auto-complete code to the input
    ctrl = $('#' + selectorId).autocomplete(url+'/'+(indiciaData.speciesGrid[gridId].cacheLookup ? 'cache_taxon_searchterm' : 'taxa_taxon_list'), autocompleteSettings);
    ctrl.bind('result', handleSelectedTaxon);
    ctrl.bind('return', returnPressedInAutocomplete);
    // Check that the new entry control for taxa will remain in view with enough space for the autocomplete drop down
    if (scroll && ctrl.offset().top > $(window).scrollTop() + $(window).height() - 180) {
      var newTop = ctrl.offset().top - $(window).height() + 180;
      // slide the body upwards so the grid entry box remains in view, as does the drop down content on the autocomplete for taxa
      $('html,body').animate({scrollTop: newTop}, 500);
    }
    // increment the count so it is unique next time and we can generate unique IDs
    indiciaData['gridCounter-'+gridId]++;
    return ctrl;
  };
  
  /*
   * Returns true if an item is found in an array
   */
  inArray = function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
  }
}) (jQuery);