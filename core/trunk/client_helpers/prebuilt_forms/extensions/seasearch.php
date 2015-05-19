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
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Extension class that supplies new controls to support recording of dives.
 */
class extension_seasearch {

  /**
   * Returns a control that allows the setting of the centre of a dive, plus optional
   * start and end points if a drift dive.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options
   * - drift_start_attr_id
   * - drift_end_attr_id
   * @param $path
   * @return string
   */
  public static function drift_dive_position_entry($auth, $args, $tabalias, $options, $path, $attributes) {
    if (empty($options['drift_start_attr_id']) || empty($options['drift_end_attr_id']))
      return 'The seasearch.drift_dive_position_entry control requires @drift_start_attr_id ' .
          'and @drift_start_attr_id options to be supplied.';
    $options = array_merge(array(
      'systems' => array(
        '4326'=>'Latitude and longitiude (degrees and decimal minutes WGS84)',
        '4277'=>'Latitude and longitiude (degrees and decimal minutes OSGB36)',
        'OSGB' => 'Ordnance Survey British National Grid'
      )
    ), $options);
    $centreTokens = self::getCentreTokens();
    foreach ($attributes as $attribute) {
      if (preg_match("/^smpAttr:$options[drift_start_attr_id](:\d+)?$/", $attribute['fieldname']))
        $driftStartDefault = $attribute['default'];
      if (preg_match("/^smpAttr:$options[drift_end_attr_id](:\d+)?$/", $attribute['fieldname']))
        $driftEndDefault = $attribute['default'];
    }
    $regexToExtractPartsOfLatLong = '/(?P<latdeg>\d+):(?P<latmin>\d+(\.\d+)?)N, (?P<longdeg>\d+):(?P<longmin>\d+(\.\d+)?)(?P<longdir>[EW])/';
    preg_match($regexToExtractPartsOfLatLong, $driftStartDefault, $driftStartTokens);
    preg_match($regexToExtractPartsOfLatLong, $driftEndDefault, $driftEndTokens);
    // fill in defaults to make code cleaner later
    $driftStartTokens = array_merge(
      array('latdeg'=>'', 'latmin'=>'', 'longdeg'=>'', 'longmin'=>'', 'longdir'=>''),
      $driftStartTokens);
    $driftEndTokens = array_merge(
      array('latdeg'=>'', 'latmin'=>'', 'longdeg'=>'', 'longmin'=>'', 'longdir'=>''),
      $driftEndTokens);
    // Add a GPS datum or grid system selection control
    $r = '<label class="auto">Position format and datum '.
      data_entry_helper::sref_system_select(array(
        'fieldname'=>'sample:entered_sref_system',
        'systems'=>$options['systems']
      )) . '</label>';
    $r .= '<div><div id="input-ll-container"><p>'.lang::get('Position (degrees and decimal minutes)').'</p>';
    $r .= '<table id="position-data"><thead><th colspan="2"></th><th colspan="2">'.lang::get('Latitude').'</th><th colspan="2">'.lang::get('Longitude').
      '</th><th>'.lang::get('W or E').'</th><tr></tr></thead>';
    $r .= '<tbody><tr id="input-centre"><td>'.lang::get('Centre of site').'</td>';
    $r .= "<td><input type=\"radio\" title=\"".lang::get('Select this option then click on the map to set the dive centre')."\" name=\"which-point\" value=\"centre\" checked=\"checked\"/></td>";
    $r .= "<td><input id=\"input-lat-deg\" class=\"input-lat input-deg {required: true,pattern:/^[0-9]*$/}\" type=\"text\" value=\"$centreTokens[latdeg]\"/>&deg;</td>";
    $r .= "<td class=\"td-pad\"><input id=\"input-lat-min\" class=\"input-lat input-min {required: true,pattern:/^[0-9]+(.[\d]+)?$/}\" type=\"text\" value=\"$centreTokens[latmin]\"/>N</td>";
    $r .= "<td><input id=\"input-long-deg\" class=\"input-long input-deg {required: true,pattern:/^[0-9]*$/}\" type=\"text\" value=\"$centreTokens[longdeg]\"/>&deg;</td>";
    $r .= "<td><input id=\"input-long-min\" class=\"input-long input-min {required: true,pattern:/^[0-9]+(.[\d]+)?$/}\" type=\"text\" value=\"$centreTokens[longmin]\"/></td>";
    $r .= '<td>'.data_entry_helper::select(array('lookupValues'=>array('E'=>'E','W'=>'W'), 'blankText'=>lang::get('choose'),
        'fieldname'=>'e-w', 'default'=>$centreTokens['longdir'])).'</td>';
    $r .= '</tr>';
    $r .= '<tr><td colspan="6">'.lang::get('For drift dives').'</td></tr>';
    $r .= '<tr id="input-drift-from"><td>'.lang::get('From').'</td>';
    $r .= "<td><input type=\"radio\" title=\"".lang::get('Select this option then click on the map to set the dive start')."\" name=\"which-point\" value=\"from\" /></td>";
    $r .= "<td><input id=\"input-lat-deg-from\" class=\"input-lat input-deg {pattern:/^[0-9]*$/}\" type=\"text\" value=\"$driftStartTokens[latdeg]\"/>&deg;</td>";
    $r .= "<td class=\"td-pad\"><input id=\"input-lat-min-from\" class=\"input-lat input-min {pattern:/^[0-9]+(.[\d]+)?$/}\" type=\"text\" value=\"$driftStartTokens[latmin]\"/>N</td>";
    $r .= "<td><input id=\"input-long-deg-from\" class=\"input-long input-deg {pattern:/^[0-9]*$/}\" type=\"text\" value=\"$driftStartTokens[longdeg]\"/>&deg;</td>";
    $r .= "<td><input id=\"input-long-min-from\" class=\"input-long input-min {pattern:/^[0-9]+(.[\d]+)?$/}\" type=\"text\" value=\"$driftStartTokens[longmin]\"/></td>";
    $r .= '<td>'.data_entry_helper::select(array('lookupValues'=>array('E'=>'E','W'=>'W'), 'blankText'=>lang::get('choose'),
        'fieldname'=>'e-w-from', 'default'=>$driftStartTokens['longdir'])).'</td>';
    $r .= '</tr>';
    $r .= '<tr id="input-drift-to"><td>'.lang::get('To').'</td>';
    $r .= "<td><input type=\"radio\" title=\"".lang::get('Select this option then click on the map to set the dive end')."\" name=\"which-point\" value=\"to\" />";
    $r .= "<td><input id=\"input-lat-deg-to\" class=\"input-lat input-deg {pattern:/^[0-9]*$/}\" type=\"text\" value=\"$driftEndTokens[latdeg]\"/>&deg;</td>";
    $r .= "<td class=\"td-pad\"><input id=\"input-lat-min-to\" class=\"input-lat input-min {pattern:/^[0-9]+(.[\d]+)?$/}\" type=\"text\" value=\"$driftEndTokens[latmin]\"/>N</td>";
    $r .= "<td><input id=\"input-long-deg-to\" class=\"input-long input-deg {pattern:/^[0-9]*$/}\" type=\"text\" value=\"$driftEndTokens[longdeg]\"/>&deg;</td>";
    $r .= "<td><input id=\"input-long-min-to\" class=\"input-long input-min {pattern:/^[0-9]+(.[\d]+)?$/}\" type=\"text\" value=\"$driftEndTokens[longmin]\"/></td>";
    $r .= '<td>'.data_entry_helper::select(array('lookupValues'=>array('E'=>'E','W'=>'W'), 'blankText'=>lang::get('choose'),
        'fieldname'=>'e-w-to', 'default'=>$driftEndTokens['longdir'])).'</td>';
    $r .= '</tr>';
    $r .= '</tbody></table></div>';
    $r .= '<label id="input-os-grid-container">OS Grid Reference<input id="input-os-grid" type="text"/></label>';
    $default = empty(data_entry_helper::$entity_to_load['sample:entered_sref']) ? '' : data_entry_helper::$entity_to_load['sample:entered_sref'];
    $r .= "<input type=\"hidden\" name=\"sample:entered_sref\" id=\"imp-sref\" value=\"$default\" />";
    // Pass the drift start and end  attribute IDs to JS so the values can be synced to the visible controls
    data_entry_helper::$javascript .= "indiciaData.driftStartAttrFieldname='smpAttr:$options[drift_start_attr_id]';\n";
    $r .= "<input type=\"hidden\" name=\"smpAttr:$options[drift_start_attr_id]\" id=\"smpAttr:$options[drift_start_attr_id]\" value=\"$driftStartDefault\"/>";
    data_entry_helper::$javascript .= "indiciaData.driftEndAttrFieldname='smpAttr:$options[drift_end_attr_id]';\n";
    $r .= "<input type=\"hidden\" name=\"smpAttr:$options[drift_end_attr_id]\" name=\"smpAttr:$options[drift_end_attr_id]\"  value=\"$driftEndDefault\"/>";
    $r .= '</div>';
    return $r;
  }

