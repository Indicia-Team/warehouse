var mapDiv = null, occurrence_id = null, current_record = null, urlSep, validator;
var email = {to:'', subject:'', body:'', type:''};

function selectRow(tr) {
  // The row ID is row1234 where 1234 is the occurrence ID. 
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
        var tabcount = $('#record-details-tabs').tabs('length');
        $('#record-details-tabs').tabs('url', tabcount-2, indiciaData.ajaxUrl + '/images/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id);
        $('#record-details-tabs').tabs('url', tabcount-1, indiciaData.ajaxUrl + '/comments/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrence_id);
        // reload current tabs
        $('#record-details-tabs').tabs('load', $('#record-details-tabs').tabs('option', 'selected'));
        $('#record-details-toolbar *').attr('disabled', '');
        showTab();
        // remove any wms layers for species or the gateway data
        $.each(mapDiv.map.layers, function(idx, layer) {
          if (layer.CLASS_NAME==='OpenLayers.Layer.WMS') {
            mapDiv.map.removeLayer(layer);
          }
        });
        var layer, thisSpSettings, filter;
        if (typeof indiciaData.wmsSpeciesLayers!=="undefined") {
          $.each(indiciaData.wmsSpeciesLayers, function(idx, layerDef) {
            thisSpLyrSettings = $.extend({}, layerDef.settings);
            // replace values with the extrnal key if the token is used
            $.each(thisSpLyrSettings, function(prop, value) {
              if (typeof(value)=='string' && $.trim(value)==='{external_key}') {
                thisSpLyrSettings[prop]=data.taxon_external_key;
              }
            });
            layer = new OpenLayers.Layer.WMS(layerDef.title, layerDef.url.replace('{external_key}', data.taxon_external_key), 
                thisSpLyrSettings, layerDef.olSettings);
            mapDiv.map.addLayer(layer);
          });
        }
        if (typeof indiciaData.indiciaSpeciesLayer!=="undefined") {
          filter=indiciaData.indiciaSpeciesLayer.cqlFilter.replace('{filterValue}',data[indiciaData.indiciaSpeciesLayer.filterField]);
          layer = new OpenLayers.Layer.WMS(indiciaData.indiciaSpeciesLayer.title, indiciaData.indiciaSpeciesLayer.wmsUrl, 
              {layers: indiciaData.indiciaSpeciesLayer.featureType, transparent: true, CQL_FILTER: filter},
              {isBaseLayer: false, sphericalMercator: true, singleTile: true});
          mapDiv.map.addLayer(layer);
        }
      }
    }
  );
}

function getAttributeValue(caption) {
  //returns the value of the attribute in the current_record.data with the caption supplied
 var r = '';
 $.each(current_record.data, function(){
    if (this.caption == caption) {
      //found attribute
      r = this.value
      return false;
    }
  });
  return r
}

/** 
 * Post an object containing occurrence form data into the Warehouse. Updates the
 * visual indicators of the record's status.
 */
