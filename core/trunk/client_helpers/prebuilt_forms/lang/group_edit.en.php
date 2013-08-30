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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

global $custom_terms;

/**
 * Language terms for the group_edit form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Filter_Instruct' => 'Please click on the parameters below to define the records that are of interest to this group. For example, '.
      'you might want to specify the species or species groups that relate to your group using the <strong>What</strong> option, '.
      'as well as the geographic area your group covers using the <strong>Where</strong> option.',
  'Click to Filter What' => 'Click here to select a list of species or species groups to include',
  'Click to Filter Where' => 'Click here to define the geographic area, site or map reference to include',
  'Click to Filter When' => 'Click here to define a date range for records to include',
  'Click to Filter Who' => 'Click here to define who\'s records to include',
  'Click to Filter Quality' => 'Click here to include or exclude records based on quality criteria such as verification status or presence of photos',
  'Click to Filter Source' => 'Click here to include or exclude records based on which website, survey or input form contributed them',
);