var mapDiv = null, occurrence_id = null, current_record = null, urlSep, validator;

function selectRow(tr) {
  // The row ID is row1234 where 1234 is the occurrence ID. 
  occurrence_id = tr.id.substr(3);
  $.getJSON(
    indiciaData.ajaxUrl + '/details' + urlSep + 'occurrence_id=' + occurrence_id,
    null,
    function (data) {
      current_record = data;
      $('#click-record-notice').hide();
      $('#record-details-content').show();
      if ($(tr).parents('tbody').length !== 0) {
        $(tr).parents('tbody').children('tr').removeClass('selected');
        $(tr).addClass('selected');
        // point the image and comments tabs to the correct AJAX call for the selected occurrence.
        //$('#record-details-tabs').tabs('url', 0, indiciaData.rootUrl + '?q=iform/ajax/verification_3/details&occurrence_id='+occurrence_id);
        $('#record-details-tabs').tabs('url', 2, indiciaData.ajaxUrl + '/images' + urlSep + 'occurrence_id=' + occurrence_id);
        $('#record-details-tabs').tabs('url', 3, indiciaData.ajaxUrl + '/comments' + urlSep + 'occurrence_id=' + occurrence_id);
        // reload current tabs
        $('#record-details-tabs').tabs('load', $('#record-details-tabs').tabs('option', 'selected'));
        $('#record-details-toolbar *').attr('disabled', '');
        showTab();
      }
    }
  );
}

/** 
 * Post an object containing occurrence form data into the Warehouse. Updates the
 * visual indicators of the record's status.
 */
function postOccurrence(occ) {
  var status = occ['occurrence:record_status'];
  $.post(
    indiciaData.ajaxFormPostUrl,
    occ,
    function (data) {
      $('#row' + occurrence_id + ' td:first div, #details-tab td').removeClass('status-V');
      $('#row' + occurrence_id + ' td:first div, #details-tab td').removeClass('status-C');
      $('#row' + occurrence_id + ' td:first div, #details-tab td').removeClass('status-R');
      $('#row' + occurrence_id + ' td:first div, #details-tab td').removeClass('status-I');
      $('#row' + occurrence_id + ' td:first div, #details-tab td').removeClass('status-T');
      $('#row' + occurrence_id + ' td:first div, #details-tab td.status').addClass('status-' + status);
      var text = indiciaData.statusTranslations[status];
      $('#details-tab td.status').html(text);
      if ($('#record-details-tabs').tabs('option', 'selected') == 0 ||
          $('#record-details-tabs').tabs('option', 'selected') == 3) {
        $('#record-details-tabs').tabs('load', $('#record-details-tabs').tabs('option', 'selected'));
      }
      if (indiciaData.autoDiscard) {
        var nextRow = $('#row' + occurrence_id).next();
        $('#row' + occurrence_id).remove();
        selectRow(nextRow[0]);
      }
    }
  );
  $('#add-comment').remove();
}

function sendEmail() {
  if (validator.numberOfInvalids()===0) {
    var data = {
      'to': $('#email-to').val(),
      'subject': $('#email-subject').val(),
      'body': $('#email-body').val()
    };
    // ensure images are loaded
    $.ajax({
      url: indiciaData.ajaxUrl + '/imagesAndComments' + urlSep + 'occurrence_id=' + occurrence_id,
      async: false,
      dataType: 'json',
      success: function (response) {
        data.body = data.body.replace(/\[Photos\]/g, response.images);
        data.body = data.body.replace(/\[Comments\]/g, response.comments);
      }
    });
    // set the status
    var status = 'S',
      occ = {
        'website_id': indiciaData.website_id,
        'occurrence:id': occurrence_id,
        'occurrence:record_status': status
      };
    postOccurrence(occ);
    // use an AJAX call to get the server to send the email
    $.post(
      indiciaData.ajaxUrl + '/email',
      data,
      function (response) {
        if (response === 'OK') {
          $.fancybox.close();
          alert(indiciaData.popupTranslations.emailSent);
        } else {
          $.fancybox('<div class="manual-email">' + indiciaData.popupTranslations.requestManualEmail +
                '<div class="ui-helper-clearfix"><span class="left">To:</span><div class="right">' + data.to + '</div></div>' +
                '<div class="ui-helper-clearfix"><span class="left">Subject:</span><div class="right">' + data.subject + '</div></div>' +
                '<div class="ui-helper-clearfix"><span class="left">Content:</span><div class="right">' + data.body.replace(/\n/g, '<br/>') + '</div></div>' +
                '</div>');
        }
      }
    );
  }
  return false;
}

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

