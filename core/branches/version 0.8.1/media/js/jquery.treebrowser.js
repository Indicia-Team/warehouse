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
 *
 * @package Media
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link    http://code.google.com/p/indicia/
 */

/**
 * JQuery tree browser widget for Indicia.
 */

(function($) {

  /**
   * Constructor. 
   */
  $.fn.indiciaTreeBrowser = function(options) {
    this.settings = {};
    // Extend with defaults and options
    $.extend(this.settings, $.fn.indiciaTreeBrowser.defaults, options);
    load(this);
  };
      
  /**
   * Load a level into the control 
   */
  function load(container, id) {
    // Store the clicked on id in the hidden form field.        
    $("#"+container.settings.valueControl.replace(':','\\:')).val(id);
    // Ensure the Warehouse services understand an empty value
    if (id===undefined) {
      id='NULL';
    }
    var extras = '';
    for (var p in container.settings.extraParams) {
      extras += '&' + p + '=' + container.settings.extraParams[p];
    }
    container.find("li").removeClass('ui-state-highlight');
    $("li#"+id).addClass('ui-state-highlight');
    $.getJSON(container.settings.url+"?callback=?&"+container.settings.parentField+"="+id+"&view="+container.settings.view+extras,      
      null,
      function(response) {
      if (response.length) {
        var itemClass;
        // Build a new list at the bottom of the container, hidden for now
        var list = $("<ul/>").appendTo(container);
        list.hide();            
        // Create entries in the list
        $.each(response, function(idx, record) {          
          var node = container.settings.nodeTmpl;
          record.caption = record[container.settings.captionField];
          for (var item in record) {
            node = node.replace(new RegExp('{'+item+'}','g'), record[item]);
          }          
          itemClass=container.settings.listItemClass;
          if (record[container.settings.valueField]==container.settings.defaultValue) {
            itemClass += ' ui-state-highlight';
          }
          var current = $('<li class="'+itemClass+'"/>').attr("id", record[container.settings.valueField] || "").html(node).appendTo(list);
          current.click(function() {
            load(container, this.id);
          });            
        });
        // If in single layer mode, hide the previous level lists. Add a go back button. 
        if (container.settings.singleLayer && id!='NULL') {
          container.children().slideUp('fast');
          $('<li class="ui-state-default">'+container.settings.backCaption+"</li>").click(function() {
            list.slideUp('fast', function() {list.remove();} );                
            container.children().show('fast');
          }).appendTo(list);
        }
        if (id!='NULL') {
          list.slideDown('fast');            
        } else {
          list.fadeIn('slow');
        }
      }                    
    });
  }

})(jQuery);

$.fn.indiciaTreeBrowser.defaults = {
    panelClass: "ui-widget ui-widget-content ui-corner-all",
    headerClass: "",
    listItemClass: "ui-state-default ui-corner-all"
};