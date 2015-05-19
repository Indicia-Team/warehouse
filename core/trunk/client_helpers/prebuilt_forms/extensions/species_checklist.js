var initSpeciesHints;

jQuery(document).ready(function($) {
  var speciesListJson;

  initSpeciesHints = function(filename) {
    $.getJSON(filename, function (json) {
      speciesListJson = json;
    });
  }

  hook_species_checklist_new_row.push(function(data, row) {
    if (typeof speciesListJson[data.external_key] !== "undefined") {
      $('#species-hints').prepend('<div class="species-hint">' +
          '<div class="species-hint-label">Additional info for records of ' + data.taxon+ '</div>' +
          '<div class="species-hint-content">' + speciesListJson[data.external_key]) + '</div></div>';
    }
  });
});