jQuery(document).ready(function($) {
  $('#taxon_list_id').change(function() {
    var options = $('input#workflow_event\\:key_value\\:taxon').indiciaAutocomplete('option');
    options.extraParams.taxon_list_id = $('#taxon_list_id').val();
    $('input#workflow_event\\:key_value\\:taxon').indiciaAutocomplete('option', options);
  });

  $('#workflow_event\\:entity').change(function entityChange() {
    var previousValue = $('#workflow_event\\:event_type').val();
    var entityKeys = Object.keys(indiciaData.entities);
    var i;
    var j;
    // First build event types list for select.
    if (!previousValue) {
      previousValue = $('#old_workflow_event_event_type').val();
    }
    $('#workflow_event\\:event_type option').remove();
    for (i = 0; i < entityKeys.length; i++) {
      if (entityKeys[i] === $('#workflow_event\\:entity').val()) {
        for (j = 0; j < indiciaData.entities[entityKeys[i]].event_types.length; j++) {
          $('#workflow_event\\:event_type').append(
            '<option value="' + indiciaData.entities[entityKeys[i]].event_types[j].code + '">' +
            indiciaData.entities[entityKeys[i]].event_types[j].title + '</option>'
          );
        }
      }
    }
    $('#workflow_event\\:event_type').val(previousValue);
    // now do Keys
    previousValue = $('#workflow_event\\:key').val();
    if (!previousValue) {
      previousValue = $('#old_workflow_event_key').val();
    }
    $('#workflow_event\\:key option').remove();
    for (i = 0; i < entityKeys.length; i++) {
      if (entityKeys[i] === $('#workflow_event\\:entity').val()) {
        for (j = 0; j < indiciaData.entities[entityKeys[i]].keys.length; j++) {
          $('#workflow_event\\:key').append(
            '<option value="' + indiciaData.entities[entityKeys[i]].keys[j].db_store_value + '">' +
            indiciaData.entities[entityKeys[i]].keys[j].title + '</option>'
          );
        }
        if (indiciaData.entities[entityKeys[i]].keys.length === 1) {
          $('#ctrl-wrap-workflow_event-key').hide();
        } else {
          $('#ctrl-wrap-workflow_event-key').show();
        }
      }
    }
    if ($('#workflow_event\\:key option[value="' + previousValue + '"]').length !== 0) {
      $('#workflow_event\\:key').val(previousValue);
    }
  });
  $('#workflow_event\\:entity').change();
});
