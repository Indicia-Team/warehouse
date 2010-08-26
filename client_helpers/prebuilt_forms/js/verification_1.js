/**
 * Builds a URL that any post-backs to the form can use as a destination. This URL embeds the grid's current sort
 * and pagination information into it, allowing the grid to reload it's current state. For example, when clicking the 
 * verify or reject buttons, this URL is used as the destination for the post.
 */
function submit_to(){
  // We need to dynamically build the submitTo so we get the correct sort order
  var submitTo = "";
  // access globals created by the report grid to get the current state of pagination and sort as a result of AJAX calls
  url.params["page-verification-grid"] = report_grid_page;
  if (report_grid_orderby!==null && report_grid_orderby!=="") {
    url.params["orderby-verification-grid"] = report_grid_orderby;
  } else {
    delete url.params["orderby-verification-grid"];
  }
  if (report_grid_sortdir!==null && report_grid_sortdir!=="") {
    url.params["sortdir-verification-grid"] = report_grid_sortdir;
  } else {
    delete url.params["sortdir-verification-grid"];
  }
  $.each(url.params, function(field, value) {
    submitTo += (submitTo ==="" ? "?" : "&");
    submitTo += field + "=" + value;
  });
  submitTo = url.path + submitTo;
  return submitTo;
}

/**
 * Handler for click on the Verify or Reject button in the grid. Displays a form for entering a comment, 
 * then saves the comment and sets the record status.
 */
function indicia_verify(taxon, id, valid, cmsUser){
  var action = valid ? 'verification' : 'rejection';  
  jQuery.fancybox(
  '<form id="verify_comment" action="" method="post" >'+
  '<fieldset><legend>Enter ' + action + ' comment for record of '+taxon+'</legend>'+
  '<label for="comment">Comment:</label>'+
  '<textarea name="comment" class="required" id="comment" rows="10" cols="80"></textarea><br/>'+
  '</fieldset>'+
  '<input type="button" value="Save Comment" onclick="indicia_save_verification_comment(\''+taxon+'\','+id+','+valid+','+cmsUser + ');">'+
  '<input type="button" name="Cancel" value="Cancel" onclick="indicia_close_comment_form();">'+
  '</form>');
}

/**
 * Click cancel handler on comment form. Closes the form.
 */
function indicia_close_comment_form() {
  jQuery.fancybox.close();
}

function indicia_save_verification_comment(taxon, id, valid, cmsUser) {
  var action;
  $("#occurrence_comment\\:comment").val($("#comment").val());
  if (valid) {
    $("#occurrence\\:record_status").val("V");
    action = "verify";
  } else {
    $("#occurrence\\:record_status").val("R");
    action = "reject";
  }
  if (confirm("Are you sure you want to " + action + " this record of " + taxon + "?")) {
    $("#occurrence\\:id").attr("value", id);
    var verifier = "";
    if (verifiers_mapping.indexOf("=")==-1) {
      verifier = verifiers_mapping;
    } else {
      var verifierMaps = verifiers_mapping.split(",");
      var keyval = [];
      $.each(verifierMaps, function(idx, map) {
        keyval = map.split("=");
        if (parseInt($.trim(keyval[0]))==cmsUser) {
          verifier = $.trim(keyval[1]);
        }
      });
    }
    if (verifier === ""){
      alert("You do not have a mapping to an Indicia user so cannot verify records");
    } else {
      $("#occurrence\\:verified_by_id").attr("value", verifier);
      $("form#verify").attr("action", submit_to());
      $("form#verify").submit();
    }
  }
}

/**
 * When send to verifier is clicked, the FancyBox plugin is used to overlay an email form. The
 * form is prepopulated with suggested content, and the user is required to enter the email address of the 
 * verifier as well as check or amend the content. When it is send, then content is posted back to this
 * page, triggering the sending of the email. Additionally, the post data contains the occurrence id and
 * updated record status, causing the record to be set to Sent for verification.
 */
function indicia_send_to_verifier(taxon, id, cmsUser, websiteId) {
  // build email to send a request for verification to a verifier
  jQuery('#send_for_verification_subject').val('This is the subject');
  var subject = 'Record of ' + taxon + ' requires verification (ID:'+id+')';
  var photoHTML = '';
  var row = jQuery('#row' + id)[0];
  var body='The following record requires verification. Please reply to this mail with the word Verified or Rejected in the email body, followed by any '+
     'comments you have including the proposed re-identification if relevant on the next line.\n\n';      
  jQuery.each(row.childNodes, function(i, item) {
    if (jQuery.trim(item.textContent)!=='' && item.classList.length >= 2 && item.classList[0] == 'data') {
      body += item.classList[1] + ': ' + item.textContent + "\n";
    } else if (item.childElementCount===1 && item.children[0].attributes.getNamedItem('href')!==null) {
      // replace the photo with a tag, since our email is edited in a textarea that cannot show HTML. 
      body += item.classList[1] + ': [photo]\n';
      // capture the HTML required for the image link, so it can be used in the email
      photoHTML=item.innerHTML;
    }
  });
  jQuery.fancybox(
'<form id="send_for_verification_email" action="" method="post" >'+
'<fieldset><legend>Compose email to verifier</legend>'+
'<label for="send_for_verification_email_to">Email to:</label>'+
'<input type="text" name="email_to" class="required email" id="send_for_verification_email_to" size="80" ><br/>'+
'<label for="send_for_verification_subject">Subject:</label>'+
'<input type="text" name="email_subject" class="required" id="send_for_verification_subject" size="80" value="'+subject+'" ><br/>'+
'<label for="send_for_verification_subject">Body:</label>'+
'<textarea name="email_content" class="required" id="send_for_verification_body" rows="10" cols="80">'+body+'</textarea><br/>'+
// pass the HTML for the photo through in the form, so it can be replaced into the text
'<input type="hidden" name="photoHTML" value="' + escapeHTML(photoHTML) + '">'+
'<input type="hidden" name="email" value="1">'+
auth['write'] + 
'<input type="hidden" id="occurrence:id" name="occurrence:id" value="'+id+'" />'+
'<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="S" />'+
'<input type="hidden" id="website_id" name="website_id" value="' + websiteId + '" />'+
'</fieldset>'+
'<input type="button" value="Send Email" onclick="'+
'$(\'form#send_for_verification_email\').attr(\'action\', submit_to());'+
'$(\'form#send_for_verification_email\').submit();'+
'">'+
'</form>');
  jQuery('#send_for_verification_email').validate({errorElement: "span"});

}

escapeHTML = function(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};
