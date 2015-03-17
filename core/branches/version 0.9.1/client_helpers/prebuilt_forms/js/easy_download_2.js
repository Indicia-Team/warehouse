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
 *
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link    http://code.google.com/p/indicia/
 */
 
var setAvailableDownloadFilters;
 
(function ($) {
  // Function called on selection of a download sharing type (and context filter) to 
  // set the available subfilters and surveys into the form controls.
  setAvailableDownloadFilters=function() {
    // first char of type drop down value is always the sharing type code (e.g. R for reporting)
    var sharingType=$('#download-type').val().substr(0,1);
    // remove the filters available from the select so we can repopulate with the appropriate ones
    $('#download-subfilter option').remove();
    $('#download-subfilter').append('<option value="">&lt;All available records&gt;</option>');
    $.each(indiciaData.optionalFilters[sharingType], function(filter, title) {
      $('#download-subfilter').append('<option value="'+filter.replace(/^filter_/, '')+'">'+title+'</option>');
    });
    // if just the Select a filter option available, can hide the control
    if ($('#download-subfilter option').length===1) {
      $('#ctrl-wrap-download-subfilter').hide();
      if ($('select#download-type').length===0) {
        // if no download type options at all, hide the whole fieldset
        $('#download-type-fieldset').hide();
      }
    } else {
      $('#ctrl-wrap-download-subfilter').show();
      $('#download-type-fieldset').show();
    }
    // load the survey options into the select
    if ($('select#survey_id').length) {
      var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
      $.getJSON(
        indiciaData.ajaxUrl + '/surveys_for_sharing_type/' + indiciaData.nid + urlSep + 'sharing_type=' + sharingType +
          '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token,
        null,
        function (data) {
          $('select#survey_id option').remove();
          // @todo i18n
          $('select#survey_id').append('<option value="">&lt;All&gt;</option>');
          $.each(data, function(id, title) {
            id = id.replace(/^survey-/, '');
            $('select#survey_id').append('<option value="'+id+'">'+title+'</option>');
          });
        
        }
      );
    }
  }

  jQuery(document).ready(function ($) {
    $('#download-type').change(setAvailableDownloadFilters);
  });
}(jQuery));