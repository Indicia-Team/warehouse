var setupHtmlForLinkingPhotosToHabitats, setupDroppableItemsForLinkingPhotosToHabitats,setupSubSampleAttrsForHabitat, createNewHabitat, setupAjaxPreSubmissionObject;
var setupAjaxPageSaving, setupClickEvents, inArray, current, next, hideOccurrenceAddphoto, disableTabContents;

jQuery(window).load(function($) {
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
      //currently selected tab number need incrementing    
      current++;
      //If finished wizard, then set in-progress attribute for sample to false.
      if (current>6) {
        $(indiciaData.inProgressAttrSelector).val(0);
      }
      hideOccurrenceAddphoto();
      //Setup saving without a full php post
      setupAjaxPageSaving(false);
      //see detailed notes before method
      disableTabContents();
    });  

    $('.tab-prev').click(function() {
      //currently selected tab number needs decrementing     
      current--;
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
        var sampleIdToUse;
        //Get the sample id after save.
        var getSampleId = indiciaData.getSampleId;
        var responseObject = $.parseJSON(response);
        //In add mode get the sample id from the response, otherwise get from the URL.
        if (getSampleId) {
          sampleIdToUse=getSampleId;
        } else {
          sampleIdToUse=responseObject.outer_id;
        }
        //Some tabs need a full reload (perhaps the following tab using php for setup). When the reload takes place then we need to put
        //the sample id in the url and also the tab we need to return to.
        //"current" has already been incremented, so need to check the last tab to check if it is in the list of tabs to reload.
        //Always reload when going back in the wizard as some pages contain attributes that need reloading with sample_attribute_values.
        if (inArray(current-1,indiciaData.reloadtabs)||backButtonUsed===true) {
          var url = window.location.href.toString().split('?');
          var urlWithoutParams = url[0];
          urlWithoutParams += '?sample_id='+sampleIdToUse;
          //Current has already been incremented, so we just need to load the new current tab
          urlWithoutParams += '&load_tab='+current;
          window.location=urlWithoutParams;
          window.location.href;
        }
      }
    );
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
      $('#tab-otherdiveinformation').remove();
    }

    if (current>3) {
      $('#tab-uploadplansandsketches').remove();
    }
  }
  
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