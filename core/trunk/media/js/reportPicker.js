function displayReportMetadata(path) {
  // safe for Windows paths
  path = path.replace('\\','/');
  path = path.split('/');
  var current = indiciaData.reportList;
  $.each(path, function(idx, item) {
    current = current[item];
    if (current.type==='report') {
      $('.report-metadata').html('<strong>'+current.title+'</strong><br/>'+
          '<p>' + current.description + '</p>');
    } else {
      current = current['content'];
    }
  });
}