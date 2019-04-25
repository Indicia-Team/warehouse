$(document).ready(function docReady() {
  var startInfo;

  $('#progress').hide();
  $('#output').hide();
  $('#progress').progressbar();
  function doRequest(data) {
    $.ajax({
      url: 'rest_api_sync/process_batch',
      dataType: 'json',
      data: data,
      success: function successResponse(response) {
        var chunkPercentage;
        var progress;
        $('#output .panel-body').append(response.log.join('<br/>'));
        if (response.state === 'done') {
          $('#output .panel-body').append('<div class="alert alert-success">Synchronisation complete</div>');
          $('#progress').hide();
          $.ajax({
            url: 'rest_api_sync/end',
            dataType: 'json',
            data: startInfo
          });
        } else {
          chunkPercentage = 100 / startInfo.servers.length;
          progress = ((response.serverIdx - 1) + ((response.page - 1) / response.pageCount)) * chunkPercentage;
          $('#progress').progressbar('value', progress);
          doRequest({
            serverIdx: response.serverIdx,
            page: response.page
          });
        }
      },
      error: function errorResponse(jqXHR, textStatus, errorThrown) {
        alert('An error occurred syncing records. ' . errorThrown);
      }
    });
  }

  $('#start-sync').click(function startSync() {
    $('#start-sync').attr('disabled', 'disabled');
    $('#progress').show();
    $('#output').show();
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
