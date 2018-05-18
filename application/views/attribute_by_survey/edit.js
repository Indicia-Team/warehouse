jQuery(document).ready(function docReady($) {
  // Changing a checkbox for a validation rule may need to show or hide the
  // related inputs.
  $('#validation-rules :checkbox').change(function(evt) {
    var selector = '#' + evt.currentTarget.id + '_inputs';
    if ($(selector).length > 0) {
      if ($(evt.currentTarget).is(':checked')) {
        $(selector).slideDown();
      } else {
        $(selector).slideUp();
      }
    }
  });

  // Perform initial setup of inputs linked to rule checkboxes.
  $.each($('#validation-rules :checkbox'), function() {
    var selector = '#' + this.id + '_inputs';
    if ($(selector).length > 0 && !$(this).is(':checked')) {
      $(selector).hide();
    }
  });

  // Force the sex stage restriction controls to not be required and set a
  // useful default.
  $('.scOccAttrCell :input').removeAttr('data-rule-required');
  $('.scOccAttrCell .input-group-addon').remove();
  $.each($('.scOccAttrCell select'), function processBlankVal() {
    var blankOption = $(this).find('option[value=""]');
    if (blankOption.length === 0) {
      $(this).prepend('<option value="" selected="selected">&lt;No sex or life stage restriction&gt;</option');
    } else {
      $(blankOption).text('<No sex or life stage restrictions>');
    }
    $(this).find('option').removeAttr('selected');
  });

  indiciaFns.loadExistingRestrictions = function(taxonRestrictions) {
    // If there are taxon restrictions to load for editing, set the stage data (since we are hacking the
    // species checklist control to our own purposes).
    $.each(taxonRestrictions, function() {
      var presenceInput = $('.scPresence[value=' + this.taxa_taxon_list_id + ']');
      var row = $(presenceInput).closest('tr');
      $(row).find('.scOccAttrCell option').removeAttr('selected');
      if (this.restrict_to_stage_termlists_term_id !== '') {
        $(row).find('.scOccAttrCell option[value=' + this.restrict_to_stage_termlists_term_id + ']').attr('selected', 'selected');
      }
    });
  }
});
