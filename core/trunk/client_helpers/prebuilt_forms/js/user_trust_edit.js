$(document).ready(function($) {
  "use strict";
  // keep track of the last person loaded for each of the 3 tabs
  var surveyLoadedUID=null, groupLoadedUID=null, locationLoadedUID=null;
  
  function showTab(tab) {
    var uid=$('#user_trust\\:user_id').val();
    if (uid!=='') {
      if (tab==='tab-surveys' && surveyLoadedUID!==uid) {
        indiciaData.reports.surveys_summary.grid_surveys_summary[0].settings.extraParams.user_id = uid;
        indiciaData.reports.surveys_summary.grid_surveys_summary.ajaxload();
        surveyLoadedUID=uid;
      }
      else if (tab==='tab-taxon-groups' && groupLoadedUID!==uid) {
        indiciaData.reports.taxon_groups_summary.grid_taxon_groups_summary[0].settings.extraParams.user_id = uid;
        indiciaData.reports.taxon_groups_summary.grid_taxon_groups_summary.ajaxload();
        groupLoadedUID=uid;
      }
      else if (tab==='tab-locations' && locationLoadedUID!==uid) {
        indiciaData.reports.locations_summary.grid_locations_summary[0].settings.extraParams.user_id = uid;
        indiciaData.reports.locations_summary.grid_locations_summary.ajaxload();
        locationLoadedUID=uid;
      }
    }
  }
  var tabHandler = function(event, ui) {
    showTab(ui.panel.id);
  };
  $('#summary-tabs').bind('tabsshow', tabHandler);
  $('#user_trust\\:user_id').change(function() {
    showTab($('#summary-tabs .ui-tabs-panel:not(.ui-tabs-hide)')[0].id);
  });
  // focus controls can select appropriate summary tab
  $('#user_trust\\:survey_id\\:title').focus(function() {
    indiciaFns.activeTab($('#summary-tabs'), 0);
  });
  $('#user_trust\\:taxon_group_id\\:title').focus(function() {
    indiciaFns.activeTab($('#summary-tabs'), 1);
  });
  $('#user_trust\\:location_id\\:name').focus(function() {
    indiciaFns.activeTab($('#summary-tabs'), 2);
  });
  
});