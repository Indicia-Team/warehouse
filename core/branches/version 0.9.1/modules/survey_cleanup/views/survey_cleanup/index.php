<?php
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
 * @package	Survey cleanup
 * @subpackage Views
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */
 
require_once(DOCROOT.'client_helpers/data_entry_helper.php');

if (!($this->auth->logged_in('CoreAdmin') || $this->auth->has_website_access('admin', $survey->website_id))) {
  echo '<p class="page-notice ui-state-highlight ui-corner-all">You must be an admin of the website that this survey belongs to in order to use the Survey Cleanup tool</p>';
} else {
  echo data_entry_helper::radio_group(array(
    'label' => 'Type of data to cleanup',
    'fieldname' => 'mode',
    'id' => 'mode',
    'lookupValues' => array(
      'deleted'=>'Mark deleted records',
      'test'=>'Test records',
      'all'=>'All records'
    ),
    'sep' => '<br/>',
    'default' => 'deleted'
  ));
  echo data_entry_helper::hidden_text(array(
    'fieldname'=>'survey_id',
    'default'=>$survey->id
  ));

  echo data_entry_helper::apply_template('submitButton', array(
    'id'=>'cleanup-button',
    'class'=>'indicia-button',
    'caption'=>'Cleanup Records'
  ));

  data_entry_helper::link_default_stylesheet();
  data_entry_helper::$dumped_resources[] = 'jquery';
  data_entry_helper::$dumped_resources[] = 'jquery_ui';
  data_entry_helper::$dumped_resources[] = 'fancybox';
  data_entry_helper::$javascript .= "$('#cleanup-button').click(function() {
    if (!confirm('Are you certain you want to cleanup these records?')) {
      return false;
    } else {
      $.post(\"".url::site('survey_cleanup/cleanup')."\", 
        { mode: $('input:radio[name=mode]:checked').val(), survey_id: $('#survey_id').val() }, 
        function(data, textStatus) {
          alert(data);
        }
      );
    }
  });\n";
  echo data_entry_helper::dump_javascript();
}

?>