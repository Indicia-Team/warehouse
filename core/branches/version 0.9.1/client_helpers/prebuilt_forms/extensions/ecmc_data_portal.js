jQuery(document).ready(function($) {
  
  /* Effort/sightings radio buttons */
  $('#points-params input:radio').change(function(e) {
    if (typeof indiciaData.reports!=="undefined") {
      e.preventDefault();
      indiciaData.reports.dynamic.grid_report_grid_0[0].settings.extraParams.effort_or_sightings=$(e.currentTarget).val();
      indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
      return false;
    }
  });
  $('#points-params input:radio').change();
  
  /* New transect button */
  
  $('#new-transect').click(function(e) {
    e.preventDefault();
    $('#popup-transect-id').val('');
    $.fancybox({"href":"#add-transect-popup"});
    return false;
  });
  
  $('#transect-popup-cancel').click(function() {
    $.fancybox.close();
  });
  
  $('#transect-popup-save').click(function() {
    if ($('#popup-transect-id').val()==='') {
      alert(indiciaData.langRequireTransectID);
      return;
    }
    else if ($('#popup-sample-type').val()==='') {
      alert(indiciaData.langRequireSampleType);
      return;
    }
    var sample = {"website_id": indiciaData.websiteId, "user_id": indiciaData.userId, 
        "survey_id": indiciaData.surveyId, "parent_id": indiciaData.surveySampleId, 
        "date": $('#popup-transect-date').val(), "sample_method_id":indiciaData.transectSampleMethodId,
        "entered_sref":indiciaData.sampleSref, "entered_sref_system": 4326
    };
    sample['smpAttr:'+indiciaData.transectIdAttrId] = $('#popup-transect-id').val(); 
    sample['smpAttr:'+indiciaData.sampleTypeAttrId] = $('#popup-sample-type').val(); 
    $.post(indiciaData.saveSampleUrl, 
      sample, 
      function (data) {
        if (typeof data.error === 'undefined') {
          alert('The transect has been saved. You can now add effort and sightings points for this transect.');
          $('#transect-param').append('<option value="'+data.outer_id+'">'+$('#popup-transect-id').val()+' - '+
               $('#popup-sample-type option:selected').text()+'</option>');
          $('#transect-param').val(data.outer_id);
          $.fancybox.close();
        } else {
          alert(data.error);
        }
      },
      'json'
    );
  });
  
  /* Add point buttons */
  $('.edit-point').click(function(e) {
    $(e.currentTarget).attr('href', $(e.currentTarget).attr('href').replace(/transect_sample_id=\d+/, 'transect_sample_id='+$('#transect-param').val()));
  });
  
  function postToServer(s) {
	  $.post(indiciaData.postUrl, 
		s,
		function (data) {
		  if (typeof data.error === 'undefined') {
			  indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
		  } else {
			  alert(data.error);
		  }
		},
		'json'
	  );
	}
	delete_route = function(id, route) {
    if (confirm(indiciaData.langConfirmDelete.replace('{1}', route))) {
      var s = {
        "website_id": indiciaData.websiteId,
        "location:id":id,
        "location:deleted":"t"
      };
      postToServer(s);
    }
	}
  
  /* Lat long control stuff */
  if ($('#lat_long-lat').length>0) {
    var updateSref = function() {
      $('#sample\\:entered_sref').val(
        $('#lat_long-lat').val() + ', ' + $('#lat_long-long').val()
      );
    },
    coords = $('#sample\\:entered_sref').val().split(', ');
    $('#lat_long-lat,#lat_long-long').blur(updateSref);
    // load existing value
    if (coords.length===2) {
      $('#lat_long-lat').val(coords[0]);
      $('#lat_long-long').val(coords[1]);
    }
  }
  
  /* Dynamic redirection stuff */
  if ($('#ecmc-redirect').length===1) {
    var setRedirect=function() {
      switch ($('#next-action').val()) {
        case 'effort': 
          $('#ecmc-redirect').val('survey/points/edit-effort?parent_sample_id='+$('#parent_sample_id').val()+'&transect_sample_id='+$('#sample\\:parent_id').val());
          break;
        case 'sighting': 
          $('#ecmc-redirect').val('survey/points/edit-sighting?parent_sample_id='+$('#parent_sample_id').val()+'&transect_sample_id='+$('#sample\\:parent_id').val());
          break;
        case 'surveypoints': 
          $('#ecmc-redirect').val('survey/points-list');
          break;
      }
    };
    $('#next-action').change(setRedirect);
    setRedirect();
  }
  
});