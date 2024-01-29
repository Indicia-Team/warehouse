$(document).ready(function() {

  $('#filters-limit-user').change(function() {
    if ($('#filters-limit-user').is(':checked')) {
      $('#rest_api_client_connection\\:filter_id\\:title').setExtraParams({
        'created_by_id': indiciaData.user_id
      });
    } else {
      $('#rest_api_client_connection\\:filter_id\\:title').unsetExtraParams([
        'created_by_id'
      ]);
    }
    $('#rest_api_client_connection\\:filter_id\\:title').refresh();
  });

});