function postOccurrence(occ) {
  var status = occ['occurrence:record_status'];
  var id=occ['occurrence:id'];
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
      var text = indiciaData.statusTranslations[status];
      $('#details-tab td.status').html(text);
      if (indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] == 'details' ||
          indiciaData.detailsTabs[$('#record-details-tabs').tabs('option', 'selected')] == 'comments') {
        $('#record-details-tabs').tabs('load', $('#record-details-tabs').tabs('option', 'selected'));
      }
      if (indiciaData.autoDiscard) {
        var nextRow = $('#row' + id).next();
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

function buildVerifierEmail() {
  //Form to create email of record details
  var record = '';
  $.each(current_record.data, function (idx, obj) {
    if (obj.value !== null && obj.value !=='') {
      record += obj.caption + ': ' + obj.value + "\n";
    }
  });

  record += "\n\n[Photos]\n\n[Comments]";

  email.subject = indiciaData.email_subject_send_to_verifier
      .replace('%taxon%', current_record.additional.taxon)
      .replace('%id%', occurrence_id),
  email.body = indiciaData.email_body_send_to_verifier
      .replace('%taxon%', current_record.additional.taxon)
      .replace('%id%', occurrence_id)
      .replace('%record%', record);
  $('#record-details-tabs').tabs('load', 0);
  email.to = '';
  email.type = 'verifier';
  popupEmail();
}

function buildRecorderEmail(status, comment)
{
  if (status == 'V') {
    email.subject = indiciaData.email_subject_verified;
    email.body = indiciaData.email_body_verified;
  }
  else if (status == 'R') {
    email.subject = indiciaData.email_subject_rejected;
    email.body = indiciaData.email_body_rejected;
  }
  else if (status == 'D') {
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

    if (email.type == 'verifier') {
    // ensure images are loaded
    $.ajax({
      url: indiciaData.ajaxUrl + '/imagesAndComments' + urlSep + 'occurrence_id=' + occurrence_id,
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
    data;
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
  var sendEmail = false
  if (indiciaData.email_request_attribute !== '') {
    //Find attribute to see if recorder wants email confirmation
    if (getAttributeValue(indiciaData.email_request_attribute) == 1) {
      sendEmail = true;
      buildRecorderEmail(status, comment);
    }
  }
  if (!sendEmail) $.fancybox.close();

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
            seriesDefaults:{renderer:$.jqplot.BarRenderer, rendererOptions:[]}, legend:[], series:[],
            axes:{"xaxis":{"renderer":$.jqplot.CategoryAxisRenderer,"ticks":["1","2","3","4","5","6","7","8","9","10","11","12"]},"yaxis":{"min":0}}
          });
          $('#chart-div').css('opacity',1);
        }
      );
    }
    if (mapDiv !== null) {
      // Ensure the current record is centred and highlighted on the map.
      var parser = new OpenLayers.Format.WKT(),
        feature = parser.read(current_record.additional.wkt),
        c,
        lastFeature;
      if (mapDiv.map.projection.getCode() != 'EPSG:3857') {
        feature.geometry = feature.geometry.transform(new OpenLayers.Projection('EPSG:3857'), mapDiv.map.projection);
      }
      // Make the current record marker obvious
      var style = $.extend({}, mapDiv.map.editLayer.styleMap.styles['default']['defaultStyle'], {fillOpacity: 0.5, fillColor: '#0000FF'});
      feature.style=style;
      feature.tag='currentRecord';
      var toRemove=[];
      $.each(mapDiv.map.editLayer.features, function(idx, feature) {
        if (feature.tag==='currentRecord') {
          toRemove.push(feature);
        }
      });
      mapDiv.map.editLayer.removeFeatures(toRemove, {});
      mapDiv.map.editLayer.addFeatures([feature]);
      c = feature.geometry.getCentroid();
      mapDiv.map.setCenter(new OpenLayers.LonLat(c.x, c.y));
      // default is to show approx 100m of map
      var maxDimension=100;
      if (feature.geometry.CLASS_NAME!=='OpenLayers.Geometry.Point') {
        var bounds = feature.geometry.bounds;
        maxDimension = Math.max(bounds.right-bounds.left, bounds.top-bounds.bottom)*4;
      }
      mapDiv.map.zoomTo(mapDiv.map.getZoomForExtent(new OpenLayers.Bounds(0, 0, maxDimension, maxDimension)));
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
  
  $('table.report-grid tbody').click(function (evt) {
    // Find the appropriate separator for AJAX url params - depends on clean urls setting.
    urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    $('#record-details-toolbar *').attr('disabled', 'disabled');
    selectRow($(evt.target).parents('tr')[0]);
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

  $('#btn-email').click(function () {
    buildVerifierEmail();
  });
  
  $('#btn-verify-all').click(function () {
    var thOccId = document.getElementById('verification-grid-th-occurrence_id');
    var idIndex = $('#verification-grid thead th').index(thOccId);
    $.each($('#verification-grid tbody tr td:nth-child(1)'), function(idx, occurrenceIdCell) {
      data = {
        'website_id': indiciaData.website_id,
        'occurrence:id': occurrenceIdCell.textContent,
        'occurrence:record_status': 'V',
        'occurrence_comment:comment': 'Verified as it passes all automated checks.',
        'occurrence_comment:person_name': indiciaData.username
      };
      postOccurrence(data);
    });
  });

});

