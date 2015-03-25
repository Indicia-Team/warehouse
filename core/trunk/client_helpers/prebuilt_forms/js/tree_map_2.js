var iTM2Opts = {};
var iTM2Data = {
		mySiteWKT : [],
		mySites: [],
		myData: [],
		mySpecies: [],
		mySpeciesIDs: [],
		last_displayed: -1,
		global_timer_function: false,
		maxDayIndex: 365, // Dec 31 on a leap year
		minDayIndex: 0, // first January
		year1: '',
		species1: '',
		event1: '',
		year2: '',
		species2: '',
		event2: '',
		advancedButtons: true
};

// for loops: Use of these is meant to prevent bugs.
// i = records
// j = days in year
// k = events
// m = species
// n = map controls
// p = features

// functions
var rgbvalue, applyJitter, setToDate, loadYear;

(function ($) {
	var stopAnimation = function() {
		if(iTM2Data.global_timer_function)
			clearInterval(iTM2Data.global_timer_function);
		iTM2Data.global_timer_function = false;
	};
 
	var enableSpeciesControlOptions = function(){
		var year = $(iTM2Opts.yearControlSelector).val(); // this will never be blank.
		var oldSpecies = $(iTM2Opts.speciesControlSelector).val();
		var anySpecies = false;
		for(var m=0; m<iTM2Data.mySpeciesIDs.length; m++) {
			var validSpecies = false;
			for(var k=0; k<iTM2Opts.triggerEvents.length; k++)
				for(var j=0; j<365; j++)
					if(typeof iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]] != 'undefined')
						validSpecies = true;
			anySpecies |= validSpecies;
			// If this means deselecting current choice: set species control to blank.
			if(iTM2Data.mySpeciesIDs[m] == oldSpecies && !validSpecies)
				$(iTM2Opts.speciesControlSelector).val('');
			$(iTM2Opts.speciesControlSelector).find('option[value='+iTM2Data.mySpeciesIDs[m]+']').each(function(idx, elem){
				if(validSpecies) $(elem).removeAttr('disabled').show();
				else $(elem).attr('disabled','disabled').hide();
			});
		}
		$('.dateErrorWarning').remove();
		if(!anySpecies)
			$(iTM2Opts.yearControlSelector).after('<img class="dateErrorWarning" src="'+iTM2Opts.imgPath+'warning.png" title="There is no event data for this year">');
	};

	var enableEventControlOptions = function(){
		var year = $(iTM2Opts.yearControlSelector).val();
		var species = $(iTM2Opts.speciesControlSelector).val();
		var oldEvent = $(iTM2Opts.eventControlSelector).val();
		if(species=='') {
			$(iTM2Opts.eventControlSelector).val('');
			$(iTM2Opts.eventControlSelector).find('option[value!=]').attr('disabled','disabled').hide();
		} else { // else search through events for that year/species: enable/disable as appropriate.
			for(var k=0; k<iTM2Opts.triggerEvents.length; k++) {
				var validEvent = false;
				for(var j=0; j<365; j++)
					if(typeof iTM2Data.myData[year][k][j][species] != 'undefined')
						validEvent = true;
				// If this means deselecting current event choice: set event control to blank.
				if(k == oldEvent && !validEvent)
					$(iTM2Opts.eventControlSelector).val('');
				$(iTM2Opts.eventControlSelector).find('option[value='+k+']').each(function(idx, elem){
					if(validEvent) $(elem).removeAttr('disabled').show();
					else $(elem).attr('disabled','disabled').hide();
				});
			}
			if($(iTM2Opts.eventControlSelector).val() == '' &&
					$(iTM2Opts.eventControlSelector).find('option[value!=]').not(':disabled').length == 1)
				$(iTM2Opts.eventControlSelector).val($(iTM2Opts.eventControlSelector).find('option[value!=]').not(':disabled').val());
		}
	};
  
	var buildRhsControlOptions = function(){
		if(!iTM2Opts.twinMaps) return;
		var year = $(iTM2Opts.yearControlSelector).val();
		var species = $(iTM2Opts.speciesControlSelector).val();
		var event = $(iTM2Opts.eventControlSelector).val();
		var rhs = $(iTM2Opts.rhsControlSelector).val();
		var rhsParts = rhs.split(':');
		$(iTM2Opts.rhsControlSelector).find('option[value!=]').remove();
		if(year != '' && species != '' && event != ''){
			// Add other years/this species/this event.
			$(iTM2Opts.yearControlSelector).find('option').each(function(idx,elem){
				if($(elem).val()!=year)
					$(iTM2Opts.rhsControlSelector).append('<option value="'+$(elem).val()+':'+species+':'+event+'">'+$(elem).val()+' '+iTM2Data.mySpecies[species].taxon+' '+iTM2Opts.triggerEvents[event].name + '</option>');
			})
			// Add this year/species/valid events (exclude LHS)
			for(var m=0; m<iTM2Data.mySpeciesIDs.length; m++) {
				for(var k=0; k<iTM2Opts.triggerEvents.length; k++) {
					var validEvent = false;
					for(var j=0; j<365; j++)
						if(typeof iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]] != 'undefined')
							validEvent = true;
					if(validEvent && (k!=event || iTM2Data.mySpeciesIDs[m]!=species))
						$(iTM2Opts.rhsControlSelector).append('<option value="'+year+':'+iTM2Data.mySpeciesIDs[m]+':'+k+'">'+year+' '+iTM2Data.mySpecies[iTM2Data.mySpeciesIDs[m]].taxon+' '+iTM2Opts.triggerEvents[k].name + '</option>');
				}
			}
		}
		if(rhsParts.length != 3) return;
		if(rhsParts[1] == species && rhsParts[2] == event) {
			if(rhsParts[0] == year) $(iTM2Opts.rhsControlSelector).val('');
			else $(iTM2Opts.rhsControlSelector).val(rhs);
		} else if(rhsParts[1] == species && rhsParts[0] == year) {
			if(rhsParts[2] == event) $(iTM2Opts.rhsControlSelector).val('');
			else $(iTM2Opts.rhsControlSelector).val(rhs);
		} else if(rhsParts[1] == species) {
			$(iTM2Opts.rhsControlSelector).val('');
		} else if(rhsParts[0] == year && rhsParts[2] == event) {
			$(iTM2Opts.rhsControlSelector).val(rhs);
		} else $(iTM2Opts.rhsControlSelector).val('');
		$(iTM2Opts.rhsControlSelector).change();
	};

	var calculateMinAndMax = function(){
		var year = $(iTM2Opts.yearControlSelector).val();
		var species = $(iTM2Opts.speciesControlSelector).val();
		var event = $(iTM2Opts.eventControlSelector).val();
		iTM2Data.year1 = year;
		iTM2Data.species1 = species;
		iTM2Data.event1 = event;
		var rhs, rhsParts;
		if(iTM2Opts.twinMaps) {
			rhs = $(iTM2Opts.rhsControlSelector).val();
			rhsParts = rhs.split(':');
			iTM2Data.year2 = (rhsParts.length == 3 ? rhsParts[0] : '');
			iTM2Data.species2 = (rhsParts.length == 3 ? rhsParts[1] : '');
			iTM2Data.event2 = (rhsParts.length == 3 ? rhsParts[2] : '');
		}
		
		iTM2Data.minDayIndex = 365;
		iTM2Data.maxDayIndex = 0;
		if(species == '') {
			iTM2Data.minDayIndex = 0;
			iTM2Data.maxDayIndex = (Date.UTC(year, 12, 31) - Date.UTC(year, 1, 1))/ (24 * 60 * 60 * 1000);
		} else if(event == '') {
			for(var k=0; k<iTM2Opts.triggerEvents.length; k++)
				for(var j=0; j<365; j++)
					if(typeof iTM2Data.myData[year][k][j][species] != 'undefined') {
						if(j < iTM2Data.minDayIndex) iTM2Data.minDayIndex = j;
						if(j > iTM2Data.maxDayIndex) iTM2Data.maxDayIndex = j;
					}
		} else {
			for(var j=0; j<365; j++)
				if(typeof iTM2Data.myData[year][event][j][species] != 'undefined' ||
						(iTM2Opts.twinMaps && rhs != '' && typeof iTM2Data.myData[rhsParts[0]][rhsParts[2]][j][rhsParts[1]] != 'undefined')) {
					if(j < iTM2Data.minDayIndex) iTM2Data.minDayIndex = j;
					if(j > iTM2Data.maxDayIndex) iTM2Data.maxDayIndex = j;
				}
		}
		if(iTM2Data.minDayIndex>0) iTM2Data.minDayIndex--; // allow for day before data actually starts
		if(iTM2Opts.advanced_UI) {
			var slider =  $(iTM2Opts.timeControlSelector);
			$( iTM2Opts.timeControlSelector ).slider( 'option', 'min', iTM2Data.minDayIndex );
			$( iTM2Opts.timeControlSelector ).slider( 'option', 'max', iTM2Data.maxDayIndex );
			var diff = iTM2Data.maxDayIndex-iTM2Data.minDayIndex;
			var spacing =  100 / diff;
			slider.find('.ui-slider-tick-mark').remove();
			slider.find('.ui-slider-label').remove();
			var maxLabels = 11; // TODO ".(isset($args['numberOfDates']) && $args['numberOfDates'] > 1 ? $args['numberOfDates'] : 11).";
			var maxTicks = 100;
			var daySpacing = diff == 0 ? 1 : Math.ceil(diff/maxTicks);
			var provisionalLabelSpacing = Math.max(7, Math.ceil(diff/maxLabels));
			var actualLabelSpacing = daySpacing*Math.ceil(provisionalLabelSpacing/daySpacing);
			for (var j = iTM2Data.minDayIndex; j <= iTM2Data.maxDayIndex ; j+=daySpacing) {
				var myDate=new Date();
				myDate.setFullYear(year,0,1);
				if(j>0) myDate.setDate(myDate.getDate()+j);
				if(j>iTM2Data.minDayIndex && j<iTM2Data.maxDayIndex)
					$('<span class=\"ui-slider-tick-mark'+(!((j-iTM2Data.minDayIndex) % actualLabelSpacing) ? ' long' : '')+'\"></span>').css('left', Math.round(spacing * (j-iTM2Data.minDayIndex) * 10)/10 +  '%').appendTo(slider);
				if(!((j-iTM2Data.minDayIndex) % actualLabelSpacing) && spacing*(j-iTM2Data.minDayIndex) < 95)
					$('<span class=\"ui-slider-label\"><span>'+myDate.getDate()+' '+iTM2Opts.monthNames[myDate.getMonth()]+'</span></span>').css('left', Math.round(spacing * (j-iTM2Data.minDayIndex) * 10)/10 +  '%').appendTo(slider);
			}
		} else {
			var select =  $( iTM2Opts.timeControlSelector );
			select.find('option').remove();
			for (var j = iTM2Data.minDayIndex; j <= iTM2Data.maxDayIndex ; j++) {
				var myDate=new Date();
				myDate.setFullYear(year,0,1);
				if(j) myDate.setDate(myDate.getDate()+j);
				$('<option value="'+j+'">'+myDate.getDate()+' '+iTM2Opts.monthNames[myDate.getMonth()]+'</option>').appendTo(select); 
			}
		}
		// if playing, leave playing, otherwise jump to end.
		if(!iTM2Data.global_timer_function) {
			setToDate(-1);
			setToDate(iTM2Data.maxDayIndex);
		} else setLastDisplayed(getLastDisplayed());
	};

	var getLastDisplayed = function(){
		return iTM2Data.last_displayed;
	};

	var setLastDisplayed = function(idx){
		iTM2Data.last_displayed = idx;
		if(iTM2Opts.advanced_UI)
			$( iTM2Opts.timeControlSelector ).slider( 'option', 'value', idx );
		else
			$( iTM2Opts.timeControlSelector ).val( idx );
	};
	
	var resetMap = function(){
		var last = getLastDisplayed();
		setToDate(-1);
		setToDate(last);
	}

  // init must be called before the maps are initialised, as it sets up a 
  initTreeMap2 = function(options) {
	var defaults = {
			advanced_UI: false,
			twinMaps: false,
		    firstDateRGB: {r:0, g:0, b:255}, // colour of first date displayed.
    		lastDateRGB:  {r:255, g:0, b:0}, // colour of last date displayed.
			dotSize: 3,
			jitterRadius: 15000,
			timerDelay: 250, // milliseconds
			yearControlSelector: '#yearControl',
			speciesControlSelector: '#speciesControl',
			eventControlSelector: '#eventControl',
			rhsControlSelector: '#rhsCtrl',
			primaryMapSelector: '#map',
			secondaryMapSelector: '#map2',
			mapContainerClass: 'mapContainers',
			leftMapOnlyClass: 'leftMapOnly',
			bothMapClass: 'bothMaps',
			indicia_user_id: false,
			firstButtonSelector: '#beginning',
			lastButtonSelector: '#end',
			playButtonSelector: '#playMap',
			playButtonPlayLabel: 'play',
			playButtonPlayIcon:  'ui-icon-play',
			playButtonPauseLabel: 'pause',
			playButtonPauseIcon:  'ui-icon-pause',
			timeControlSelector: '#timeSlider',
			dotControlSelector: '#dotControl',
			dotControlMin: 2,
			dotControlMax: 5,
			dotSize: 3,
			errorDiv: '#errorMsg',
			pleaseSelectPrompt: 'Please select a Year / Species / Event combination before playing',
			waitDialogText: 'Please wait whilst the data for {year} is loaded.',
    		waitDialogTitle: 'Loading Data...',
    		// waitDialogOK: 'OK',
  			noMappableDataError: 'The report does not output any mappable data.',
  			noDateError: 'The report does not output a date.',
			sitesLayerLabel: 'My Sites', // don't need events layer label as not in switcher.
			monthNames: [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ],
			imgPath: '/'
		};
	iTM2Opts = $.extend({}, defaults, options);
	iTM2Data.dotSize = iTM2Opts.dotSize;

	// Field change events:
	
	$(iTM2Opts.yearControlSelector).change(function(evt){
		var year = $(evt.target).val();
		stopAnimation();
		loadYear(year, 'lh');
	});

	$(iTM2Opts.speciesControlSelector).change(function(evt){
		stopAnimation();
		enableEventControlOptions();
		buildRhsControlOptions();
		calculateMinAndMax();
		resetMap();
	});

	$(iTM2Opts.eventControlSelector).change(function(evt){
		stopAnimation();
		buildRhsControlOptions();
		calculateMinAndMax();
		resetMap();
	});

	$(iTM2Opts.rhsControlSelector).change(function(evt){
		// we are assumming that map and map2 are identical
		// This could be animated.
		var centre = new OpenLayers.LonLat(iTM2Opts.long, iTM2Opts.lat);
		if ($(iTM2Opts.primaryMapSelector)[0].map.displayProjection.getCode()!=$(iTM2Opts.primaryMapSelector)[0].map.projection.getCode())
			centre.transform($(iTM2Opts.primaryMapSelector)[0].map.displayProjection, $(iTM2Opts.primaryMapSelector)[0].map.projection);
		if($(evt.target).val()!=''){
			$('.'+iTM2Opts.mapContainerClass).removeClass(iTM2Opts.leftMapOnlyClass).addClass(iTM2Opts.bothMapClass);
			// need to resize the maps.
			$(iTM2Opts.secondaryMapSelector)[0].map.setCenter(centre, iTM2Opts.zoom);
			$(iTM2Opts.secondaryMapSelector)[0].map.updateSize();
		} else {
			$('.'+iTM2Opts.mapContainerClass).removeClass(iTM2Opts.bothMapClass).addClass(iTM2Opts.leftMapOnlyClass);
		}
		$(iTM2Opts.primaryMapSelector)[0].map.setCenter(centre, iTM2Opts.zoom);
		$(iTM2Opts.primaryMapSelector)[0].map.updateSize();
		if($(evt.target).val() != '') {
			var rhs = $(iTM2Opts.rhsControlSelector).val();
			var rhsParts = rhs.split(':');
			loadYear(rhsParts[0], 'rh');
		} else {
			calculateMinAndMax();
			resetMap();
		}
	});
	
	$( iTM2Opts.playButtonSelector ).click(function() {
		if(iTM2Data.year1 == '' || iTM2Data.species1 == '' || iTM2Data.event1 == ''){
			alert(iTM2Opts.pleaseSelectPrompt);
			return;
		}
		    
		var caller = function() {
			var value = getLastDisplayed();
			if(value < iTM2Data.maxDayIndex) {
				setToDate(value+1);
			} else {
				stopAnimation();
				if(iTM2Opts.advanced_UI && iTM2Data.advancedButtons) $( iTM2Opts.playButtonSelector ).button( 'option', { label: iTM2Opts.playButtonPlayLabel, icons: { primary: iTM2Opts.playButtonPlayIcon }} );
				else $( iTM2Opts.playButtonSelector ).text(iTM2Opts.playButtonPlayLabel);
			}
		};
		    
		var options;
		if ( !iTM2Data.global_timer_function ) {
			var value = getLastDisplayed();
			if(value >= iTM2Data.maxDayIndex) setToDate(iTM2Data.minDayIndex);
			options = { label: iTM2Opts.playButtonPauseLabel, icons: { primary: iTM2Opts.playButtonPauseIcon }};
			iTM2Data.global_timer_function = setInterval(caller, iTM2Opts.timerDelay);
		} else {
			stopAnimation();
			options = { label: iTM2Opts.playButtonPlayLabel, icons: { primary: iTM2Opts.playButtonPlayIcon }};
		}
		if(iTM2Opts.advanced_UI && iTM2Data.advancedButtons) $( this ).button( 'option', options );
		else $( this ).text(options.label);
	});

	$( iTM2Opts.firstButtonSelector ).click(function() {
		stopAnimation();
		if(iTM2Opts.advanced_UI && iTM2Data.advancedButtons)
			$( iTM2Opts.playButtonSelector ).button( 'option', { label: iTM2Opts.playButtonPlayLabel, icons: { primary: iTM2Opts.playButtonPlayIcon }} );
		else
			$( iTM2Opts.playButtonSelector ).text(iTM2Opts.playButtonPlayLabel);
		setToDate(iTM2Data.minDayIndex);
	});

	$( iTM2Opts.lastButtonSelector ).click(function() {
		stopAnimation();
		if(iTM2Opts.advanced_UI && iTM2Data.advancedButtons) $( iTM2Opts.playButtonSelector ).button( 'option', { label: iTM2Opts.playButtonPlayLabel, icons: { primary: iTM2Opts.playButtonPlayIcon }} );
		else $( iTM2Opts.playButtonSelector ).text(iTM2Opts.playButtonPlayLabel);
		setToDate(iTM2Data.maxDayIndex);
	});

	// Time control
	if(iTM2Opts.advanced_UI) {
		$( iTM2Opts.timeControlSelector ).slider();
		$( iTM2Opts.timeControlSelector ).slider({change : function( event, ui ) {
				setToDate($( iTM2Opts.timeControlSelector ).slider( 'value' ));
			} });
		if(iTM2Data.advancedButtons) {
			$( iTM2Opts.firstButtonSelector ).button({ text: false, icons: { primary: 'ui-icon-seek-start' } });
			$( iTM2Opts.playButtonSelector ).button({ text: false, icons: { primary: 'ui-icon-play' }});
			$( iTM2Opts.lastButtonSelector ).button({ text: false, icons: { primary: 'ui-icon-seek-end' }});
		}
	} else {
		$( iTM2Opts.timeControlSelector ).change(function( event, ui ) { setToDate($( iTM2Opts.timeControlSelector ).val()); });
	}

	// Dot size control
	if(iTM2Opts.advanced_UI) {
		$( iTM2Opts.dotControlSelector ).slider();
		$( iTM2Opts.dotControlSelector ).slider( 'option', 'min', iTM2Opts.dotControlMin );
		$( iTM2Opts.dotControlSelector ).slider( 'option', 'max', iTM2Opts.dotControlMax );
		$( iTM2Opts.dotControlSelector ).slider( 'option', 'value', iTM2Data.dotSize );
		$( iTM2Opts.dotControlSelector ).slider({change : function( event, ui ) {
			iTM2Data.dotSize = $( iTM2Opts.dotControlSelector ).slider( 'value' );
			if($( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.features.length>0){
				var features = $( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.features;
				$( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.removeFeatures(features);
				for(p=0; p< features.length; p++)
					features[p].style.pointRadius = iTM2Data.dotSize+2;
				$( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.addFeatures(features);
			}
			if( iTM2Opts.twinMaps && $( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.features.length>0){
				var features = $( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.features;
				$( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.removeFeatures(features);
				for(p=0; p< features.length; p++)
					features[p].style.pointRadius = iTM2Data.dotSize+2;
				$( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.addFeatures(features);
			}
			resetMap();
		}});
	} else {
		$( iTM2Opts.dotControlSelector ).val( iTM2Data.dotSize );
		$( iTM2Opts.dotControlSelector ).change(function( event, ui ) {
			iTM2Data.dotSize = $( iTM2Opts.dotControlSelector ).val();
			if($( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.features.length>0){
				var features = $( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.features;
				$( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.removeFeatures(features);
				for(p=0; p< features.length; p++)
					features[p].style.pointRadius = iTM2Data.dotSize+2;
				$( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.addFeatures(features);
			}
			if( iTM2Opts.twinMaps && $( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.features.length>0){
				var features = $( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.features;
				$( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.removeFeatures(features);
				for(p=0; p< features.length; p++)
					features[p].style.pointRadius = iTM2Data.dotSize+2;
				$( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.addFeatures(features);
			}
			resetMap();
		});
	}

	mapInitialisationHooks.push(function(mapdiv) {
		// each map gets its own site and events layers.
		mapdiv.map.eventsLayer = new OpenLayers.Layer.Vector('Events Layer', {displayInLayerSwitcher: false});
		mapdiv.map.sitesLayer = new OpenLayers.Layer.Vector(iTM2Opts.sitesLayerLabel, {displayInLayerSwitcher: true});
		mapdiv.map.addLayer(mapdiv.map.sitesLayer);
		mapdiv.map.addLayer(mapdiv.map.eventsLayer);
		// switch off the mouse drag pan.
		for(var n = 0; n < mapdiv.map.controls.length; n++){
		  if(mapdiv.map.controls[n].CLASS_NAME == 'OpenLayers.Control.Navigation')
		    mapdiv.map.controls[n].deactivate();
		}
		if('#'+mapdiv.id == iTM2Opts.primaryMapSelector)
			loadYear($(iTM2Opts.yearControlSelector).val(), 'lh');
	});
  };

  loadYear = function(year, side) {
	  if(typeof iTM2Data.myData[year] != 'undefined') {
		  if(side == 'lh'){
			  enableSpeciesControlOptions();
			  enableEventControlOptions();
			  buildRhsControlOptions();
		  }
		  calculateMinAndMax();
		  resetMap();
		  return; // already loaded.
	  }
	  iTM2Data.myData[year] = [];
	  for(k = 0; k < iTM2Opts.triggerEvents.length; k++) {
		  iTM2Data.myData[year][k] = []; // first event, array of days
		  for(j = 0; j <= 365; j++) 
			  iTM2Data.myData[year][k][j] = []; // arrays of species geometry pairs
	  }
	  $(iTM2Opts.errorDiv).empty();
	  dialog = $('<p>'+iTM2Opts.waitDialogText.replace('{year}', year)+'</p>').dialog({ title: iTM2Opts.waitDialogTitle, buttons: { 'OK': function() { dialog.dialog('close'); }}});
	  // Report record should have location_id, sample_id, occurrence_id, sample_date, species ttl_id, attributes, geometry.
	  jQuery.getJSON(iTM2Opts.base_url+'/index.php/services/report/requestReport?report='+iTM2Opts.report_name+'.xml&reportSource=local&mode=json' +
				'&auth_token='+iTM2Opts.auth_token+'&reset_timeout=true&nonce='+iTM2Opts.nonce + iTM2Opts.reportExtraParams +
				'&callback=?&year='+year+'&date_from='+year+'-01-01&date_to='+year+'-12-31', 
		function(data) {
		  var canIDuser = false;
		  var hasDate = false;
		  var wktCol = false;
		  var parser = new OpenLayers.Format.WKT();
			
			if(typeof data.records != 'undefined') {
				if(data.records.length > 0) {
					// first isolate geometry column
					$.each(data.columns, function(column, properties){
				      if(column == 'created_by_id') canIDuser = true;
				      if(column == 'date') hasDate = true;
					  if(typeof properties.mappable != 'undefined' && properties.mappable =='true' && !wktCol) wktCol = column;
	      			});
		    		if (!wktCol) return $(iTM2Opts.errorDiv).append('<p>'+iTM2Opts.noMappableDataError+'</p>');
	    			if (!hasDate) return $(iTM2Opts.errorDiv).append('<p>'+iTM2Opts.noDateError+'</p>');
		      		// TODO add check for taxon and taxon_id: data drive from form args
	    			// put location_id checks in
	    			
	    			// Date preprocess and sort
	    	  		for (var i=0;i<data.records.length;i++) {
	      				var parts = data.records[i].date.split('/');
	      				data.records[i].year = parts[2];
	      				data.records[i].converted_date = parts[2]+'/'+parts[1]+'/'+parts[0];
	      				data.records[i].recordDayIndex = (Date.UTC(parts[2], parts[1], parts[0]) - Date.UTC(parts[2], 1, 1))/ (24 * 60 * 60 * 1000);
	    	  		}
	    	  		data.records.sort(function(a, b){
	    	  			var aDate = new Date(a.converted_date);
	    	  			var bDate = new Date(b.converted_date);
	    	  			return aDate-bDate});
	    	  		var year = data.records[0].year;
	    	  		
	    	  		for (var i=0; i<data.records.length; i++) {
	      				var wkt;
	      				// remove point stuff: don't need to convert to numbers, as that was only to save space in php.
	      				wkt = data.records[i][wktCol].replace(/POINT\(/, '').replace(/\)/, '');

	      				if(typeof iTM2Data.mySpecies[data.records[i].species_id] == 'undefined') {
	      		    		iTM2Data.mySpeciesIDs.push(data.records[i].species_id);
	      		    		iTM2Data.mySpecies[data.records[i].species_id] = {id: data.records[i].species_id, taxon: data.records[i].taxon};
	      		    		$(iTM2Opts.speciesControlSelector).append('<option value="'+data.records[i].species_id+'" '+(side=='rh'?'disabled="disabled" style="display:none;"':'')+'>'+data.records[i].taxon+'</option>');
	      		    	}
	      				for(k=0; k<iTM2Opts.triggerEvents.length; k++){
	      					// event definition
	      					// check first that this hasn't happened already! we are using assumption that the data is sorted by date, so earlier records will be processed first.
	      					var found = false;
	      					for (j=0; j< data.records[i].recordDayIndex; j++){
	      						if(typeof iTM2Data.myData[year][k][j][data.records[i].species_id] != 'undefined'
	      								&& iTM2Data.myData[year][k][j][data.records[i].species_id].locations.indexOf(data.records[i].location_id) >= 0) {
	      							found = true;
	      							break;
	      						}
	      					}
	      					if(found) continue;
	      					// user locations independant of event
  		      				if(canIDuser && iTM2Opts.indicia_user_id && data.records[i].created_by_id == iTM2Opts.indicia_user_id &&
  		      						typeof iTM2Data.mySites[data.records[i].location_id] == 'undefined') {
  		      					iTM2Data.mySites[data.records[i].location_id] = true;
  		      					iTM2Data.mySiteWKT.push(wkt);
  		      				}
	      					// TODO: Dev: allow between values as rules.
	      				    if(iTM2Opts.triggerEvents[k].type == 'presence' ||
	      				    	(iTM2Opts.triggerEvents[k].type == 'arrayVal' &&
	      				    		typeof data.records[i]['attr_occurrence_'+iTM2Opts.triggerEvents[k].attr] != 'undefined' &&
	      				    		iTM2Opts.triggerEvents[k].values.indexOf(data.records[i]['attr_occurrence_'+iTM2Opts.triggerEvents[k].attr]) >= 0)) {
	      				    	if(typeof iTM2Data.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id] == 'undefined')
	      				    		iTM2Data.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id] = {'mine':{'attributes':{}, 'feature':false, 'wkt': []},'others':{'attributes':{}, 'feature':false, 'wkt': []}, 'locations': []};      				    		
	      		      			if(canIDuser && iTM2Opts.indicia_user_id && data.records[i].created_by_id == iTM2Opts.indicia_user_id) {
	      		      				iTM2Data.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id].mine.wkt.push(wkt);
	      		      			} else {
	      		      				iTM2Data.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id].others.wkt.push(wkt);
	      		      			}
	      		      			iTM2Data.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id].locations.push(data.records[i].location_id);
	      				    }
	      				}
		      		}
	    	  		// loop through all records in year, and convert array of WKT to features.
	    	  		for(k = 0; k < iTM2Opts.triggerEvents.length; k++) {
	    	  			for(j = 0; j <= 365; j++) 
	    	  				for(m = 0; m<iTM2Data.mySpeciesIDs.length; m++) {
	    	  					if(typeof iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]] != 'undefined' &&
	    	  							iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].mine.wkt.length > 0) {
	    	  						iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].mine.feature = 
	    	  								parser.read((iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].mine.wkt.length == 1 ? 'POINT(' : 'MULTIPOINT(') +
	    	  										iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].mine.wkt.join(',') + ')');
		      		      			iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].mine.feature.style =
		      		      					{strokeWidth: 3, strokeColor: 'Yellow', graphicName: 'square', fillOpacity: 1};
		      		      			iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].mine.feature.attributes.dayIndex = j;
	    	  					}
	    	  					if(typeof iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]] != 'undefined' &&
	    	  							iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].others.wkt.length > 0) {
	    	  						iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].others.feature = 
	    	  								parser.read((iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].others.wkt.length == 1 ? 'POINT(' : 'MULTIPOINT(') +
	    	  										iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].others.wkt.join(',') + ')');
	    	    	  				iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].others.feature.style = {fillOpacity: 0.8, strokeWidth: 0};
		      		      			iTM2Data.myData[year][k][j][iTM2Data.mySpeciesIDs[m]].others.feature.attributes.dayIndex = j;
	    	  					}
	    	  				}
	    	  		}

	    	  		$( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.destroyFeatures();
	    	  		if( iTM2Opts.twinMaps ) $( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.destroyFeatures();
  					if( iTM2Data.mySiteWKT.length > 0 ) {
  						var feature = parser.read((iTM2Data.mySiteWKT.length == 1 ? 'POINT(' : 'MULTIPOINT(') + iTM2Data.mySiteWKT.join(',') + ')');
    	  				feature.style = {fillColor: 0, fillOpacity: 0, strokeWidth: 2, strokeColor: 'Yellow', graphicName: 'square', pointRadius: iTM2Data.dotSize+2};
    	    	  		$( iTM2Opts.primaryMapSelector )[0].map.sitesLayer.addFeatures([feature]);
    	    	  		if( iTM2Opts.twinMaps ) $( iTM2Opts.secondaryMapSelector )[0].map.sitesLayer.addFeatures([feature.clone()]);    	  			
	    	  		}
	      		}
	  		} else if (typeof data.error != 'undefined') {
				$(iTM2Opts.errorDiv).html('<p>Error Returned from warehouse report:<br>' + data.error + '<br/>' +
						(typeof data.code != 'undefined' ? 'Code: ' + data.code + '<br/>' : '') +
						(typeof data.file != 'undefined' ? 'File: ' + data.file + '<br/>' : '') +
						(typeof data.line != 'undefined' ? 'Line: ' + data.line + '<br/>' : '') +
						// not doing trace
						'</p>');
			} else {
				$(iTM2Opts.errorDiv).html('<p>Internal Error: Format from report request not recognised.</p>');
			}
			if(side == 'lh'){
				  enableSpeciesControlOptions();
				  enableEventControlOptions();
				  buildRhsControlOptions();
			}
			calculateMinAndMax();
			resetMap();
			dialog.dialog('close');
	    });
	};

	rgbvalue = function(dateidx) {
		var r = parseInt(iTM2Opts.lastDateRGB.r*(dateidx-iTM2Data.minDayIndex)/(iTM2Data.maxDayIndex-iTM2Data.minDayIndex) + iTM2Opts.firstDateRGB.r*(iTM2Data.maxDayIndex-dateidx)/(iTM2Data.maxDayIndex-iTM2Data.minDayIndex));
		r = (r<16 ? '0' : '') + r.toString(16);
		var g = parseInt(iTM2Opts.lastDateRGB.g*(dateidx-iTM2Data.minDayIndex)/(iTM2Data.maxDayIndex-iTM2Data.minDayIndex) + iTM2Opts.firstDateRGB.g*(iTM2Data.maxDayIndex-dateidx)/(iTM2Data.maxDayIndex-iTM2Data.minDayIndex));
		g = (g<16 ? '0' : '') + g.toString(16);
		var b = parseInt(iTM2Opts.lastDateRGB.b*(dateidx-iTM2Data.minDayIndex)/(iTM2Data.maxDayIndex-iTM2Data.minDayIndex) + iTM2Opts.firstDateRGB.b*(iTM2Data.maxDayIndex-dateidx)/(iTM2Data.maxDayIndex-iTM2Data.minDayIndex));
		b = (b<16 ? '0' : '') + b.toString(16);
		return '#'+r+g+b;
	};

	setToDate = function(idx){
		var displayDay = function(idx){
			var applyJitter = function(layer, feature){
				var X = iTM2Opts.jitterRadius+1;
				for(var p = 0; p < layer.features.length; p++)
					X = Math.min(X, feature.geometry.distanceTo(layer.features[p].geometry));
				if(!feature.attributes.jittered && X<iTM2Opts.jitterRadius){
					feature.attributes.jittered = true;
					var angle = Math.random()*Math.PI*2;
					feature.geometry.move(iTM2Opts.jitterRadius*Math.cos(angle),iTM2Opts.jitterRadius*Math.sin(angle));
				}
			};

			var applyDay= function(day, layer) {
				if(typeof day != 'undefined' && day !== false) {
					if(day.others.feature) {
						applyJitter(layer, day.others.feature);
						day.others.feature.style.pointRadius = iTM2Data.dotSize;
						day.others.feature.style.fillColor= rgbvalue(idx);
						layer.addFeatures([day.others.feature]);
					}
					if(day.mine.feature) {
						// Dont apply jitter to own data as this may 
						day.mine.feature.style.pointRadius = iTM2Data.dotSize+2;
						day.mine.feature.style.fillColor= rgbvalue(idx);
						layer.addFeatures([day.mine.feature]);
					}
				}
			}
			
			if(iTM2Data.year1 != '' && iTM2Data.species1 != '' && iTM2Data.event1 != '') {
				applyDay(iTM2Data.myData[iTM2Data.year1][iTM2Data.event1][idx][iTM2Data.species1], $(iTM2Opts.primaryMapSelector)[0].map.eventsLayer);
			}
			if(iTM2Opts.twinMaps && (iTM2Data.year2 != '' && iTM2Data.species2 != '' && iTM2Data.event2 != '')) {
				applyDay(iTM2Data.myData[iTM2Data.year2][iTM2Data.event2][idx][iTM2Data.species2], $(iTM2Opts.secondaryMapSelector)[0].map.eventsLayer);
			}
		};

		var rmFeatures = function(layer, dayIdx) {
			var toRemove = [];
			for(var p = 0; p < layer.features.length; p++)
				if(layer.features[p].attributes.dayIndex > dayIdx)
					toRemove.push(layer.features[p]);
			if(toRemove.length>0)
				layer.removeFeatures(toRemove);
		};
		
		var displayYear = (iTM2Data.year2 == '' || iTM2Data.year1==iTM2Data.year2);
		var myDate=new Date();
		myDate.setFullYear(iTM2Data.year1,0,1);
		myDate.setDate(myDate.getDate()+idx);
		$('#displayDate').html(myDate.getDate()+'/'+(myDate.getMonth()+1)+(displayYear ? '/'+myDate.getFullYear() : ''));
		    
		if(iTM2Data.year1 == '' || iTM2Data.species1 == '' || iTM2Data.event1 == '')
			rmFeatures($(iTM2Opts.primaryMapSelector)[0].map.eventsLayer, -1);
		if(iTM2Opts.twinMaps && (iTM2Data.year2 == '' || iTM2Data.species2 == '' || iTM2Data.event2 == ''))
			rmFeatures($(iTM2Opts.secondaryMapSelector)[0].map.eventsLayer, -1);
		if(idx !== getLastDisplayed()) {
			if(getLastDisplayed() > idx){
				rmFeatures($(iTM2Opts.primaryMapSelector)[0].map.eventsLayer, idx);
				if(iTM2Opts.twinMaps)
					rmFeatures($(iTM2Opts.secondaryMapSelector)[0].map.eventsLayer, idx);
			} else {
				for(var j = getLastDisplayed()+1; j <= idx; j++)
					displayDay(j);
			}
			setLastDisplayed(idx);
		}
	};
}(jQuery));