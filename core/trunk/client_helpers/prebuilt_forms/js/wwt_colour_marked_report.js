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

  /*
   * Private variables
   */
  var colourTable = [
    ["R", "Red", "red"],
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

  var makeIdentifierCode = function(idx, typeId) {
    // constructs the unique code from the identifier characteristics
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

  /*
   * Public functions
   */

  /**
   * initialises lock settings and set event handlers, called from indicia ready
   * handler.
   */
  indicia.wwt.initForm = function(pSvcUrl, pReadNonce, pReadAuthToken) {
    // set config from PHP.
    svcUrl = pSvcUrl;
    readNonce = pReadNonce;
    readAuthToken = pReadAuthToken;
    // install the submit handler for the form
    $('form#entry_form').submit(function(event) {
      var codes = [];
      // for each identifier
      $('fieldset.taxon_identifier').each(function() {
        // set the unique identifier:code
        var parts = this.id.split(':');
        var idx = parts[1];
        var iType = parts[2];
        var iCode = makeIdentifierCode(idx, iType);
        if (iCode) {
          $('#idn\\:'+idx+'\\:'+iType+'\\:identifier\\:code').val(iCode);
          codes[codes.length] = iCode;
        }
      });
      // check if each identifier exists on warehouse and set its id if so.
      var query = {"in" : ["code", codes ]};
      if ($('input.identifier_id').val()=='-1') {
        $.ajax({
          type: 'GET', 
          url: svcUrl+'/data/identifier?mode=json' +
              '&nonce='+readNonce+'&auth_token='+readAuthToken+
              '&orderby=id&callback=?&query='+escape(JSON.stringify(query)), 
          data: {}, 
          success: function(detData) {
            if(detData.length>0) { // we found one or more matches
              var id$ = $('input.identifier_id');
              var code$ = $('input.identifier\\:code');
              for (i=0; i<detData.length; i++) {
                for (j=0; j<code$.length; j++) {
                  if (code$[j].value==detData[i].code) {
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
      // now continue with the submit
      return true;
    });
  };
})(jQuery);
