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
  var positionId = '';
  var verticalDefault = '?';
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

  var makeIdentifierCode = function(prefix) {
    // constructs the unique coded_value from the identifier characteristics
    // currently based on BTO coding scheme http://www.btoipmr.f9.co.uk/cm/cm_codes.htm
    var iCode = '';
    var parts = prefix.split('\\:');
    var idnTypeName = parts[2];
    var iBase = $('#'+prefix+'idnAttr\\:'+baseColourId+' option:selected').text();
    var iText = $('#'+prefix+'idnAttr\\:'+textColourId+' option:selected').text();
    var iPos = $('#'+prefix+'idnAttr\\:'+positionId+' option:selected').text();
    var iSeq = $('#'+prefix+'idnAttr\\:'+sequenceId+'').val();
    iSeq = $.trim(iSeq);
    if (iSeq==='') {
      // return false;
    }
    switch (idnTypeName) {
    case 'neck-collar':
      iCode = 'NC';
      break;
    case 'colour-left':
      iCode = 'L';
      iCode = iCode + verticalDefault;
      break;
    case 'colour-right':
      iCode = 'R';
      iCode = iCode + verticalDefault;
      break;
    case 'metal':
      if (iPos.toLowerCase().indexOf('left')!==-1) {
        iCode = 'L';
      } else if (iPos.toLowerCase().indexOf('right')!==-1) {
        iCode = 'R';
      } else {
        iCode = '?';
      }
      if (iPos.toLowerCase().indexOf('above')!==-1) {
        iCode = iCode + 'A';
      } else if (iPos.toLowerCase().indexOf('below')!==-1) {
        iCode = iCode + 'B';
      } else {
        iCode = iCode + verticalDefault;
      }
      break;
    default:
      iCode = '??';
    }
    if (idnTypeName==='metal') {
      iCode += getColourCode('metal')+'('+iSeq.toUpperCase()+')';
    } else {
      iCode += getColourCode(iBase)+getColourCode(iText)+'('+iSeq.toUpperCase()+')';
    }
    
    return iCode;
  };
  
  var setIdentifierVisualisation = function(ctl) {
    // set the colour identifier visualisation panel to reflect the attributes
    var parts = ctl.id.split(':');
    var colourBox$ = $('.'+parts[2]+'-indentifier-colourbox', $(ctl).closest('div.individual_panel'));
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
    case positionId :
      if ($('#'+esc4jq(ctl.id)+' option:selected').text().toLowerCase().indexOf('left')!==-1) {
        colourBox$.removeClass('right-leg').addClass('left-leg');
      } else if ($('#'+esc4jq(ctl.id)+' option:selected').text().toLowerCase().indexOf('right')!==-1) {
        colourBox$.removeClass('left-leg').addClass('right-leg');
      } else {
        colourBox$.removeClass('left-leg').removeClass('right-leg');
      }
      break;
    }
  };

  var setTaxonPicture = function(ctl) {
    // set the individual panel to reflect the taxon
    var panel$ = $(ctl).closest('.individual_panel');
    var classList = panel$.attr('class');
    var classPrefix = 'ind-tax-img-';
    var imgClassArray = classList.match(/ind-tax-img-\S+/g);
    // remove any existing image classes
    for(i=0; imgClassArray && i<imgClassArray.length; i++) {
      panel$.removeClass(imgClassArray[i]);
    }
    // if species control is a select
    $('option:selected', $(ctl)).each(function() {
      panel$.addClass(classPrefix+$(this).text().toLowerCase().replace(/[^a-z]/g, ''));
    });
    // if species control is an autocomplete input
    $(ctl).filter('input').each(function() {
      panel$.addClass(classPrefix+$(this).val().toLowerCase().replace(/[^a-z]/g, ''));
    });
  };

  var setTaxonHeader = function(ctl) {
    // set the individual panel header to reflect the taxon
    var heading$ = $(ctl).closest('.individual_panel').prev('h3').children('a');
    var taxonName = '';
    // if species control is a select
    $('option:selected', $(ctl)).each(function() {
      taxonName = $(this).text();
    });
    // if species control is an autocomplete input
    $(ctl).filter('input').each(function() {
      taxonName = $(this).val();
    });
    heading$.text(heading$.attr('data-heading')+' : '+taxonName);
  };

  var controlIsSet = function(ctl) {
    // return true if this is set
    // if control is a select
    if ($(ctl).filter('select').length>0) {
      if (ctl.selectedIndex!==0) return true;
    }
    // if control is an input
    if ($(ctl).filter('input').length>0) {
      if ($.trim(ctl.value).length>0) return true;
    }
    return false;
  };

  var autoSetCheckbox = function(ctl) {
    // set the identifier checkbox to set if any of its controls are set
    var control$ = $(ctl).closest('.idn\\:accordion\\:panel').children('select, input[type="text"]');
    var checkbox = $(ctl).closest('.idn\\:accordion\\:panel').children('.identifier_checkbox')[0];
    var setting = false;
    // set checkbox if any of this identifier's controls are set
    control$.each(function() {
      if (controlIsSet(this)) {
        setting = true;
      }
    });
    checkbox.checked = setting;
    // if checked, all identifier values are required
    if (setting) {
      control$.addClass('required');
    } else {
      control$.removeClass('required');
    }
  };

  var errorHTML = function(ctlId, msg) {
    var html = '<p class="inline-error" generated="true" htmlfor="'+ctlId+'">'+msg+'.</p>';
    return html;
  };
  
  var initIndividuals = function(scope) {
    // colour any colour selects
    $('select.select_colour option', scope).each(function() {
      var hexCode = getColourHex(getColourCode($(this).text()));
      if (hexCode!=='?') {
        $(this).css('background-color', '#'+hexCode).attr('data-colour', '#'+hexCode);
        var colour = (parseInt(hexCode.substr(0,2), 16)+parseInt(hexCode.substr(2,2), 16)+parseInt(hexCode.substr(4,2), 16))<384 ? 'ffffff' : '000000';
        $(this).css('color', '#'+colour);
      }
    });
    // set the initial state of the taxon
    $('.select_taxon', scope).each(function() {
      setTaxonPicture(this);
      setTaxonHeader(this);
    });
    // set the initial state of the identifier visuals
    $('select.select_colour, input.select_colour', scope).each(function() {
      autoSetCheckbox(this);
      setIdentifierVisualisation(this);
    });
    // install a change handler for the colour selecters to set the ring colours
    $('select.select_colour', scope).change(function(event) {
      autoSetCheckbox(this);
      setIdentifierVisualisation(this);
    });
    // install a keyup handler for the colour selecters to set the ring sequence
    $('input.select_colour', scope).keyup(function(event) {
      $(this).val($(this).val().toUpperCase());
      autoSetCheckbox(this);
      setIdentifierVisualisation(this);
    });
    // install a change handler for the taxon selecters to set the pictures and header
    $('.select_taxon', scope).change(function(event) {
      setTaxonPicture(this);
      setTaxonHeader(this);
    });
    // install an additional 'blur' handler for the autocomplete taxon selecters to set the pictures and header
    $('input.select_taxon', scope).blur(function(event) {
      setTaxonPicture(this);
      setTaxonHeader(this);
    });
    // activate accordions
    $('.idn-accordion, .idn-subject-accordion', scope).accordion();
  };
  
  /*
   * Public functions
   */

  /**
   * initialises settings and set event handlers, called from indicia ready
   * handler.
   */
  indicia.wwt.initForm = function(pSvcUrl, pReadNonce, pReadAuthToken, pBaseColourId, pTextColourId, pSequenceId, pPositionId, pVerticalDefault, pCollarRegex, pColourRegex, pMetalRegex, pValidate) {
    // set config from PHP.
    svcUrl = pSvcUrl;
    readNonce = pReadNonce;
    readAuthToken = pReadAuthToken;
    baseColourId = parseInt(pBaseColourId);
    textColourId = parseInt(pTextColourId);
    sequenceId = parseInt(pSequenceId);
    positionId = parseInt(pPositionId);
    verticalDefault = (pVerticalDefault==='') ? '?' : pVerticalDefault;
    collarRegex = pCollarRegex;
    colourRegex = pColourRegex;
    metalRegex = pMetalRegex;
    validate = pValidate=='true';
    // install the submit handler for the form
    $('form#entry_form').submit(function(event) {
      var codes = [];
      var idnCount = 0;
      // for each identifier in use
      $('.idn\\:accordion\\:panel').each(function() {
        var fldPrefix = this.id.replace('panel', '');
        var escFldPrefix = esc4jq(fldPrefix);
        var checkboxId = fldPrefix + 'identifier:checkbox';
        if ($('#'+esc4jq(checkboxId)+':checked').length > 0) {
          idnCount++;
          // set the unique identifier:coded_value
          var iCode = makeIdentifierCode(escFldPrefix);
          if (iCode) {
            $('#'+escFldPrefix+'identifier\\:coded_value').val(iCode);
            if ($('#'+escFldPrefix+'identifier\\:identifier_id').val()=='-1') {
              codes[codes.length] = iCode;
            }
          }
        }
      });
      if (codes.length > 0) {
        // for each identifier in use and without an id, check if it exists on the warehouse and set its id if so.
        var query = {"in" : ["coded_value", codes ]};
        var ajaxError = false;
        $.ajax({
          type: 'GET', 
          url: svcUrl+'/data/identifier?mode=json' +
              '&nonce='+readNonce+'&auth_token='+readAuthToken+
              '&orderby=id&callback=?&query='+escape(JSON.stringify(query)), 
          data: {}, 
          success: function(data) {
            if (typeof data.error!=="undefined") {
              if (typeof data.errors!=="undefined") {
                $.each(data.errors, function(idx, error) {
                  alert(error);
                });
                ajaxError = true;
              } else {
                if (data.error.indexOf('unauthorised')===-1) {
                  alert('An error occured when trying to save the data '+data.error);
                  ajaxError = true;
                } else {
                  // just ignore if unauthorised, let it submit and fail and refresh the tokens.
                }
              }
            } else if(typeof data.length!=='undefined' && data.length>0) { // we found one or more matches
              var id$ = $('input.identifier_id');
              var code$ = $('input.identifier\\:coded_value');
              for (i=0; i<data.length; i++) {
                for (j=0; j<code$.length; j++) {
                  if (code$[j].value==data[i].coded_value) {
                    // set the id
                    id$[j].value = data[i].id+'';
                  }
                }
              }
            }
          },
          dataType: 'json', 
          async: false 
        });
        if (ajaxError) {
          // stop the submit
          return false;
        }
      }
      return true;
    });
    // add jQuery validation options/methods
    if (validate==true) {
      jQuery.validator.addMethod("identifierRequired", function(value, element) {
        // select the checked checkboxes in this identifier set and return true if any are set
        var checkbox$ = $('.identifier_checkbox:checked', $(element).closest('.idn-accordion'));
        return checkbox$.length > 0;
      }, "Please record at least one identifier for this bird");
      jQuery.validator.addMethod('collarFormat', function (value, element) { 
        if (collarRegex==='') {
          return true;
        } else {
          var re = new RegExp(collarRegex);
          return this.optional(element) || re.test(value);
        }
      }, 'This is not a known neck collar format, please check the value and re-enter.');
      jQuery.validator.addMethod('colourRingFormat', function (value, element) { 
        if (colourRegex==='') {
          return true;
        } else {
          var re = new RegExp(colourRegex);
          return this.optional(element) || re.test(value);
        }
      }, 'This is not a known colour ring format, please check the value and re-enter.');
      jQuery.validator.addMethod('metalRingFormat', function (value, element) { 
        if (metalRegex==='') {
          return true;
        } else {
          var re = new RegExp(metalRegex);
          return this.optional(element) || re.test(value);
        }
      }, 'This is not a known metal ring format, please check the value and re-enter.');
    }
    // initialise individual and identifier controls
    initIndividuals('body');
    // install a click handler for the 'add another' button
    $('input#idn\\:add-another').click(function(event) {
      var indCount = $('.individual_panel').length;      
      var newInd = window.indicia.wwt.newIndividual.replace(/idn:0/g, 'idn:'+indCount)
        .replace(/Colour-marked individual 1/g, 'Colour-marked individual '+(indCount+1));
      $('#idn\\:subject\\:accordion').append(newInd);
      // initialise new individual and identifier controls
      initIndividuals('#idn\\:'+indCount+'\\:individual\\:panel');
      // initialise new javascript dependent controls
      eval(window.indicia.wwt.newJavascript.replace(/idn:0/g, 'idn:'+indCount).replace(/idn\\\\:0/g, 'idn\\\\:'+indCount));
      // reactivate subject accordion
      $('.idn-subject-accordion').accordion('destroy').accordion({'active':indCount});
    });
  };
})(jQuery);
