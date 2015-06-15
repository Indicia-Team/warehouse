var drawPoints, saveSample;

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
  
  /* Transect selection */ 
  
  function updateSelectedTransect() {
    // Make the add new buttons link to the correct transect sample
    $('#edit-effort,#edit-sighting').attr('href', $('#edit-effort').attr('href')
        .replace(/transect_sample_id=[0-9]+/, 'transect_sample_id=' + $('#transect-param').val()));
    if (typeof indiciaData.reports!=="undefined") {
      indiciaData.reports.dynamic.grid_report_grid_0[0].settings.extraParams.transect_sample_id=$('#transect-param').val();
      indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
    }
  }
  
  $('#transect-param').change(updateSelectedTransect);
  if ($('#transect-param').length>0) {
    updateSelectedTransect();
  }
  
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
    coords = $('#sample\\:entered_sref').val().split(/, ?/);
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
  
  drawPoints = function() {
    var geoms=[], style = {
      strokeWidth: 1,
      strokeColor: "#FF0000"
    };
    $.each(indiciaData.reportlayer.features, function() {
      geoms.push(this.geometry);
    });
    
    indiciaData.reportlayer.addFeatures([new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString(geoms), {}, style)]);
    indiciaData.mapdiv.map.events.on({"featureclick":function(e) {
      log("Map says: " + e.feature.id + " clicked on " + e.feature.layer.name);
    }});
  };
  
  saveSample = function(sampleId) {
    var lat=$('#input-lat-'+sampleId).val(),
        lng=$('#input-long-'+sampleId).val();
    if (!lat.match(/^\-?[\d]+(\.[\d]+)?$/) || !lng.match(/^\-?[\d]+(\.[\d]+)?$/)) {
      alert('The latitude and longitude cannot be saved because values are not of the correct format.');
      return;
    }
    var data = {
      'website_id': indiciaData.website_id,
      'sample:id': sampleId,
      'sample:entered_sref': lat + ', ' + lng,
      'sample:entered_sref_system':4326
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          $('#input-lat-'+sampleId+',#input-long-'+sampleId).css('border-color','silver');
        } else {
          alert(data.error);
        }
      },
      'json'
    );
  };
  
  // Change inputs on the transect points review screen will recolour to show they are edited.
  $('body').on('change', '.input-lat,.input-long', function(e) {
    if ($(e.currentTarget).val().match(/^\-?[\d]+(\.[\d]+)?$/)) {
      $(e.currentTarget).css('border', 'solid 1px red');
    }
    var sampleId = e.currentTarget.id.replace(/input\-(lat|long)\-/, ''),
        lat=$('#input-lat-'+sampleId).val(),
        lng=$('#input-long-'+sampleId).val(),
        point;
    // if we hav a valid lat long, move the associated point
    if (lat.match(/^\-?[\d]+(\.[\d]+)?$/) && lng.match(/^\-?[\d]+(\.[\d]+)?$/)) {
      point = new OpenLayers.Geometry.Point(lng, lat);
      if (indiciaData.mapdiv.map.projection.getCode() != 4326) {
        point.transform('EPSG:4326', indiciaData.mapdiv.map.projection);
      }
      $.each(indiciaData.reportlayer.features, function() {
        if (this.id===sampleId) {
          this.move(new OpenLayers.LonLat(point.x, point.y));
        }
      });
    }
  });
  
});