  /**
   * If there is an existing sample, use the entered sref to build the array of tokens required for the centre position controls.
   * @return array Array of tokens for control default values.
   */
  private static function getCentreTokens() {
    $r = array();
    if (!empty(data_entry_helper::$entity_to_load['sample:entered_sref'])) {
      // If a decimal lat long, decode this to degrees + decimal minutes
      if (preg_match('/(?P<latdeg>\d+).(?P<latdec>\d+)N,? (?P<longdeg>\d+).(?P<longdec>\d+)(?P<longdir>[EW])/',
        data_entry_helper::$entity_to_load['sample:entered_sref'], $r)) {
        // convert the decimal values into decimal minutes in the $r array
        $r['latmin'] = ('0.' + $r['latdec'])*60;
        $r['longmin'] = ('0.'+$r['longdec'])*60;
      } else {
        preg_match('/(?P<latdeg>\d+):(?P<latmin>\d+(\.\d+)?)N,? (?P<longdeg>\d+):(?P<longmin>\d+(\.\d+)?)(?P<longdir>[EW])/',
          data_entry_helper::$entity_to_load['sample:entered_sref'], $r);
      }
    }
    $r = array_merge(
      array('latdeg'=>'', 'latmin'=>'', 'longdeg'=>'', 'longmin'=>'', 'longdir'=>''),
      $r);
    return $r;
  }

}