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
  var baseColourId = '';
  var textColourId = '';
  var sequenceId = '';
  var positionId = '';
  var verticalDefault = '?';
  var collarRegex = '';
  var colourRegex = '';
  var metalRegex = '';
  var validate = false;
  var subjectAccordion = false;
  var subjectCount = 0;

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
    return selector ? selector.replace(/([ #;&,.+*~\':"!\^$\[\]()=>|\/%])/g,
        '\\$1') : '';
  };

  var getColourCode = function(colour) {
    // finds the single character code for colour name or returns '?' if not found
    var cCode = '?';
    var compactColour = colour.toLowerCase().replace(/[^a-z]/g, '');
    var i;
    
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
    var i;
    
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
    var iSeq = $('#'+prefix+'idnAttr\\:'+sequenceId).val();
    iSeq = $.trim(iSeq);
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
    switch (parseInt(parts[parts.length-1], 10)) {
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
    var i;
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
    var heading$;
    if (subjectAccordion) {
      heading$ = $(ctl).closest('.individual_panel').prev('h3').children('a');
    } else {
      heading$ = $(ctl).closest('.individual_panel').prev('h3');
    }
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
    if ($(ctl).filter('select').length>0 && ctl.selectedIndex >= 0 && ctl.options[ctl.selectedIndex].text!=='<Please select>') {
      return true;
    }
    // if control is an input
    if ($(ctl).filter('input').length>0 && $.trim(ctl.value).length>0) {
      return true;
    }
    return false;
  };

  var autoSetCheckbox = function(ctl) {
    // set the identifier checkbox to set if any of its controls are set
    var panel$ = $(ctl).closest('.idn\\:accordion\\:panel');
    var fldPrefix = panel$.attr('id').replace('panel', '');
    var escFldPrefix = esc4jq(fldPrefix);
    var control$ = panel$.children('select, input[type="text"]');
    var checkbox = panel$.children('.identifier_checkbox')[0];
    var setting = false;
    // set checkbox if any of this identifier's controls are set
    control$.each(function() {
      if (controlIsSet(this)) {
        setting = true;
      }
    });
    checkbox.checked = setting;
    // if checked, all identifier values are required except sequence which requires a warning if not set, 
    // and conditions which is always optional (but not in the control$ wrapped set anyway)
    // set identifier coded_value
    if (setting) {
      control$.not('.idn-sequence').addClass('required');
      control$.filter('.idn-sequence').addClass('confirmIfBlank');
      var iCode = makeIdentifierCode(escFldPrefix);
      $('#'+escFldPrefix+'identifier\\:coded_value').val(iCode);
    } else {
      control$.removeClass('required confirmIfBlank');
      $('#'+escFldPrefix+'identifier\\:coded_value').val('');
    }
  };

  var setRemoveButtonDisplay = function() {
    // set the remove buttons to display none if only one bird or if a bird exists on database
    var button$ = $('.idn-remove-individual');
    if (button$.length<2) {
      button$.hide();
    } else {
      button$.each(function() {
        var subjectId$ = $('#'+esc4jq(this.id.replace('remove-individual', 'subject_observation:id')));
        if (subjectId$.length>0 && subjectId$.val()>0) {
          $(this).hide();
        } else {
          $(this).show();
        }
      });
    }
  };

  var removeIndividual = function(ctl) {
    // remove panel for individual when remove button clicked
    var panel$ = $(ctl).closest('.individual_panel');
    var header$ = panel$.prev('h3');
    header$.remove();
    // hide panel slowly then remove the html and reset the form
    panel$.slideUp('normal',function() {
      panel$.remove();
      var indCount = $('.individual_panel').length;   
      setRemoveButtonDisplay();
      // reactivate subject accordion, if used
      if (subjectAccordion) {
        $('.idn-subject-accordion').accordion('destroy').accordion({'active':indCount});
      }
    });
  };

  var setHandlers = function(scope) {
    // install a change handler for the colour selecters to set the ring colours
    $('select.select_colour', scope).bind('change', function(event) {
      autoSetCheckbox(this);
      setIdentifierVisualisation(this);
    });
    // install a keyup handler for the colour selecters to set the ring sequence
    $('input.identifier_sequence', scope).bind('keyup', function(event) {
      $(this).val($(this).val().toUpperCase());
      autoSetCheckbox(this);
      setIdentifierVisualisation(this);
    });
    // install a change handler for the colour selecters to set the ring sequence
    $('input.identifier_sequence', scope).bind('change', function(event) {
      $(this).val($(this).val().toUpperCase());
      autoSetCheckbox(this);
      setIdentifierVisualisation(this);
    });
    // install a change handler for the taxon hidden fields to trigger change on their inputs
    $("input[id$='occurrence:taxa_taxon_list_id']", scope).bind('change', function(event) {
      $('#'+esc4jq(this.id+':taxon')).change();
    });
    // install a change handler for the taxon selecters to set the pictures and header
    $('.select_taxon', scope).bind('change', function(event) {
      setTaxonPicture(this);
      setTaxonHeader(this);
    });
    // install an additional 'blur' handler for the autocomplete taxon selecters to set the pictures and header
    $('input.select_taxon', scope).bind('blur', function(event) {
      setTaxonPicture(this);
      setTaxonHeader(this);
    });
    // install a click handler for the remove individual button
    $('input.idn-remove-individual', scope).bind('click', function(event) {
      removeIndividual(this);
    });
  };
  
  var addValidationMethods = function() {
    // add jQuery validation options/methods
    $.validator.addMethod("identifierRequired", function(value, element) {
      // select the checked checkboxes in this identifier set and return true if any are set
      var checkbox$ = $('.identifier_checkbox:checked', $(element).closest('.idn-accordion'));
      return checkbox$.length > 0;
    }, "Please record at least one identifier for this bird");
    $.validator.addMethod("confirmIfBlank", function(value, element) {
      // if an identifier is being used, but the sequence is blank we must get confirmation to continue
      if ($.trim(value)==='' && $(element).data('confirmed')===undefined) {
        var identifier = $(element).closest('.idn\\:accordion\\:panel').prev('h3').text();
        var confirmation = confirm('You\'ve entered no sequence for the '+identifier+', please choose \'OK\' to continue, or \'Cancel\' to enter a sequence.');
        if (confirmation) {
          // only say this once
          $(element).data('confirmed', true);
        }
        return confirmation;
      }
      return true;
    }, "This field is blank, you will be prompted for confirmation");
    $.validator.addMethod("textAndBaseMustDiffer", function(value, element) {
      // no identifier can have the same base colour and text colour as it would be unreadable
      var colourSelected$ = $('select.select_colour option:selected', $(element).closest('.idn\\:accordion\\:panel'));
      if (colourSelected$.length===2) {
        return colourSelected$[0].value!==colourSelected$[1].value || colourSelected$[0].value==='';
      }
      return true;
    }, "Base colour is the same as text colour, please check and re-enter.");
    $.validator.addMethod("noDuplicateIdentifiers", function(value, element) {
      // no two identifiers on the form can be of same type with same attributes
      var result = true;
      var panel$ = $(element).closest('.idn\\:accordion\\:panel');
      var fldPrefix = panel$.attr('id').replace('panel', '');
      var escFldPrefix = esc4jq(fldPrefix);
      var iCode = makeIdentifierCode(escFldPrefix);
      var i;
      if (iCode && !(/\(\)/.test(iCode))) {
        // ignore leg ring position
        if (/^[LR][AB]/.test(iCode)) {
          iCode = 'LR'+iCode.substring(2);
        }
        var otherCode$ = $('.identifier_coded_value').not('#'+escFldPrefix+'identifier\\:coded_value');
        for (i=0; i<otherCode$.length; i++) {
          var oCode = otherCode$[i].value;
          if (oCode!=='') {
            // ignore leg ring position
            if (/^[LR][AB]/.test(oCode)) {
              oCode = 'LR'+oCode.substring(2);
            }
            if (iCode===oCode) {
              result = false;
            }
          }
        }
      }
      return result;
    }, "This identifier already exists on this form, please check and re-enter.");
    $.validator.addMethod('collarFormat', function (value, element) { 
      if (collarRegex==='') {
        return true;
      }
      var re = new RegExp(collarRegex);
      return this.optional(element) || re.test(value);
    }, 'This is not a known neck collar format, please check the value and re-enter.');
    $.validator.addMethod('colourRingFormat', function (value, element) { 
      if (colourRegex==='') {
        return true;
      }
      var re = new RegExp(colourRegex);
      return this.optional(element) || re.test(value);
    }, 'This is not a known colour ring format, please check the value and re-enter.');
    $.validator.addMethod('metalRingFormat', function (value, element) { 
      if (metalRegex==='') {
        return true;
      }
      var re = new RegExp(metalRegex);
      return this.optional(element) || re.test(value);
    }, 'This is not a known metal ring format, please check the value and re-enter.');
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
    $('select.select_colour, input.identifier_sequence', scope).each(function() {
      autoSetCheckbox(this);
      setIdentifierVisualisation(this);
    });
    // activate accordions
    if (subjectAccordion) {
      $('.idn-accordion, .idn-subject-accordion', scope).accordion();
    } else {
      $('.idn-accordion', scope).accordion();
    }
  };
  
  var addIndividual = function() {
    // use subjectCount for the incremental number of individuals (not reduced when subjects removed), indCount for the actual number.
    subjectCount++;
    var indCount = $('.individual_panel').length;      
    var newInd = window.indicia.wwt.newIndividual.replace(/idn:0:/g, 'idn:'+subjectCount+':')
      .replace(/Colour-marked Individual 1/g, 'Colour-marked Individual '+(subjectCount+1));
    var fromSelector = '#'+esc4jq($('.individual_panel').filter(':last').attr('id'));
    $('#idn\\:subject\\:accordion').append(newInd);
    var toSelector = '#'+esc4jq($('.individual_panel').filter(':last').attr('id'));
    // hide remove buttons if only one bird or for birds which exist on database
    setRemoveButtonDisplay();
    // initialise new javascript dependent controls
    eval(window.indicia.wwt.newJavascript.replace(/idn:0:/g, 'idn:'+subjectCount+':')
      .replace(/idn\\\\:0\\\\:/g, 'idn\\\\:'+subjectCount+'\\\\:'));
    // copy locks from preceding individual
    if (indicia.locks.copyLocks!=='undefined') {
      indicia.locks.copyLocks(fromSelector, toSelector);
    }
    // initialise new individual and identifier controls
    initIndividuals('#idn\\:'+subjectCount+'\\:individual\\:panel');
    setHandlers('#idn\\:'+subjectCount+'\\:individual\\:panel');
    // reactivate subject accordion, if used
    if (subjectAccordion) {
      $('.idn-subject-accordion').accordion('destroy').accordion({'active':indCount});
    }
  };
  
  /*
   * Public functions
   */

  /**
   * initialises settings and set event handlers, called from indicia ready
   * handler.
   */
  indicia.wwt.initForm = function(pBaseColourId, pTextColourId, 
      pSequenceId, pPositionId, pVerticalDefault, pCollarRegex, 
      pColourRegex, pMetalRegex, pValidate, pSubjectAccordion) {
    // set config from PHP.
    baseColourId = parseInt(pBaseColourId, 10);
    textColourId = parseInt(pTextColourId, 10);
    sequenceId = parseInt(pSequenceId, 10);
    positionId = parseInt(pPositionId, 10);
    verticalDefault = (pVerticalDefault==='') ? '?' : pVerticalDefault;
    collarRegex = pCollarRegex;
    colourRegex = pColourRegex;
    metalRegex = pMetalRegex;
    validate = pValidate=='true';
    subjectAccordion = pSubjectAccordion=='true';
    // set count of loaded individuals
    subjectCount = $('.individual_panel').length - 1;
    // add jQuery validation options/methods
    if (validate==true) {
      addValidationMethods();
    }
    // initialise individual and identifier controls
    initIndividuals('body');
    // set handlers
    setHandlers('body');
    // hide remove buttons if only one bird or for birds which exist on database
    setRemoveButtonDisplay();
    // install a click handler for the 'add another' button
    $('input#idn\\:add-another').click(function(event) {
      addIndividual();
    });
  };
}(jQuery));
