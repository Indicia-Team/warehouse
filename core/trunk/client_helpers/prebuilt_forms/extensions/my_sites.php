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
    if (!function_exists('iform_ajaxproxy_url'))
      return 'An AJAX Proxy module must be enabled for the My Sites Form to work.';
    $r = "<fieldset><legend>" . lang::get('Find additional sites to store in your sites list') . "</legend>";
    if (empty($options['locationTypes']) || !preg_match('/^([0-9]+,( )?)*[0-9]+$/', $options['locationTypes']))
      return 'The My sites form is not correctly configured. Please provide the location types to allow search by.';
    $locationTypes = explode(',', str_replace(' ', '', $options['locationTypes']));
    if (empty($options['locationTypeResults']) || !preg_match('/^([0-9]+,( )?)*[0-9]+$/', $options['locationTypeResults']))
      return 'The My sites form is not correctly configured. Please provide the location types to allow results to be returned for.';
    if (empty($options['mySitesPsnAttrId']) || !preg_match('/^[0-9]+$/', $options['mySitesPsnAttrId']))
      return 'The My sites form is not correctly configured. Please provide the person attribute ID used to store My Sites.';
    $localityOpts = array(
      'fieldname' => 'locality_id',
      'id' => 'locality_id',
      'extraParams' => $auth['read'] + array('orderby' => 'name'),
      'blankText'=>'<' . lang::get('all') . '>',
      'suffixTemplate' => 'nosuffix'
    );
    if (count($locationTypes)>1) {
      $r .= '<label>'.lang::get('Select site by type then locality:').'</label> ';
      $r .= data_entry_helper::select(array(
        'fieldname' => 'location_type_id',
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => $auth['read'] + array('orderby' => 'term', 'query' => urlencode(json_encode(array('in'=>array('id', $locationTypes))))),
        'blankText'=>'<' . lang::get('please select') . '>',
        'suffixTemplate' => 'nosuffix'
      ));
      // link the locality select to the location type select
      $localityOpts = array_merge(array(
        'parentControlId' => 'location_type_id',
        'parentControlLabel' => lang::get('Site type to search'),
        'filterField' => 'location_type_id',
        'filterIncludesNulls' => false,
        'emptyFilterIsUnfiltered' => true
      ), $localityOpts);
    } 
    else {
      $r .= '<label>'.lang::get('Select site by locality').'</label> ';
      // no need for a locality select, so just filter to the location type
      $localityOpts['extraParams']['location_type_id'] = $locationTypes[0];
      $localityOpts['default'] = hostsite_get_user_field('location');
    }
    $r .= data_entry_helper::location_select($localityOpts);
    $r .= data_entry_helper::location_select(array(
      'id' => 'location-select',
      'report' => 'library/locations/locations_for_my_sites',
      'table' => '',
      'valueField' => 'location_id',
      'captionField' => 'q',
      'extraParams' => $auth['read'] + array('location_type_ids'=>$options['locationTypeResults'], 'locattrs'=>'', 
          'user_id' => hostsite_get_user_field('indicia_user_id'), 'person_site_attr_id'=>$options['mySitesPsnAttrId'], 'hide_existing' => 1),
      'parentControlId' => 'locality_id',
      'parentControlLabel' => lang::get('Locality to search'),
      'filterField' => 'parent_id',
      'filterIncludesNulls' => false,
      'blankText'=>'<' . lang::get('please select') . '>',
      'suffixTemplate' => 'nosuffix'
    ));
    $r .= '<button id="add-site-button" type="button">' . lang::get('Add to My Sites') . '</button><br/>';
    $r .= data_entry_helper::location_autocomplete(array(
      'id' => 'location-search',
      'label' => lang::get('<strong>Or</strong> search for a site'),
      'report' => 'library/locations/locations_for_my_sites',
      'table' => '',
      'valueField' => 'location_id',
      'captionField' => 'q',
      'extraParams' => $auth['read'] + array('location_type_ids'=>$options['locationTypeResults'], 'locattrs'=>'', 
          'user_id' => hostsite_get_user_field('indicia_user_id'), 'person_site_attr_id'=>$options['mySitesPsnAttrId'], 
          'hide_existing' => 1, 'parent_id'=>''),
      'suffixTemplate' => 'nosuffix'
    ));
    $r .= '<button id="add-searched-site-button" type="button">' . lang::get('Add to My Sites') . '</button><br/>';
    $postUrl = iform_ajaxproxy_url(null, 'person_attribute_value');
    data_entry_helper::$javascript .= "
      function addSite(locationId) {
        if (!isNaN(locationId) && locationId!=='') {
          $.post('$postUrl', 
            {\"website_id\":".$args['website_id'].",\"person_attribute_id\":".$options['mySitesPsnAttrId'].
                ",\"user_id\":".hostsite_get_user_field('indicia_user_id').",\"int_value\":locationId} ,
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
      }
      $('#add-site-button').click(function() {
        addSite($('#location-select').val());
        if (!isNaN($('#location-select').val())) {
          $('#location-select option:selected').remove();
        }
      });
      $('#add-searched-site-button').click(function() {addSite($('#location-search').val());});
      $('#location-select, #location-search, #locality_id').change(function() {
        indiciaData.mapdiv.locationSelectedInInput(indiciaData.mapdiv, this.value);
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
  
  /**
   * A select box allowing you to pick one of your sites.
   * Supply @personSiteAttrId as an option to give the ID of the person custom attribute used
   * to link people to their sites.
   */
  public function my_sites_select($auth, $args, $tabalias, $options, $path) {
    $location_list_args=array_merge_recursive(array(
      'extraParams'=>array_merge(array('orderby'=>'name'), $auth['read'])
    ), $options);
    if (!isset($location_list_args['label']))
      $location_list_args['label'] = lang::get('Select site');
    $userId = hostsite_get_user_field('indicia_user_id');
    if (!empty($userId)) {
      if (!empty($options['personSiteAttrId'])) {
        $location_list_args['extraParams']['user_id']=$userId;
        $location_list_args['extraParams']['person_site_attr_id']=$options['personSiteAttrId'];
        $location_list_args['report'] = 'library/locations/my_sites_lookup';
      } else 
        $location_list_args['extraParams']['created_by_id']=$userId;
    }
    $location_list_args['extraParams']['view']='detail';
    $location_list_args['allowCreate']=true;
    return data_entry_helper::location_autocomplete($location_list_args);
  }
  
}


?>