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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */
 
require_once('includes/user.php');
require_once('includes/groups.php');

/**
 * Prebuilt Indicia data form that provides an import wizard
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_importer {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_importer_definition() {
    return array(
      'title'=>'Importer',
      'category' => 'Utilities',
      'description'=>'A page containing a wizard for uploading CSV file data.',
      'helpLink'=>'https://readthedocs.org/projects/indicia-docs/en/latest/site-building/iform/prebuilt-forms/importer.html',
      'supportsGroups'=>true
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array(
      array(
        'name'=>'model',
        'caption'=>'Type of data to import',
        'description'=>'Select the type of data that each row represents in the file you want to import.',
        'type'=>'select',
        'options'=>array(
          'url' => 'Use setting in URL (&type=...)',
          'occurrence' => 'Species Occurrences',
          'location' => 'Locations'
        ),
        'required'=>true
      ),
      array(
        'name'=>'presetSettings',
        'caption'=>'Preset Settings',
        'description'=>'Provide a list of predetermined settings which the user does not need to specify, one on each line in the form name=value. '.
            'The preset settings available are those which are available for input on the first page of the import wizard, depending on the table you '.
            'are inputting data for. You can use the following replacement tokens in the values: {user_id}, {username}, {email} or {profile_*} (i.e. any '.
            'field in the user profile data).',
        'type'=>'textarea',
        'required'=>false
      )
    );
  }

  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $node Drupal node object
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $node, $response) {
    iform_load_helpers(array('import_helper'));
    $auth = import_helper::get_read_write_auth($args['website_id'], $args['password']);
    group_authorise_form($node, $auth['read']);
    if ($args['model']=='url') {
      if (!isset($_GET['type']))
        return "This form is configured so that it must be called with a type parameter in the URL";
      $model = $_GET['type'];
    } else
      $model = $args['model'];
    if (isset($args['presetSettings'])) {
      $presets = get_options_array_with_user_data($args['presetSettings']);
      $presets = array_merge(array('website_id'=>$args['website_id'], 'password'=>$args['password']), $presets);
    } else {
      $presets = array('website_id'=>$args['website_id'], 'password'=>$args['password']);
    }
    
    if (!empty($_GET['group_id'])) {
      // loading data into a recording group. 
      $group = data_entry_helper::get_population_data(array(
        'table'=>'group',
        'extraParams'=>$auth['read'] + array('id'=>$_GET['group_id'], 'view'=>'detail')
      ));
      $group = $group[0];
      $presets['sample:group_id'] = $_GET['group_id'];      
      hostsite_set_page_title(lang::get('Import data into the {1} group', $group['title']));
      // if a single survey specified for this group, then force the data into the correct survey
      $filterdef = json_decode($group['filter_definition'], true);
      if (!empty($filterdef['survey_list_op']) && $filterdef['survey_list_op']==='in' && !empty($filterdef['survey_list'])) {
        $surveys = explode(',', $filterdef['survey_list']);
        if (count($surveys)===1)
          $presets['survey_id'] = $surveys[0];
      }
    }
    $r = import_helper::importer(array(
      'model' => $model,
      'auth' => $auth,
      'presetSettings' => $presets
    ));
    return $r;
  }

}