/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 */

/*
* Plugin for jQuery.indiciaMap allowing it to be used to grab spatial references
* for data entry forms.
* @requires jquery
* @requires jquery.indiciamap
*
*/

/**
* Extends the jQuery.indiciaMap plugin to provide support from editing within the map.
*/

(function($)
{
  $.extend({indiciaMapEdit : new function()
  {
    this.defaults =
    {
      wkt : null,
      layerName : "Current location boundary",
      input_field_name : 'sample:entered_sref',
      geom_field_name : 'sample:geom',
      systems_field_name : 'sample:entered_sref_system',
      systems : {OSGB : "British National Grid", 4326 : "Lat/Long on the WGS84 Datum"},
      label_spatial_ref : "Spatial Ref.",
      label_system : "using",
      placeControls : true,
      controlPosition : 0,
      boundaryStyle: new OpenLayers.Util.applyDefaults({ strokeWidth: 1, strokeColor: "#ff0000", fillOpacity: 0.3, fillColor:"#ff0000" }, OpenLayers.Feature.Vector.style['default'])
    };

    this.construct = function(options)
    {
      return this.each(function()
      {
        var settings = {};
        $.extend(settings, $.indiciaMapEdit.defaults, this.settings, options);
        this.settings = settings;

        // Add an editable layer to the map
        var editLayer = new OpenLayers.Layer.Vector(this.settings.layerName, {style: this.settings.boundaryStyle, 'sphericalMercator': true});
        this.map.editLayer = editLayer;
        this.map.addLayers([this.map.editLayer]);

        if (this.settings.wkt != null)
        {
          _showWktFeature(this, this.settings.wkt);
        }

        if (this.settings.placeControls)
        {
          _placeControls(this);
        }

        _registerControls(this);

      });
    };

    this.showWktFeature=_showWktFeature;

    // Private functions

    function _showWktFeature(div, wkt) {
      var editlayer = div.map.editLayer;
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read(wkt);
      var bounds=feature.geometry.getBounds();

      editlayer.destroyFeatures();
      editlayer.addFeatures([feature]);
      // extend the boundary to include a buffer, so the map does not zoom too tight.
      var dy = (bounds.top-bounds.bottom)/1.5;
      var dx = (bounds.right-bounds.left)/1.5;
      bounds.top = bounds.top + dy;
      bounds.bottom = bounds.bottom - dy;
      bounds.right = bounds.right + dx;
      bounds.left = bounds.left - dx;
      // if showing a point, don't zoom in too far
      if (dy==0 && dx==0) {
        div.map.setCenter(bounds.getCenterLonLat(), 13);
      } else {
        // Set the default view to show something triple the size of the grid square
        div.map.zoomToExtent(bounds);
      }
    };

    /**
    * Adds controls into the div in the specified position.
    */
    function _placeControls(div)
    {
      var pos = div.settings.controlPosition;
      var systems = div.settings.systems;

      var html = "<div>";
      html += "<label for='"+div.settings.input_field_name+"'>"+div.settings.label_spatial_ref+":</label>\n";
      html += "<input type='text' id='" + div.settings.input_field_name + "' name='" + div.settings.input_field_name + "' />\n";
      // hidden field for the geom
      html += "<input type='hidden' class='hidden' id='" + div.settings.geom_field_name + "' name='" + div.settings.geom_field_name + "' />\n";
      if (systems.length == 1)
      {
        // Hidden field for the system
        html += "<input type='hidden' id='" + div.settings.systems_field_name + "' name='" + div.settings.systems_field_name + "'/>\n";
      }
      else
      {
        html += "<label style=\"width: auto\" for='"+div.settings.systems_field_name+"'>"+div.settings.label_system+":</label>";
        html += "<select id='" + div.settings.systems_field_name + "' name='" + div.settings.systems_field_name + "' >\n";
        $.each(systems, function(key, val) { html += "<option value='" + key + "'>" + val + "</option>\n" });
        html += "</select>\n";
      }
      html += "</div>";

      if (pos == 0) {
        $(div).before(html);
      }
      else {
        $(div).after(html);
      }
    }

    /**
    * Registers controls with the map - binds functions to them and places correct data in if wkt has been supplied.
    */
    function _registerControls(div)
    {
      // Get jQuery selectors, escaping any colons appropriately
      var inputFld = '#' + div.settings.input_field_name.replace(':', '\\:');
      var geomFld = '#' + div.settings.geom_field_name.replace(':', '\\:');
      var systemsFld = '#' +div.settings.systems_field_name.replace(':', '\\:');
      var map = div.map;

      OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {

        defaultHandlerOptions: { 'single': true, 'double': false, 'pixelTolerance': 0, 'stopSingle': false, 'stopDouble': false },

        initialize: function(options)
        {
          this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
          OpenLayers.Control.prototype.initialize.apply(this, arguments);
          this.handler = new OpenLayers.Handler.Click( this, {'click': this.trigger}, this.handlerOptions );
        },

        trigger: function(e)
        {
          var lonlat = map.getLonLatFromViewPortPx(e.xy);
          // get approx metres accuracy we can expect from the mouse click - about 5mm accuracy.
          var precision = map.getScale()/200;
          // now round to find appropriate square size
          if (precision<30) {
            precision=8;
          } else if (precision<300) {
            precision=6;
          } else if (precision<3000) {
            precision=4;
          } else {
            precision=2;
          }
          $.getJSON(div.settings.indiciaSvc + "/index.php/services/spatial/wkt_to_sref"+
            "?wkt=POINT(" + lonlat.lon + "  " + lonlat.lat + ")"+
            "&system=" + $(systemsFld).val() +
            "&precision=" + precision +
            "&callback=?", function(data)
            {
              $(inputFld).attr('value', data.sref);
              map.editLayer.destroyFeatures();
              $(geomFld).attr('value', data.wkt);
              var parser = new OpenLayers.Format.WKT();
              var feature = parser.read(data.wkt);
              map.editLayer.addFeatures([feature]);
            }
          );
        }
      });

      // Add the click control to the map.
      var click = new OpenLayers.Control.Click();
      map.addControl(click);
      click.activate();

      // Bind functions to the input field.
      $(inputFld).change(function() {
        $.getJSON(div.settings.indiciaSvc + "/index.php/services/spatial/sref_to_wkt"+
          "?sref=" + $(this).val() +
          "&system=" + $(systemsFld).val() +
          "&callback=?", function(data) {
            $(geomFld).attr('value', data.wkt);
            _showWktFeature(div, data.wkt);
          }
        );
      });
    }

  }
  });

  $.fn.extend({ indiciaMapEdit : $.indiciaMapEdit.construct });
})(jQuery);