function saveComment() {
  var data = {
    'website_id': indiciaData.website_id,
    'occurrence_comment:occurrence_id': occurrence_id,
    'occurrence_comment:comment': $('#comment-text').val(),
    'occurrence_comment:person_name': indiciaData.username
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
    }
  );
}


function saveVerifyComment() {
  var status = $('#set-status').val(),
    comment = indiciaData.statusTranslations[status],
    data;
  if ($('#verify-comment').val()!=='') {
    comment += ".\n" + $('#verify-comment').val();
  }  
  data = {
    'website_id': indiciaData.website_id,
    'occurrence:id': occurrence_id,
    'occurrence:record_status': status,
    'occurrence_comment:comment': comment,
    'occurrence_comment:person_name': indiciaData.username
  };
  $.fancybox.close();
  postOccurrence(data);
}

function showTab() {
  if (current_record !== null) {
    if ($('#record-details-tabs').tabs('option', 'selected') === 0) {
      $('#details-tab').html(current_record.content);
    } else if ($('#record-details-tabs').tabs('option', 'selected') === 1 && mapDiv !== null) {
      var parser = new OpenLayers.Format.WKT(),
        feature = parser.read(current_record.additional.wkt),
        c = feature.geometry.getCentroid();
      mapDiv.map.editLayer.removeAllFeatures();
      mapDiv.map.editLayer.addFeatures([feature]);
      mapDiv.map.setCenter(new OpenLayers.LonLat(c.x, c.y));
    }
  }
}

mapInitialisationHooks.push(function (div) {
  mapDiv = div;
  div.map.editLayer.style = null;
  div.map.editLayer.styleMap = new OpenLayers.StyleMap({
    'default': {
      pointRadius: 5,
      strokeColor: "#0000FF",
      strokeWidth: 3,
      fillColor: "#0000FF",
      fillOpacity: 0.4
    }
  });
  showTab();
});


$(document).ready(function () {
  
  $('table.report-grid').click(function (evt) {
    // Find the appropriate separator for AJAX url params - depends on clean urls setting.
    urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    $('#record-details-toolbar *').attr('disabled', 'disabled');
    selectRow($(evt.target).parents('tr')[0]);
  });

  function setStatus(status) {
    $.fancybox('<fieldset class="popup-form">' +
          '<legend>' + indiciaData.popupTranslations.title.replace('{1}', indiciaData.popupTranslations[status]) + '</legend>' +
          '<label>Comment:</label><textarea id="verify-comment" rows="5" cols="80"></textarea><br />' +
          '<input type="hidden" id="set-status" value="' + status + '"/>' +
          '<button type="button" class="default-button" onclick="saveVerifyComment();">' +
              indiciaData.popupTranslations.save.replace('{1}', indiciaData.popupTranslations['verb' + status]) + '</button>' +
          '</fieldset>');
  }

  $('#record-details-tabs').bind('tabsshow', function (evt, ui) {
    showTab();
  });

  $('#btn-verify').click(function () {
    setStatus('V');
  });

  $('#btn-reject').click(function () {
    setStatus('R');
  });

  $('#btn-email').click(function () {
    var record = '';
    $.each(current_record.data, function (idx, obj) {
      if (obj.value !== null && obj.value !=='') {
        record += obj.caption + ': ' + obj.value + "\n";
      }
    });

    record += "\n\n[Photos]\n\n[Comments]";

    var subject = indiciaData.email_subject_send_to_verifier
        .replace('%taxon%', current_record.additional.taxon)
        .replace('%id%', occurrence_id),
      body = indiciaData.email_body_send_to_verifier
        .replace('%taxon%', current_record.additional.taxon)
        .replace('%id%', occurrence_id)
        .replace('%record%', record);
    $('#record-details-tabs').tabs('load', 0);
    $.fancybox('<form id="email-form"><fieldset class="popup-form">' +
          '<legend>' + indiciaData.popupTranslations.emailTitle + '</legend>' +
          '<label>To:</label><input type="text" id="email-to" class="email required"/><br />' +
          '<label>Subject:</label><input type="text" id="email-subject" class="require" value="' + subject + '"/><br />' +
          '<label>Body:</label><textarea id="email-body" class="required">' + body + '</textarea><br />' +
          '<input type="hidden" id="set-status" value="' + status + '"/>' +
          '<input type="submit" class="default-button" ' +
              'value="' + indiciaData.popupTranslations.sendEmail + '" />' +
          '</fieldset></form>');
    validator = $('#email-form').validate({});
    $('#email-form').submit(sendEmail);
  });

});

