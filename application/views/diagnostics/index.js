$(document).ready(function() {

  $('#run-maintenance').click(function() {
    $('#run-maintenance').prop('disabled', true);
    $.ajax({
      dataType: 'json',
      url: indiciaData.warehouseUrl + 'index.php/diagnostics/maintenance',
    }).done(function(data) {
      $('#run-maintenance').prop('disabled', false);
      $('#maintenance-output').show();
      if (data.log.length > 0) {
        $.each(data.log, function() {
          $('#maintenance-output .panel-body').append(this + '<br/>');
        });
        $('#maintenance-output .panel-body').append('Maintenance done.<br/>');
      }
      else {
        $('#maintenance-output .panel-body').append('No maintenance required.<br/>');
      }
      indiciaData.reports.work_queue_report.grid_work_queue_report.reload();
    })
    .fail(function() {
      $('#run-maintenance').prop('disabled', false);
      alert('Maintenance operation failed');
    });
  });
});