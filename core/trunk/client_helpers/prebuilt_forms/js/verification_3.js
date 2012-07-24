var occurrence_id = null, current_record = null, urlSep, validator, speciesLayers = [];
var email = {to:'', subject:'', body:'', type:''};

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

function selectRow(tr) {
  // The row ID is row1234 where 1234 is the occurrence ID. 
  if (tr.id.substr(3)===occurrence_id) {
    return;
  }
  occurrence_id = tr.id.substr(3);
  // make it clear things are loading
  $('#chart-div').css('opacity',0.15);
  $.getJSON(
    indiciaData.ajaxUrl + '/details/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id,
    null,
    function (data) {
      current_record = data;
      $('#instructions').hide();
      $('#record-details-content').show();
      if ($(tr).parents('tbody').length !== 0) {
        $(tr).parents('tbody').children('tr').removeClass('selected');
        $(tr).addClass('selected');
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
        var layer, thisSpLyrSettings, filter, skip;
        if (typeof indiciaData.wmsSpeciesLayers!=="undefined" && data.additional.taxon_external_key!==null) {
          $.each(indiciaData.wmsSpeciesLayers, function(idx, layerDef) {
            thisSpLyrSettings = $.extend({}, layerDef.settings);
            // replace values with the extrnal key if the token is used
            $.each(thisSpLyrSettings, function(prop, value) {
              if (typeof(value)==='string' && $.trim(value)==='{external_key}') {
                thisSpLyrSettings[prop]=data.additional.taxon_external_key;
              }
            });
            layer = new OpenLayers.Layer.WMS(layerDef.title, layerDef.url.replace('{external_key}', data.additional.taxon_external_key), 
                thisSpLyrSettings, layerDef.olSettings);
            indiciaData.mapdiv.map.addLayer(layer);
            speciesLayers.push(layer);
          });
        }
        if (typeof indiciaData.indiciaSpeciesLayer!=="undefined" && data.additional[indiciaData.indiciaSpeciesLayer.filterField]!==null) {
          filter=indiciaData.indiciaSpeciesLayer.cqlFilter.replace('{filterValue}',data.additional[indiciaData.indiciaSpeciesLayer.filterField]);
          layer = new OpenLayers.Layer.WMS(indiciaData.indiciaSpeciesLayer.title, indiciaData.indiciaSpeciesLayer.wmsUrl, 
              {layers: indiciaData.indiciaSpeciesLayer.featureType, transparent: true, CQL_FILTER: filter, STYLES: indiciaData.indiciaSpeciesLayer.sld},
              {isBaseLayer: false, sphericalMercator: true, singleTile: true, opacity: 0.5});
          indiciaData.mapdiv.map.addLayer(layer);
          speciesLayers.push(layer);
        }
      }
    }
  );
}

