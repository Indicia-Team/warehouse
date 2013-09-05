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

include_once 'dynamic.en.php';

/**
 * Additional language terms or overrides for dynamic_sample_occurrence form.
 *
 * @package	Client
 */
$custom_terms = array_merge($custom_terms, array(
		'Overall Comment' => 'Did you make any additional observations?',
		'LANG_SRef_Label' => 'Grid Ref',
		'LANG_Georef_Label' => 'Search for farm',
		'species_checklist.species' => 'Insect group',
		'Assistance Provided' => 'Assistance Provided: please tick one',
		'OFS 2013 Crop' => 'Which Crop habitat are you looking at? Please tick one',
		'OFS 2013 Other Habitat' => 'Which Other habitat are you looking at? Please tick one',
		'sunny' => 'Sunny'
  )
);