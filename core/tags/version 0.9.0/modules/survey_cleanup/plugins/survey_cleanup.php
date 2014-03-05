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
 * @package	Survey Cleanup
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Adds a tab for Survey details, that provides options for cleanup of records contained in the surveys.
 * @return array List of user interface extensions.
 */
function survey_cleanup_extend_ui() {
  return array(array(
    'view'=>'survey/survey_edit', 
    'type'=>'tab',
    'controller'=>'survey_cleanup/index',
    'title'=>'Cleanup Records',
    'allowForNew' => false
  ));
}