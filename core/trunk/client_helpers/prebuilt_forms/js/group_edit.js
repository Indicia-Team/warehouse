jQuery(document).ready(function($) {
  $('#group\\:private_records').change(function() {
    if ($('#group\\:private_records').attr('checked')) {
      $('#release-warning').hide();
    } else {
      $('#release-warning').show();
    }
  });
});