$(document).ready(function docReady() {
  var startInfo;
  var pageCount;
  var pagesDone = 0;

  $('#sync-progress').hide();
  function doRequest(data) {
    $.ajax({
      url: 'rest_api_sync/process_batch',
      dataType: 'json',
      data: data,
      success: function successResponse(response) {
        var chunkPercentage;
        var progress;
        // Get pageCount from pagesToGo on first iteration.
        pageCount = pageCount ?? response.pagesToGo ?? null;

        $('#output .panel-body').append('<div>' + response.log.join('<br/>') + '</div>');
        if (response.state === 'done') {
          $('#output .panel-body').append('<div class="alert alert-success">Synchronisation complete</div>');
          $('#progress, #progress-info').hide();
          $.ajax({
            url: 'rest_api_sync/end',
            dataType: 'json',
            data: startInfo
          });
        } else {
          pagesDone++;
          if (response.moreToDo) {
            if (pageCount) {
              chunkPercentage = 100 / startInfo.servers.length;
              progress = ((response.serverIdx - 1) + ((response.page - 1) / pageCount)) * chunkPercentage;
              $('#progress').val(progress);
            }
            else {
              // Alternative if true progress info missing.
              $('#progress-info').removeClass('hide').text('Batches done so far: ' + pagesDone);
            }
          }
          doRequest({
            serverIdx: response.serverIdx,
            page: response.page
          });
        }
      },
      error: function errorResponse(jqXHR, textStatus, errorThrown) {
        alert('An error occurred syncing records. ' + errorThrown);
      }
    });
  }

  $('#start-sync').click(function startSync() {
    $('#start-sync').attr('disabled', 'disabled');
    $('#sync-progress').show();
    $('#rest_api_sync_skipped_record.report-grid-container').hide();
    $.ajax({
      url: 'rest_api_sync/start',
      dataType: 'json',
      success: function successResponse(response) {
        startInfo = response;
        doRequest({});
      }
    });
  });
});
