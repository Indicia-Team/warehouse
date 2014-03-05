/**
 * TODO:
 * If the centroid is already available from the list of available sites, then ask if they really want to 
 * create a new site.
 * 
 */
 
var allowCreateSites;

(function ($) {
  "use strict";
  
allowCreateSites=function() {
  // create a button for saving the location
  $('#imp-location\\:name').after('<button id="save-site" type="button" title="'+indiciaData.msgRememberSiteHint+
      '" id="save-site" style="display: none;" class="ui-corner-all ui-widget-content ui-state-default indicia-button inline-control">'+
      indiciaData.msgRememberSite+'</button>'+
      '<input name="save-site-flag" id="save-site-flag" type="hidden" value="0"/>');
  $('#imp-location\\:name,#imp-location,#imp-sref').change(function() {
    if ($('#imp-location\\:name').val().length>0 && $('#imp-sref').val().length>0 &&
        $('#imp-location').val().length===0) {
      $('#save-site').show();
    } else {
      $('#save-site').hide();
    }
  });
  $('#save-site').click(function() {
    if ($('#save-site').hasClass('ui-state-highlight')) {
      $('#save-site').removeClass('ui-state-highlight');
      $('#save-site-flag').val('0');
    } else {
      $('#save-site').addClass('ui-state-highlight');
      $('#save-site-flag').val('1');
      alert(indiciaData.msgSiteWillBeRemembered);
    }
  });
};

}) (jQuery);


