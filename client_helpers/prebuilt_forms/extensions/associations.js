/**
 * @todo What happens if species already selected as association is edited in the species entry grid?
 * @todo What happens when extra rows added to species list? Are existing drop downs updated?
 * @todo For existing data reloaded, does the way of using the scPresence name work?
 *
 */

var populate_existing_associations;

jQuery(document).ready(function($) {
  "use strict";

  function validate_complete() {
    console.log('Validate_complete to do');
    return true;
  }

  /**
   * Returns a value that can be used to uniquely identify each species row in each grid, combining the grid ID with the row index.
   * @param row The row element
   * @return string The grid ID followed by a hyphen then the row index.
   */
  function get_grid_and_row_index(row) {
    // We can trim some stuff off the beginning and end of the presence control name to get what we need.
    var presenceControlName = $(row).find('.scPresence').attr('name');
    return presenceControlName.replace(/^sc:/, '').replace(/:(\d+)?:present$/, '');
  }


  function populate_drop_downs(data, selector) {
    var fromList = $('#' + indiciaData.associationCtrlOptions.from_grid_id + ' tbody tr').not('.scClonableRow'),
        toList = $('#' + indiciaData.associationCtrlOptions.to_grid_id + ' tbody tr').not('.scClonableRow'), options='', ctrl,
      oldVal;
    if (typeof selector==="undefined") {
      selector = '#associations-list tbody';
    }

    ctrl = $(selector).find('select.species-assoc-from');
    if (ctrl) {
      $.each(fromList, function() {
        options += '<option value="' + get_grid_and_row_index(this) + '">' + $(this).find('.scTaxonCell').text().trim() + '</option>';
      });
      oldVal = ctrl.val();
      ctrl.html('').append(options);
      ctrl.val(oldVal);
    }
    options = '';
    ctrl = $(selector).find('select.species-assoc-to');
    if (ctrl) {
      $.each(toList, function() {
        options += '<option value="' + get_grid_and_row_index(this) + '">' + $(this).find('.scTaxonCell').text().trim() + '</option>';
      });
      oldVal = ctrl.val();
      ctrl.html('').append(options);
      ctrl.val(oldVal);
    }
  }

  hook_species_checklist_new_row.push(populate_drop_downs);

  function addAssociationRow(associationId) {
    if (!validate_complete()) {
      return;
    }
    var extraControls = [], options, row, namePrefix,
      allTermlists = ['association_type', 'position', 'part', 'impact', 'condition'],
      idx = $('#associations-list').find('div.association-row').length; // ensure zero indexed
    // format of the association field:
    // occurrence_association:<row index>:<occurrence_association_id for existing>:<fieldname>
    if (typeof associationId==="undefined") {
      // defaults to a new association row.
      associationId = '';
    }
    namePrefix = 'occurrence_association:' + idx + ':' + associationId + ':';
    // retrieve controls or labels for each termlist linked to by an association record
    $.each(allTermlists, function() {
      if (typeof indiciaData.associationCtrlOptions[this]!=="undefined") {
        extraControls[this] = '<span>' + indiciaData.associationCtrlOptions[this] + '</span>';
      } else if (typeof indiciaData.associationCtrlOptions[this + '_terms']!=="undefined") {
        options = [];
        $.each(indiciaData.associationCtrlOptions[this + '_terms'], function(id, term) {
          options.push('<option value="' + id + '">' + term + '</option>');
        });
        extraControls[this] = '<select class="' + this + '-control" name="' + namePrefix + this + '_id">'
        + options + '</select>';
        if (this==='impact') {
          extraControls[this] = ' causing ' + extraControls[this];
        }
        else if (this==='condition') {
          extraControls[this] = ' (condition ' + extraControls[this] + ')';
        }
      }
      else {
        extraControls[this] = '';
      }
    });
    $('#associations-list').append('<div class="association-row">' +
    '<select class="species-assoc-from" name="' + namePrefix + 'from_occurrence_id"></select>' +
    extraControls['association_type'] +
    '<select class="species-assoc-to" name="' + namePrefix + 'to_occurrence_id"></select>' +
    extraControls['position'] +
    extraControls['part'] +
    extraControls['impact'] +
    extraControls['condition'] +
    '<span class="ind-delete-icon"/></div>');
    row = $('#associations-list').find('div.association-row:last-child');
    populate_drop_downs(null, row);
    return row;
  }

  $('#associations-add').click(addAssociationRow);

  /**
   * Check for a click on the associations grid delete button
   */
  $('#associations-list').click(function(e) {
    if ($(e.target).hasClass('ind-delete-icon')) {
      $(e.target).parents('div.association-row').remove();
    }
  });

  populate_existing_associations = function(existingAssociations) {
    var row, fromFieldName, toFieldName;
    $.each(existingAssociations, function() {
      row = addAssociationRow(this.id);
      // search for the presence fields matching the occurrence we are relating from and to. This gives us the correct grid ID and
      // row index to use when looking up the association species drop down values.
      fromFieldName = $('table#' + indiciaData.associationCtrlOptions.from_grid_id + ' input.scPresence[name$=\\:'+this.from_occurrence_id+'\\:present]').attr('name');
      $(row).find('.species-assoc-from').val(fromFieldName.split(':')[1]);
      toFieldName = $('table#' + indiciaData.associationCtrlOptions.to_grid_id + ' input.scPresence[name$=\\:'+this.to_occurrence_id+'\\:present]').attr('name');
      $(row).find('.species-assoc-to').val(toFieldName.split(':')[1]);
      // Set correct values for the other lookups
      $(row).find('.association_type-control').val(this.association_type_id);
      $(row).find('.position-control').val(this.position_id);
      $(row).find('.part-control').val(this.part_id);
      $(row).find('.impact-control').val(this.impact_id);
      $(row).find('.condition-control').val(this.condition_id);
    });
  };

});