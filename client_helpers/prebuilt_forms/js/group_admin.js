var approveMember;

(function ($) {
  "use strict";
  approveMember=function(id) {
    var data = {
      'website_id': indiciaData.website_id,
      'groups_user:id': id,
      'groups_user:pending': 'f'
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          alert('Member approved');
          indiciaData.reports.report_output.grid_report_output.reload();
        } else {
          alert(data.error);
        }
      },
      'json'
    );
  };

})(jQuery);