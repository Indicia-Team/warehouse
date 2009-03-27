/*
* Editable plugin for jQuery.indiciaMap.
* @requires jquery
* @requires jquery.indiciamap
*
*/

/**
* Extends the jQuery.indiciaMap plugin to provide support from editing within the map.
*/

(function($)
{
  $.extend( indiciaMapEdit : function()
  {
    this.defaults = 
    {
      wkt : null,
      input_field_name : 'entered_sref',
	    geom_field_name : 'geom'
    };
    
    this.construct = function(options)
    {
      var settings = {};
      $.extend(true, settings, $.indiciaMap.defaults, $.indiciaMapEdit.defaults);
      return this.each(function()
      {
	this.settings = settings;
	var div = this;
      });
    }
  };
  
  $.fn.extend( indiciaMapEdit : $.indiciaMapEdit.construct );
})(jQuery);