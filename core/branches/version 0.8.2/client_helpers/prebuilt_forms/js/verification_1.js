/**
 * Builds a URL that any post-backs to the form can use as a destination. This URL embeds the grid's current sort
 * and pagination information into it, allowing the grid to reload it's current state. For example, when clicking the 
 * verify or reject buttons, this URL is used as the destination for the post.
 */
function submit_to(){
  // We need to dynamically build the submitTo so we get the correct sort order
  var submitTo = "";
  // access globals created by the report grid to get the current state of pagination and sort as a result of AJAX calls
  if (typeof report_grid_page!=="undefined" && report_grid_page!==null && report_grid_page!=="") {
    url.params["page-verification-grid"] = report_grid_page;
  }
  if (typeof report_grid_orderby!=="undefined" && report_grid_orderby!==null && report_grid_orderby!=="") {
    url.params["orderby-verification-grid"] = report_grid_orderby;
  } else {
    delete url.params["orderby-verification-grid"];
  }
  if (typeof report_grid_sortdir!=="undefined" && report_grid_sortdir!==null && report_grid_sortdir!=="") {
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
    var verifier = indicia_user_id;
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
  var subject = email_subject_send_to_verifier.replace(/%taxon%/g, taxon).replace(/%id%/g, id);
  var photoHTML = '';
  var row = jQuery('#row' + id)[0];
  var record='';
  jQuery.each(row.childNodes, function(i, item) {
    var $item = jQuery(item);
    var attrClass = $item.attr('class');
    if (typeof attrClass !== "undefined") {
      var classList = attrClass.split(/\s+/);
      if (jQuery.trim($item.text())!=='' && classList.length >= 2 && classList[0] == 'data') {
        record += classList[1] + ': ' + $item.text() + "\n";
      } else if ($item.children().length===1 && $item.children().attr('href')!==undefined) {
        // replace the photo with a tag, since our email is edited in a textarea that cannot show HTML.
        record += classList[1] + ': [photo]\n';
        // capture the HTML required for the image link, so it can be used in the email
        photoHTML=$item.html();
      }
    }
   });
  var body=email_body_send_to_verifier.replace(/%taxon%/g, taxon).replace(/%id%/g, id).replace(/%record%/g, record);
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
'<input type="hidden" name="action" value="send_to_verifier" />'+
'<input type="hidden" id="occurrence:id" name="occurrence:id" value="'+id+'" />'+
'<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="S" />'+
'<input type="hidden" id="website_id" name="website_id" value="' + websiteId + '" />'+
'</fieldset>'+
'<input type="button" value="Send Email" onclick="'+
//'$(\'form#send_for_verification_email\').attr(\'action\', submit_to());'+
//jQuery is confused by the hidden input called "action"
'$(\'form#send_for_verification_email\').get(0).setAttribute(\'action\', submit_to());'+
'$(\'form#send_for_verification_email\').submit();'+
'">'+
'</form>');
  jQuery('#send_for_verification_email').validate({errorElement: "span"});

}

escapeHTML = function(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

/**
 * Method to display a comments popup, with the ability to add a new one.
 */
indicia_comments = function(taxon, id, cmsUser, read_nonce, read_auth_token, write_nonce, write_auth_token) {
  jQuery.getJSON(svc + 'occurrence_comment?callback=?', {"mode": 'json', "nonce": read_nonce, "auth_token": read_auth_token, "occurrence_id": id}, function(response) {
    var html = '<div id="comment-popup"><h1>Comments on record of '+taxon+'</h1>';
    if (response.length>0) {
      html += '<table>';
      jQuery.each(response, function(i, item) {
        html += '<tr>';
        html += '<td class="metadata">' + item.username + '<br/>' + item.updated_on + '</td>';
        html += '<td>' + item.comment + '</td>';
        html += '</tr>';
      });
      html += '</table>';    
    } else {
      html += '<p>There are no comments for this record yet.</p>';
    }
    
    html += '<form id="general_comment" action="'+submit_to()+'" method="post" >'+
        '<fieldset><legend>Enter comment for record of '+taxon+'</legend>'+
        '<label for="comment">Comment:</label>'+
        '<textarea name="comment" class="required" rows="10" cols="80"></textarea><br/>'+
        '<input type="hidden" name="occurrence:id" value="'+id+'" />'+
        '<input type="hidden" name="action" value="general_comment" />'+
        '</fieldset>'+
        '<input type="submit" value="Save Comment">'+
        '<input type="button" name="Cancel" value="Cancel" onclick="indicia_close_comment_form();">'+
        '</form>'
    html += '</div>';
    jQuery.fancybox(html);
  });
}