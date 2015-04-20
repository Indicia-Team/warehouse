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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link    http://code.google.com/p/indicia/
 */
 
jQuery(document).ready(function ($) {
  function setVisibleSurveyControl() {
    // check first option if none checked
    if ($('input[name="user-filter"]:checked').length===0) {
      $('#user-filter\\:0').attr('checked',true);
    }
    var filter=$('input[name="user-filter"]:checked').val();
    switch (filter) {
      case expert:
        // expert records - might be limited so a different list of surveys
        $('#survey_expertise').show();
        $('#survey_all').hide();
        break;
      case mine:
      case all:
        // my records or all records - can pick any survey
        $('#survey_all').show();
        $('#survey_expertise').hide();
        break;
    }
  }
  
  // toggle between the surveys available for the 2 options (my data, or data I am an expert for)
  $('#user-filter\\:0, #user-filter\\:1, #user-filter\\:2').click(setVisibleSurveyControl);
  
  setVisibleSurveyControl();
});