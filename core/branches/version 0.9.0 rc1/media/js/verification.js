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

/**
 * JavaScript for the data_entry_helper::verification_panel control.
 */

$(document).ready(function() {
  $('#verify-btn').click(function() {
    $('#verification-panel div.messages').css('opacity', 0.3);
    var info, occurrences=[], species, includes, grids, gridId;
    if ($('#sample\\:date').val()==='' || $('#imp-sref').val()===''  || $('#imp-sref-system').val()==='') {
      alert(indiciaData.verifyMessages.completeRecordFirst)
      return;
    }
    if ($('#occurrence\\:taxa_taxon_list_id').length>0) {
      occurrences.push('{"occurrence:taxa_taxon_list_id":'+$('#occurrence\\:taxa_taxon_list_id').val()+'}');
    } else {
      grids = $('table.species-grid');
      $.each(grids, function(idx, grid) {
        gridId = grid.id.replace('species-grid-', '');
        if (indiciaData['rowInclusionCheck-species-grid-'+gridId]==='hasData') {
          $.each($(grid).find('tbody tr'), function(y, row) {
            $.each($(row).find('.scOccAttrCell, .scCommentCell, .scConfidentialCell').find('input, select'), function(x, input) {
              if ($(input).val()!=='' && $(input).val()!=='0' && input.type!=="checkbox" || $(input).attr('checked')) {
                occurrences.push('{"occurrence:taxa_taxon_list_id":'+$(input).val()+'}');
                return false; // goes to the next row
              }
            });
          });
        } else {
          includes = $(grid).find('input.scPresence:checked');
          $.each(includes, function(idx, chkbox) {
            occurrences.push('{"occurrence:taxa_taxon_list_id":'+$(chkbox).val()+'}');
          });
        }
          
      });     
      
    }
    if (occurrences.length===0) {
      alert(indiciaData.verifyMessages.nothingToCheck)
      return;
    }
    info={
      'nonce':indiciaData.read.nonce,
      'auth_token':indiciaData.read.auth_token,
      'sample':'{"sample:survey_id":"'+$('#survey_id').val()+'",'+
        '"sample:date":"'+$('#sample\\:date').val()+'",'+
        '"sample:entered_sref":"'+$('#imp-sref').val()+'",'+
        '"sample:entered_sref_system":"'+$('#imp-sref-system').val()+'"}',
      'occurrences':'['+occurrences.join(',')+']'
    };
    $.post(indiciaData.read.url + "index.php/services/data_cleaner/verify", 
      info,
      function(data) {
        // For some reason the datatype json does not work for jquery post, so we handle this ourselves
        data=JSON.parse(data);
        species=null;
        // need to nicely parse the messages
        if (data.length>0) {
          $('#verification-panel div.messages').html('<p>'+indiciaData.verifyMessages.problems+'</p>');
          $.each(data, function(idx, msg) {
            if ($('#occurrence\\:taxa_taxon_list_id\\:searchterm').length>0) {
              species=$('#occurrence\\:taxa_taxon_list_id\\:searchterm').val();
            } else {
              grids = $('table.species-grid');
              $.each(grids, function(idx, grid) {
                if (indiciaData['rowInclusionCheck-species-grid-'+gridId]==='hasData') {
                  $.each($(grid).find('tbody tr'), function(y, row) {
                    $.each($(row).find('.scOccAttrCell, .scCommentCell, .scConfidentialCell').find('input, select'), function(x, input) {
                      if ($(input).val()!=='' && $(input).val()!=='0' && input.type!=="checkbox" || $(input).attr('checked')) {
                        if ($(input).val()===msg.taxa_taxon_list_id) {
                          species = $(input).parents('tr:first').find('td.scTaxonCell')[0].innerHTML;
                          return false; // breaks from the for each as we've found our species
                        }
                      }
                    });
                    if (species!==null) {
                      return false; // breaks from the for each as we've found our species
                    }
                  });
                } else {
                  includes = $('input.scPresence:checked');
                  $.each(includes, function(idx, include) {
                    if ($(include).val()===msg.taxa_taxon_list_id) {
                      species = $(include).parents('tr:first').find('td.scTaxonCell')[0].innerHTML;
                    }
                  });
                }
              });
            }
            $('#occurrence\\:taxa_taxon_list_id\\:searchterm').val()
            $('#verification-panel div.messages').append('<div class="message ui-state-highlight ui-widget-content ui-corner-all">'+
              '<div class="ui-state-default">'+species+'</div>'+msg.message+'</div>');
          });
          $('#verification-panel div.messages').append('<p>'+indiciaData.verifyMessages.problemsFooter+'</p>');
          $('#verification-panel div.messages').addClass('note-fail');
          $('#verification-panel div.messages').removeClass('note-success');
          $('#verification-panel div.messages').show();
        } else {
          $('#verification-panel div.messages').html(indiciaData.verifyMessages.noProblems);
          $('#verification-panel div.messages').addClass('note-success');
          $('#verification-panel div.messages').removeClass('note-fail');
          $('#verification-panel div.messages').show();
        }
        $('#verification-panel div.messages').css('opacity', 1);
      }
    );
  });
});