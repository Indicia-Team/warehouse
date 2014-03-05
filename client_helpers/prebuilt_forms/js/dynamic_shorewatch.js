
//Need to attach the functions to global variables so outside code can see the functions that are inside the jQuery drupal 7 wrapper.
var setup_time_validation;
var hide_wdcs_newsletter;
var details_field_behaviour;
var cetaceans_control_next_step;
var adhoc_reticule_fields_validator;
var adhoc_sightings_grid_species_validator;
var empty_site_list_detect;

jQuery(document).ready(function($) {
  empty_site_list_detect();
  setup_time_validation();
  hide_wdcs_newsletter();
  details_field_behaviour();
  //If the page is locked then we don't run the logic on the Save/Next Step button
  //as this logic enables the button when we don't want it enabled.
  if (!indiciaData.dontRunCetaceanSaveButtonLogic) {
    cetaceans_control_next_step();
  }
  $('[id$=\"comment-0\"]').text('Notes');
  adhoc_reticule_fields_validator();
  adhoc_sightings_grid_species_validator();
});
//Wrapping required for drupal 7
(function ($) {
  //If the location list is empty, hide it and change the Other Site label to "Site".
  //The length of an empty location list is 1 because there is a "Please Select" option.
  empty_site_list_detect = function empty_site_list_detect() {
    if ($('#imp-location option').size()===1) {
      $('#imp-location').hide();
      $('[for=\"imp-location\"]').hide();
      $('[for=\"sample\\:location_name\"]').text('Site');
    }
  }
  //The length of efforts needs to be ten minutes, setup the rules for this
  setup_time_validation = function setup_time_validation() {
    $('#entry_form').validate();
    //classes to attach validator to
    $('#smpAttr\\:'+ indiciaData.start_time_attr_id).addClass('tenMinuteTime');
    $('#smpAttr\\:' + indiciaData.end_time_attr_id).addClass('tenMinuteTime');
    //Add Time/Start Time as a mandatory field
    $('#smpAttr\\:' + indiciaData.start_time_attr_id).rules('add', {required:true});
    $('<span class=\"deh-required\">*</span>').insertAfter('#smpAttr\\:' + indiciaData.start_time_attr_id);
      if (indiciaData.roleType!='data manager' && indiciaData.roleType!='staff' && indiciaData.roleType!='volunteer') {
        //Guests just have a single time field.
        //We need to use "For" as a selector because the field label doesn't have a class or id and needs to be removed also.
        $('[for=\"smpAttr\\:' + indiciaData.start_time_attr_id + '\"]').text('Time');
        $('[for=\"smpAttr\\:' + indiciaData.end_time_attr_id + '\"]').remove();
        $('#smpAttr\\:' + indiciaData.end_time_attr_id).remove();
      } else {
        //For staff and volunteers
        //Add End Time as a mandatory field
        $('#smpAttr\\:'  + indiciaData.end_time_attr_id).rules('add', {required:true});
        //Add a mandatory field red star
        $('<span class=\"deh-required\">*</span>').insertAfter('#smpAttr\\:' + indiciaData.end_time_attr_id);
        //Validate time fields and make sure the watch lenth is ten minutes or less when not in adhoc mode.
        if (indiciaData.adhocMode==0) {
          jQuery.validator.addMethod('tenMinuteTime',
            function(value, element) {
              //Only apply validation when both time fields are filled in
              if ($('#smpAttr\\:' + indiciaData.start_time_attr_id).val() && $('#smpAttr\\:' + indiciaData.end_time_attr_id).val()) {
                //Get the times in minutes from the start of the day
                startTimeHourMin=$('#smpAttr\\:' + indiciaData.start_time_attr_id).val().split(':'); 
                endTimeHourMin=$('#smpAttr\\:' + indiciaData.end_time_attr_id).val().split(':');
                startTimeMin=parseInt(startTimeHourMin[0],10)*60+parseInt(startTimeHourMin[1],10);
                endTimeMin=parseInt(endTimeHourMin[0],10)*60+parseInt(endTimeHourMin[1],10);
                //There are two scenerios which are valid. 
                //1.The difference between the end and start time is less than ten minutes.
                //2. If the time crosses midnight then the end - start times must be 0 and -1430. 
                if (((endTimeMin-startTimeMin)<=(-1430))||(((endTimeMin-startTimeMin)>0)&&((endTimeMin-startTimeMin)<=10)))
                  return true;
              //always pass validation if both times aren't filled in.
              } else {
                return true;
              }
            }, indiciaData.tenMinMessage
          );
          //We need to set the sample attributes we want mandatory manually, this is because we are using sub-samples
          //which don't include these sample attributes and we don't want the system to flag these as not filled
          //in on submission.
          $('#smpAttr\\:'+indiciaData.sea_state_attr_id).rules('add', {required:true});
          $('<span class=\"deh-required\">*</span>').insertAfter('#smpAttr\\:'+indiciaData.sea_state_attr_id)
          $('#smpAttr\\:'+indiciaData.visibility_attr_id).rules('add', {required:true});
          $('<span class=\"deh-required\">*</span>').insertAfter('#smpAttr\\:'+indiciaData.visibility_attr_id)
          //Note: Use the selector type "name starts with" because selecting by id doesn't work if a radio button pair is used because an extra 0 and 1
          //would appear at the end of the ids to keep the ids for the Yes and No radio buttons unqiue. This would break the selector if we had used an exact selector like $('#smpAttr\\:' + indiciaData.cetaceans_seen_attr_id).
          $('[name^=\"smpAttr\\:' + indiciaData.cetaceans_seen_attr_id + '\"]').rules('add', {required:true});
          //Need to add the red star mandatory field icon after the second option (which is "No"), the second option is at index 1
          $('<span class=\"deh-required\">*</span>').insertAfter('[for=\"smpAttr\\:' + indiciaData.cetaceans_seen_attr_id + '\\:1' + '\"]')
          $('[name^=\"smpAttr\\:' + indiciaData.non_cetacean_marine_animals_seen_attr_id + '\"]').rules('add', {required:true});
          $('<span class=\"deh-required\">*</span>').insertAfter('[for=\"smpAttr\\:' + indiciaData.non_cetacean_marine_animals_seen_attr_id + '\\:1' + '\"]')
          $('[name^=\"smpAttr\\:' + indiciaData.feeding_birds_seen_attr_id + '\"]').rules('add', {required:true});
          $('<span class=\"deh-required\">*</span>').insertAfter('[for=\"smpAttr\\:' + indiciaData.feeding_birds_seen_attr_id + '\\:1' + '\"]')
          $('#smpAttr\\:'+indiciaData.number_of_people_spoken_to_during_watch_attr_id).rules('add', {required:true});
          $('<span class=\"deh-required\">*</span>').insertAfter('#smpAttr\\:'+indiciaData.number_of_people_spoken_to_during_watch_attr_id)
        }
      }
  }
  
  //The wdcs newsletter checkbox should only be shown for guests
  hide_wdcs_newsletter = function hide_wdcs_newsletter() {
    if (indiciaData.roleType=='data manager' ||indiciaData.roleType=='staff' || indiciaData.roleType=='volunteer') {
      //Hide WDCS newsletter when user isn't a guest as this is held in user's profile
      $('#smpAttr\\:'+ indiciaData.wdcs_newsletter_attr_id).attr('style', style='display: none;');
      //hide the field label 
      $('[for=\"smpAttr\\:' + indiciaData.wdcs_newsletter_attr_id + '\"]').attr('style', style='display: none;');
      $('#smpAttr\\:'+ indiciaData.wdcs_newsletter_attr_id).nextAll('.helpText:first').remove();
    }
  }

  //The fields like Observer Name, Email and Phone Number has different behaviours depending on the type of user logged in
  //and the type of form being shown. e.g. one of phone or email must be provided.
  //Implement these behaviours.
  details_field_behaviour = function details_field_behaviour() {
    $('#entry_form').validate();
    switch (indiciaData.roleType) {
      case 'data manager':
      //Data manager to have same rights as staff here, so we 'fall through' the case statement without breaking
      case 'staff':
        //When logging in as staff we remove the existing Observer Name textbox and replace it with an autocomplete
        $('#smpAttr\\:' + indiciaData.observer_name_attr_id).remove();
        $('#smpAttr\\:' + indiciaData.observer_name_attr_id + '_lock').remove();
        $('[for=\"smpAttr\\:' + indiciaData.observer_name_attr_id + '\"]').remove();
        //Give the autocomplete user control the same name as the sample attribute so that indicia recognises it.
        $('#obSelect\\:' + indiciaData.observer_name_attr_id + '\\:fullname_surname_first').attr('name', 'smpAttr:' + indiciaData.observer_name_attr_id);
        //Make the observer name mandatory.
        $('#obSelect\\:' + indiciaData.observer_name_attr_id + '\\:fullname_surname_first').rules('add', {required:true});       
        $('<span class=\"deh-required\">*</span>').insertAfter($('#obSelect\\:' + indiciaData.observer_name_attr_id + '\\:fullname_surname_first'));
        //Auto-fill these fields if we can if not already filled in and not in edit mode.
        if (indiciaData.user_email && !$('#smpAttr\\:'+ indiciaData.observer_email_attr_id).val() && !indiciaData.sample_id) {
          $('#smpAttr\\:'+ indiciaData.observer_email_attr_id).val(indiciaData.user_email);
        }
        if (indiciaData.observer_phone_number_attr_id && !$('#smpAttr\\:'+ indiciaData.observer_phone_number_attr_id).val() && !indiciaData.sample_id) {
          $('#smpAttr\\:'+ indiciaData.observer_phone_number_attr_id).val(indiciaData.user_phone_number);
        }
        //Add class to attach validator to
        $('#smpAttr\\:'+ indiciaData.observer_email_attr_id).addClass('emailOrPhoneRequired');
        $('#smpAttr\\:'+ indiciaData.observer_phone_number_attr_id).addClass('emailOrPhoneRequired');
      break;
      case 'volunteer':
        //When logged in as a volunteer we don't show the fields, we collect the values in the background from the profile.
        //Make sure we don't overrite the fields if they are already filled in.
        $('#obSelect\\:' + indiciaData.observer_name_attr_id + '\\:fullname_surname_first').remove();
        $('#obSelect\\:' + indiciaData.observer_name_attr_id + '_lock').remove();
        $('[for=\"obSelect\\:' + indiciaData.observer_name_attr_id + '\\:fullname_surname_first\"]').remove();
        $('#observer-fieldset').hide();
        $('#smpAttr\\:' + indiciaData.observer_name_attr_id).hide();
        if (!$('#smpAttr\\:' + indiciaData.observer_name_attr_id).val() && !indiciaData.sample_id) {
          $('#smpAttr\\:' + indiciaData.observer_name_attr_id).val(indiciaData.person_name);
        }//Remove the lock icon
        $('#smpAttr\\:' + indiciaData.observer_name_attr_id + '_lock').remove();
        $('[for=\"smpAttr\\:' + indiciaData.observer_name_attr_id + '\"]').remove();
        
        if (indiciaData.user_email && !$('#smpAttr\\:'+ indiciaData.observer_email_attr_id).val() && !indiciaData.sample_id) {
          $('#smpAttr\\:'+indiciaData.observer_email_attr_id).val(indiciaData.user_email);
        }
        //Only hide (not remove) this field's value as we still want to submit it
        $('#smpAttr\\:' + indiciaData.observer_email_attr_id).hide();
        $('#smpAttr\\:' + indiciaData.observer_email_attr_id + '_lock').remove();
        $('[for=\"smpAttr\\:' + indiciaData.observer_email_attr_id + '\"]').remove();
        if (indiciaData.observer_phone_number_attr_id && !$('#smpAttr\\:'+ indiciaData.observer_phone_number_attr_id).val() && !indiciaData.sample_id) {
          $('#smpAttr\\:'+indiciaData.observer_phone_number_attr_id).val(indiciaData.user_phone_number);
        }
        $('#smpAttr\\:' + indiciaData.observer_phone_number_attr_id).hide();
         $('#smpAttr\\:' + indiciaData.observer_phone_number_attr_id + '_lock').remove();
        $('[for=\"smpAttr\\:' + indiciaData.observer_phone_number_attr_id + '\"]').remove();
      break;
      default:
        //Guests get free text fields to fill in, observer name autocomplete isn't displayed
        $('#obSelect\\:' + indiciaData.observer_name_attr_id + '_lock').remove();
        $('#obSelect\\:' + indiciaData.observer_name_attr_id + '\\:fullname_surname_first').remove();  
        $('[for=\"obSelect\\:' + indiciaData.observer_name_attr_id + '\\:fullname_surname_first\"]').remove();
        $('[name=\"smpAttr\\:' + indiciaData.observer_name_attr_id + '\"]').rules('add', {required:true});
        $('<span class=\"deh-required\">*</span>').insertAfter($('[name=\"smpAttr\\:' + indiciaData.observer_name_attr_id + '\"]'));
        
        //attach validator classes for these fields
        $('#smpAttr\\:'+ indiciaData.observer_email_attr_id).addClass('emailOrPhoneRequired');
        $('#smpAttr\\:'+ indiciaData.observer_phone_number_attr_id).addClass('emailOrPhoneRequired');
      break;
    }

    //Phone number or email must be supplied, attach validator to the class.
    $('#entry_form').validate(); 
    jQuery.validator.addMethod('emailOrPhoneRequired',
      function(value, element) {
        if ($('#smpAttr\\:' + indiciaData.observer_email_attr_id).val() ||  $('#smpAttr\\:' + indiciaData.observer_phone_number_attr_id).val()) {
          return true;
        }   
      }, indiciaData.emailPhoneMessage
    );
    
    //Only on the normal (not adhoc) recording form, the "New Step" button is only displayed if the Cetaceans Seen option is yes.
    cetaceans_control_next_step = function cetaceans_control_next_step() {
      //By default we show the save button
      $('[id=\"tab-next\"]').hide();
      $('.right.buttons').append('<input id=\"save-button\" class=\"indicia-button inline-control tab-submit\" type=\"submit\" value=\"Save\">');
      //Show Next Step button instead of save if Cetaceans Seen is yes. Do this on page load but also on change     
      $('[name^=\"smpAttr\\:' + indiciaData.cetaceans_seen_attr_id + '\"]').change(function() {
        //Need "\\:0" at the end of the selector as it is a pair of radio button options and we want the first one which is "Yes"
        if ($('#smpAttr\\:' + indiciaData.cetaceans_seen_attr_id + '\\:0').is(':checked')) {
          $('[id=\"tab-next\"]').show();
          $('[id=\"save-button\"]').hide();
        } else {     
          $('[id=\"tab-next\"]').hide();
          $('[id=\"save-button\"]').show();
        }
      });
      //Need "\\:0" at the end of the selector as it is a pair of radio button options and we want the first one which is "Yes"
      if ($('#smpAttr\\:' + indiciaData.cetaceans_seen_attr_id + '\\:0').is(':checked')) {
        $('[id=\"tab-next\"]').show();
        $('[id=\"save-button\"]').hide();
      } else {     
        $('[id=\"tab-next\"]').hide();
        $('[id=\"save-button\"]').show();
      }
    }
    
    //There are two Reticule drop-downs in the grid. If one is field is filled in then the other is becomes mandatory on the adhoc form (both are always mandatory
    //on the non-adhoc form)
    adhoc_reticule_fields_validator = function adhoc_reticule_fields_validator() {
      if (indiciaData.adhocMode==1) {
        var currentId, idElements;
        //Validation applies if either Reticles fields are changed
        $('.scReticules,.scReticulesFrom').live('change',function () {
          currentId=this.id;
          idElements = currentId.split(':');
          //The last part of the field id that has been changed is the attribute id
          //Check this to see if it matches the Reticules field.
          //If it doesn't match then we know the Reticules From field has changed instead
          if (idElements.pop()==indiciaData.reticules_attr_id) {
            //If the user has changed the Reticules field, then the 'current' field we are working with
            //is Reticules and the 'other' is Reticules From
            //(Note that we need different selectors for items we are reading and adding as the read selectors need
            //backslashes before the colons which we don't have when adding)
            currentIdToRead = 'sc\\:'+ idElements[1] + '\\:\\:occAttr\\:' + indiciaData.reticules_attr_id;
            otherIdToRead = 'sc\\:'+ idElements[1] + '\\:\\:occAttr\\:'  + indiciaData.reticules_from_attr_id;
            currentIdToAdd = 'sc:'+ idElements[1] + '::occAttr:' + indiciaData.reticules_attr_id;
            otherIdToAdd = 'sc:'+ idElements[1] + '::occAttr:' + indiciaData.reticules_from_attr_id;
          } else {
            //If the user has changed the Reticules From field, then the 'current' field we are working with
            //is Reticules From and the 'other' is Reticules.
            currentIdToRead = 'sc\\:'+ idElements[1] + '\\:\\:occAttr\\:' + indiciaData.reticules_from_attr_id;
            otherIdToRead = 'sc\\:'+ idElements[1] + '\\:\\:occAttr\\:' + indiciaData.reticules_attr_id;
            currentIdToAdd  = 'sc:'+ idElements[1] + '::occAttr:' + indiciaData.reticules_from_attr_id;
            otherIdToAdd  = 'sc:'+ idElements[1] + '::occAttr:' + indiciaData.reticules_attr_id;
          }
          //Ids associated with the mandatory field red stars
          reticulesFromStarIdCurrent = currentIdToRead + '-' + 'star';
          reticulesFromStarIdOther = otherIdToRead + '-' + 'star';
          //When neither reticule field is filled in or when both are filled in, then we don't need to warn the user
          if ((!$('#'+ currentIdToRead).val() && !$('#'+ otherIdToRead).val()) || ($('#'+ currentIdToRead).val() && $('#'+ otherIdToRead).val())) {
            //If the user has changed a reticule field into a position where we don't need to warn the user anymore,
            //then clear any existing stars or warnings.
            if ($('#'+ reticulesFromStarIdCurrent).length) {          
              $('#'+ reticulesFromStarIdCurrent).remove();
              $('#'+ currentIdToRead).rules('remove', 'required');
            }
            if ($('#'+ reticulesFromStarIdOther).length) {
              $('#'+ reticulesFromStarIdOther).remove(); 
              $('#'+ otherIdToRead).rules('remove', 'required'); 
            }
          } else {
            //Otherwise we need to add warnings
            //If either field is empty, then add the mandatory warnings and stars
            if (!$('#'+ currentIdToRead).val()) {
              reticulesStarIdCurrentToRead = currentIdToRead + '-' + 'star';
              reticulesStarIdCurrentToAdd = currentIdToAdd + '-' + 'star';
              if(!$('#'+ reticulesStarIdCurrentToRead).length) {
                $('#'+ currentIdToRead).rules('add', {required:true});
                $('<span id='+reticulesStarIdCurrentToAdd+' class=\"deh-required\">*</span>').insertAfter('#'+ currentIdToRead);
              }
            }
            if (!$('#'+ otherIdToRead).val()) {
              reticulesStarIdOtherToRead = otherIdToRead + '-' + 'star';
              reticulesStarIdOtherToAdd = otherIdToAdd + '-' + 'star';
              if(!$('#'+ reticulesStarIdOtherToRead ).length) {
                $('#'+ otherIdToRead).rules('add', {required:true});
                $('<span id='+reticulesStarIdOtherToAdd+' class=\"deh-required\">*</span>').insertAfter('#'+ otherIdToRead);
              }
            }
          }
        });
      }
    }
  }
  
  //When page is in addhoc mode, the user is not allowed submit when there are no sightings.
  adhoc_sightings_grid_species_validator = function adhoc_sightings_grid_species_validator() {
    $('#entry_form').validate(); 
    if (indiciaData.adhocMode==1) {
      jQuery.validator.addMethod('grid-required',
        function(value, element) {
          //We check the row number by taking the last character of the species cell id.
          if (value && element.id[element.id.length-1]==='0'|| 
            element.id[element.id.length-1]!=='0') { 
              return true;
          }   
        }, indiciaData.addSpeciesMessage
      );
    }
  }
}) (jQuery);
