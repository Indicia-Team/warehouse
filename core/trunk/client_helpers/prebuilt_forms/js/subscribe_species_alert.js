jQuery(document).ready(function($) {
  // add a custom required rule on the species input control, so that the rule can be dependent on the species list
  // selection control
  $('#taxa_taxon_list_id\\:taxon').rules('add', {
    required: function() {
      return $('#species_alert\\:taxon_list_id').val()==='';
    },
    messages: {
      required: "Select either a species or a list of species to trigger the alert for in the control below."
    }
  });
});