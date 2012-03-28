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
 * Helper methods for additional JavaScript functionality required by the WWT
 * Colour-marked Report form.
 */

(function($) {
  // this function wraps our code so
  // 1) $ is privately defined as jQuery and doesn't conflict with other code
  // 2) we can use var to make our helper functions private. Note these local
  // functions must be declared before they are used.

  // establish namespace so we don't have name clashes with other javascript
  // libraries
  if (!window.indicia) {
    window.indicia = {};
  }
  if (!window.indicia.wwt) {
    window.indicia.wwt = {};
  }

  // variables pumped in from PHP.
  var svcUrl = '';
  var readNonce = '';
  var readAuthToken = '';
  var baseColourId = '';
  var textColourId = '';
  var sequenceId = '';
  var hideOrDisable = 'disable';
  var validate = false;

  /*
   * Private variables
   */
  var colourTable = [
    ["R", "Red", "red",],
    ["P", "Pale Blue", "paleblue"],
    ["W", "White", "white"],
    ["O", "Orange", "orange"],
    ["G", "Dark Green", "darkgreen"],
    ["G", "Dark Green", "green"],
    ["C", "Dark Pink (Carmine)", "darkpinkcarmine"],
    ["C", "Dark Pink (Carmine)", "darkpink"],
    ["C", "Dark Pink (Carmine)", "carmine"],
    ["L", "Light Green (Lime)", "lightgreenlime"],
    ["L", "Light Green (Lime)", "lightgreen"],
    ["L", "Light Green (Lime)", "lime"],
    ["K", "Light Pink", "lightpink"],
    ["N", "Black (Niger)", "blackniger"],
    ["N", "Black (Niger)", "black"],
    ["N", "Black (Niger)", "niger"],
    ["B", "Blue (Dark)", "blue"],
    ["B", "Blue (Dark)", "bluedark"],
    ["B", "Blue (Dark)", "darkblue"],
    ["M", "Scheme Metal Ring", "schememetalring"],
    ["M", "Scheme Metal Ring", "schemering"],
    ["M", "Scheme Metal Ring", "metalring"],
    ["M", "Scheme Metal Ring", "scheme"],
    ["M", "Scheme Metal Ring", "metal"],
    ["V", "Violet/Mauve/Purple", "violetmauvepurple"],
    ["V", "Violet/Mauve/Purple", "violet"],
    ["V", "Violet/Mauve/Purple", "mauve"],
    ["V", "Violet/Mauve/Purple", "purple"],
    ["Y", "Yellow", "yellow"],
    ["S", "Silver/Grey", "silvergrey"],
    ["S", "Silver/Grey", "silver"],
    ["S", "Silver/Grey", "grey"],
    ["A", "Other metal ring", "othermetalring"],
    ["A", "Other metal ring", "othermetal"],
    ["A", "Other metal ring", "other"],
    ["U", "Brown (Umber)", "brownumber"],
    ["U", "Brown (Umber)", "brown"],
    ["U", "Brown (Umber)", "umber"]
  ];
  var colourTableLength = colourTable.length;
  
  var hexColours = [
    ["R", "ff0000"],
    ["P", "7f7fff"],
    ["W", "ffffff"],
    ["O", "ff7f00"],
    ["G", "007f00"],
    ["C", "bf3f3f"],
    ["L", "bfff7f"],
    ["K", "ffbfbf"],
    ["N", "000000"],
    ["B", "00007f"],
    ["V", "7f00ff"],
    ["Y", "ffff00"],
    ["S", "bfbfbf"],
    ["U", "3f3f00"]
  ];
  var hexColoursLength = hexColours.length;

  /*
   * Private helper functions
   */
  var esc4jq = function(selector) {
    // escapes the jquery metacharacters for jquery selectors
    return selector ? selector.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,
        '\\$1') : '';
  };

  var getColourCode = function(colour) {
    // finds the single character code for colour name or returns '?' if not found
    var cCode = '?';
    var compactColour = colour.toLowerCase().replace(/[^a-z]/g, '');
    
    for (i=0; i<colourTableLength; i++) {
      if (colourTable[i][2]===compactColour) {
        cCode = colourTable[i][0];
        break;
      }
    }
    return cCode;
  };

  var getColourHex = function(cCode) {
    // finds the hex code for colour character or returns 'ffffff' if not found
    var cHex = 'ffffff';
    
    for (i=0; i<hexColoursLength; i++) {
      if (hexColours[i][0]===cCode) {
        cHex = hexColours[i][1];
        break;
      }
    }
    return cHex;
  };

  var makeIdentifierCode = function(idx, typeId) {
    // constructs the unique coded_value from the identifier characteristics
    // currently based on BTO coding scheme http://www.btoipmr.f9.co.uk/cm/cm_codes.htm
    var iCode = '';
    var prefix = 'idn\\:'+idx+'\\:'+typeId+'\\:';
    var iTypeName = $('#'+prefix+'identifier\\:identifier_name').val();
    var iBase = $('#'+prefix+'idnAttr\\:1 option:selected').text();
    var iText = $('#'+prefix+'idnAttr\\:2 option:selected').text();
    var iSeq = $('#'+prefix+'idnAttr\\:3').val();
    iSeq = $.trim(iSeq);
    if (iSeq==='') {
      return false;
    }
    switch (iTypeName.toLowerCase().replace(/[^a-z]/g, '')) {
    case 'darvicring':
      iCode = 'LB'; // assume left below, not a characteristic of the identifier
      break;
    case 'neckcollar':
      iCode = 'NC';
      break;
    default:
      iCode = '??';
    }
    iCode += getColourCode(iBase)+getColourCode(iText)+'('+iSeq.toUpperCase()+')';
    return iCode;
  };
  
  var setIdentifierDisplayState = function(checkboxId) {
    // make identifier fieldset display state reflect the checkbox setting
    var fieldsetId = checkboxId.replace('identifier:checkbox', 'fieldset');
    switch (hideOrDisable) {
    case 'hide' :
      if ($('#'+esc4jq(checkboxId)+':checked').length===0) {
        $('#'+esc4jq(fieldsetId)).slideUp();
      } else {
        $('#'+esc4jq(fieldsetId)).slideDown();
      }
      break;
    case 'disable' :
      if ($('#'+esc4jq(checkboxId)+':checked').length===0) {
        $('#'+esc4jq(fieldsetId)+':hidden').show();
        $('input, select', '#'+esc4jq(fieldsetId)).each(function() {
          $(this).attr('disabled', true);
        });
        $('.indentifier-colourbox', '#'+esc4jq(fieldsetId)).fadeOut();
      } else {
        $('#'+esc4jq(fieldsetId)+':hidden').show();
        $('input, select', '#'+esc4jq(fieldsetId)).each(function() {
          $(this).attr('disabled', false);
        });
        $('.indentifier-colourbox', '#'+esc4jq(fieldsetId)).fadeIn();
      }
      break;
    }
  };

  var setIdentifierVisualisation = function(ctl) {
    // set the colour identifier visualisation panel to reflect the attributes
    var parts = ctl.id.split(':');
    var colourBox$ = $('.indentifier-colourbox', $(ctl).closest('fieldset'));
    switch (parseInt(parts[parts.length-1])) {
    case baseColourId :
      colourBox$.css('background-color', $('#'+esc4jq(ctl.id)+' option:selected').attr('data-colour'));
      break;
    case textColourId :
      colourBox$.css('color', $('#'+esc4jq(ctl.id)+' option:selected').attr('data-colour'));
      break;
    case sequenceId :
      colourBox$.text($(ctl).val());
      break;
    }
  };
  
  var errorHTML = function(ctlId, msg) {
    var html = '<p class="inline-error" generated="true" htmlfor="'+ctlId+'">'+msg+'.</p>';
    return html;
  };

  /*
   * Public functions
   */

  /**
   * initialises settings and set event handlers, called from indicia ready
   * handler.
   */
  indicia.wwt.initForm = function(pSvcUrl, pReadNonce, pReadAuthToken, pBaseColourId, pTextColourId, pSequenceId, pHideOrDisable, pValidate) {
      // set config from PHP.
      svcUrl = pSvcUrl;
    readNonce = pReadNonce;
    readAuthToken = pReadAuthToken;
    baseColourId = parseInt(pBaseColourId);
    textColourId = parseInt(pTextColourId);
    sequenceId = parseInt(pSequenceId);
    hideOrDisable = pHideOrDisable;
    validate = pValidate=='true';
    // install the submit handler for the form
    $('form#entry_form').submit(function(event) {
      var codes = [];
      var idnCount = 0;
      // for each identifier in use
      $('fieldset.taxon_identifier').each(function() {
        var checkboxId = this.id.replace('fieldset', 'identifier:checkbox');
        if ($('#'+esc4jq(checkboxId)+':checked').length > 0) {
          idnCount++;
          // set the unique identifier:coded_value
          var parts = this.id.split(':');
          var idx = parts[1];
          var iType = parts[2];
          var iCode = makeIdentifierCode(idx, iType);
          if (iCode) {
            $('#idn\\:'+idx+'\\:'+iType+'\\:identifier\\:coded_value').val(iCode);
            if ($('#idn\\:'+idx+'\\:'+iType+'\\:identifier\\:identifier_id').val()=='-1') {
              codes[codes.length] = iCode;
            }
          }
        }
      });
      if (codes.length > 0) {
        // check if each identifier in use and without an id, exists on warehouse and set its id if so.
        var query = {"in" : ["coded_value", codes ]};
        $.ajax({
          type: 'GET', 
          url: svcUrl+'/data/identifier?mode=json' +
              '&nonce='+readNonce+'&auth_token='+readAuthToken+
              '&orderby=id&callback=?&query='+escape(JSON.stringify(query)), 
          data: {}, 
          success: function(detData) {
            if(detData.length>0) { // we found one or more matches
              var id$ = $('input.identifier_id');
              var code$ = $('input.identifier\\:coded_value');
              for (i=0; i<detData.length; i++) {
                for (j=0; j<code$.length; j++) {
                  if (code$[j].value==detData[i].coded_value) {
                    // set the id
                    id$[j].value = detData[i].id+'';
                  }
                }
              }
            }
          },
          dataType: 'json', 
          async: false 
        });
      }
      // if jQuery validation not in use, we do some basic validation
      var valid = true;
      if (validate) {
        // the taxon must be set
        $('[id$="taxa_taxon_list_id"]').each(function() {
          if ($(this).val()=='') {
            valid = false;
            $(this).addClass('ui-state-error');
            $(this).after(errorHTML(this.id, 'This field is required'));
          }
        });
        // at least one identifier must be set
        $('[id$="taxa_taxon_list_id"]').each(function() {
          var parts = this.id.split(':');
          var iCount = $('input[id^="'+parts[0]+'\\:'+parts[1]+'\\:"]:checked').filter('.identifier_checkbox').length;
          if (iCount===0) {
            valid = false;
            var checkBox$ = $('input[id^="'+parts[0]+'\\:'+parts[1]+'\\:"]').filter('.identifier_checkbox');
            checkBox$.after(errorHTML(this.id, 'At least one identifier must be recorded'));
          }
        });
        // the sample date must be set
        var date$ = $('#sample\\:date');
        if (date$.val()=='') {
          valid = false;
          date$.addClass('ui-state-error');
          date$.after(errorHTML(this.id, 'This field is required'));
        }
        // the spatial ref must be set
        var place$ = $('#imp-sref');
        if (place$.val()=='') {
          valid = false;
          place$.addClass('ui-state-error');
          place$.after(errorHTML(this.id, 'This field is required'));
        }
      }
      // now continue with the submit if all valid
      return valid;
    });
    // set indentifier initial display state to reflect the checkbox setting
    $('.identifier_checkbox').each(function() {
      setIdentifierDisplayState(this.id);
    });
    // install a click handler for the identifier checkboxes to display the identifier fields
    $('.identifier_checkbox').click(function(event) {
      setIdentifierDisplayState(this.id);
    });
    // colour any colour selects
    $('select.select_colour option').each(function() {
      var hexCode = getColourHex(getColourCode($(this).text()));
      $(this).css('background-color', '#'+hexCode).attr('data-colour', '#'+hexCode);
      var color = (parseInt(hexCode.substr(0,2), 16)+parseInt(hexCode.substr(2,2), 16)+parseInt(hexCode.substr(4,2), 16))<384 ? 'ffffff' : '000000';
      $(this).css('color', '#'+color);
    });
    // set the initial state of the identifier visuals
    $('select.select_colour, input.select_colour').each(function() {
      setIdentifierVisualisation(this);
    });
    // install a change handler for the colour selecters to set the ring colours
    $('select.select_colour').change(function(event) {
      setIdentifierVisualisation(this);
    });
    // install a keyup handler for the colour selecters to set the ring colours
    $('input.select_colour').keyup(function(event) {
      setIdentifierVisualisation(this);
    });
  };
})(jQuery);
