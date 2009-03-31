/*
* Location finder plugin for jQuery.indiciaMap.
* @requires jquery
* @requires jquery.indiciaMap
* @requires jquery.indiciaMap.edit
*
*/

/**
* jQuery plugin to support Yahoo geoplanet lookup in the map control.
*/

(function($)
{
  $.extend( { locationFinder : new function()
  {
    this.defaults = 
    {
      placeControls : true,
	    controlPosition : 0,
	    search_box_name : 'place_search_box',
	    search_output_name : 'place_search_output',
	    preferedArea : 'gb',
	    country : 'United Kingdom',
	    lang : 'en-EN'
    };
    
    this.construct = function(options)
    {
      var settings = {};
      $.extend(settings, $.indiciaMap.defaults, $.indiciaMapEdit.defaults, $.locationFinder.defaults, options);
      return this.each(function()
      {
	this.settings = settings;
	
	if (this.settings.placeControls)
	{
	  placeControls(this);
	}
      });
    };
    
    // Private functions
    
    function placeControls(div)
    {
      var pos = div.settings.controlPosition;
      
      var html = "<div>";
      html += "</div>";
    }
    
  }
  }});
  })(jQuery);