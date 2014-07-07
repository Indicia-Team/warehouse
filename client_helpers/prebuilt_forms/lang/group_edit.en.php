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
  'LANG_Pages_Instruct' => 'Use the following grid to define any pages that you would like your group members to use. These pages will then ' .
      'appear in the Links column in the list of recording groups. You only need to specify a link caption if you want to override the ' .
      'default page name when accessed via your group. Please note that you must link at least one recording form page to the group if ' .
      'you want to allow your members to explicitly post records into the group. You must also link at least one reporting page if you want your '.
      'members to be able to view the output of the group.'
);