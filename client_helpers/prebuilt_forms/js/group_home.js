var release_record
var verify_record;
var add_comment;
var occurrence_comment_submit;

jQuery(document).ready(function($) {
  function postToServer(s, typeOfPost) {
    if (typeOfPost==='occurrence') 
      $postUrl = indiciaData.baseUrl+'/?q=ajaxproxy&node='+indiciaData.nodeId+'&index=occurrence';
    else if (typeOfPost==='occurrence_comment') 
      $postUrl = indiciaData.baseUrl+'/?q=ajaxproxy&node='+indiciaData.nodeId+'&index=occ-comment';
    else if (typeOfPost==='notification') 
      $postUrl = indiciaData.baseUrl+'/?q=ajaxproxy&node='+indiciaData.nodeId+'&index=notification';
    else 
      $postUrl = indiciaData.baseUrl+'/?q=ajaxproxy&node='+indiciaData.nodeId+'&index=occurrence';
    
	  $.post($postUrl, 
		s,
		function (data) {
      //No need to reload grid if only adding a comment
      if (typeof data.error === 'undefined'&typeOfPost!=='occurrence_comment') {
        indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
      } else {
        alert(data.error);
      }
		},
		'json'
	  );
	}
  
  //Set the release_status of an occurrence to released.
  //Can be called from the action column configuration on the edit tab.
	release_record = function(id) {
    var confirmation = confirm('Do you really want to release the record with id '+id+'?');
	  if (confirmation) { 
      var s = {
      "website_id":indiciaData.website_id,
      "occurrence:id":id,
      "occurrence:release_status":"R"
      };
      postToServer(s, 'occurrence');
    } else {
      return false;
    }
	}
  
  //Verify a record.
  //Can be called from the action column configuration on the edit tab.
  verify_record = function(id) {
    var confirmation = confirm('Do you really want to verify the record with id '+id+'?');
    if (confirmation) { 
      verify_reject_post_to_server(id, "V",indiciaData.verifiedTranslation); 
    } else {
      return false;
    }
	}
  
  //Reject a record.
  //Can be called from the action column configuration on the edit tab.
  reject_record = function(id) {
    var confirmation = confirm('Do you really want to reject the record with id '+id+'?');
    if (confirmation) { 
      verify_reject_post_to_server(id, "R",indiciaData.rejectedTranslation);  
    } else {
      return false;
    }
	}
  
  verify_reject_post_to_server = function(id,record_status,comment) {
    var s = {
      "website_id":indiciaData.website_id,
      "occurrence:id":id,
      "occurrence:record_status":record_status,
      "occurrence:release_status":"R",
      'user_id': indiciaData.userId,
      'occurrence_comment:comment': comment
    };
    postToServer(s, 'occurrence');
  }
  
  //Add an occurrence comment.
  //Can be called from the action column configuration on the edit tab.
  add_comment = function(id) {
    $.fancybox(
      '<form id="occurrence-comment-form">' +
        '<fieldset class="popup-form">' +
          '<legend>Occurrence Comment</legend>' +
          '<textarea id="occurrence-comment" rows="4" class="required"></textarea>'+
          '<input type="button" id="occurrence-comment-save-button" value="Submit">' +
        '</fieldset>' +
      '</form>'
    );
    $('#occurrence-comment-save-button').click(function(){ 
      //Trim white space
      if (!$('#occurrence-comment').val().trim()) {
        alert('Please enter an occurrence comment before saving.'); 
        return false;
      } else {
        occurrence_comment_submit(id);
      }
    });
  }
  
  //Submit an occurrence comment to the database
  occurrence_comment_submit = function(id) {
      var dateObj = new Date();
      var dateTimeTodaySlashed = dateObj.getFullYear() + '-' + (dateObj.getMonth()+1)+ '-' + dateObj.getDate() + ' ' + dateObj.getHours() + ':' + dateObj.getMinutes() + ':' + dateObj.getSeconds();
      var dateTimeTodayHyphened = dateObj.getDate() + '/' + (dateObj.getMonth()+1) + '/' + dateObj.getFullYear()+ ' ' + dateObj.getHours() + ':' + dateObj.getMinutes() + ':' + dateObj.getSeconds();
      //Post the occurrence comment to the database.
      var occurrenceComment = {
        "website_id":indiciaData.website_id,
        "occurrence_comment:occurrence_id":id,
        "occurrence_comment:comment": $('#occurrence-comment').val()
      };
      postToServer(occurrenceComment, 'occurrence_comment');
      $.fancybox.close();
      alert('Comment added');
    }
});