/**
 * @todo What happens if species already selected as association is edited in the species entry grid?
 * @todo What happens when extra rows added to species list? Are existing drop downs updated?
 * @todo For existing data reloaded, does the way of using the scPresence name work?
 *
 */

var populate_existing_associations;

jQuery(document).ready(function($) {
  "use strict";

  // keys() polyfill from https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/keys
  if (!Object.keys) {
    Object.keys = (function() {
      'use strict';
      var hasOwnProperty = Object.prototype.hasOwnProperty,
        hasDontEnumBug = !({ toString: null }).propertyIsEnumerable('toString'),
        dontEnums = [
          'toString',
          'toLocaleString',
          'valueOf',
          'hasOwnProperty',
          'isPrototypeOf',
          'propertyIsEnumerable',
          'constructor'
        ],
        dontEnumsLength = dontEnums.length;

      return function(obj) {
        if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
          throw new TypeError('Object.keys called on non-object');
        }

        var result = [], prop, i;

        for (prop in obj) {
          if (hasOwnProperty.call(obj, prop)) {
            result.push(prop);
          }
        }

        if (hasDontEnumBug) {
          for (i = 0; i < dontEnumsLength; i++) {
            if (hasOwnProperty.call(obj, dontEnums[i])) {
              result.push(dontEnums[i]);
            }
          }
        }
        return result;
      };
    }());
  }

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

  /**
   * Hook for a new species checklist grid row. Causes all the species selection drop downs in the associations list to
   * repopulate.
   */
  function populate_drop_downs_after_added_species() {
    populate_drop_downs('#associations-list');
  }

  function populate_drop_downs(assocRowSelector) {
    var fromList = $('#' + indiciaData.associationCtrlOptions.from_grid_id + ' tbody tr').not('.scClonableRow'),
        toList = $('#' + indiciaData.associationCtrlOptions.to_grid_id + ' tbody tr').not('.scClonableRow'), ctrl;
    ctrl = $(assocRowSelector).find('select.species-assoc-from');
    populate_drop_down_controls(ctrl, fromList);
    ctrl = $(assocRowSelector).find('select.species-assoc-to');
    populate_drop_down_controls(ctrl, toList);
  }

  function populate_drop_down_controls(ctrl, fromList) {
    var options='', oldVal, blankOption;
    if (ctrl) {
      $.each(fromList, function() {
        options += '<option value="' + get_grid_and_row_index(this) + '">' + $(this).find('.scTaxonCell').text().trim() + '</option>';
      });
      $.each(ctrl, function() {
        oldVal = $(this).val();
        // a little bit of intelligence as to whether the please select option is required.
        blankOption = fromList.length > 1 && !oldVal ? '<option>&lt;Please select&gt;</option>' : '';
        $(this).html('').append(blankOption).append(options);
        $(this).val(oldVal);
      });
    }
  }

  /**
   * Get the species checklists to tell us when a species is added to the page. Then we can update the available species in the
   * associations drop downs.
   */
  hook_species_checklist_new_row.push(populate_drop_downs_after_added_species);

  function addAssociationRow(e, associationId) {
    if (!validate_complete()) {
      return;
    }
    var extraControls = [], options, optionsList, row, namePrefix,
      allTermlists = ['association_type', 'position', 'part', 'impact'],
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
        optionsList = indiciaData.associationCtrlOptions[this + '_terms'];
        // more than 1 choice, so show a drop down
        if (Object.keys(optionsList).length>1) {
          options = ['<option value="">&lt;please select&gt;</option>'];
          $.each(optionsList, function (id, term) {
            options.push('<option value="' + id + '">' + term + '</option>');
          });
          extraControls[this] = '<select class="' + this + '-control" name="' + namePrefix + this + '_id">'
              + options + '</select>';
        } else if (Object.keys(optionsList).length===1) {
          var termlist = this;
          $.each(optionsList, function (id, term) {
            extraControls[termlist] = '<span>' + term + '</span>' +
                '<input type="hidden" name="' + namePrefix + termlist + '_id" value="' + id +'">';
          });
        }
        if (this==='impact') {
          extraControls[this] = ' causing ' + extraControls[this];
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
    '<span class="ind-delete-icon"/></div>');
    row = $('#associations-list').find('div.association-row:last-child');
    populate_drop_downs(row);
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
      row = addAssociationRow(null, this.id);
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
    });
  };

});