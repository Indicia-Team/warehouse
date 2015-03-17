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

/**
 * Additional language terms or overrides for dynamic_sample_occurrence form.
 *
 * @package	Client
 */
$custom_terms = array_merge($custom_terms, array(
    'blankText' => 'Please select...',
    'next step' => 'Next Step',
    'prev step' => 'Previous Step',
    'save' => 'Save',
    // Location.
    'LANG_Tab_location' => 'Time and place',
    'LANG_Tab_location help' => 'Please identify when and where you made the observation.',
    'LANG_Date' => 'When was the observation made?',
    'ICPVeg Country' => 'Select the country where this observation was made.',
    'ICPVeg Sensitivity' => 'How accurately can we display this record to others?',
    'ICPVeg Sensitivity blankText' => 'Display without restriction',
    'ICPVeg Sensitivity 50km' => 'Display as a 50km square',
    'LANG_SRef_Label' => 'Zoom the map in to the location of the observation and click to obtain its coordinates.',
    // Species.
    'LANG_Tab_species' => 'Species',
    'LANG_Tab_species help' => 'Now identify the plant species which was affected.',
    'occurrence:taxa_taxon_list_id' => 'Use the selectors below as far as possible to choose a species.',
    'ICPVeg Species' => 'If you chose one of the \'Other\' options above, please provide the species name.',
    // Photograph.
    'LANG_Tab_photograph' => 'Photographs',
    'LANG_Tab_photograph help' => 'Please upload two photographs:<br/>'
    . '(1) Showing the symptoms on the leaf<br/>'
    . '(2) Showing the plant growing in its surroundings',
    // Injury.
    'LANG_Tab_injury' => 'Leaf injury symptoms',
    'LANG_Tab_injury help' => 'Please answer the following questions.',
    'ICPVeg Injury Colour' => 'Which colour best describes the symptoms?',
    'ICPVeg Injury Location Veins' => 'Where are the symptoms?',
    'ICPVeg Injury Location Surface' => 'On which side of the leaf are the symptoms?',
    'ICPVeg Injury Location Age' => 'On which leaves are the symptoms most severe?',
    'ICPVeg Injury Abundance' => 'How widespread are the symptoms?',
    'ICPVeg Injury Evidence' => 'Is there any evidence of the following? Check all that apply.',
    'ICPVeg Injury Evidence Other' => 'If you checked \'Other\' above, please describe the symptoms here.',
    // Weather.
    'LANG_Tab_weather' => 'Weather',
    'LANG_Tab_weather help' => 'Please provide an indication of recent weather where the injury was seen.',
    'ICPVeg Weather Temp' => 'In the last week, what has the maximum temperature each day been on average?',
    'ICPVeg Weather Rain' => 'In the last week, on how many days has there been rain?',
    // Pollution.
    'LANG_Tab_pollution' => 'Ozone pollution',
    'LANG_Tab_pollution help' => 'If you are aware of ozone concentrations in the area, please answer the following questions.',
    'ICPVeg Ozone Maximum' => 'In the last week, what has the maximum ozone concentration been?',
    'ICPVeg Ozone Persistence' => 'In the last week, on how many days has the ozone concentration exceeded 50 ppb?',
    )
);