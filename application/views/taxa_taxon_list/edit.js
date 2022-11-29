jQuery(document).ready(function($) {
  // Replace default delete check message handling.
  $('.button-set input[type="submit"][value="Delete"]').attr('onclick', '');
  $('.button-set input[type="submit"][value="Delete"]').click(function(e) {
    if (typeof indiciaData.deleteConfirmed !== 'undefined') {
      return;
    }
    e.preventDefault();
    $('#delete-replacement-check-msg').fadeIn();
    $.getJSON(
      indiciaData.read.url + 'index.php/taxa_taxon_list/check_occurrences',
      {
        taxa_taxon_list_id: $('input[name="taxa_taxon_list\\:id"]').val()
      }
    ).done(function (data) {
      $('#delete-replacement-check-msg').hide();
      if (data.found) {
        $('#delete-replacement').fadeIn();
      } else {
        if (confirm('There are no existing occurrences for this taxon so it should be safe to delete. Are you sure you want to delete this taxon?')) {
          indiciaData.deleteConfirmed = true;
          $('input[type="submit"][value="Delete"]').click();
        }
      }
    }).fail(function(data) {
      alert('A problem occurred whilst checking if there are existing occurrences for the taxon you are deleting.');
    });
  });

  function setTaxonListFilter() {
    const taxonListId = $('#filter-taxon_list_id').val();
    if (taxonListId) {
      $('#new_taxa_taxon_list_id\\:taxon').setExtraParams({ taxon_list_id: taxonListId });
      $('#confirm-delete-btn').removeAttr('disabled');
    } else {
      $('#confirm-delete-btn').attr('disabled', true);
    }
  }
  $('#filter-taxon_list_id').change(function() {
    setTaxonListFilter();
    $('#filter-taxa_taxon_list_id\\:taxon').val('');
    $('#filter-taxa_taxon_list_id').val('');
  });
  setTimeout(function() {
    setTaxonListFilter();
  }, 100);

});