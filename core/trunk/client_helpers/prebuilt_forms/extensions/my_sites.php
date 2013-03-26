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
 * Extension class that supplies new controls to support My Sites selection for dynamic forms.
 */
class extension_my_sites {

  public static function my_sites_form($auth, $args, $tabalias, $options, $path) {
    $r .= "<fieldset><legend>" . lang::get('Find additional sites to store in your sites list') . "</legend>";
    if (empty($options['locationTypes']) || !preg_match('/^([0-9]+,( )?)*[0-9]+$/', $options['locationTypes']))
      return 'The My sites form is not correctly configured. Please provide the location types to allow search by.';
    $locationTypes = explode(',', str_replace(' ', '', $options['locationTypes']));
    if (empty($options['locationTypeResults']) || !preg_match('/^([0-9]+,( )?)*[0-9]+$/', $options['locationTypeResults']))
      return 'The My sites form is not correctly configured. Please provide the location types to allow results to be returned for.';
    if (empty($options['mySitesPsnAttrId']) || !preg_match('/^[0-9]+$/', $options['mySitesPsnAttrId']))
      return 'The My sites form is not correctly configured. Please provide the person attribute ID used to store My Sites.';
    $localityOpts = array(
      'label' => lang::get('Locality to search'),
      'fieldname' => 'locality_id',
      'id' => 'locality_id',
      'extraParams' => $auth['read']
    );
    if (count($locationTypes)>1) {
      $r .= data_entry_helper::select(array(
        'label' => lang::get('Site type to search'),
        'fieldname' => 'location_type_id',
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => $auth['read'] + array('query' => urlencode(json_encode(array('in'=>array('id', $locationTypes)))))
      ));
      // link the locality select to the location type select
      $localityOpts = array_merge(array(
        'parentControlId' => 'location_type_id',
        'parentControlLabel' => lang::get('Site type to search'),
        'filterField' => 'location_type_id',
        'filterIncludesNulls' => false
      ), $localityOpts);
    } 
    else {
      // no need for a locality select, so just filter to the location type
      $localityOpts['extraParams']['location_type_id'] = $locationTypes[0];
    }
    $r .= data_entry_helper::location_select($localityOpts);
    $r .= data_entry_helper::location_select(array(
      'label' => lang::get('Select site'),
      'report' => 'library/locations/locations_for_my_sites',
      'table' => '',
      'valueField' => 'location_id',
      'extraParams' => $auth['read'] + array('location_type_ids'=>$options['locationTypeResults'], 'locattrs'=>'', 
          'user_id' => hostsite_get_user_field('indicia_user_id'), 'person_site_attr_id'=>$options['mySitesPsnAttrId'], 'hide_existing' => 1),
      'parentControlId' => 'locality_id',
      'parentControlLabel' => lang::get('Locality to search'),
      'filterField' => 'parent_id',
      'filterIncludesNulls' => false
    ));
    $r .= '<button id="add-site-button" type="button">' . lang::get('Add to My Sites') . '</button>';
    $postUrl = iform_ajaxproxy_url($node, 'person_attribute_value');
    data_entry_helper::$javascript .= "
      $('#add-site-button').click(function() {
        $.post('$postUrl', 
          {\"website_id\":".$args['website_id'].",\"person_attribute_id\":".$options['mySitesPsnAttrId'].
              ",\"user_id\":".hostsite_get_user_field('indicia_user_id').",\"int_value\":$('#imp-location').val()} ,
          function (data) {
            if (typeof data.error === 'undefined') {
              indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
            } else {
              alert(data.error);
            }
          },
          'json'
        );
      });
      
      linked_site_delete = function(pav_id) {
        var userId=".hostsite_get_user_field('indicia_user_id').";
        $.post('$postUrl', 
          {\"website_id\":".$args['website_id'].",\"id\":pav_id, \"deleted\":\"t\"},
          function (data) {
            if (typeof data.error === 'undefined') {
              indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
            } else {
              alert(data.error);
            }
          },
          'json'
        );
      }
    ";
    return $r;
  }
  
}


?>