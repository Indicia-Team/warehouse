var occurrence_id = null, current_record = null, saveComment;

(function($) {

function showComment(comment, username) {
  // Remove message that there are no comments
  $('#no-comments').hide();
  var html = '<div class="comment">', c = comment.replace(/\n/g, '<br/>');
  html += '<div class="header">';
  html += '<strong>' + username + '</strong> Now';
  html += '</div>';
  html += '<div>' + c + '</div>';
  html += '</div>';
  $('#comment-list').prepend(html);
}

saveComment = function (occurrence_id) {
  var data = {
    'website_id': indiciaData.website_id,
    'occurrence_comment:occurrence_id': occurrence_id,
    'occurrence_comment:comment': $('#comment-text').val(),
    'occurrence_comment:person_name': indiciaData.username,
    'user_id': indiciaData.user_id
  };
  $.post(
    indiciaData.ajaxFormPostUrl.replace('occurrence', 'occ-comment'),
    data,
    function (data) {
      if (typeof data.error === "undefined") {
        showComment($('#comment-text').val(), indiciaData.username);
        $('#comment-text').val('');
      } else {
        alert(data.error);
      }
    },
    'json'
  );
}

})(jQuery);