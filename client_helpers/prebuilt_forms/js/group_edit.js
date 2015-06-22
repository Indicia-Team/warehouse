jQuery(document).ready(function($) {
  $('#group\\:private_records').change(function() {
    if ($('#group\\:private_records').attr('checked')) {
      $('#release-warning').hide();
    } else {
      $('#release-warning').show();
    }
  });
  function checkViewSensitiveAllowed() {
    // Fully public groups can't allow sensitive data to be viewed. Also sensitive data viewing only available for groups
    // that expect all records to be posted via a group form.
    if ($('input[name=group\\:joining_method]:checked').val()==='P' ||
        $('input[name=group\\:implicit_record_inclusion]:checked').val()!=='f') {
      if ($('#group\\:view_full_precision').attr('checked')==='checked') {
        $('#group\\:view_full_precision').removeAttr('checked');
        alert('The show records at full precision setting has been unticked as it cannot be used with these settings.');
      }
      $('#group\\:view_full_precision').attr('disabled', true);
    } 
    else {
      $('#group\\:view_full_precision').removeAttr('disabled');
    }
  }
  $('input[name=group\\:joining_method],input[name=group\\:implicit_record_inclusion]').change(checkViewSensitiveAllowed);
  checkViewSensitiveAllowed();
});