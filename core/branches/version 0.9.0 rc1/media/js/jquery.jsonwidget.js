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

/**
 * A jQuery plugin wrapper for the jsonwidget JavaScript control.
 */

(function($) {
  $.fn.jsonedit = function(options) {

    // Extend our default options with those provided, basing this on an empty object
    // so the defaults don't get changed.
    var opts = $.extend({}, $.fn.jsonedit.defaults, options);

    return this.each(function() {
      this.settings = opts;

      $(this).append('<div id="'+this.id+'_je_warningdiv"></div>');
      $(this).append('<div id="'+this.id+'_je_formdiv"></div>');
      $(this).append('<textarea id="'+this.id+'_je_sourcetextarea" style="display: none !important;" rows="20" cols="80" name="'+opts.fieldname+'">'+
          opts['default']+'</textarea>\n');
      $(this).append('<div>\n'+
          '<span id="'+this.id+'_je_formbutton" style="cursor: pointer">[Edit w/Form]</span>\n'+
          '<span id="'+this.id+'_je_sourcebutton" style="cursor: pointer">[Edit Source]</span>\n'+
          '</div>\n');
      var je=new jsonwidget.editor();
      je.schema = opts.schema;
      // override the default control ids to the ones used by our plugin
      je.htmlids.formdiv = this.id+'_je_formdiv';
      je.htmlids.warningdiv = this.id+'_je_warningdiv';
      je.htmlids.sourcetextarea = this.id+'_je_sourcetextarea';
      je.htmlbuttons.form = this.id+'_je_formbutton';
      je.htmlbuttons.source = this.id+'_je_sourcebutton';

      je.setView('form');
    });

  };
}(jQuery));

jQuery.fn.jsonedit.defaults = {
  schema: {},
  fieldname: 'jsonwidget'
};

