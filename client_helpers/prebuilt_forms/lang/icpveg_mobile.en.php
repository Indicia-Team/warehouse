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
    'Please select' => 'Please select...',
    'next step' => 'Next',
    'prev step' => 'Back',
    'save' => 'Save',
    // Location.
    'LANG_Tab_location' => 'Location',
    'LANG_Tab_location help' => 'Please answer these questions about your location. Your exact position will be provided by GPS.',
    'ICPVeg Country' => 'Select the country where you are.',
    'ICPVeg Sensitivity' => 'How accurately can we display this record to others?',
    'ICPVeg Sensitivity blankText' => 'Display without restriction',
    'ICPVeg Sensitivity 50km' => 'Display as a 50km square',
    // Species.
    'LANG_Tab_species' => 'Species',
    'LANG_Tab_species help' => 'Now identify the plant species which is affected.',
    'ICPVeg Vegetation Type' => 'Select the broad vegetation type first and then refine the description in the control that appears.',
    'ICPVeg Vegetation Type Other' => 'If you selected \'Other\' above, please describe the vegetation type.',
    'occurrence:taxa_taxon_list_id' => 'Select the species name from the list.',
    'ICPVeg Species' => 'If the species is not in the list above, please provide the species name.',
    // Photograph.
    'LANG_Tab_photograph' => 'Photographs',
    'LANG_Tab_photograph help' => 'Please take two photographs:<br/>'
    . '(1) Showing the symptoms on the leaf<br/>'
    . '(2) Showing the plant growing in its surroundings',
    'Species photos' => 'The symptoms on the leaf',
    'Sample photos' => 'The plant in its surroundings',
    // Injury.
    'LANG_Tab_injury' => 'Symptoms',
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
    'LANG_Tab_weather help' => 'Please provide an indication of weather, in the last week, where the injury was seen.',
    'ICPVeg Weather Temp' => 'What was the maximum daily temperature on average?',
    'ICPVeg Weather Rain' => 'On how many days was there rain?',
    // Pollution.
    'LANG_Tab_pollution' => 'Ozone',
    'LANG_Tab_pollution help' => 'If you are aware of ozone concentrations in the area, in the last week, please answer the following questions.',
    'ICPVeg Ozone Maximum' => 'What was the maximum ozone concentration?',
    'ICPVeg Ozone Persistence' => 'On how many days has the ozone concentration exceeded 50 ppb?',
    )
);