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
      placeControls : true;
      
    }
  }});
})(jQuery);