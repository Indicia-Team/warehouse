var approveMember;
var removeMember;
var toggleRole;

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

  removeMember=function(id, name) {
    if (confirm('Do you really want to remove "' + name + '" from the group?')) { 
      var data = {
        'website_id': indiciaData.website_id,
        'groups_user:id': id,
        'groups_user:deleted': 't'
      };
      $.post(
        indiciaData.ajaxFormPostUrl,
        data,
        function (data) {
          if (typeof data.error === "undefined") {
            alert('Member removed');
            indiciaData.reports.report_output.grid_report_output.reload();
          } else {
            alert(data.error);
          }
        },
        'json'
      );
    }
  };
  
  toggleRole=function(id, name, makeRole) {
    var setAdministrator;
    if (makeRole==='administrator') {
      setAdministrator = true;
    } else {
      setAdministrator = false;
    }
    var data = {
      'website_id': indiciaData.website_id,
      'groups_user:id': id,
      'groups_user:administrator': setAdministrator
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          indiciaData.reports.report_output.grid_report_output.reload();
        } else {
          alert(data.error);
        }
      },
      'json'
    );    
  };
})(jQuery);