function getAttributeValue(caption) {
  //returns the value of the attribute in the current_record.data with the caption supplied
 var r = '';
 $.each(current_record.data, function(){
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
function postOccurrence(occ) {
  var status = occ['occurrence:record_status'], id=occ['occurrence:id'];
  $.post(
    indiciaData.ajaxFormPostUrl,
    occ,
    function (data) {
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
function setupRecordCheckEmail(subject, body) {
  //Form to create email of record details
  var record = '';
  $.each(current_record.data, function (idx, section) {
    $.each(section, function(idx, field) {
      if (field.value !== null && field.value !=='') {
        record += field.caption + ': ' + field.value + "\n";
      }
    });
  });
  record += "\n\n[Photos]\n\n[Comments]";
  email.subject = indiciaData.email_subject_send_to_verifier
      .replace('%taxon%', current_record.additional.taxon)
      .replace('%id%', occurrence_id);
  email.body = indiciaData.email_body_send_to_verifier
      .replace('%taxon%', current_record.additional.taxon)
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
  popupEmail();
}

/**
 * Build an email for sending to the recorder to request more details.
 */
function buildRecorderConfirmationEmail() {
  setupRecordCheckEmail(indiciaData.email_subject_send_to_recorder, indiciaData.email_body_send_to_recorder); 
  email.to=current_record.additional.recorder_email;
  popupEmail();
}

function buildRecorderEmail(status, comment)
{
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
      .replace('%taxon%', current_record.additional.taxon)
      .replace('%id%', occurrence_id)
      .replace('%sample_id%', current_record.additional.sample_id);
  email.body = email.body
      .replace('%taxon%', current_record.additional.taxon)
      .replace('%id%', occurrence_id)
      .replace('%sample_id%', current_record.additional.sample_id)
      .replace('%date%', current_record.additional.date)
      .replace('%entered_sref%', current_record.additional.entered_sref)
      .replace('%comment%', comment)
      .replace('%verifier%', indiciaData.username);

  popupEmail();
}

function popupEmail() {
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

function processEmail(){
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
    // set the status
    var status = 'S',
      occ = {
        'website_id': indiciaData.website_id,
        'occurrence:id': occurrence_id,
        'occurrence:record_status': status
      };
    postOccurrence(occ);
    }

    sendEmail();
  }
  return false;
}

function sendEmail() {
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

function showTab() {
  if (current_record !== null) {
    if (indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] === 'details') {
      $('#details-tab').html(current_record.content);
    } else if (indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] === 'phenology') {
      $.getJSON(
        indiciaData.ajaxUrl + '/phenology/' + indiciaData.nid + urlSep + 
            'external_key=' + current_record.additional.taxon_external_key +
            '&taxon_meaning_id=' + current_record.additional.taxon_meaning_id,
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
    }
    // make it clear things are loading
    if (indiciaData.mapdiv !== null) {
      $(indiciaData.mapdiv).css('opacity', current_record.additional.wkt===null ? 0.1 : 1);
    }
  }
}

function setStatus(status) {
  $.fancybox('<fieldset class="popup-form">' +
        '<legend>' + indiciaData.popupTranslations.title.replace('{1}', indiciaData.popupTranslations[status]) + '</legend>' +
        '<label>Comment:</label><textarea id="verify-comment" rows="5" cols="80"></textarea><br />' +
        '<input type="hidden" id="set-status" value="' + status + '"/>' +
        '<button type="button" class="default-button" onclick="saveVerifyComment();">' +
            indiciaData.popupTranslations.save.replace('{1}', indiciaData.popupTranslations['verb' + status]) + '</button>' +
        '</fieldset>');
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


$(document).ready(function () {
  
  $('table.report-grid tbody').click(function (evt) {
    // Find the appropriate separator for AJAX url params - depends on clean urls setting.
    urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    $('#record-details-toolbar *').attr('disabled', 'disabled');
    selectRow($(evt.target).parents('tr')[0]);
    if ($(evt.target).hasClass('quick-verify')) {
      var visibleIdx=0, userColIdx, taxonColIdx, userVal, taxonVal, row=$(evt.target).parents('tr:first')[0], popupHtml;
      // work out which visible column index applies to the user and species data
      $.each(indiciaData.reports.verification.grid_verification_grid[0].settings.columns, function(idx, column) {
        if (typeof column.fieldname !=="undefined") {
          if (column.fieldname==='user') {
            userColIdx = visibleIdx;
          } else if (column.fieldname==='taxon') {
            taxonColIdx = visibleIdx;
          }
        }
        if (column.visible!=="false" && column.visible!==false) {
          visibleIdx++;
        }
      });
      userVal = $(row).find('input.user-val').val();
      if (typeof userVal==="undefined") {
        userVal = $(row).find('td:eq('+userColIdx+')').html();
      }
      taxonVal = $(row).find('input.taxon-val').val();
      if (typeof taxonVal==="undefined") {
        taxonVal = $(row).find('td:eq('+taxonColIdx+')').html();
      }
      popupHtml = '<div class="quick-verify-popup" style="width: 550px"><h2>Quick verification</h2>'+
          '<p>The following options let you rapidly verify records. The only records affected are those in the grid but they can be on any page of the grid, '+
          'so please ensure you have set the grid\'s filter correctly before proceeding. You should only proceed if you are certain that data you are verifying '+
          'can be trusted without further investigation.</p>'+
          '<label><input type="radio" name="quick-option" value="species" /> Verify grid\'s records of <span class="quick-taxon">'+taxonVal+'</span></label><br/>';
      if (userVal!=='') {
        popupHtml += '<label><input type="radio" name="quick-option" value="recorder"/> Verify grid\'s records by <span class="quick-user">'+userVal+'</span></label><br/>'+          
            '<label><input type="radio" name="quick-option" value="species-recorder" /> Verify grid\'s records of <span class="quick-taxon">'+taxonVal+
            '</span> by <span class="quick-user">'+userVal+'</span></label><br/>';
      }
      popupHtml += '<label><input type="checkbox" name="ignore-checks" /> Include failures?</label><p class="helpText">The records will only be verified if they do not fail '+
          'any automated verification checks. If you <em>really</em> trust the records are correct then you can verify them even if they fail some checks by ticking this box.</p>';
      popupHtml += '<button type="button" class="default-button verify-button">Verify chosen records</button>'+
          '<button type="button" class="default-button cancel-button">Cancel</button>'+
          "</div>\n";
      $.fancybox(popupHtml);
      $('.quick-verify-popup button').click(function(evt) {
        if ($(evt.target).hasClass('verify-button')) {
          var params=indiciaData.reports.verification.grid_verification_grid.getUrlParamsForAllRecords(), request,
              radio=$('.quick-verify-popup input[name=quick-option]:checked');
          if (radio.length===1) {
            if ($(radio).val().indexOf('recorder')!==-1) {
              params.user=userVal;
            }
            if ($(radio).val().indexOf('species')!==-1) {
              params.taxon=taxonVal;
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
        } else {
          $.fancybox.close();
        }
      });
    }
  });

  $('#record-details-tabs').bind('tabsshow', function (evt, ui) {
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

