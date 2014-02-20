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
 * File containing general purpose JavaScript functions for Indicia.
 */
if (typeof window.indiciaData==="undefined") {
  window.indiciaData = {
    onloadFns: [],
    idDiffRuleMessages: {}
  };
  window.indiciaFns = {};
}

(function ($) {
  "use strict";

  /**
   * Method to attach to the hover event of an id difficulty warning icon. The icon should have 
   * data-rule and data-diff attributes, pointing to to the rule ID and id difficulty level
   * respectively.
   */
  indiciaFns.hoverIdDiffIcon = function(e) {
    if ($(e.currentTarget).attr('title')==='') {
      // Hovering over an ID difficulty marker, so load up the message hint. We load the whole 
      // lot for this rule, to save multiple service hits. So check if we've loaded this rule already
      if (typeof indiciaData.idDiffRuleMessages['rule'+$(e.currentTarget).attr('data-rule')]==="undefined") {
        $.ajax({
          dataType: "jsonp",
          url: indiciaData.read.url+'index.php/services/data/verification_rule_datum',
          data: {"verification_rule_id":$(e.currentTarget).attr('data-rule'), "header_name":"INI",
            "auth_token":indiciaData.read.auth_token, "nonce":indiciaData.read.nonce},
          success: function(data) {
            indiciaData.idDiffRuleMessages['rule'+$(e.currentTarget).attr('data-rule')]={};
            $.each(data, function(idx, msg) {
              indiciaData.idDiffRuleMessages['rule'+$(e.currentTarget).attr('data-rule')]
                  ['diff'+msg.key] = msg.value;
            });
            $(e.currentTarget).attr('title', indiciaData.idDiffRuleMessages['rule'+$(e.currentTarget).attr('data-rule')]
                ['diff'+$(e.currentTarget).attr('data-diff')]);
          },
          error: function() {
            // put a default in place.
            $(e.currentTarget).attr('title', 'Caution, identification difficulty level ' + $(e.currentTarget).attr('data-rule') + ' out of 5');
          }
        });
      } else {
        $(e.currentTarget).attr('title', indiciaData.idDiffRuleMessages['rule'+$(e.currentTarget).attr('data-rule')]
                  ['diff'+$(e.currentTarget).attr('data-diff')]);
      }
    }
  };

}) (jQuery);