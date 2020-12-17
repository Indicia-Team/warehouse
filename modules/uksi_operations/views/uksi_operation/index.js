jQuery(document).ready(function($) {
  $('.run-filter')
    .after(' <button type="button" class="btn btn-primary" id="run-process">Process all operations</button>');
  $('#run-process').click(function() {
    if (confirm('If you are using Elasticsearch then before proceeding, please ensure that Logstash is ' +
      'stopped before proceeding. Click OK to confirm you have done this.')) {
      window.location = window.location.href + '/process';
    }
  });
});