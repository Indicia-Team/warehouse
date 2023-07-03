$(document).ready(function documentReady() {

  function doRelocations(type) {
    if (typeof type === 'undefined') {
      if ($('#entity').val()) {
        type = $('#entity').val();
        $('#entity').attr('disabled', true);
      }
      else {
        alert('Please select a type of media to process first.');
        return;
      }
    }
    $.ajax({
      type: "POST",
      url: indiciaData.siteUrl + 'image_organiser/process_relocate_batch',
      dataType: 'json',
      data: {
        type: type
      }
    }).done(
      function(data) {
        if (data.status === 'OK' || data.status === 'Done') {
          $('#output').append(data.moved + ' images were relocated. Now up to ID ' + data.id + ' in the ' + data.entity + ' data.\n');
        }
        if (data.status === 'OK') {
          $('#current-status').text('Processing');
          doRelocations(type);
        } else if (data.status === 'Done') {
          $('#current-status').text('Complete');
          $('#current-status').removeClass('alert-info');
          $('#current-status').addClass('alert-success');
        } else {
          if (data.reason) {
            $('#current-status').text(data.reason);
          }
          if (data.status === 'Paused') {
            setTimeout(function() {
              doRelocations(type);
            }, 30 * 1000);
          }
          else {
            $('#current-status').removeClass('alert-info');
            $('#current-status').addClass('alert-warning');
          }
        }
      }
    );
  }

  $('#move-batch').click(doRelocations);

  function doDeletes(type) {
    if (typeof type === 'undefined') {
      if ($('#entity').val()) {
        type = $('#entity').val();
        $('#entity').attr('disabled', true);
      }
      else {
        alert('Please select a type of media to process first.');
        return;
      }
    }
    $.ajax({
      type: "POST",
      url: indiciaData.siteUrl + 'image_organiser/process_delete_batch',
      dataType: 'json',
      data: {
        type: type
      }
    }).done(
      function(data) {
        $('#output').append(data.deleted + ' images were deleted. Now up to ID ' + data.id + ' in the ' + data.entity + ' data.\n');
        if (data.reason) {
          $('#output').append(data.reason + '\n');
        }
        if (data.status === 'OK') {
          $('#current-status').text('Deleting');
          // Do another batch.
          doDeletes(type);
        } else if (data.status === 'Done') {
          $('#current-status').text('Complete');
          $('#current-status').removeClass('alert-info');
          $('#current-status').addClass('alert-success');
        }
        else {
          if (data.reason) {
            $('#current-status').text(data.reason);
          } else {
            $('#current-status').text('Deletion aborted');
          }
          $('#current-status').removeClass('alert-info');
          $('#current-status').addClass('alert-warning');
        }
      }
    );
  }

  $('#delete-batch').click(doDeletes);

});