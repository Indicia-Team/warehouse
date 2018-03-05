$(document).ready(function docReady() {
  $('#progress').hide();
  $('#progress').progressbar();
  function doRequest(data) {
    $.ajax({
      url: 'rest_api_sync/process_batch',
      dataType: 'json',
      data: data,
      success: function successResponse(response) {
        var chunkPercentage;
        var progress;
        $('#output').append(response.log.join('<br/>'));
        if (response.state === 'done') {
          $('#output').append('<div class="alert alert-success">Synchronisation complete</div>');
          $('#progress').hide();
        } else {
          chunkPercentage = 100 / response.servers.length;
          progress = ((response.serverIdx - 1) + ((response.page - 1) / response.pageCount)) * chunkPercentage;
          $('#progress').progressbar('value', progress);
          doRequest({
            serverIdx: response.serverIdx,
            page: response.page
          });
        }
      },
      error: function errorResponse(jqXHR, textStatus, errorThrown) {
        alert('error');
      }
    });
  }

  $('#start-sync').click(function startSync() {
    $('#start-sync').attr('disabled', 'disabled');
    $('#progress').show();
    doRequest({});
  });
});
