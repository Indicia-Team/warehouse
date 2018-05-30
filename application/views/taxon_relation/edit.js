$(document).ready(function docReady() {
  $('#taxon_relation\\:taxon_relation_type_id').change(function () {
    var i;
    for (i = 0; i < indiciaData.subTypes.length; i++) {
      if (indiciaData.subTypes[i].id == $(this).val()) {
        jQuery('#term').html(indiciaData.subTypes[i].forward_term);
      }
    }
  });
  $('#swap-taxa').click(function swapTaxaClick() {
    var x = $('#taxon\\:from_taxon').val();
    $('#taxon\\:from_taxon').val($('#taxon\\:to_taxon').val());
    $('#taxon\\:to_taxon').val(x);
    x = $('#taxon\\:from_taxon\\:taxon').val();
    $('#taxon\\:from_taxon\\:taxon').val($('#taxon\\:to_taxon\\:taxon').val());
    $('#taxon\\:to_taxon\\:taxon').val(x);
  });
});
