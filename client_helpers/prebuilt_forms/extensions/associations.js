/**
 * @todo What happens if species already selected as association is edited in the species entry grid?
 * @todo What happens when extra rows added to species list? Are existing drop downs updated?
 * @todo For existing data reloaded, does the way of using the scPresence name work?
 *
 */

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
  function getGridIdAndRowIndex(row) {
    // We can trim some stuff off the beginning and end of the presence control name to get what we need.
    var presenceControlName = $(row).find('.scPresence').attr('name');
    return presenceControlName.replace(/^sc:/, '').replace(/:(\d+)?:present$/, '');
  }


  function populate_drop_downs(data, selector) {


    // @TODO not hard coded fungi.


    var fromList = $('#fungi tr.added-row'), toList = $('#associations tr.added-row'), options='', ctrl,
      oldVal, presenceControlName;
    if (typeof selector==="undefined") {
      selector = '#associations-list tbody';
    }

    ctrl = $(selector).find('select.species-assoc-from');
    if (ctrl) {
      $.each(fromList, function() {
        options += '<option value="' + getGridIdAndRowIndex(this) + '">' + $(this).find('.scTaxonCell').text().trim() + '</option>';
      });
      oldVal = ctrl.val();
      ctrl.html('').append(options);
      ctrl.val(oldVal);
    }
    options = '';
    ctrl = $(selector).find('select.species-assoc-to');
    if (ctrl) {
      $.each(toList, function() {
        options += '<option value="' + getGridIdAndRowIndex(this) + '">' + $(this).find('.scTaxonCell').text().trim() + '</option>';
      });
      oldVal = ctrl.val();
      ctrl.html('').append(options);
      ctrl.val(oldVal);
    }
  }

  hook_species_checklist_new_row.push(populate_drop_downs);

  $('#associations-add').click(function() {
    if (!validate_complete()) {
      return;
    }
    var extraControls = [], options,
      allTermlists = ['association_type', 'position', 'part', 'impact', 'condition'],
      idx = $('#associations-list div.association-row').length; // ensure zero indexed
    // format of the association field:
    // occurrence_association:<row index>:<occurrence_association_id for existing>:<fieldname>
    // retrieve controls or labels for each termlist linked to by an association record
    $.each(allTermlists, function() {
      if (typeof indiciaData.associationCtrlOptions[this]!=="undefined") {
        extraControls[this] = '<span>' + indiciaData.associationCtrlOptions[this] + '</span>';
      } else if (typeof indiciaData.associationCtrlOptions[this + '_terms']!=="undefined") {
        options = [];
        $.each(indiciaData.associationCtrlOptions[this + '_terms'], function(id, term) {
          options.push('<option value="' + id + '">' + term + '</option>');
        });
        extraControls[this] = '<select class="' + this + '-control" name="occurrence_association:' + idx + '::' + this + '_id">'
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
        '<select class="species-assoc-from" name="occurrence_association:' + idx + '::from_occurrence_id"></select>' +
        extraControls['association_type'] +
        '<select class="species-assoc-to" name="occurrence_association:' + idx + '::to_occurrence_id"></select>' +
        extraControls['position'] +
        extraControls['part'] +
        extraControls['impact'] +
        extraControls['condition'] +
        '<span class="ind-delete-icon"/></div>');
    populate_drop_downs(null, $('#associations-list div.association-row:last-child'));
  });

  /**
   * Check for a click on the associations grid delete button
   */
  $('#associations-list').click(function(e) {
    if ($(e.target).hasClass('ind-delete-icon')) {
      $(e.target).parents('div.association-row').remove();
    }
  });
});