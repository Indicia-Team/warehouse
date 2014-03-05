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
 * @package  Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link    http://code.google.com/p/indicia/
 */

require_once('includes/map.php');
require_once('includes/user.php');
require_once('includes/language_utils.php');

/**
 * Prebuilt Indicia data entry form that presents taxon search box, date control, map picker,
 * survey selector and comment entry controls.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_mnhnl_citizen_science_1 {

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
      iform_user_get_user_parameters(),
      array(
        array(
          'name'=>'interface',
          'caption'=>'Interface Style Option',
          'description'=>'Choose the style of user interface, either dividing the form up onto separate tabs, '.
              'wizard pages or having all controls on a single page.',
          'type'=>'select',
          'options' => array(
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page'
          ),
          'group' => 'User Interface'
        ),
        array(
          'name'=>'species_ctrl',
          'caption'=>'Species Control Type',
          'description'=>'The type of control that will be available to select a species.',
          'type'=>'select',
          'options' => array(
            'autocomplete' => 'Autocomplete',
            'select' => 'Select',
            'listbox' => 'List box',
            'radio_group' => 'Radio group',
            'treeview' => 'Treeview',
            'tree_browser' => 'Tree browser'
          ),
          'group'=>'User Interface'
        ),
        array(
          'name' => 'restrict_species_to_users_lang',
          'caption' => 'Restrict species by user\'s language',
          'description' => 'Only show species that are have common names in the user\'s selected language.',
          'type' => 'boolean',
          'group'=>'User Interface'
        ),
        array(
          'name'=>'preferred',
          'caption'=>'Preferred species only?',
          'description'=>'Should the selection of species be limited to preferred names only?',
          'type'=>'boolean',
          'group'=>'User Interface'
        ),
        array(
          'name'=>'abundance_ctrl',
          'caption'=>'Abundance Control Type',
          'description'=>'The type of control that will be available to select the approximate abundance.',
          'type'=>'select',
          'options' => array(
            'select' => 'Select',
            'listbox' => 'List box',
            'radio_group' => 'Radio group'
          ),
          'group'=>'User Interface'
        ),
        array(
          'name'=>'abundance_overrides',
          'caption'=>'Abundance Overrides by Species',
          'description'=>'If a species should not use the default abundance attribute, list each species preferred name on a separate line, followed '.
              'by a colon then the attribute IDs, comma separated. This only works when loading the form with a preset species in the URL.',
          'type'=>'textarea',
          'group'=>'User Interface',
          'required'=>false
        ),
        array(
          'name'=>'list_id',
          'caption'=>'Species List ID',
          'description'=>'The Indicia ID for the species list that species can be selected from.',
          'type'=>'int',
          'group'=>'Misc'
        ),
        array(
          'name'=>'survey_id',
          'caption'=>'Survey ID',
          'description'=>'The Indicia ID for the survey that data is saved into.',
          'type'=>'int',
          'group'=>'Misc'
        ),
        array(
          'name'=>'record_status',
          'caption'=>'Record Status',
          'description'=>'The initial record status for saved records.',
          'type'=>'select',
          'options' => array(
            'C' => 'Records are flagged as data entry complete.',
            'I' => 'Records are flagged as data entry in progress.',
            'T' => 'Records are flagged as for testing purposes.'
          ),
          'default' => 'C',
          'group'=>'Misc'
        ),
        array(
          'name'=>'first_name_attr_id',
          'caption'=>'First Name Attribute ID',
          'description'=>'Indicia ID for the sample attribute that stores the user\'s first name.',
          'type'=>'smpAttr',
          'group'=>'Sample Attributes'
        ),
        array(
          'name'=>'surname_attr_id',
          'caption'=>'Surname Attribute ID',
          'description'=>'Indicia ID for the sample attribute that stores the user\'s surname.',
          'type'=>'smpAttr',
          'group'=>'Sample Attributes'
        ),
        array(
          'name'=>'phone_attr_id',
          'caption'=>'Phone Attribute ID',
          'description'=>'Indicia ID for the sample attribute that stores the user\'s phone.',
          'type'=>'smpAttr',
          'group'=>'Sample Attributes'
        ),
        array(
          'name'=>'contact_attr_id',
          'caption'=>'Contactable Attribute ID',
          'description'=>'Indicia ID for the sample attribute that if the user has opted in for being contacted regarding this record.',
          'type'=>'smpAttr',
          'group'=>'Sample Attributes'
        ),
        array(
          'name'=>'abundance_attr_id',
          'caption'=>'Abundance Attribute ID',
          'description'=>'Indicia ID for the occurrence attribute that records the approximate abundance.',
          'type'=>'occAttr',
          'group'=>'Occurrence Attributes'
        ),
        array(
          'name'=>'abundance_termlist_id',
          'caption'=>'Abundance Termlist ID',
          'description'=>'Indicia ID for the termlist that contains the options to select from when specifying the approximate abundance.',
          'type'=>'termlist',
          'group'=>'Termlists'
        ),
        array(
          'name'=>'spatial_systems',
          'caption'=>'Allowed Spatial Ref Systems',
          'description'=>'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326). '.
              'Set to "default" to use the settings defined in the IForm Settings page.',
          'type'=>'string',
          'group'=>'Misc'
        )
      )
    );
  }

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_mnhnl_citizen_science_1_definition() {
    return array(
      'title'=>self::get_title(),
      'category' => 'MNHNL forms',      
      'description'=>'MNHNL Citizen Science 1 form - form designed for citizen science projects.'
    );
  }

  /*
   * Return the form title.
   * @return string The title of the form.
   */
  public static function get_title() {
    return 'MNHNL Citizen Science 1';
  }

  /**
   * Return the generated form output.
   * @return Form HTML.
   */
  public static function get_form($args) {

    global $user;
    $logged_in = $user->uid>0;
    // Get authorisation tokens to update and read from the Warehouse.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $readAuth = $auth['read'];
    // enable image viewing with FancyBox - only required if the file box is enabled
    //data_entry_helper::$javascript .= "jQuery(\"a.fancybox\").fancybox();\n";
    $r = "\n<form method=\"post\" id=\"entry_form\">\n";
    if (isset($_GET['taxa_taxon_list_id']) || isset($_GET['taxon_external_key'])) {
      if (isset($_GET['taxa_taxon_list_id']))
        $filter = array('id' => $_GET['taxa_taxon_list_id']);
      else
        $filter = array('external_key' => $_GET['taxon_external_key']);
      $species = data_entry_helper::get_population_data(array(
        'table'=>'taxa_taxon_list',
        'extraParams' => $readAuth + $filter + array('taxon_list_id' => $args['list_id'], 'view' => 'detail', 'preferred' => 't')
      ));
      // we need only one result, but there could be more than one picture, therefore multiple rows
      $uniqueMeaning=false;
      if (count($species)==1) {
        $uniqueMeaning=$species[0]['taxon_meaning_id'];
      }
      if (count($species)>1) {
        $uniqueMeaning=$species[0]['taxon_meaning_id'];
        foreach($species as $item) {
          if ($item['taxon_meaning_id']!=$uniqueMeaning)
            $uniqueMeaning = false;
        }
      }
      if ($uniqueMeaning) {
        // now we have the meaning_id, we need to fetch the actual species in the chosen common name
        $speciesCommon = data_entry_helper::get_population_data(array(
            'table'=>'taxa_taxon_list',
            'extraParams' => $readAuth + array('taxon_meaning_id' => $uniqueMeaning,
                'language_iso' => iform_lang_iso_639_2($user->lang), 'view' => 'detail')
        ));
        $r .= '<div class="ui-widget ui-widget-content ui-corner-all page-notice ui-helper-clearfix">';
        $nameString = ($species[0]['language_iso']=='lat' ? '<em>' : '') . $species[0]['taxon'] . ($species[0]['language_iso']=='lat' ? '</em>' : '');
        if (count($speciesCommon)>=1)
          // use a common name if we have one
          $nameString = $speciesCommon[0]['taxon'] . ' (' . $nameString . ')';
        if (!empty($species[0]['description_in_list'])) {
          $r .= '<div class="page-notice">'.lang::get('you are recording a', $nameString).'</div>';
        }
        $taxa_taxon_list_id=$species[0]['id'];
        $images_path = data_entry_helper::$base_url.
              (isset(data_entry_helper::$indicia_upload_path) ? data_entry_helper::$indicia_upload_path : 'upload/');
        foreach($species as $item) {
          if (!empty($item['image_path'])) {
            $r .= '<a class="fancybox left" href="'.$images_path.$item['image_path'].'" style="margin: 0 1em 1em;">';
            $r .= '<img width="100" src="'.$images_path.'thumb-'.$item['image_path'].'" />';
            $r .= '</a>';
          }
        }
        if (!empty($species[0]['description_in_list'])) {
          $r .= '<p>'.$species[0]['description_in_list']."</p>";
        } else {
          $r .= '<p>'.lang::get('you are recording a', $nameString).'</p>';
        }
        $r .= "</div>\n";
      } else {
        $r .= "<p>The species not be identified uniquely from the URL parameters.</p>\n";
      }
    }


    // request automatic JS validation
    data_entry_helper::enable_validation('entry_form');

    $r .= "<div id=\"controls\">\n";

    if ($args['interface']!='one_page') {
      $r .= "<ul>\n";
      if (!$logged_in) {
        $r .= '  <li><a href="#about_you"><span>'.lang::get('about you')."</span></a></li>\n";
      }
      if (!isset($taxa_taxon_list_id)) {
        $r .= '  <li><a href="#species"><span>'.lang::get('what did you see')."</span></a></li>\n";
      }
      $r .= '  <li><a href="#place"><span>'.lang::get('where was it')."</span></a></li>\n";
      $r .= '  <li><a href="#other"><span>'.lang::get('other information')."</span></a></li>\n";
      $r .= "</ul>\n";
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface']
      ));
    }
    if ($user->uid==0) {
      $r .= "<fieldset id=\"about_you\">\n";
      $r .= '<p class="page-notice ui-state-highlight ui-corner-all">'.lang::get('about you tab instructions')."</p>";
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('first name'),
        'fieldname'=>'smpAttr:'.$args['first_name_attr_id'],
        'class'=>'control-width-4'
      ));
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('surname'),
        'fieldname'=>'smpAttr:'.$args['surname_attr_id'],
        'class'=>'control-width-4'
      ));
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('email'),
        'fieldname'=>'smpAttr:'.$args['email_attr_id'],
        'class'=>'control-width-4'
      ));
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('phone number'),
        'fieldname'=>'smpAttr:'.$args['phone_attr_id'],
        'class'=>'control-width-4'
      ));
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>'first'
        ));
      }
      $r .= "</fieldset>\n";
    }
    // the species tab is ommitted if the page is called with a taxon in the querystring parameters
    if (isset($taxa_taxon_list_id)) {
      $r .= "<input type=\"hidden\" name=\"occurrence:taxa_taxon_list_id\" value=\"$taxa_taxon_list_id\"/>\n";
    } else {
      $r .= "<fieldset id=\"species\">\n";
      $r .= '<p class="page-notice ui-state-highlight ui-corner-all">'.lang::get('species tab instructions')."</p>";
      $extraParams = $readAuth + array('taxon_list_id' => $args['list_id']);
      if ($args['preferred']) {
        $extraParams += array('preferred' => 't');
      }
      if ($args['restrict_species_to_users_lang']) {
        $extraParams += array('language_iso' => iform_lang_iso_639_2($user->lang));
      }
      $species_list_args=array(
          'label'=>lang::get('occurrence:taxa_taxon_list_id'),
          'fieldname'=>'occurrence:taxa_taxon_list_id',
          'table'=>'taxa_taxon_list',
          'captionField'=>'taxon',
          'valueField'=>'id',
          'columns'=>2,
          'view'=>'detail',
          'parentField'=>'parent_id',
          'extraParams'=>$extraParams
      );
      if ($args['species_ctrl']=='tree_browser') {
        // change the node template to include images
        global $indicia_templates;
        $indicia_templates['tree_browser_node']='<div>'.
            '<img src="'.data_entry_helper::$base_url.'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
            '<span>{caption}</span>';
      }
      // Dynamically generate the species selection control required.
      $r .= call_user_func(array('data_entry_helper', $args['species_ctrl']), $species_list_args);
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>($user->id==0) ? 'first' : 'middle'
        ));
      }
      $r .= "</fieldset>\n";
    }
    $r .= "<fieldset id=\"place\">\n";
    // Output all our hidden data here, because this tab is always present
    $r .= $auth['write'];
    if ($logged_in) {
      // If logged in, output some hidden data about the user
      $r .= iform_user_get_hidden_inputs($args);
    }
    // if the species being recorded is a fixed species defined in the URL, then output a hidden
    if (isset($taxa_taxon_list_id))
      $r .= "<input type=\"hidden\" name=\"occurrence:taxa_taxon_list_id'\" value=\"".$taxa_taxon_list_id."\" />\n";
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"record_status\" value=\"".$args['record_status']."\" />\n";
    $r .= '<p class="page-notice ui-state-highlight ui-corner-all">'.lang::get('place tab instructions')."</p>";
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems=array();
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    $r .= data_entry_helper::georeference_lookup(iform_map_get_georef_options($args, $auth['read']));
    $r .= data_entry_helper::sref_and_system(array(
        'label' => lang::get('sample:entered_sref'),
        'systems' => $systems
    ));
    // retrieve options for the IndiciaMapPanel, and optionally options for OpenLayers.
    $options = iform_map_get_map_options($args, $readAuth);
    $options['tabDiv'] = 'place';
    $olOptions = iform_map_get_ol_options($args);
    $options['scroll_wheel_zoom']=false;
    $r .= data_entry_helper::map_panel($options, $olOptions);
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls',
        'page'=>($user->id==0 && isset($taxa_taxon_list_id)) ? 'first' : 'middle'
      ));
    }
    $r .= "</fieldset>\n";
    $r .= "<fieldset id=\"other\">\n";
    $r .= '<p class="page-notice ui-state-highlight ui-corner-all">'.lang::get('other tab instructions')."</p>";
    $r .= data_entry_helper::date_picker(array(
        'label'=>lang::get('Date'),
        'fieldname'=>'sample:date'
    ));
    $r .= data_entry_helper::file_box(array(
        'caption' => 'Upload your photos',
        'readAuth' => $readAuth,
        'resizeWidth' => 1024,
        'resizeHeight' => 768,
        'table' => 'occurrence_image',
        'tabDiv' => 'other',
        // reduce the number of runtimes, because flash and silverlight don't seem reliable on this form.
        'runtimes' => array('html5','html4')
    ));


    // Dynamically create a control for the abundance, unless overridden for this species
    if (isset($species) && count($species)>0 && trim($args['abundance_overrides'])!=='') {
      $overrides = explode("\n", $args['abundance_overrides']);
      foreach ($overrides as $override) {
        $tokens = explode(':',$override);
        if ($tokens[0]==$species[0]['taxon']) {
          // remove the default abundance attribute behaviour
          $args['abundance_attr_id']='';
          if (trim($tokens[1])!=='') {
            $attrIds = explode(',',$tokens[1]);
            $attributes = data_entry_helper::getAttributes(array(
                'id' => null,
                'valuetable'=>'occurrence_attribute_value',
                'attrtable'=>'occurrence_attribute',
                'key'=>'occurrence_id',
                'fieldprefix'=>"occAttr",
                'extraParams'=>$readAuth + array('query' => urlencode(json_encode(array(
                  'in'=>array('id', $attrIds)
                )))),
              'survey_id'=>$args['survey_id']
            ));
            foreach ($attributes as $attribute) {
              $r .= data_entry_helper::outputAttribute($attribute, array('language' => iform_lang_iso_639_2($user->lang), 'booleanCtrl' => 'checkbox'));
            }

          }
        }
      }
    }
    if (!empty($args['abundance_attr_id'])) {
      $abundance_args = array(
        'label'=>lang::get('abundance'),
        'fieldname'=>'occAttr:'.$args['abundance_attr_id'],
        'table'=>'termlists_term',
        'captionField'=>'term',
        'valueField'=>'id',
        'extraParams'=>$readAuth + array('termlist_id' => $args['abundance_termlist_id']),
        'size'=>6, // for listboxes
        'sep'=>'<br/>'
      );
      $r .= call_user_func(array('data_entry_helper', $args['abundance_ctrl']), $abundance_args);
    }
    $r .= data_entry_helper::textarea(array(
        'label'=>lang::get('sample:comment'),
        'fieldname'=>'sample:comment',
        'class'=>'wide',
    ));
    $r .= '<div class="footer">'.data_entry_helper::checkbox(array(
        'label'=>lang::get('happy for contact'),
        'labelClass'=>'auto',
        'fieldname'=>'smpAttr:'.$args['contact_attr_id']
    )).'</div>';
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls',
        'page'=>'last'
      ));
    } else {
      $r .= "<input type=\"submit\" class=\"ui-state-default ui-corner-all\" value=\"Save\" />\n";
    }
    $r .= "</fieldset>\n";
    $r .= "</div>\n";
    $r .= "</form>";

    return $r;
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    return data_entry_helper::build_sample_occurrence_submission($values);
  }

  /**
   * Hook the indicia_define_remembered_fields method to specify the personal details page as
   * remembered between sessions.
   * @param $args
   * @return unknown_type
   */
  public static function indicia_define_remembered_fields($args) {
    data_entry_helper::set_remembered_fields(array(
      'smpAttr:'.$args['first_name_attr_id'],
      'smpAttr:'.$args['surname_attr_id'],
      'smpAttr:'.$args['email_attr_id'],
      'smpAttr:'.$args['phone_attr_id']
    ));
  }

}


