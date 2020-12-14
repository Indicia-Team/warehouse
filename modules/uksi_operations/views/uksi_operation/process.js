jQuery(document).ready(function($) {
  /**
  * Process a single operation
  */
  processOperation = function() {
    $.ajax({
      url: window.baseUrl + 'index.php/uksi_operation/process_next',
      dataType: 'json',
      success: function(response) {
        var message = response.message ? response.message : ' ';
        var nothingToDo = message === 'Nothing to do';
        $('#operation').text(message);
        if (!nothingToDo) {
          done++;
          $('#done').text(done);
          $('#progress-bar').progressbar ('option', 'value', done / window.totalToProcess * 100);
        }
        if (!nothingToDo && done < window.totalToProcess) {
          processOperation();
        } else {
          $('#operation').html('Processing complete.');
          window.location = window.baseUrl + 'index.php/uksi_operation/processing_complete?total=' + done;
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        // Find the best error info.
        var error = jqXHR.responseJSON && jqXHR.responseJSON.error ? jqXHR.responseJSON.error : errorThrown;
        $('#progress-text').html(error);
        $('#progress-text').removeClass('alert-info').addClass('alert-danger');
      }
    });
  };

  var done = 0;
  jQuery('#progress-bar').progressbar ({value: 0});
  alert('here');
  processOperation();
});