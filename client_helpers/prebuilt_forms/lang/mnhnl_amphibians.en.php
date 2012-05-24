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

// Translation strings for when in Amphibian mode, on top of reptile ones.
// this file should be copied to the file 'node.<nid>.en.php';
global $custom_term_overrides;
$custom_term_overrides[] = array(
	// 'Edit' is left unchanged
	'LANG_Shapefile_Download'=> 'This download provides a zipped up shape file for the locations - there is a single point per site. Click to select:'
	,'LANG_LocModTool_Instructions2'=>"Either click on the map (ensuring that the select tool on the map is active) to select the site you wish to modify, or choose from the drop down list. You may then change its name, or modify or add Points, Lines, or Polygons to define the site shape. You must choose the correct draw tool on the map for each of these. You may drag the highlighted vertices. To delete a vertex, place the mouse over the vertex and press the 'd' or 'Delete' buttons.<br />You can't create a new site using this tool - that has to be done within the survey data entry itself."
	,'LANG_DE_Instructions2'=>"To choose a site, either click the relevant site on the map (ensuring that the select tool on the map is active) or pick it from the drop down list (then the selected site is highlighted in blue on the map).<br />You may add a new site: click the 'Start a new site' button on the map, and click on the map. You can change the position by dragging the circle.<br />Selecting an existing site will remove any new site.<br />It is not possible to change a site name or position on this form once it has been saved - this can be done by an Admin user using their special tool."
	,'LANG_Location_X_Label' => 'Site coordinates: X'
	,'LANG_PointTooltip'=>'Click on map to set site position'
	,'LANG_SpeciesInstructions'=>"Additional species may be added using the control under the grid.<br />Additional rows may be added using the control for existing taxa if a different combination of Type/Stage/Sex is to be added.<br />There are various combinations Type/Stage/Sex/Behaviour which are not allowed. Such banned combinations will be greyed out in the drop down lists. In addition, it is not possible to enter multiple rows for the same combination of Species/Type/Stage/Sex: again duplicate possiblities will be greyed out.<br />If you think a combination is valid, but you can not select it, first check that there is no other existing row with this combination.<br />Click the red 'X' to delete the relevant row."
	,'validation_no_record' => "The <strong>Recording summary</strong> must reflect the table of species. When no data is recorded then either 'No records taken' or 'No observation' must be checked."
	,'Amphibian Visit (Sites)'=>'Visit'
	,'Amphibian Sites Survey Method'=>'Amphibian Sites Survey Method'
	,'Amphibian Type (Sites)'=>'Type'
	,'Amphibian Stage (Sites)'=>'Stage'
	,'Amphibian Behaviour'=>'Behaviour'
	,'Amphibian Recording Summary'=>'Recording summary'
	,'LANG_PositionOutsideCommune' => 'The position you have chosen is outside the set of allowable Communes.'
	,'LANG_PositionInDifferentCommune' => 'The position you have chosen is outside the selected Commune. Do you wish to change the Commune field to match the point?'
	
);
?>