var rowRequest=null, occurrence_id = null, currRec = null, urlSep, validator, speciesLayers = [], trustsCounter;
var email = {to:'', subject:'', body:'', type:''};
var selectRow, getAttributeValue, quickVerifyMenu, postOccurrence, setupRecordCheckEmail, buildVerifierEmail,
buildRecorderConfirmationEmail, buildRecorderEmail, popupEmail, processEmail, sendEmail, showComment, saveComment,
saveVerifyComment, showTab, setStatus, drawExistingTrusts, removeTrust,
trustsPopup, quickVerifyPopup;
// IE7 compatability
if(!Array.indexOf){
  Array.prototype.indexOf = function(obj){
    "use strict";
    var i;
    for(i=0; i<this.length; i++){
      if(this[i]===obj){
        return i;
      }
    }
  };
}

(function ($) {
  selectRow = function selectRow(tr, callback) {
    "use strict";
    // The row ID is row1234 where 1234 is the occurrence ID.
    if (tr.id.substr(3)===occurrence_id) {
      if (typeof callback!=="undefined") {
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
    // make it clear things are loading
    $('#chart-div').css('opacity',0.15);
    rowRequest = $.getJSON(
      indiciaData.ajaxUrl + '/details/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id,
      null,
      function (data) {
        // refind the row, as $(tr) sometimes gets obliterated.
        var $row = $('#row' + data.data.Record[0].value);
        rowRequest=null;
        currRec = data;
        $('#instructions').hide();
        $('#record-details-content').show();
        if ($row.parents('tbody').length !== 0) {
          $row.parents('tbody').children('tr').removeClass('selected');
          $row.addClass('selected');
          // point the image and comments tabs to the correct AJAX call for the selected occurrence.
          $('#record-details-tabs').tabs('url', indiciaData.detailsTabs.indexOf('images'), indiciaData.ajaxUrl + '/images/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id);
          $('#record-details-tabs').tabs('url', indiciaData.detailsTabs.indexOf('comments'), indiciaData.ajaxUrl + '/comments/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id);
          // reload current tabs
          $('#record-details-tabs').tabs('load', $('#record-details-tabs').tabs('option', 'selected'));
          $('#record-details-toolbar *').attr('disabled', '');
          showTab();
          // remove any wms layers for species or the gateway data
          $.each(speciesLayers, function(idx, layer) {
            indiciaData.mapdiv.map.removeLayer(layer);
          });
          speciesLayers = [];
          var layer, thisSpLyrSettings, filter;
          if (typeof indiciaData.wmsSpeciesLayers!=="undefined" && data.extra.taxon_external_key!==null) {
            $.each(indiciaData.wmsSpeciesLayers, function(idx, layerDef) {
              thisSpLyrSettings = $.extend({}, layerDef.settings);
              // replace values with the external key if the token is used
              $.each(thisSpLyrSettings, function(prop, value) {
                if (typeof(value)==='string' && $.trim(value)==='{external_key}') {
                  thisSpLyrSettings[prop]=data.extra.taxon_external_key;
                }
              });
              layer = new OpenLayers.Layer.WMS(layerDef.title, layerDef.url.replace('{external_key}', data.extra.taxon_external_key),
                  thisSpLyrSettings, layerDef.olSettings);
              indiciaData.mapdiv.map.addLayer(layer);
              layer.setZIndex(0);
              speciesLayers.push(layer);
            });
          }
          if (typeof indiciaData.indiciaSpeciesLayer!=="undefined" && data.extra[indiciaData.indiciaSpeciesLayer.filterField]!==null) {
            filter=indiciaData.indiciaSpeciesLayer.cqlFilter.replace('{filterValue}',data.extra[indiciaData.indiciaSpeciesLayer.filterField]);
            layer = new OpenLayers.Layer.WMS(indiciaData.indiciaSpeciesLayer.title, indiciaData.indiciaSpeciesLayer.wmsUrl,
                {layers: indiciaData.indiciaSpeciesLayer.featureType, transparent: true, CQL_FILTER: filter, STYLES: indiciaData.indiciaSpeciesLayer.sld},
                {isBaseLayer: false, sphericalMercator: true, singleTile: true, opacity: 0.5});
            indiciaData.mapdiv.map.addLayer(layer);
            layer.setZIndex(0);
            speciesLayers.push(layer);
          }
        }
        if (typeof callback!=="undefined") {
          callback(tr);
        }
      }
    );
  }

  getAttributeValue = function getAttributeValue(caption) {
    "use strict";
    //returns the value of the attribute in the currRec.data with the caption supplied
   var r = '';
   $.each(currRec.data, function(){
      if (this.caption === caption) {
        //found attribute
        r = this.value;
        return false;
      }
    });
    return r;
  }

  /**
   * Post an object containing occurrence form data into the Warehouse. Updates the
   * visual indicators of the record's status.
   */
  postOccurrence = function postOccurrence(occ) {
    "use strict";
    var status = occ['occurrence:record_status'], id=occ['occurrence:id'];
    $.post(
      indiciaData.ajaxFormPostUrl,
      occ,
      function () {
        $('#row' + id + ' td:first div, #details-tab td').removeClass('status-V');
        $('#row' + id + ' td:first div, #details-tab td').removeClass('status-C');
        $('#row' + id + ' td:first div, #details-tab td').removeClass('status-R');
        $('#row' + id + ' td:first div, #details-tab td').removeClass('status-I');
        $('#row' + id + ' td:first div, #details-tab td').removeClass('status-T');
        $('#row' + id + ' td:first div, #details-tab td.status').addClass('status-' + status);
        var text = indiciaData.statusTranslations[status], nextRow;
        $('#details-tab td.status').html(text);
        if (indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] === 'details' ||
            indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] === 'comments') {
          $('#record-details-tabs').tabs('load', $('#record-details-tabs').tabs('option', 'selected'));
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
          }
        }
      }
    );
    $('#add-comment').remove();
  }

  /**
   * Build an email to send to a verifier or the original recorder, containing the record details.
   */
  setupRecordCheckEmail = function setupRecordCheckEmail(subject, body) {
    "use strict";
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
  buildVerifierEmail = function buildVerifierEmail() {
    "use strict";
    setupRecordCheckEmail(indiciaData.email_subject_send_to_verifier, indiciaData.email_body_send_to_verifier);
    // Let the user pick the recipient
    email.to = '';
    email.subtype='V';
    popupEmail();
  }

  /**
   * Build an email for sending to the recorder to request more details.
   */
  buildRecorderConfirmationEmail = function buildRecorderConfirmationEmail() {
    "use strict";
    setupRecordCheckEmail(indiciaData.email_subject_send_to_recorder, indiciaData.email_body_send_to_recorder);
    email.to=currRec.extra.recorder_email;
    email.subtype='R';
    popupEmail();
  }

  function buildRecorderEmail(status, comment) {
    "use strict";
    if (status === 'V') {
      email.subject = indiciaData.email_subject_verified;
      email.body = indiciaData.email_body_verified;
    }
    else if (status === 'R') {
      email.subject = indiciaData.email_subject_rejected;
      email.body = indiciaData.email_body_rejected;
    }
    else if (status === 'D') {
      email.subject = indiciaData.email_subject_dubious;
      email.body = indiciaData.email_body_dubious;
    }

    email.to = getAttributeValue(indiciaData.email_address_attribute);
    email.subject = email.subject
        .replace('%taxon%', currRec.extra.taxon)
        .replace('%id%', occurrence_id)
        .replace('%sample_id%', currRec.extra.sample_id);
    email.body = email.body
        .replace('%taxon%', currRec.extra.taxon)
        .replace('%id%', occurrence_id)
        .replace('%sample_id%', currRec.extra.sample_id)
        .replace('%date%', currRec.extra.date)
        .replace('%entered_sref%', currRec.extra.entered_sref)
        .replace('%comment%', comment)
        .replace('%verifier%', indiciaData.username);

    popupEmail();
  }

  popupEmail = function popupEmail() {
    "use strict";
    $.fancybox('<form id="email-form"><fieldset class="popup-form">' +
          '<legend>' + indiciaData.popupTranslations.emailTitle + '</legend>' +
          '<label>To:</label><input type="text" id="email-to" class="email required" value="' + email.to + '"/><br />' +
          '<label>Subject:</label><input type="text" id="email-subject" class="require" value="' + email.subject + '"/><br />' +
          '<label>Body:</label><textarea id="email-body" class="required">' + email.body + '</textarea><br />' +
          '<input type="hidden" id="set-status" value="' + status + '"/>' +
          '<input type="submit" class="default-button" ' +
              'value="' + indiciaData.popupTranslations.sendEmail + '" />' +
          '</fieldset></form>');
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  processEmail = function processEmail(){
    "use strict";
    //Complete creation of email of record details
    if (validator.numberOfInvalids()===0) {
      email.to = $('#email-to').val();
      email.subject = $('#email-subject').val();
      email.body = $('#email-body').val();

      if (email.type === 'recordCheck') {
        // ensure images are loaded
        $.ajax({
          url: indiciaData.ajaxUrl + '/imagesAndComments/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id,
          async: false,
          dataType: 'json',
          success: function (response) {
              email.body = email.body.replace(/\[Photos\]/g, response.images);
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

  sendEmail = function sendEmail() {
    "use strict";
    //Send an email
    // use an AJAX call to get the server to send the email
    $.post(
      indiciaData.ajaxUrl + '/email',
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

  showComment = function showComment(comment, username) {
    "use strict";
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

  saveComment = function saveComment(text) {
    "use strict";
    if (typeof text==="undefined" && $('#comment-text')) {
      text=$('#comment-text').val();
    }
    var data = {
      'website_id': indiciaData.website_id,
      'occurrence_comment:occurrence_id': occurrence_id,
      'occurrence_comment:comment': text,
      'occurrence_comment:person_name': indiciaData.username
    };
    $.post(
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'occ-comment'),
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          showComment(text, indiciaData.username);
          if ($('#comment-text')) {
            $('#comment-text').val('');
          }
        } else {
          alert(data.error);
        }
      }
    );
  }

  saveVerifyComment = function saveVerifyComment() {
    "use strict";
    var status = $('#set-status').val(),
      comment = indiciaData.statusTranslations[status],
      data, sendEmail = false;
    if ($('#verify-comment').val()!=='') {
      comment += ".\n" + $('#verify-comment').val();
    }
    data = {
      'website_id': indiciaData.website_id,
      'occurrence:id': occurrence_id,
      'occurrence:verified_by_id': indiciaData.userId,
      'occurrence:record_status': status,
      'occurrence_comment:comment': comment,
      'occurrence_comment:person_name': indiciaData.username
    };
    postOccurrence(data);

    //Does the recorder email option exist?
    if (indiciaData.email_request_attribute !== '') {
      //Find attribute to see if recorder wants email confirmation
      if (getAttributeValue(indiciaData.email_request_attribute) === 1) {
        sendEmail = true;
        buildRecorderEmail(status, comment);
      }
    }
    if (!sendEmail) {$.fancybox.close();}

  }

  showTab = function showTab() {
    "use strict";
    if (currRec !== null) {
      if (indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] === 'details') {
        $('#details-tab').html(currRec.content);
      } else if (indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] === 'experience') {
        $.get(
          indiciaData.ajaxUrl + '/experience/' + indiciaData.nid + urlSep +
              'occurrence_id=' + occurrence_id + '&user_id=' + currRec.extra.created_by_id,
          null,
          function (data) {
            $('#experience-div').html(data);
          }
        );
      } else if (indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] === 'phenology') {
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
      } else if (indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] === 'images') {
        $('#images-tab a.fancybox').fancybox();
      }
      // make it clear things are loading
      if (indiciaData.mapdiv !== null) {
        $(indiciaData.mapdiv).css('opacity', currRec.extra.wkt===null ? 0.1 : 1);
      }
    }
  }

  setStatus = function setStatus(status) {
    "use strict";
    $.fancybox('<fieldset class="popup-form">' +
          '<legend>' + indiciaData.popupTranslations.title.replace('{1}', indiciaData.popupTranslations[status]) + '</legend>' +
          '<label>Comment:</label><textarea id="verify-comment" rows="5" cols="80"></textarea><br />' +
          '<input type="hidden" id="set-status" value="' + status + '"/>' +
          '<button type="button" class="default-button" onclick="saveVerifyComment();">' +
              indiciaData.popupTranslations.save.replace('{1}', indiciaData.popupTranslations['verb' + status]) + '</button>' +
          '</fieldset>');
  }

  mapInitialisationHooks.push(function (div) {
    "use strict";
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
    "use strict";

    //Use jQuery to add a button to the top of the verification page. Use this button to access the popup
    //which allows you to verify all trusted records.
    var verifyAllTrustedButton = '<input type="button" value="..." class="default-button verify-grid-trusted tools-btn" id="verify-grid-trusted"/>', 
        trustedHtml, request;
    $('#verification-params #run-report').before(verifyAllTrustedButton);
    $('#verify-grid-trusted').click(function() {
      trustedHtml = '<div class="trusted-verify-popup" style="width: 550px"><h2>Verify records by trusted recorders</h2>'+
                    '<p>This facility allows you to verify all records where the recorder is trusted based on the record\'s '+
                    'survey, taxon group and location. Before using this facility, set up the recorders you wish to trust '+
                    'using the ... button next to each record.</p>';
      trustedHtml += '<label><input type="checkbox" name="ignore-checks-trusted" /> Include failures?</label><p>The records will only be verified if they have been through automated checks ' +
                     'without any rule violations. If you <em>really</em> trust the records are correct then you can verify them even if they fail some checks by ticking this box.</p>'+
                     '<button type="button" class="default-button" id="verify-trusted-button">Verify trusted records</button></div>';
      $.fancybox(trustedHtml);

      $('#verify-trusted-button').click(function() {
        var params=indiciaData.reports.verification.grid_verification_grid.getUrlParamsForAllRecords();
        //We pass "trusted" as a parameter to the existing verification_list_3 report which has been adjusted
        //to handle verification of all trusted records.
        params.records="trusted";
        request = indiciaData.ajaxUrl + '/bulk_verify/'+indiciaData.nid;
        $.post(request,
          'report='+encodeURI(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource)+'&params='+encodeURI(JSON.stringify(params))+
          '&user_id='+indiciaData.userId+'&ignore='+$('.trusted-verify-popup input[name=ignore-checks-trusted]').attr('checked'),
          function(response) {
            indiciaData.reports.verification.grid_verification_grid.reload();
            alert(response + ' records verified');
          }
        );
        $.fancybox.close();
      });
    });

  function quickVerifyPopup() {
    var popupHtml;
    popupHtml = '<div class="quick-verify-popup" style="width: 550px"><h2>Quick verification</h2>'+
        '<p>The following options let you rapidly verify records. The only records affected are those in the grid but they can be on any page of the grid, '+
        'so please ensure you have set the grid\'s filter correctly before proceeding. You should only proceed if you are certain that data you are verifying '+
        'can be trusted without further investigation.</p>'+
        '<label><input type="radio" name="quick-option" value="species" /> Verify grid\'s records of <span class="quick-taxon">'+currRec.extra.taxon+'</span></label><br/>';
    if (currRec.extra.recorder!=='') {
      popupHtml += '<label><input type="radio" name="quick-option" value="recorder"/> Verify grid\'s records by <span class="quick-user">'+currRec.extra.recorder+'</span></label><br/>'+
          '<label><input type="radio" name="quick-option" value="species-recorder" /> Verify grid\'s records of <span class="quick-taxon">'+currRec.extra.taxon+
          '</span> by <span class="quick-user">'+currRec.extra.recorder+'</span></label><br/>';
    }
    popupHtml += '<label><input type="checkbox" name="ignore-checks" /> Include failures?</label><p class="helpText">The records will only be verified if they do not fail '+
        'any automated verification checks. If you <em>really</em> trust the records are correct then you can verify them even if they fail some checks by ticking this box.</p>';
    popupHtml += '<button type="button" class="default-button verify-button">Verify chosen records</button>'+
        '<button type="button" class="default-button cancel-button">Cancel</button></p></div>';
    $.fancybox(popupHtml);
    $('.quick-verify-popup .verify-button').click(function() {
      var params=indiciaData.reports.verification.grid_verification_grid.getUrlParamsForAllRecords(), request,
          radio=$('.quick-verify-popup input[name=quick-option]:checked');
      if (radio.length===1) {
        if ($(radio).val().indexOf('recorder')!==-1) {
          params.user=currRec.extra.recorder;
        }
        if ($(radio).val().indexOf('species')!==-1) {
          params.taxon=currRec.extra.taxon;
        }
        // We now have parameters that can be applied to a report and we know the report, so we can ask the warehouse
        // to verify the occurrences provided by the report that match the filter.
        request = indiciaData.ajaxUrl + '/bulk_verify/'+indiciaData.nid;
        $.post(request,
            'report='+encodeURI(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource)+'&params='+encodeURI(JSON.stringify(params))+
                '&user_id='+indiciaData.userId+'&ignore='+$('.quick-verify-popup input[name=ignore-checks]').attr('checked'),
            function(response) {
              indiciaData.reports.verification.grid_verification_grid.reload();
              alert(response + ' records verified');
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
        'that the recorder is trusted for the records will not be automatically verified. However, you can filter the grid to show only "trusted" records and use the ... button at the top ' +
        'of the grid to bulk-verify all these records. If you want to trust records from <em>' + currRec.extra.recorder + '</em> in future, you can use the following options to select the ' +
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
        popupHtml += '<label>Trust will be applied to records from locality:</label><br/>'+
            '<label><input type="radio" name="trust-location" value="all"> All </label><br/>';
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
        var downgradeConfirmRequired = false;
        var downgradeConfirmed=false;
        var duplicateDetected = false;
        var trustNeedsRemoval = [];
        var getTrustsReport = indiciaData.read.url +'/index.php/services/report/requestReport?report=library/user_trusts/get_user_trust_for_record.xml&mode=json&mode=json&callback=?';
        var getTrustsReportParameters = {
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
            var downgradeDetect = 0;
            var upgradeDetect = 0;
            var trustNeedsRemovalIndex = 0;
            var trustNeedsDowngradeIndex = 0;
            var trustNeedsDowngrade = [];
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
              if (theData['user_trust:survey_id'] && !data[i].survey_id ||
                  theData['user_trust:taxon_group_id'] && !data[i].taxon_group_id ||
                  theData['user_trust:location_id'] && !data[i].location_id) {
                downgradeDetect++;
              }
              //If any of the 3 trust items the user has entered are bigger than the existing trust item we are looking at,
              //then we flag it as the existing row needs to be at least partially upgraded
              if (!theData['user_trust:survey_id'] && data[i].survey_id ||
                  !theData['user_trust:taxon_group_id'] && data[i].taxon_group_id ||
                  !theData['user_trust:location_id'] && data[i].location_id) {
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

  quickVerifyMenu = function quickVerifyMenu(row) {
    // can't use User Trusts if the recorder is not linked to a warehouse user.
    if (typeof currRec!=="undefined") {
      if (currRec.extra.created_by_id==="1") {
        $('.trust-tool').hide();
      } else {
        $('.trust-tool').show();
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
    }
    else {
      selectRow(row);
    }
  });

    $('#record-details-tabs').bind('tabsshow', function () {
      showTab();
    });

    $('#btn-verify').click(function () {
      setStatus('V');
    });

    $('#btn-reject').click(function () {
      setStatus('R');
    });

    $('#btn-dubious').click(function () {
      setStatus('D');
    });

    $('#btn-email-expert').click(function () {
      buildVerifierEmail();
    });

    $('#btn-email-recorder').click(function () {
      buildRecorderConfirmationEmail();
    });

  });

  //Function to draw any existing trusts from the database
  drawExistingTrusts = function drawExistingTrusts() {
    "use strict";
    var getTrustsReport = indiciaData.indiciaSvc +'/index.php/services/report/requestReport?report=library/user_trusts/get_user_trust_for_record.xml&mode=json&callback=?', 
        getTrustsReportParameters = {
          'user_id':currRec.extra.created_by_id,
          'survey_id':currRec.extra.survey_id,
          'taxon_group_id':currRec.extra.taxon_group_id,
          'location_ids':currRec.extra.locality_ids,
          'auth_token': indiciaData.readAuth,
          'nonce': indiciaData.nonce,
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
              textMessage += '<div class="existingTrustSection existingTrustData">' + data[0].recorder_name + ' is trusted for ';
            }
            else {
              textMessage += '<div class="existingTrustSection">This record is trusted as ' + data[0].recorder_name + ' has the following trust criteria:<br/><ul><br/>';
            }
            //for each trust we build the HTML
            for (i=0; i<data.length; i++) {
              if(data.length>1) {
                textMessage += '<li class="existingTrustData" id="trust-' + data[i].trust_id + '">The ';
              }
              else {
                textMessage += 'the ';
              }

              if (data[i].survey_title) {
                textMessage += '<b>survey </b><i>' +  data[i].survey_title +  '</i>, ';
              }
              if (data[i].taxon_group) {
                textMessage += '<b> taxon group</b><i> ' +  data[i].taxon_group + '</i>, ';
              }
              if (data[i].location_name) {
                textMessage += '<b> location</b><i> ' +  data[i].location_name + '</i>';
              }
              //Remove comma from end of trust information if there is a dangling comma because location info isn't present
              if (!data[i].location_name) {
                textMessage = textMessage.substring(0, textMessage.length - 2);
              }
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

  removeTrust = function removeTrust(RemoveTrustId) {
    "use strict";
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
