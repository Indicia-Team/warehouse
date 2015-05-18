var saveComment, saveVerifyComment, verificationGridLoaded, reselectRow, rowIdToReselect = false;

(function ($) {
  "use strict";
  
  var rowRequest=null, occurrence_id = null, currRec = null, urlSep, validator, speciesLayers = [], 
      trustsCounter, multimode=false, email = {to:'', subject:'', body:'', type:''};

  reselectRow = function() {
    if (rowIdToReselect) {
      // Reselect current record if still in the grid
      var row = $('tr#row' + rowIdToReselect);
      if (row.length) {
        occurrence_id = null;
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
    occurrence_id = null;
    currRec = null;
  }
      
  mapInitialisationHooks.push(function(div) {
    // nasty hack to fix a problem where these layers get stuck and won't reload after pan/zoom on IE & Chrome
    div.map.events.register('moveend', null, function() {
      $.each(speciesLayers, function(idx, layer) {
        indiciaData.mapdiv.map.removeLayer(layer);
        indiciaData.mapdiv.map.addLayer(layer);
      });
    });
  });

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
    // The row ID is row1234 where 1234 is the occurrence ID.
    if (tr.id.substr(3) === occurrence_id) {
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
    occurrence_id = tr.id.substr(3);
    $(tr).addClass('selected');
    // make it clear things are loading
    $('#chart-div').css('opacity', 0.15);
    rowRequest = $.getJSON(
      indiciaData.ajaxUrl + '/details/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id,
      null,
      function (data) {
        // refind the row, as $(tr) sometimes gets obliterated.
        var $row = $('#row' + data.data.Record[0].value);
        rowRequest = null;
        currRec = data;
        $('#instructions').hide();
        $('#record-details-content').show();
        if ($row.parents('tbody').length !== 0) {
          // point the image and comments tabs to the correct AJAX call for the selected occurrence.
          indiciaFns.setTabHref($('#record-details-tabs'), indiciaData.detailsTabs.indexOf('media'), 'media-tab-tab',
            indiciaData.ajaxUrl + '/media/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id);
          indiciaFns.setTabHref($('#record-details-tabs'), indiciaData.detailsTabs.indexOf('comments'), 'comments-tab-tab',
            indiciaData.ajaxUrl + '/comments/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id);
          // reload current tabs
          $('#record-details-tabs').tabs('load', indiciaFns.activeTab($('#record-details-tabs')));
          $('#record-details-toolbar *').removeAttr('disabled');
          showTab();
          // remove any wms layers for species or the gateway data
          $.each(speciesLayers, function (idx, layer) {
            indiciaData.mapdiv.map.removeLayer(layer);
            layer.destroy();
          });
          speciesLayers = [];
          var layer, thisSpLyrSettings, filter;
          if (typeof indiciaData.wmsSpeciesLayers !== "undefined" && data.extra.taxon_external_key !== null) {
            $.each(indiciaData.wmsSpeciesLayers, function (idx, layerDef) {
              thisSpLyrSettings = $.extend({}, layerDef.settings);
              // replace values with the external key if the token is used
              $.each(thisSpLyrSettings, function (prop, value) {
                if (typeof value === 'string' && $.trim(value) === '{external_key}') {
                  thisSpLyrSettings[prop] = data.extra.taxon_external_key;
                }
              });
              layer = new OpenLayers.Layer.WMS(layerDef.title, layerDef.url.replace('{external_key}', data.extra.taxon_external_key),
                thisSpLyrSettings, layerDef.olSettings);
              indiciaData.mapdiv.map.addLayer(layer);
              layer.setZIndex(0);
              speciesLayers.push(layer);
            });
          }
          if (typeof indiciaData.indiciaSpeciesLayer !== "undefined" && data.extra[indiciaData.indiciaSpeciesLayer.filterField] !== null) {
            filter = indiciaData.indiciaSpeciesLayer.cqlFilter.replace('{filterValue}', data.extra[indiciaData.indiciaSpeciesLayer.filterField]);
            layer = new OpenLayers.Layer.WMS(indiciaData.indiciaSpeciesLayer.title, indiciaData.indiciaSpeciesLayer.wmsUrl,
              {
                layers: indiciaData.indiciaSpeciesLayer.featureType,
                transparent: true,
                CQL_FILTER: filter,
                STYLES: indiciaData.indiciaSpeciesLayer.sld
              },
              {isBaseLayer: false, sphericalMercator: true, singleTile: true, opacity: 0.5});
            indiciaData.mapdiv.map.addLayer(layer);
            layer.setZIndex(0);
            speciesLayers.push(layer);
          }
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
   * Post an object containing occurrence form data into the Warehouse. Updates the
   * visual indicators of the record's status.
   */
  function postVerification(occ) {
    var status = occ['occurrence:record_status'], id=occ['occurrence:id'],
      substatus = typeof occ['occurrence:record_substatus']==='undefined' ? null : occ['occurrence:record_substatus'];
    $.post(
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'single_verify'),
      occ,
      function () {
        removeStatusClasses('#row' + id + ' td:first div, #details-tab td', 'status', ['V','C','R','I','T']);
        removeStatusClasses('#row' + id + ' td:first div, #details-tab td', 'substatus', [1,2,3,4,5]);
        $('#row' + id + ' td:first div, #details-tab td.status').addClass('status-' + status);
        if (substatus) {
          $('#row' + id + ' td:first div, #details-tab td.status').addClass('substatus-' + substatus);
        }
        var text = indiciaData.statusTranslations[status], nextRow;
        $('#details-tab td.status').html(text);
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
        .replace('%id%', occurrence_id);
    email.body = body
        .replace('%taxon%', currRec.extra.taxon)
        .replace('%id%', occurrence_id)
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
          url: indiciaData.ajaxUrl + '/mediaAndComments/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id,
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
      'occurrence_comment:occurrence_id': occurrence_id,
      'occurrence_comment:comment': text,
      'occurrence_comment:person_name': indiciaData.username,
      'occurrence_comment:query': query
    };
    $.post(
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'occ-comment'),
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

  function postStatusComment(occId, status, substatus, comment) {
    var data = {
      'website_id': indiciaData.website_id,
      'occurrence:id': occId,
      'user_id': indiciaData.userId,
      'occurrence:record_status': status,
      'occurrence_comment:comment': comment,
      'occurrence:record_decision_source': 'H'
    };
    if (substatus) {
      data['occurrence:record_substatus'] = substatus;
    }
    postVerification(data);
  }

  function statusLabel(status, substatus) {
    var labels = [];
    if (typeof indiciaData.popupTranslations[status]!=="undefined" && (status!=='C' || substatus!==3)) {
      labels.push(indiciaData.popupTranslations[status]);
    }
    if (indiciaData.popupTranslations['sub' + substatus]) {
      labels.push(indiciaData.popupTranslations['sub' + substatus]);
    }
    return labels.join('::');
  }

  saveVerifyComment=function() {
    var status = $('#set-status').val(),
      substatus = $('#set-substatus').val(),
      comment = statusLabel(status, substatus);
    if ($('#verify-comment').val()!=='') {
      comment += ".\n" + $('#verify-comment').val();
    }
    $.fancybox.close();
    if (multimode) {
      $.each($('.check-row:checked'), function(idx, elem) {
        $($(elem).parents('tr')[0]).css('opacity', 0.2);
        postStatusComment($(elem).val(), status, substatus, comment);
      });
    } else {
      postStatusComment(occurrence_id, status, substatus, comment);
    }
  };

  // show the list of tickboxes for verifying multiple records quickly
  function showTickList() {
    $('.check-row').attr('checked', false);
    $('#row' + occurrence_id + ' .check-row').attr('checked', true);
    $('.check-row').show();
    $('#btn-multiple').addClass('active');
    $('#btn-edit-verify').hide();
    $('#action-buttons-status label').html('With ticked records:');
    $('#btn-multiple').val('Verify single records');
    $('#btn-multiple').after($('#action-buttons-status'));
    $('#action-buttons-status button').removeAttr('disabled');
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
      } else if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'experience') {
        if (currRec.extra.created_by_id==='1') {
          $('#experience-div').html('No experience information available. This record does not have the required information for other records by the same recorder to be extracted.');
        } 
        else {
          $.get(
            indiciaData.ajaxUrl + '/experience/' + indiciaData.nid + urlSep +
                'occurrence_id=' + occurrence_id + '&user_id=' + currRec.extra.created_by_id,
            null,
            function (data) {
              $('#experience-div').html(data);
            }
          );
        }
      } else if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'phenology') {
        $.getJSON(
          indiciaData.ajaxUrl + '/phenology/' + indiciaData.nid + urlSep +
              'external_key=' + currRec.extra.taxon_external_key +
              '&taxon_meaning_id=' + currRec.extra.taxon_meaning_id,
          null,
          function (data) {
            $('#chart-div').empty();
            $.jqplot('chart-div', [data], {
              seriesDefaults:{renderer:$.jqplot.LineRenderer, rendererOptions:[]}, legend:[], series:[],
              axes:{
                "xaxis":{"label":indiciaData.str_month,"showLabel":true,"renderer":$.jqplot.CategoryAxisRenderer,"ticks":["1","2","3","4","5","6","7","8","9","10","11","12"]},
                "yaxis":{"min":0}
              }
            });
            $('#chart-div').css('opacity',1);
          }
        );
      } else if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'media') {
        $('#media-tab a.fancybox').fancybox();
      }
      // make it clear things are loading
      if (indiciaData.mapdiv !== null) {
        $(indiciaData.mapdiv).css('opacity', currRec.extra.wkt===null ? 0.1 : 1);
      }
    }
  }

  function saveRedetComment() {
    var data = {
      'website_id': indiciaData.website_id,
      'occurrence:id': occurrence_id,
      'occurrence:taxa_taxon_list_id': $('#redet').val(),
      'user_id': indiciaData.userId
    };
    if ($('#verify-comment').val()) {
      data['occurrence_comment:comment'] = $('#verify-comment').val();
    }
    $.fancybox.close();
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (response) {
        if (typeof response.error !== "undefined") {
          alert(response.error);
        } else {
          // reload current tab
          if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'details' ||
            indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'comments') {
            $('#record-details-tabs').tabs('load', indiciaFns.activeTab($('#record-details-tabs')));
          }
          rowIdToReselect = occurrence_id;
          indiciaData.reports.verification.grid_verification_grid[0].settings.callback = 'reselectRow';
          // Reload grid to remove row if not in your current verification set
          indiciaData.reports.verification.grid_verification_grid.reload(true);
        }
      }
    );
    $('#add-comment').remove();
  }

  function showRedeterminationPopup() {
    var html = '<fieldset class="popup-form">' +
      '<legend>' + indiciaData.popupTranslations.redetermine + '</legend>';
    html += '<div id="redet-dropdown-popup-ctnr"></div>';
    $('#redet\\:taxon').setExtraParams({"taxon_list_id": currRec.extra.taxon_list_id});
    html += '<label class="auto">Comment:</label><textarea id="verify-comment" rows="5" cols="80"></textarea><br />' +
        '<button type="button" class="default-button" id="save-redet-submit">' +
        indiciaData.popupTranslations.redetermine + '</button>' +
    '</fieldset>';
    $.fancybox(html, {
      "onCleanup" : function() {
        $('#redet-dropdown').appendTo($('#redet-dropdown-ctnr'));
      }
    });
    // move taxon input box onto the form
    $('#redet').val('');
    $('#redet\\:taxon').val('');
    $('#redet-dropdown').appendTo($('#redet-dropdown-popup-ctnr'));
    // Hide the full list checkbox if same as current record or full list not known
    if (typeof indiciaData.mainTaxonListId==="undefined" || parseInt(currRec.extra.taxon_list_id)===indiciaData.mainTaxonListId) {
      $('#ctrl-wrap-redet-from-full-list').hide();
    } else {
      $('#ctrl-wrap-redet-from-full-list').show();
      $('#redet-from-full-list').removeAttr('checked');
    }
    // hook submit handler
    $('#save-redet-submit').click(saveRedetComment);
  }

  function setStatus(status, substatus) {
    if (typeof substatus==="undefined") {
      substatus='';
    }
    var helpText='', html, verb;
    if (multimode && $('.check-row:checked').length>1) {
      helpText='<p class="warning">'+indiciaData.popupTranslations.multipleWarning+'</p>';
    }
    verb = status==='C' ? indiciaData.popupTranslations['verbC3'] : indiciaData.popupTranslations['verb' + status];
    html = '<fieldset class="popup-form">' +
          '<legend>' + indiciaData.popupTranslations.title.replace('{1}', statusLabel(status, substatus)) + '</legend>';
    html += '<label class="auto">Comment:</label><textarea id="verify-comment" rows="5" cols="80"></textarea><br />' +
          helpText +
          '<input type="hidden" id="set-status" value="' + status + '"/>' +
          '<input type="hidden" id="set-substatus" value="' + substatus + '"/>' +
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
  
  function verifyRecordSet(trusted) {
    var request, params=indiciaData.reports.verification.grid_verification_grid.getUrlParamsForAllRecords(),
      substatus = $('#process-grid-substatus').length ? '&record_substatus=' + $('#process-grid-substatus').val() : '',
      ignoreRules=$('.grid-verify-popup input[name=ignore-checks-trusted]').attr('checked')==='checked' ? 'true' : 'false';
    //If doing trusted only, this through as a report parameter.
    if (trusted) {
      params.quality_context="T";
    }
    request = indiciaData.ajaxUrl + '/bulk_verify/'+indiciaData.nid;
    $.post(request,
      'report='+encodeURI(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource)+'&params='+encodeURI(JSON.stringify(params))+
      '&user_id='+indiciaData.userId+'&ignore='+ignoreRules+substatus,
      function(response) {
        indiciaData.reports.verification.grid_verification_grid.reload(true);
        alert(response + ' records processed');
      }
    );
    $.fancybox.close();
  }

  $(document).ready(function () {
    //Use jQuery to add button to the top of the verification page. Use the first button to access the popup
    //which allows you to verify all trusted records or all records. The second enabled multiple record verification checkboxes
    var verifyGridButtons = '<button type="button" class="default-button verify-grid-trusted tools-btn" id="verify-grid-trusted">Review grid</button>'+
        '<button type="button" id="btn-multiple" title="Select this tool to tick off a list of records and action all of the ticked records in one go">Review tick list</button>',
        trustedHtml;
    $('#filter-build').after(verifyGridButtons);
    $('#verify-grid-trusted').click(function() {
      trustedHtml = '<div class="grid-verify-popup" style="width: 550px"><h2>Review all grid data</h2>'+
                    '<p>This facility allows you to set the status of entire sets of records in one step. Before using this '+
                    'facility, you should filter the grid so that only the records you want to process are listed. '+
                    'You can then choose to either process the entire set of records from <em>all pages of the grid</em> '+
                    'or you can process only those records where the recorder is trusted based on the record\'s '+
                    'survey, taxon group and location. Before using this tool to limit to trusted recorders, set up the recorders '+
                    'you wish to trust using the ... button next to each record.</p>';
      trustedHtml += '<p>The records will only be accepted if they have been through automated checks without any rule violations. If you <em>really</em>' +
                     ' trust the records are correct then you can verify them even if they fail some checks by ticking the following box.</p>'+
                     '<label class="auto"><input type="checkbox" name="ignore-checks-trusted" /> Include records which fail automated checks?</label><br/>';
      var settings=indiciaData.reports.verification.grid_verification_grid[0].settings;
      if (settings.recordCount > settings.itemsPerPage) {
        trustedHtml += '<p class="warning">Remember that the following buttons will verify records from every page in the grid up to a maximum of ' +
          settings.recordCount + ' records, not just the current page.</p>';
      }
      if (typeof $.cookie !== "undefined") {
        var show = $.cookie('verification-status-buttons');
        if (show === 'more') {
          trustedHtml += '<div><label class="auto">Accepted records will be flagged as:<select id="process-grid-substatus">' +
              '<option selected="selected" value="2">considered correct</option><option value="1">correct</option></select></label></div>';
        }
      }
      trustedHtml += '<button type="button" class="default-button" id="verify-trusted-button">Accept trusted records</button>';
      trustedHtml += '<button type="button" class="default-button" id="verify-all-button">Accept all records</button></div>';
      
      $.fancybox(trustedHtml);
      $('#verify-trusted-button').click(function() {verifyRecordSet(true);});
      $('#verify-all-button').click(function() {verifyRecordSet(false);});
    });

    function quickVerifyPopup() {
      var popupHtml;
      popupHtml = '<div class="quick-verify-popup" style="width: 550px"><h2>Quick verification</h2>'+
          '<p>The following options let you rapidly verify records. The only records affected are those in the grid but they can be on any page of the grid, '+
          'so please ensure you have set the grid\'s filter correctly before proceeding. You should only proceed if you are certain that data you are verifying '+
          'can be trusted without further investigation.</p>'+
          '<label><input type="radio" name="quick-option" value="species" /> Verify grid\'s records of <span class="quick-taxon">'+currRec.extra.taxon+'</span></label><br/>';
      // at this point, we need to know who the created_by_id recorder name is. And if it matches extra.recorder, otherwise this record may have been input by proxy
      if (currRec.extra.recorder!=='' && currRec.extra.input_by_surname!==null && currRec.extra.created_by_id!=='1'
          && (currRec.extra.recorder===currRec.extra.input_by_first_name + ' ' + currRec.extra.input_by_surname
          || currRec.extra.recorder===currRec.extra.input_by_surname + ', ' + currRec.extra.input_by_first_name)) {
        popupHtml += '<label><input type="radio" name="quick-option" value="recorder"/> Verify grid\'s records by <span class="quick-user">'+currRec.extra.recorder+'</span></label><br/>'+
            '<label><input type="radio" name="quick-option" value="species-recorder" /> Verify grid\'s records of <span class="quick-taxon">'+currRec.extra.taxon+
            '</span> by <span class="quick-user">'+currRec.extra.recorder+'</span></label><br/>';
      } 
      else if (currRec.extra.recorder!=='' && currRec.extra.recorder!==null && currRec.extra.input_by_surname!==null && currRec.extra.created_by_id!=='1') {
        popupHtml += '<p class="helpText">Because the recorder, ' + currRec.extra.recorder + ', is not linked to a logged in user, quick verification tools cannot filter to records by this recorder.</p>';
      }
      popupHtml += '<label><input type="checkbox" name="ignore-checks" /> Include failures?</label><p class="helpText">The records will only be accepted if they do not fail '+
          'any automated verification checks. If you <em>really</em> trust the records are correct then you can verify them even if they fail some checks by ticking this box.</p>';
      popupHtml += '<button type="button" class="default-button verify-button">Verify chosen records</button>'+
          '<button type="button" class="default-button cancel-button">Cancel</button></p></div>';
      $.fancybox(popupHtml);
      $('.quick-verify-popup .verify-button').click(function() {
        var params=indiciaData.reports.verification.grid_verification_grid.getUrlParamsForAllRecords(),
            radio=$('.quick-verify-popup input[name=quick-option]:checked'), request;
        if (radio.length===1) {
          if ($(radio).val().indexOf('recorder')!==-1) {
            params.created_by_id=currRec.extra.created_by_id;
          }
          if ($(radio).val().indexOf('species')!==-1) {
            params.taxon_meaning_list=currRec.extra.taxon_meaning_id;
          }
          // We now have parameters that can be applied to a report and we know the report, so we can ask the warehouse
          // to verify the occurrences provided by the report that match the filter.
          request = indiciaData.ajaxUrl + '/bulk_verify/'+indiciaData.nid;
          $.post(request,
              'report='+encodeURI(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource)+'&params='+encodeURI(JSON.stringify(params))+
                  '&user_id='+indiciaData.userId+'&ignore='+$('.quick-verify-popup input[name=ignore-checks]').attr('checked'),
              function(response) {
                indiciaData.reports.verification.grid_verification_grid.reload();
                alert(response + ' records processed');
              }
          );
          $.fancybox.close();
        }
      });
      $('.quick-verify-popup .cancel-button').click(function() {
        $.fancybox.close();
      });
    }

    function trustsPopup() {
      var popupHtml, surveyRadio, taxonGroupRadio, locationInput, i, theDataToRemove;
      popupHtml = '<div class="quick-verify-popup" style="width: 550px"><h2>Recorder\'s trust settings</h2>';
      popupHtml += '<p>Recorders can be trusted for records from a selected region, species group or survey combination. When they add records which meet the criteria ' +
          'that the recorder is trusted for the records will not be automatically accepted. However, you can filter the grid to show only "trusted" records and use the ... button at the top ' +
          'of the grid to accept all these records in bulk. If you want to trust records from <em>' + currRec.extra.recorder + '</em> in future, you can use the following options to select the ' +
          'criteria for when their records are to be treated as trusted.</p>';
      //Call a function to draw all the existing trusts for a record.
      drawExistingTrusts();
      //The html containing the trusts is later placed into this div using innerHtml
      popupHtml += '<div id="existingTrusts"></div>';
      popupHtml += '<h3>Add new trust criteria</h3>';
      if (indiciaData.expertise_surveys) {
        popupHtml += '<label>Trust will be applied to records from survey "' + currRec.extra.survey_title + '"</label><br/>';
      } else {
        popupHtml += '<label>Trust will only be applied to records from survey:</label>'+
                     '<label><input type="radio" name="trust-survey" value="all"> All </label>' +
                     '<label><input type="radio" name="trust-survey" value="specific" checked>' + ' ' + currRec.extra.survey_title + '</label><br/>';
      }
      if (indiciaData.expertise_taxon_groups) {
        popupHtml += '<label>Trust will be applied to records from species group "' + currRec.extra.taxon_group +'</label><br/>';
      } else {
        popupHtml += '<label>Trust will only be applied to records from species group:</label>'+
                     '<label><input type="radio" name="trust-taxon-group" value="all"> All </label>' +
                     '<label><input type="radio" name="trust-taxon-group" value="specific" checked>' + ' ' + currRec.extra.taxon_group + '</label><br/>';
      }
      if (indiciaData.expertise_location) {
        // verifier can only verify in a locality
        popupHtml += '<label>Trust will be applied to records from your verification area.</label><br/>'; // @todo VERIFIERs LOCALITY NAME
        popupHtml += '<input type="hidden" name="trust-location" value="' + indiciaData.expertise_location + '" />';
      }
      else {
        // verifier can verify anywhere
        if (currRec.extra.locality_ids!=='') {
          popupHtml += '<label>Trust will be applied to records from locality:</label>'+
              '<label><input type="radio" name="trust-location" value="all"> All </label>';
          // the record could intersect multiple locality boundaries. So can choose which...
          var locationIds = currRec.extra.locality_ids.split('|'), locations = currRec.extra.localities.split('|');
          // can choose to trust all localities or record's location
          $.each(locationIds, function(idx, id) {
            popupHtml += '<label><input type="radio" name="trust-location" value="' + id + '" checked>' + ' ' + locations[idx] + '</label><br/>';
          });
        }
        else {
          popupHtml += '<label>Trust will be applied to records from any locality.</label>';
          popupHtml += '<input type="hidden" name="trust-location" value="all" /><br/>';
        }
      }
      popupHtml += '<button type="button" id="trust-button" class="default-button trust-button">Set trust for ' + currRec.extra.recorder + '</button>'+ "</div>\n";
      $.fancybox(popupHtml);
      $('.quick-verify-popup .trust-button').click(function() {
        document.getElementById('trust-button').innerHTML = "Please Wait……";
        //As soon as the Trust button is clicked we disable it so that the user can't keep clicking it.
        $(".trust-button").attr('disabled','disabled');
        var theData = {
          'website_id': indiciaData.website_id,
          'user_trust:user_id': currRec.extra.created_by_id,
          'user_trust:deleted':false
        };
        //Get the user's trust settings to put in the database
        surveyRadio=$('.quick-verify-popup input[name=trust-survey]:checked');
        if (!surveyRadio.length || $(surveyRadio).val().indexOf('specific')!==-1) {
          theData['user_trust:survey_id'] = currRec.extra.survey_id;
        }
        taxonGroupRadio=$('.quick-verify-popup input[name=trust-taxon-group]:checked');
        if (!taxonGroupRadio.length || $(taxonGroupRadio).val().indexOf('specific')!==-1) {
          theData['user_trust:taxon_group_id'] = currRec.extra.taxon_group_id;
        }
        locationInput=$('.quick-verify-popup input[name=trust-location]:checked, .quick-verify-popup input[name=trust-location][type=hidden]');
        if ($(locationInput).val()!=='all') {
          theData['user_trust:location_id'] = $(locationInput).val();
        }
        if (!theData['user_trust:survey_id'] && !theData['user_trust:taxon_group_id'] && !theData['user_trust:location_id']) {
          alert("Please review the trust settings as unlimited trust is not allowed");
          //The attempt to create the trust is over at this point.
          //We re-enable the Trust button.
          $(".trust-button").removeAttr('disabled');
          document.getElementById('trust-button').innerHTML = "Trust";
        } else {
          var downgradeConfirmRequired = false,
              downgradeConfirmed=false,
              duplicateDetected = false,
              trustNeedsRemoval = [],
              getTrustsReport = indiciaData.read.url +'/index.php/services/report/requestReport?report=library/user_trusts/get_user_trust_for_record.xml&mode=json&mode=json&callback=?',
              getTrustsReportParameters = {
                'user_id':currRec.extra.created_by_id,
                'survey_id':currRec.extra.survey_id,
                'taxon_group_id':currRec.extra.taxon_group_id,
                'location_ids':currRec.extra.locality_ids,
                'auth_token': indiciaData.read.auth_token,
                'nonce': indiciaData.read.nonce,
                'reportSource':'local'
              };
          //Collect the existing trust data associated with the record so we can compare the new trust data with it
          $.getJSON (
            getTrustsReport,
            getTrustsReportParameters,
            function (data) {
              var downgradeDetect = 0,
                  upgradeDetect = 0,
                  trustNeedsRemovalIndex = 0,
                  trustNeedsDowngradeIndex = 0,
                  trustNeedsDowngrade = [];
              //Cycle through the existing trust data we need for the record
              for (i=0; i<data.length; i++) {
                //If the new selections match an existing record then we flag it as a duplicate not be be added
                if (theData['user_trust:survey_id'] === data[i].survey_id &&
                    theData['user_trust:taxon_group_id'] === data[i].taxon_group_id &&
                    theData['user_trust:location_id'] === data[i].location_id &&
                    currRec.extra.created_by_id === data[i].user_id) {
                  duplicateDetected = true;
                }
                //If any of the 3 trust items the user has entered are smaller than the existing trust item we are looking at,
                //then we flag it as the existing row needs to be at least partially downgraded
                if ((theData['user_trust:survey_id'] && !data[i].survey_id) ||
                    (theData['user_trust:taxon_group_id'] && !data[i].taxon_group_id) ||
                    (theData['user_trust:location_id'] && !data[i].location_id)) {
                  downgradeDetect++;
                }
                //If any of the 3 trust items the user has entered are bigger than the existing trust item we are looking at,
                //then we flag it as the existing row needs to be at least partially upgraded
                if ((!theData['user_trust:survey_id'] && data[i].survey_id) ||
                    (!theData['user_trust:taxon_group_id'] && data[i].taxon_group_id) ||
                    (!theData['user_trust:location_id'] && data[i].location_id)) {
                  upgradeDetect++;
                }
                //If we have detected that there are more items to be downgraded than upgraded for an existing trust then we flag it.
                //We can then warn the user about the downgrade and remove the existing trust
                //e.g. This means if we have a trust which is just a trust for location Dorset and the user upgrades the
                //the location trust setting to "All" but downgrades the taxon_group trust from "All" to insects,
                //then although a downgrade has been performed it is actually a completely seperate trust. In this case we don't want to
                //warn the user or remove the existing trust. DowngradeDetect and upgradeDetect are both 1 so the following code
                //wouldn't run.
                if (downgradeDetect > upgradeDetect) {
                  //Save the existing trust data to be downgraded for processing
                  trustNeedsDowngrade[trustNeedsDowngradeIndex] = data[i].trust_id;
                  trustNeedsRemoval[trustNeedsRemovalIndex] = data[i].trust_id;
                  trustNeedsDowngradeIndex++;
                  trustNeedsRemovalIndex++;
                }
                //Same logic as above but we are working out which existing trusts are being upgraded.
                //The difference is that we don't warn the user about upgrades.
                if (upgradeDetect > downgradeDetect) {
                  trustNeedsRemoval[trustNeedsRemovalIndex] = data[i].trust_id;
                  trustNeedsRemovalIndex++;
                }
                downgradeDetect = 0;
                upgradeDetect = 0;
              }

              if (duplicateDetected === true) {
                alert("Your selected trust settings already exist in the database");
                $(".trust-button").removeAttr('disabled');
                document.getElementById('trust-button').innerHTML = "Trust";
              }

              if (trustNeedsDowngrade.length!==0 && duplicateDetected===false) {
                downgradeConfirmRequired=true;
                downgradeConfirmed = confirm("Your new trust settings will result in the existing trust rights for this recorder being lowered.\n"+
                                             "Are you sure you wish to continue?");
              //Re-enable the Trust button if the user chooses the Cancel option.
              if (downgradeConfirmed ===false) {
                $(".trust-button").removeAttr('disabled');
                document.getElementById('trust-button').innerHTML = "Trust";
              }
            }
            //We are going to proceed if the user has clicked ok on the downgrade confirmation message or
            //if the message was never displayed.
            if (duplicateDetected ===false && (downgradeConfirmRequired===false || downgradeConfirmed === true)) {
              //Go through each trust item we need to remove from the database and do the removal
              var handlePostResponse = function (data) {
                if (typeof data.error !== "undefined") {
                  alert(data.error);
                }
              };
              for (i=0; i<trustNeedsRemovalIndex; i++) {
                theDataToRemove= {
                  'website_id': indiciaData.website_id,
                  'user_trust:id' : trustNeedsRemoval[i],
                  'user_trust:deleted' : true
                };
                $.post (
                  indiciaData.ajaxFormPostUrl.replace('occurrence', 'user-trust'),
                  theDataToRemove,
                  handlePostResponse
                );
              }
            }
            //Now add the new trust settings
            if (duplicateDetected ===  false && (downgradeConfirmRequired===false || downgradeConfirmed === true)) {
              $.post (
                indiciaData.ajaxFormPostUrl.replace('occurrence', 'user-trust'),
                theData,
                function (data) {
                  if (typeof data.error === "undefined") {
                    drawExistingTrusts();
                    alert("Trust settings successfully applied to the recorder");
                    $(".trust-button").removeAttr('disabled');
                    document.getElementById('trust-button').innerHTML = "Trust";
                  } else {
                    alert(data.error);
                    $(".trust-button").removeAttr('disabled');
                    document.getElementById('trust-button').innerHTML = "Trust";
                  }
                },
                'json'
              );
            }
          }
          );
        }
      });
    }

    function quickVerifyMenu(row) {
      // can't use User Trusts if the recorder is not linked to a warehouse user.
      if (typeof currRec!=="undefined" && currRec!==null) {
        if (currRec.extra.created_by_id==="1") {
          $('.trust-tool').hide();
        } else {
          $('.trust-tool').show();
        }
        if ($(row).find('.row-belongs-to-site').val()==='t') {
          $(row).find('.verify-tools .edit-record').show();
          $('#btn-edit-record').show();
        } else {
          $(row).find('.verify-tools .edit-record').hide();
          $('#btn-edit-record').hide();
        }
        // show the menu
        $(row).find('.verify-tools').show();
        // remove titles from the grid and store in data, so they don't overlay the menu
        $.each($(row).parents('table:first tbody').find('[title]'), function(idx, ctrl) {
          $(this).data('title', $(ctrl).attr('title')).removeAttr('title');
        });
      }
    }

    $('table.report-grid tbody').click(function (evt) {
      var row=$(evt.target).parents('tr:first')[0];
      $('.verify-tools').hide();
      // reinstate tooltips
      $.each($(row).parents('table:first tbody').find(':data(title)'), function(idx, ctrl) {
        $(ctrl).attr('title', $(this).data('title'));
      });
      // Find the appropriate separator for AJAX url params - depends on clean urls setting.
      urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
      if ($(evt.target).hasClass('quick-verify')) {
        selectRow(row, quickVerifyMenu);
      }
      else if ($(evt.target).hasClass('quick-verify-tool')) {
        quickVerifyPopup(row);
      }
      else if ($(evt.target).hasClass('trust-tool')) {
        trustsPopup(row);
      } else if ($(evt.target).hasClass('edit-record')) {
        editThisRecord($(row).find('.record-id').html());
      }
      else {
        selectRow(row);
      }
    });

    indiciaFns.bindTabsActivate($('#record-details-tabs'), showTab);

    function showSetStatusButtons(showMore) {
      if (showMore) {
        $('#actions-less').hide();
        $('#actions-more').show();
        $('#more-status-buttons').html('[' + indiciaData.langLess + ']');
      }
      else {
        $('#actions-more').hide();
        $('#actions-less').show();
        $('#more-status-buttons').html('[' + indiciaData.langMore + ']');
      }
      if (typeof $.cookie !== "undefined") {
        $.cookie('verification-status-buttons', showMore ? 'more' : 'less');
      }
    }

    if (typeof $.cookie !== "undefined") {
      var show = $.cookie('verification-status-buttons');
      if (show === 'more') {
        showSetStatusButtons(show);
      }
    }

    $('#more-status-buttons').click(function() {
      var showMore = $('#actions-less:visible').length;
      showSetStatusButtons(showMore);
    });

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
        $('#action-buttons-status label').html('Set status:');
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

    $('#btn-redetermine').click(function () {
      showRedeterminationPopup();
    });

    function editThisRecord(id) {
      var $row=$('tr#row'+id),
        path=$row.find('.row-input-form').val(),
        sep=(path.indexOf('?')>=0) ? '&' : '?';
      window.location=path+sep+'occurrence_id='+id;
    }

    $('#btn-edit-record').click(function() {
      editThisRecord(occurrence_id);
    });

    /**
     * On the redetermine popup, handle the switch to and from searching the full species lists for records which come from
     * custom species lists.
     */
    $('#redet-from-full-list').change(function() {
      if ($('#redet-from-full-list').attr('checked')) {
        $('#redet\\:taxon').setExtraParams({"taxon_list_id": indiciaData.mainTaxonListId});
      } else {
        $('#redet\\:taxon').setExtraParams({"taxon_list_id": currRec.extra.taxon_list_id});
      }
    });

  });

  //Function to draw any existing trusts from the database
  function drawExistingTrusts() {
    var getTrustsReport = indiciaData.read.url +'/index.php/services/report/requestReport?report=library/user_trusts/get_user_trust_for_record.xml&mode=json&callback=?', 
        getTrustsReportParameters = {
          'user_id':currRec.extra.created_by_id,
          'survey_id':currRec.extra.survey_id,
          'taxon_group_id':currRec.extra.taxon_group_id,
          'location_ids':currRec.extra.locality_ids,
          'auth_token': indiciaData.read.auth_token,
          'nonce': indiciaData.read.nonce,
          'reportSource':'local'
        }, i, idNum;
    //variable holds our HTML
    var textMessage;
    $.getJSON (
      getTrustsReport,
      getTrustsReportParameters,
      function(data) {
        if (typeof data.error === "undefined") {
          if (data.length > 0) {
            trustsCounter = data.length;
            textMessage = '<h3>Existing trust criteria</h3>';
            //If there is only one trust we put the information into a sentence, else we put it in a bullet list
            if (data.length===1) {
              textMessage += '<div class="existingTrustSection existingTrustData">' + data[0].recorder_name + ' is trusted for the ';
            }
            else {
              textMessage += '<div class="existingTrustSection">This record is trusted as ' + data[0].recorder_name + ' has the following trust criteria:';
              textMessage += '<ul>';
            }
            //for each trust we build the HTML
            for (i=0; i<data.length; i++) {
              if(data.length>1) {
                textMessage += '<li class="existingTrustData" id="trust-' + data[i].trust_id + '">The ';
              }
              if (data[i].survey_title) {
                textMessage += '<strong>survey </strong><em>' +  data[i].survey_title +  '</em>, ';
              }
              if (data[i].taxon_group) {
                textMessage += '<strong> taxon group</strong><em> ' +  data[i].taxon_group + '</em>, ';
              }
              if (data[i].location_name) {
                textMessage += '<strong> location</strong><em> ' +  data[i].location_name + '</em>';
              }
              //Remove comma from end of trust information if there is a dangling comma because location info isn't present
              if (!data[i].location_name) {
                textMessage = textMessage.substring(0, textMessage.length - 2);
              }
              textMessage += '<br/>&nbsp;&nbsp;- trust was setup by ' + data[i].trusted_by;
              textMessage += ' <a class="default-button existingTrustRemoveButton" id="deleteTrust-' +
                  data[i].trust_id + '" >Remove</a><br/>';
              if(data.length>1) {
                textMessage += '</li>';
              }
            }
            if(data.length>1) {
              textMessage += '</ul>';
            }
            textMessage += '</div>';
            //Apply the HTML to the HTML tag
            document.getElementById('existingTrusts').innerHTML = textMessage;
            //Remove a trust if the user clicks the remove button
            $(".existingTrustRemoveButton").click(function(evt) {
              //We only want the number from the end of the id
              var idNumArray = evt.target.id.match(/\d+$/);

              if (idNumArray) {
                idNum = idNumArray[0];
              }
              removeTrust(idNum);
            });
          }
        } else {
          alert(data.error);
        }
      }
    );
  }

  function removeTrust(RemoveTrustId) {
    var removeItem = {
      'website_id': indiciaData.website_id,
      'user_trust:id' : RemoveTrustId,
      'user_trust:deleted' : true
    };

    $.post (
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'user-trust'),
      removeItem,
      function (data) {
        if (typeof data.error !== "undefined") {
          alert(data.error);
        } else {
          //If there are several trusts we remove a row
          //otherwise we remove the whole trust section
          if (trustsCounter > 1) {
            $("#trust-" + RemoveTrustId).hide();
          } else {
            $(".existingTrustSection").hide();
          }
          trustsCounter--;
        }
      }
    );
  }
}) (jQuery);
