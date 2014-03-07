
/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 */

var acknowledge_all_notifications;
var store_notification_ids;
var remove_message;
var reply;
var createNotifications;

(function ($) {  
  //Setup confirmation for Remove Notifications buttons.
  //If user elects to continue, set the hidden field that indicates
  //they want to continue with the removal.
  acknowledge_all_notifications = function(id, title) { 
    if ($('#' + id + ' .source_types').val()) {
      var confirmation = confirm('Are you sure you want to acknowledge all ' + title + ' in this list?');
      if (confirmation) { 
        $('.remove-notifications').val(1); 
      } else {
        return false;
      }
    } else {
      return false;
    }
  };

  //javascript to support removal of a single message.
  remove_message = function(id) {
    data = {
      'website_id':indiciaData.website_id,
      'notification:id': id,
      'acknowledged':'t'
    };
    $.post(
      indiciaData.notification_proxy_url+'&user_id=$user_id',
      data,
      function (response) {
        if (typeof response.success==='undefined') {
          alert('An error occurred whilst removing the message.');
          alert(JSON.stringify(response));
        } else {
          //reload grid after notification is deleted
          if (indiciaData.reports.notifications_system) {
            indiciaData.reports.notifications_system.grid_notifications_system.removeRecordsFromPage(1);
            indiciaData.reports.notifications_system.grid_notifications_system.reload();
          }
          if (indiciaData.reports.notifications_user) {
            indiciaData.reports.notifications_user.grid_notifications_user.removeRecordsFromPage(1);
            indiciaData.reports.notifications_user.grid_notifications_user.reload();
          }
          $('tr#row'+id).remove();
        }
      },
      'json'
    );
  };

  //javascript to support posting of a reply.
  reply = function (occurrence_id, notification_id, continued) {
    if (continued===false) {
      $('#reply-row-'+occurrence_id).remove();
      $('tr#row'+notification_id+' .action-button').show();
      return false;
    }
    if (!$('#reply-'+occurrence_id).val()) {
      alert('Please provide a comment');
      return false;
    }
    var data = {
      'website_id': indiciaData.website_id,
      'occurrence_comment:occurrence_id': occurrence_id,
      'occurrence_comment:comment': $('#reply-'+occurrence_id).val(),
      'user_id': indiciaData.user_id
    };

    $.post(
      indiciaData.occurrence_comment_proxy_url+'&sharing=reporting',
      data,
      function (response) {
        if (typeof response.success==='undefined') {
          alert('An error occurred. Comment could not be saved.');
          alert(JSON.stringify(response));
        } else {
          alert('Your comment has been saved');
        }            
      },
      'json'
    );
    $('#reply-row-'+occurrence_id).remove();
    $('tr#row'+notification_id+' .action-button').show();
  }
}) (jQuery);
