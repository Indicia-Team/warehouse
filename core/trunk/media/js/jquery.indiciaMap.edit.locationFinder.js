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
	    search_button_name : 'place_search_button',
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
      var inputId = div.settings.search_input_name;
      var buttonId = div.settings.search_button_name;
      var searchDivId = div.settings.search_box_name;
      var outputDivId = div.settings.search_output_name;
      var closeId = div.settings.search_close_name;
      
      var html = "<div class='locationFinderControls'>";
      html += "<label for='"+inputId+"'>Search for place on map:</label>\n";
      html += "<input type='text' name='"+inputId+"' id='"+inputId+"' />\n";
      html += "<input type='button' name='"+buttonId+"' id='"+buttonId+"' style='margin-top: -2px' />\n";
      html += "<div id='"+searchDivId+"' style='display: none'><div id='"+outputDivId+"' />\n";
      html += "<a href='#' id='"+closeId+"'>Close</a>\n";
      html += "</div></div>";
      
      if (pos == 0)
      {
	$(div).before(html);
      }
      else
      {
	$(div).after(html);
      }
    }
    
    function registerControls(div)
    {
      var inputId = '#'+div.settings.search_input_name;
      var buttonId = '#'+div.settings.search_button_name;
      var searchDivId = '#'+div.settings.search_box_name;
      var outputDivId = '#'+div.settings.search_output_name;
      var closeId = '#'+div.settings.search_close_name;
      
      
    };
    
  }});
})(jQuery);