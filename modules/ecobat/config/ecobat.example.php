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
 * @package	Modules
 * @subpackage Ecobat
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

$config['website_id'] = 123;
$config['survey_id'] = 123;
$config['sample_attrs'] = array(
  'detector_make_id' => 'smpAttr:123',
  'detector_make_other' => 'smpAttr:123',
  'detector_model' => 'smpAttr:123',
  'detector_height_m' => 'smpAttr:123',
  'roost_within_25m' => 'smpAttr:123',
  'activity_elevated_by_roost' => 'smpAttr:123',
  'linear_feature_adjacent_id' => 'smpAttr:123',
  'linear_feature_25m_id' => 'smpAttr:123',
  'anthropogenic_feature_adjacent_id' => 'smpAttr:123',
  'anthropogenic_feature_25m_id integer' => 'smpAttr:123',
  'temperature_c' => 'smpAttr:123',
  'rainfall_id' => 'smpAttr:123',
  'wind_speed' => 'smpAttr:123',
  'wind_speed_unit_id' => 'smpAttr:123'
);
$config['occurrence_attrs'] = array(
  'passes' => 'occAttr:123',
  'pass_definition_id' => 'occAttr:123'
);