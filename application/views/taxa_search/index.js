$(document).ready(function() {
  function setTaxonListFilter() {
    const taxonListId = $('#filter-taxon_list_id').val();
    if (taxonListId) {
      $('#filter-taxa_taxon_list_id\\:taxon').setExtraParams({ taxon_list_id: taxonListId });
      $('#search-btn').removeAttr('disabled');
    } else {
      $('#search-btn').attr('disabled', true);
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