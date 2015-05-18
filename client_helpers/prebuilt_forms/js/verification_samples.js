var saveComment, saveVerifyComment, verificationGridLoaded, reselectRow, rowIdToReselect = false;

(function ($) {
  "use strict";
  
  var rowRequest=null, sample_id = null, currRec = null, urlSep, validator,
      multimode=false, email = {to:'', subject:'', body:'', type:''};

  reselectRow = function() {
    if (rowIdToReselect) {
      // Reselect current record if still in the grid
      var row = $('tr#row' + rowIdToReselect);
      if (row.length) {
        sample_id = null;
        selectRow(row[0]);
      } else {
        clearRow();
      }
      rowIdToReselect = false;
    }
  };

  /**
   * Resets to the state where no grid row is shown
   */
  function clearRow() {
    $('table.report-grid tr').removeClass('selected');
    $('#instructions').show();
    $('#record-details-content').hide();
    sample_id = null;
    currRec = null;
  }

  // IE7 compatability
  if(!Array.indexOf){
    Array.prototype.indexOf = function(obj){
      var i;
      for(i=0; i<this.length; i++){
        if(this[i]===obj){
          return i;
        }
      }
    };
  }

  function selectRow(tr, callback) {
    // The row ID is row1234 where 1234 is the sample ID.
    if (tr.id.substr(3) === sample_id) {
      if (typeof callback !== "undefined") {
        callback(tr);
      }
      return;
    }
    if (rowRequest) {
      rowRequest.abort();
    }
    // while we are loading, disable the toolbar
    $('#record-details-toolbar *').attr('disabled', 'disabled');
    sample_id = tr.id.substr(3);
    $(tr).addClass('selected');
    // make it clear things are loading
    $('#chart-div').css('opacity', 0.15);
    rowRequest = $.getJSON(
      indiciaData.ajaxUrl + '/details/' + indiciaData.nid + urlSep + 'sample_id=' + sample_id,
      null,
      function (data) {
        // refind the row, as $(tr) sometimes gets obliterated.
        var $row = $('#row' + data.data.Sample[0].value);
        rowRequest = null;
        currRec = data;
        $('#instructions').hide();
        $('#record-details-content').show();
        if ($row.parents('tbody').length !== 0) {
          // point the image and comments tabs to the correct AJAX call for the selected sample.
          indiciaFns.setTabHref($('#record-details-tabs'), indiciaData.detailsTabs.indexOf('media'), 'media-tab-tab',
            indiciaData.ajaxUrl + '/media/' + indiciaData.nid + urlSep + 'sample_id=' + sample_id);
          indiciaFns.setTabHref($('#record-details-tabs'), indiciaData.detailsTabs.indexOf('comments'), 'comments-tab-tab',
            indiciaData.ajaxUrl + '/comments/' + indiciaData.nid + urlSep + 'sample_id=' + sample_id);
          // reload current tabs
          $('#record-details-tabs').tabs('load', indiciaFns.activeTab($('#record-details-tabs')));
          $('#record-details-toolbar *').removeAttr('disabled');
          showTab();
        }
        if (typeof callback !== "undefined") {
          callback(tr);
        }
      }
    );
  }

  function removeStatusClasses(selector, prefix, items) {
    $.each(items, function() {
      $(selector).removeClass(prefix + '-' + this);
    });
  }

  /**
   * Post an object containing sample form data into the Warehouse. Updates the
   * visual indicators of the record's status.
   */
  function postVerification(occ) {
    var status = occ['sample:record_status'], id=occ['sample:id'];
    $.post(
      indiciaData.ajaxFormPostUrl.replace('sample', 'single_verify_sample'),
      occ,
      function () {
        removeStatusClasses('#row' + id + ' td:first div, #details-tab td', 'status', ['V','C','R','I','T']);
        $('#row' + id + ' td:first div, #details-tab td.status').addClass('status-' + status);
        var text = indiciaData.statusTranslations[status], nextRow;
        $('#details-tab').find('td.status').html(text);
        if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'details' ||
            indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'comments') {
          $('#record-details-tabs').tabs('load', indiciaFns.activeTab($('#record-details-tabs')));
        }
        if (indiciaData.autoDiscard) {
          nextRow = $('#row' + id).next();
          $('#row' + id).remove();
          if (nextRow.length>0) {
            selectRow(nextRow[0]);
            indiciaData.reports.verification.grid_verification_grid.removeRecordsFromPage(1);
          } else {
            // reload the grid once empty, to get the next page
            indiciaData.reports.verification.grid_verification_grid.reload();
            clearRow();
          }
        }
      }
    );
    $('#add-comment').remove();
  }

  /**
   * Build an email to send to a verifier or the original recorder, containing the record details.
   */
  function setupRecordCheckEmail(subject, body) {
    //Form to create email of record details
    var record = '';
    $.each(currRec.data, function (idx, section) {
      $.each(section, function(idx, field) {
        if (field.value !== null && field.value !=='') {
          record += field.caption + ': ' + field.value + "\n";
        }
      });
    });
    record += "\n\n[Photos]\n\n[Comments]";
    email.to = currRec.extra.recorder_email;
    email.subject = subject
        .replace('%taxon%', currRec.extra.taxon)
        .replace('%id%', sample_id);
    email.body = body
        .replace('%taxon%', currRec.extra.taxon)
        .replace('%id%', sample_id)
        .replace('%record%', record);
    $('#record-details-tabs').tabs('load', 0);
    email.type = 'recordCheck';
  }

  /**
   * Build an email for sending to another expert.
   */
  function buildVerifierEmail() {
    setupRecordCheckEmail(indiciaData.email_subject_send_to_verifier, indiciaData.email_body_send_to_verifier);
    // Let the user pick the recipient
    email.to = '';
    email.subtype='V';
    popupEmailExpert();
  }

  function recorderQueryEmailForm() {
    setupRecordCheckEmail(indiciaData.email_subject_send_to_recorder, indiciaData.email_body_send_to_recorder);
    return '<form id="email-form" class="popup-form"><fieldset>' +
      '<legend>' + indiciaData.popupTranslations.tab_email + '</legend>' +
      '<label>To:</label><input type="text" id="email-to" class="email required" value="' + email.to + '"/><br />' +
      '<label>Subject:</label><input type="text" id="email-subject" class="require" value="' + email.subject + '"/><br />' +
      '<label>Body:</label><textarea id="email-body" class="required">' + email.body + '</textarea><br />' +
      '<input type="submit" class="default-button" ' +
      'value="' + indiciaData.popupTranslations.sendEmail + '" />' +
      '</fieldset></form>';
  }

  function recorderQueryCommentForm() {
    return '<form class="popup-form"><fieldset><legend>Add new query</legend>' +
        '<textarea id="query-comment-text" rows="30"></textarea><br>' +
        '<button type="button" class="default-button" onclick="saveComment(jQuery(\'#query-comment-text\').val(), \'t\'); jQuery.fancybox.close();">' +
        'Add query to comments log</button></fieldset></form>';
  }

  function popupTabs(tabs) {
    var r = '<div id="popup-tabs"><ul>', title;
    $.each(tabs, function(id, tab) {
      title = indiciaData.popupTranslations['tab_' + id];
      r += '<li id="tab-' + id + '-tab"><a href="#tab-' + id + '">' + title + '</a></li>';
    });
    r += '</ul>';
    $.each(tabs, function(id, tab) {
      r += '<div id="tab-' + id + '">' + tab + '</div>';
    });
    r += '</div>';
    return r;
  }

  function popupQueryForm(html) {
    $.fancybox(html);
    if ($('#popup-tabs')) {
      $('#popup-tabs').tabs();
    }
  }

  function recorderQueryProbablyCantContact() {
    var html = '<p>' + indiciaData.popupTranslations.queryProbablyCantContact + '</p>';
    html += recorderQueryCommentForm();
    popupQueryForm(html);
  }

  function recorderQueryNeedsEmail() {
    var html = '<p>' + indiciaData.popupTranslations.queryNeedsEmail + '</p>';
    html += recorderQueryEmailForm();
    popupQueryForm(html);
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  function recorderQueryProbablyNeedsEmail(likelihoodOfReceivingNotification) {
    var tab1, tab2;
    if (likelihoodOfReceivingNotification==='no') {
      tab1 = '<p>' + indiciaData.popupTranslations.queryProbablyNeedsEmailNo + '</p>';
    }
    else {
      tab1 = '<p>' + indiciaData.popupTranslations.queryProbablyNeedsEmailUnknown + '</p>';
    }
    tab2 = recorderQueryEmailForm();
    popupQueryForm(popupTabs({"email":tab1, "comment":tab2}));
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  function recorderQueryProbablyWillGetNotified(likelihoodOfReceivingNotification) {
    var tab1, tab2;
    tab1 = '<p>' + indiciaData.popupTranslations.queryProbablyWillGetNotified + '</p>';
    tab1 += recorderQueryCommentForm();
    tab2 = recorderQueryEmailForm();
    popupQueryForm(popupTabs({"comment":tab1, "email":tab2}));
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  /**
   * Sends a query to the original recorder by the best means available.
   */
  function buildRecorderQueryMessage() {
    email.subtype='R';
    // Find out the best means of contact
    if (currRec.extra.created_by_id==='1') {
      // record not logged to a warehouse user account, so they definitely won't get notifications
      if (!currRec.extra.recorder_email) {
        recorderQueryProbablyCantContact();
      } else {
        recorderQueryNeedsEmail();
      }
    } else {
      // They are a logged in user. We need to know if they are receiving their notifications.

      $.ajax({
        url: indiciaData.ajaxUrl + '/do_they_see_notifications/' + indiciaData.nid + urlSep + 'user_id=' + currRec.extra.created_by_id,
        success: function (response) {
          if (response==='yes' || response==='maybe') {
            recorderQueryProbablyWillGetNotified(response);
          }
          else if (response==='no' || response==='unknown') {
            recorderQueryProbablyNeedsEmail(response);
          }
        }
      });
    }
  }

  function popupEmailExpert() {
    $.fancybox('<form id="email-form"><fieldset class="popup-form">' +
          '<legend>' + indiciaData.popupTranslations.emailTitle + '</legend>' +
          '<p>' + indiciaData.popupTranslations.emailInstruction + '</p>' +
          '<label>To:</label><input type="text" id="email-to" class="email required" value="'+email.to+'"/><br />' +
          '<label>Subject:</label><input type="text" id="email-subject" class="require" value="' + email.subject + '"/><br />' +
          '<label>Body:</label><textarea id="email-body" class="required">' + email.body + '</textarea><br />' +
          '<input type="submit" class="default-button" ' +
              'value="' + indiciaData.popupTranslations.sendEmail + '" />' +
          '</fieldset></form>');
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  function processEmail(){
    //Complete creation of email of record details
    if (validator.numberOfInvalids()===0) {
      email.to = $('#email-to').val();
      email.subject = $('#email-subject').val();
      email.body = $('#email-body').val();

      if (email.type === 'recordCheck') {
        // ensure media are loaded
        $.ajax({
          url: indiciaData.ajaxUrl + '/mediaAndComments/' + indiciaData.nid + urlSep + 'sample_id=' + sample_id,
          async: false,
          dataType: 'json',
          success: function (response) {
              email.body = email.body.replace(/\[Photos\]/g, response.media);
              email.body = email.body.replace(/\[Comments\]/g, response.comments);
          }
        });
        // save a comment to indicate that the mail was sent
        saveComment(indiciaData.commentTranslations.emailed.replace('{1}', email.subtype==='R' ?
            indiciaData.commentTranslations.recorder : indiciaData.commentTranslations.expert));
      }

      sendEmail();
    }
    return false;
  }

  function sendEmail() {
    //Send an email
    // use an AJAX call to get the server to send the email
    $.post(
      indiciaData.ajaxUrl + '/email' + urlSep,
      email,
      function (response) {
        if (response === 'OK') {
          $.fancybox.close();
          alert(indiciaData.popupTranslations.emailSent);
        } else {
          $.fancybox('<div class="manual-email">' + indiciaData.popupTranslations.requestManualEmail +
              '<div class="ui-helper-clearfix"><span class="left">To:</span><div class="right">' + email.to + '</div></div>' +
              '<div class="ui-helper-clearfix"><span class="left">Subject:</span><div class="right">' + email.subject + '</div></div>' +
              '<div class="ui-helper-clearfix"><span class="left">Content:</span><div class="right">' + email.body.replace(/\n/g, '<br/>') + '</div></div>' +
                '</div>');
        }
      }
    );
  }

  function showComment(comment, query, username) {
    // Remove message that there are no comments
    $('#no-comments').hide();
    var html = '<div class="comment"><div class="header">';
    if (query==='t') {
      html += '<img width="12" height="12" src="' + indiciaData.imgPath + 'nuvola/dubious-16px.png"/>';
    }
    html += '<strong>' + username + '</strong> Now';
    html += '</div>';
    html += '<div>' + comment + '</div>';
    html += '</div>';
    $('#comment-list').prepend(html);
  }

  saveComment=function(text, query) {
    if (typeof query==="undefined") {
      query='f';
    }
    var data = {
      'website_id': indiciaData.website_id,
      'sample_comment:sample_id': sample_id,
      'sample_comment:comment': text,
      'sample_comment:person_name': indiciaData.username,
      'sample_comment:query': query
    };
    $.post(
      indiciaData.ajaxFormPostUrl.replace('sample', 'smp-comment'),
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          showComment(text, query, indiciaData.username);
          if ($('#comment-text')) {
            $('#comment-text').val('');
          }
        } else {
          alert(data.error);
        }
      }
    );
  };

  function postStatusComment(smpId, status, comment) {
    var data = {
      'website_id': indiciaData.website_id,
      'sample:id': smpId,
      'user_id': indiciaData.userId,
      'sample:record_status': status,
      'sample_comment:comment': comment,
      'sample:record_decision_source': 'H'
    };
    postVerification(data);
  }

  function statusLabel(status) {
    if (typeof indiciaData.popupTranslations[status]!=="undefined") {
      return indiciaData.popupTranslations[status];
    } else {
      return '';
    }
  }

  saveVerifyComment=function() {
    var status = $('#set-status').val(),
      comment = statusLabel(status);
    if ($('#verify-comment').val()!=='') {
      comment += ".\n" + $('#verify-comment').val();
    }
    $.fancybox.close();
    if (multimode) {
      $.each($('.check-row:checked'), function(idx, elem) {
        $($(elem).parents('tr')[0]).css('opacity', 0.2);
        postStatusComment($(elem).val(), status, comment);
      });
    } else {
      postStatusComment(sample_id, status, comment);
    }
  };

  // show the list of tickboxes for verifying multiple records quickly
  function showTickList() {
    $('.check-row').attr('checked', false);
    $('#row' + sample_id + ' .check-row').attr('checked', true);
    $('.check-row').show();
    $('#btn-multiple').addClass('active');
    $('#btn-edit-verify').hide();
    $('#action-buttons').find('label').html('With ticked records:');
    $('#btn-multiple').val('Verify single records');
    $('#btn-multiple').after($('#action-buttons-status'));
    $('#action-buttons').find('button').removeAttr('disabled');
  }
    
  // Callback for the report grid. Use to fill in the tickboxes if in multiple mode.
  verificationGridLoaded = function() {
    if (multimode) {
      showTickList();
    }
  };

  function showTab() {
    if (currRec !== null) {
      if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'details') {
        $('#details-tab').html(currRec.content);
      } else if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'media') {
        $('#media-tab a.fancybox').fancybox();
      }
      // make it clear things are loading
      if (indiciaData.mapdiv !== null) {
        $(indiciaData.mapdiv).css('opacity', currRec.extra.wkt===null ? 0.1 : 1);
      }
    }
  }

  function setStatus(status) {
    var helpText='', html, verb;
    if (multimode && $('.check-row:checked').length>1) {
      helpText='<p class="warning">'+indiciaData.popupTranslations.multipleWarning+'</p>';
    }
    verb = status==='C' ? indiciaData.popupTranslations['verbC3'] : indiciaData.popupTranslations['verb' + status];
    html = '<fieldset class="popup-form">' +
          '<legend>' + indiciaData.popupTranslations.title.replace('{1}', statusLabel(status)) + '</legend>';
    html += '<label class="auto">Comment:</label><textarea id="verify-comment" rows="5" cols="80"></textarea><br />' +
          helpText +
          '<input type="hidden" id="set-status" value="' + status + '"/>' +
          '<button type="button" class="default-button" onclick="saveVerifyComment();">' +
              indiciaData.popupTranslations.save.replace('{1}', verb) + '</button>' +
          '</fieldset>';
    $.fancybox(html);
  }

  mapInitialisationHooks.push(function (div) {
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
  
  function verifyRecordSet() {
    var request, params=indiciaData.reports.verification.grid_verification_grid.getUrlParamsForAllRecords();
    //If doing trusted only, this through as a report parameter.
    request = indiciaData.ajaxUrl + '/bulk_verify/'+indiciaData.nid;
    $.post(request,
      'report='+encodeURI(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource)+'&params='+encodeURI(JSON.stringify(params))+
      '&user_id='+indiciaData.userId,
      function(response) {
        indiciaData.reports.verification.grid_verification_grid.reload(true);
        alert(response + ' records processed');
      }
    );
    $.fancybox.close();
  }

  $(document).ready(function () {
    //Use jQuery to add button to the top of the verification page. Use the first button to access the popup
    //which allows you to verify all records. The second enabled multiple record verification checkboxes
    var verifyGridButtons = '<button type="button" class="default-button review-grid tools-btn" id="review-grid"">Review grid</button>'+
        '<button type="button" id="btn-multiple" title="Select this tool to tick off a list of records and action all of the ticked records in one go">Review tick list</button>';
    $('#filter-build').after(verifyGridButtons);
    $('#review-grid').click(function() {
      var html = '<div class="grid-verify-popup" style="width: 550px"><h2>Review all grid data</h2>'+
                    '<p>This facility allows you to set the status of entire sets of records in one step. Before using this '+
                    'facility, you should filter the grid so that only the records you want to process are listed. '+
                    'You can then choose to either process the entire set of records from <em>all pages of the grid</em>.</p>';
      var settings=indiciaData.reports.verification.grid_verification_grid[0].settings;
      if (settings.recordCount > settings.itemsPerPage) {
        html += '<p class="warning">Remember that the following buttons will verify records from every page in the grid up to a maximum of ' +
          settings.recordCount + ' records, not just the current page.</p>';
      }
      html += '<button type="button" class="default-button" id="verify-all-button">Accept all records</button></div>';
      
      $.fancybox(html);
      $('#verify-all-button').click(function() {verifyRecordSet(false);});
    });

    $('table.report-grid tbody').click(function (evt) {
      var row=$(evt.target).parents('tr:first')[0];
      // reinstate tooltips
      $.each($(row).parents('table:first tbody').find(':data(title)'), function(idx, ctrl) {
        $(ctrl).attr('title', $(this).data('title'));
      });
      // Find the appropriate separator for AJAX url params - depends on clean urls setting.
      urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
      selectRow(row);
    });

    indiciaFns.bindTabsActivate($('#record-details-tabs'), showTab);

    // Handlers for basic status buttons
    $('#btn-accepted').click(function () {
      setStatus('V');
    });

    $('#btn-notaccepted').click(function () {
      setStatus('R');
    });

    // Handlers for advanced status buttons
    $('#btn-accepted-correct').click(function () {
      setStatus('V', 1);
    });

    $('#btn-accepted-considered-correct').click(function () {
      setStatus('V', 2);
    });

    $('#btn-plausible').click(function () {
      setStatus('C', 3);
    });

    $('#btn-notaccepted-unable').click(function () {
      setStatus('R', 4);
    });

    $('#btn-notaccepted-incorrect').click(function () {
      setStatus('R', 5);
    });
    
    $('#btn-multiple').click(function() {
      multimode=!multimode;
      if (multimode) {
        showTickList();
      } else {
        $('.check-row').hide();
        $('#btn-multiple').removeClass('active');
        $('#btn-edit-verify').show();
        $('#action-buttons-status').find('label').html('Set status:');
        $('#btn-multiple').val('Verify tick list');
        $('#action-buttons').prepend($('#action-buttons-status'));
        if (currRec === null) {
          $('#action-buttons-status button').attr('disabled', 'disabled');
        }
      }
    });

    $('#btn-query').click(function () {
      buildRecorderQueryMessage();
    });

    $('#btn-email-expert').click(function () {
      buildVerifierEmail();
    });

    function editThisRecord(id) {
      var $row=$('tr#row'+id),
        path=$row.find('.row-input-form').val(),
        sep=(path.indexOf('?')>=0) ? '&' : '?';
      window.location=path+sep+'sample_id='+id;
    }

    $('#btn-edit-record').click(function() {
      editThisRecord(sample_id);
    });

  });
}) (jQuery);
