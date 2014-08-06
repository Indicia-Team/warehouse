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
		  if (typeof data.error === 'undefined') {
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
	  var s = {
		"website_id":indiciaData.website_id,
		"occurrence:id":id,
		"occurrence:release_status":"R"
	  };
	  postToServer(s, 'occurrence');
	}
  
  //Verify a record.
  //Can be called from the action column configuration on the edit tab.
  verify_record = function(id) {
    var confirmation = confirm('Do you really want to verify the record with id '+id+'?');
    if (confirmation) { 
      var s = {
        "website_id":indiciaData.website_id,
        "occurrence:id":id,
        "occurrence:release_status":"R",
        "occurrence:record_status":"V"
      };
      postToServer(s, 'occurrence');
    } else {
      return false;
    }
	}
  
  //Add an occurrence comment.
  //Can be called from the action column configuration on the edit tab.
  add_comment = function(id,date,entered_sref,record_status,created_by_id) {
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
        $('#occurrence-comment-form').submit(occurrence_comment_submit(id, "'" + date + "'" , "'" + entered_sref + "'" , "'" + record_status + "'",created_by_id));
      }
    });
  }
  
  //Submit an occurrence comment to the database
  occurrence_comment_submit = function(id,date,entered_sref,record_status,created_by_id) {
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
      //The data column on the notification includes lots of information about the occurrence, collect this information to put into the database.
      var dataJson = {
        'occurrence_id':id.toString(),
        'username':indiciaData.currentUsername,
        'comment':$('#occurrence-comment').val(),
        'date':date,
        'entered_sref':entered_sref,
        'record_status':record_status,
        'updated_on':dateTimeTodaySlashed,
        'auto_generated':'t'
      };
      //Post a notification to the database to say the occurrence comment has been created.
      var notificationData = {
        "website_id":indiciaData.website_id,
        "notification:triggered_on": dateTimeTodaySlashed,
        "notification:acknowledged": "f",
        "notification:user_id": created_by_id,
        "notification:source": "occurrence_comments",
        "notification:source_type": "C",
        "notification:data": JSON.stringify(dataJson), 
        "notification:linked_id": id
      }
      postToServer(notificationData, 'notification');
      $.fancybox.close();
      alert('Comment added');
    }
});