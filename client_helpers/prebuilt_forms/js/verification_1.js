function submit_to(){
  // We need to dynamically build the submitTo so we get the correct sort order
  var submitTo = "";
  // access globals created by the report grid to get the current state of pagination and sort as a result of AJAX calls
  url.params["page-verification-grid"] = report_grid_page;
  if (report_grid_orderby!=null && report_grid_orderby!="") {
    url.params["orderby-verification-grid"] = report_grid_orderby;
  } else {
    delete url.params["orderby-verification-grid"];
  }
  if (report_grid_sortdir!=null && report_grid_sortdir!="") {
    url.params["sortdir-verification-grid"] = report_grid_sortdir;
  } else {
    delete url.params["sortdir-verification-grid"]
  }
  $.each(url.params, function(field, value) {
    submitTo += (submitTo ==="" ? "?" : "&");
    submitTo += field + "=" + value;
  });
  submitTo = url.path + submitTo;
  return submitTo;
}

function indicia_verify(taxon, id, valid, cmsUser){
  var action;
  if (valid) {
    $("#occurrence\\:record_status").attr("value", "V");
    action = "verify";
  } else {
    $("#occurrence\\:record_status").attr("value", "R");
    action = "reject";
  }
  if (confirm("Are you sure you want to " + action + " this record of " + taxon + "?")) {
    $("#occurrence\\:id").attr("value", id);
    var verifier = "";
    if (verifiers_mapping.indexOf("=")==-1) {
      verifier = verifiers_mapping;
    } else {
      var verifierMaps = verifiers_mapping.split(",");
      var keyval = new Array();
      $.each(verifierMaps, function(idx, map) {
        keyval = map.split("=");
        if (parseInt($.trim(keyval[0]))==cmsUser) {
          verifier = $.trim(keyval[1]);
        }
      });
    }
    if (verifier == ""){
      alert("You do not have a mapping to an Indicia user so cannot verify records")
    } else {
      $("#occurrence\\:verified_by_id").attr("value", verifier);
      $("form#verify").attr("action", submit_to());
      $("form#verify").submit();
    }
  }
}
