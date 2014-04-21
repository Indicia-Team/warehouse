jQuery(document).ready(function($) {
  $('#group\\:private_records').change(function() {
    if ($('#group\\:private_records').attr('checked')) {
      $('#release-warning').hide();
    } else {
      $('#release-warning').show();
    }
  });
  function checkViewSensitiveAllowed() {
    // fully public groups can't allow sensitive data to be viewed
    if ($('input[name=group\\:joining_method]:checked').val()==='P') {
      $('#group\\:view_full_precision').removeAttr('checked');
      $('#group\\:view_full_precision').attr('disabled', true);
    } 
    else {
      $('#group\\:view_full_precision').removeAttr('disabled');
    }
  }
  $('input[name=group\\:joining_method]').change(checkViewSensitiveAllowed);
  checkViewSensitiveAllowed();
});