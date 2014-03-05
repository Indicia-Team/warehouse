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

require_once('includes/map.php');
require_once('includes/user.php');

/**
 * A form for entering ad-hoc observations of cetaceans.
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * @todo Provide form description in this comment block.
 * @todo Rename the form class to iform_...
 */
class iform_ad_hoc_cetaceans {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_ad_hoc_cetaceans_definition() {
    return array(
      'title'=>'Ad-hoc cetacean records',
      'category' => 'Forms for specific surveying methods',
      'description'=>'A form designed for input of ad-hoc records of cetaceans or other marine wildlife. '.
          'Records can be entered via a map if the sighting was from the shore, or via GPS coordinates for sightings at sea.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {   
    return array_merge(
      iform_map_get_map_parameters(),
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
          'name'=>'survey_id',
          'caption'=>'Survey',
          'description'=>'The survey that data will be posted into.',
          'type'=>'select',
          'table'=>'survey',
          'captionField'=>'title',
          'valueField'=>'id'
        ),
        array(
          'fieldname'=>'species_list_id',
          'label'=>'Species List',
          'helpText'=>'The species list that species can be selected from.',
          'type'=>'select',
          'table'=>'taxon_list',
          'valueField'=>'id',
          'captionField'=>'title'
        ),
	      array(
          'name'=>'platform_attr_id',
          'caption'=>'Sighting Platform (Boat or Shore) Attribute',
          'description'=>'Indicia ID for the sample attribute that records the sighting platform.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Sample Attributes'
	      ),
	      array(
          'name'=>'platform_termlist_id',
          'caption'=>'Sighting Platform (Boat or Shore) Termlist ID',
          'description'=>'Indicia ID for the termlist that contains possible values for the sighting platform.',
          'type'=>'int',
          'group'=>'Sample Attributes'
	      ),
	      array(
          'name'=>'platform_mapped_term_id',
          'caption'=>'Shore Term ID',
          'description'=>'ID of the Shore term. This is the option that the user is allowed to click on the map for.',
          'type'=>'int',
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
          'name'=>'sample_time_attr_id',
          'caption'=>'Sighting Time Custom Attribute ID',
          'description'=>'The Indicia ID for the Sample Custom Attribute for the Sighting Time.',
          'group'=>'Sample Attributes',
          'type'=>'int'
        ),
        array(
          'name'=>'contact_attr_id',
          'caption'=>'Contactable Attribute ID',
          'description'=>'Indicia ID for the sample attribute that if the user has opted in for being contacted regarding this record.',
          'type'=>'smpAttr',
          'group'=>'Sample Attributes'
        ),
	    )
    );
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method 
   */
  public static function get_form($args, $node, $response=null) {
    global $indicia_templates, $user;
    data_entry_helper::enable_validation('entry_form');
    $url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    $r = data_entry_helper::loading_block_start();    
    $r .= "<form method=\"post\" id=\"entry_form\" action=\"$url\">\n";
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);    
    $r .= "<div id=\"controls\">\n";
    if ($args['interface']!='one_page') {
      $r .= "<ul>\n";
      if ($user->uid==0) {
        $r .= '  <li><a href="#about_you"><span>'.lang::get('about you')."</span></a></li>\n";
      }
      $r .= '  <li><a href="#species"><span>'.lang::get('what did you see')."</span></a></li>\n";
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
      if ($args['interface']=='one_page') 
        $r .= '<legend>'.lang::get('about you').'</legend>';
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('first name'),
        'fieldname'=>'smpAttr:'.$args['first_name_attr_id'],
        'class'=>'control-width-4',
        'validation'=>array('required')
      ));
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('surname'),
        'fieldname'=>'smpAttr:'.$args['surname_attr_id'],
        'class'=>'control-width-4',
        'validation'=>array('required')
      ));
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('phone number'),
        'fieldname'=>'smpAttr:'.$args['phone_attr_id'],
        'class'=>'control-width-4'
      ));
      $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('email'),
        'fieldname'=>'smpAttr:'.$args['email_attr_id'],
        'class'=>'control-width-4 optional',
        'validation' => array('email')
      ));
      if ($args['interface']=='wizard') {
        $r .= data_entry_helper::wizard_buttons(array(
          'divId'=>'controls',
          'page'=>'first'
        ));
      }
      $r .= "</fieldset>\n";
    }
    
    // Species tab
    $r .= "<fieldset id=\"species\">\n";
    if ($args['interface']=='one_page') 
        $r .= '<legend>'.lang::get('what did you see').'</legend>';
    $species_list_args=array(
        'label'=>lang::get('Species'),
        'listId'=>$args['species_list_id'],
        'columns'=>1,
        'rowInclusionCheck'=>'hasData',
        'occAttrs'=> array($args['abundance_attr_id']),
        'extraParams'=>$readAuth + array('view'=>'detail','orderby'=>'taxonomic_sort_order'),
        'survey_id'=>$args['survey_id'],
        'header' => false,
        'view' => 'detail',
        'PHPtaxonLabel' => true
    );
    // Build a nice template to show a picture of each species, with fancybox.
    data_entry_helper::add_resource('fancybox');
    $indicia_templates['taxon_label'] = 'return \'<div class="taxon-cell">'.
        '<a href="'.data_entry_helper::$base_url.'upload/{image_path}" class="fancybox" >'.
        '<img alt="{taxon}" src="'.data_entry_helper::$base_url.'upload/med-{image_path}" width="250"/></a>'.
        '<div>{taxon}</div></div>'.
        '<div class="taxon-desc"><ul><li>\'.str_replace("\n", "</li><li>","{description_in_list}").\'</li></ul>'.
        '<a href="http://www.northeastcetaceans.org.uk/?q=\'.
        strtolower(str_replace(array(" ", "\\\'"), array("-", ""), "{taxon}")).
        \'" target="_blank" class="ui-state-default ui-corner-all indicia-button">'.lang::get('More Info').'...</a></div>\';';
    // Template the taxon label cell
    $indicia_templates['taxon_label_cell'] = "\n<td class='scTaxonCell'>{content}</td>";
    // Also template the attribute controls to show the label in place.
    $indicia_templates['attribute_cell'] = "\n<td class='scOccAttrCell'><label>{label}:</label><br/>{content}</td>";
    $r .= data_entry_helper::species_checklist($species_list_args);
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls',
        'page' => ($user->uid==0) ? 'middle' : 'first'
      ));
    }
    $r .= "</fieldset>";
    // --Place tab--
    $r .= "<fieldset id=\"place\">\n";
    if ($args['interface']=='one_page') 
        $r .= '<legend>'.lang::get('where was it').'</legend>';
    $r .= data_entry_helper::radio_group(array(
      'label' => 'Where were you when you made the sighting?',
      'fieldname'=>'smpAttr:'.$args['platform_attr_id'],
      'table'=>'termlists_term',
      'captionField'=>'term',
      'valueField'=>'id',
      'extraParams' => $readAuth + array('termlist_id' => $args['platform_termlist_id']),
      'sep' => '<br />',
      'labelClass' => 'auto',
      'class' => 'inline sighting-platform',
      'validation'=>array('required')
    ));
    $r .= '<div id="place_wrapper" class="hidden">';
    // Some instructions only visible when entering data from a boat
    $r .= '<p class="boat_mode page-notice ui-state-highlight ui-corner-all">'.lang::get('Instructions for when on boat').'</p>';
    // Some instructions only visible when entering data from the shore
    $r .= '<p class="shore_mode page-notice ui-state-highlight ui-corner-all">'.lang::get('Instructions for clicking on map').'</p>';
    $r .= '<div class="boat_mode">';
    // Add help examples to the lat and long boxes
    $indicia_templates['sref_textbox_latlong'] = '<label for="{idLat}">{labelLat}:</label>'.
        '<input type="text" id="{idLat}" name="{fieldnameLat}" {class} {disabled} value="{default}" /> <p class="helpText">e.g. 55:12.345N</p>' .
        '<label for="{idLong}">{labelLong}:</label>'.
        '<input type="text" id="{idLong}" name="{fieldnameLong}" {class} {disabled} value="{default}" /> <p class="helpText">e.g. 0:45.678W</p>' .
        '<input type="hidden" id="imp-geom" name="{table}:geom" value="{defaultGeom}" />'.
        '<input type="text" id="{id}" name="{fieldname}" style="display:none" value="{default}" />';
    $r .= data_entry_helper::sref_and_system(array(
      'systems' => array(4326 => lang::get('Latitude, Longitude')),
      'splitLatLong' => true,
      'helpText' => lang::get('Instructions for latlong')
    ));
    $r .= '</div>';
    // Initially, we hide the map. Only show it when the user selects the sighting was from the shore,
    // as a click on the map for boat recordings will not be accurate.
    $r .= '<div class="shore_mode">';
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    $options['maxZoom'] = 9;
    // Switch to degrees and decimal minutes for lat long.
    $options['latLongFormat'] = 'DM';
    $options['tabDiv'] = 'place';
    $r .= data_entry_helper::map_panel($options, $olOptions);
    // Now, add some JavaScript to show or hide the map. Show it for when the sighting was from the shore.
    // Hide it for boat based sightings as we want a GPS coordinate in this case. The JavaScript looks for the 
    // checked radio button to see the value
    data_entry_helper::$javascript .= 'jQuery(".sighting-platform input").click(
      function() {
        var platformId = jQuery("input[name=smpAttr\\\\:'.$args['platform_attr_id'].']:checked").val();
        if (platformId == '.$args['platform_mapped_term_id'].') {
          jQuery("#place_wrapper").removeClass("hidden");
          jQuery(".shore_mode").removeClass("hidden");
          jQuery(".boat_mode").addClass("hidden");
        } else {          
          jQuery("#place_wrapper").removeClass("hidden");
          jQuery(".shore_mode").addClass("hidden");
          jQuery(".boat_mode").removeClass("hidden");
        }
      }
    );'."\n";
    // Force existing setting of the radio buttons to reload when showign page after validation failure
    data_entry_helper::$onload_javascript .= '
    jQuery("input[name=smpAttr\\\\:'.$args['platform_attr_id'].']:checked").trigger("click");
    ';
    $r .= '</div></div>';
    if ($args['interface']=='wizard') {
      $r .= data_entry_helper::wizard_buttons(array(
        'divId'=>'controls'
      ));
    }
    $r .= '</fieldset>';
    
    // --Other information tab--
    $r .= "<fieldset id=\"other\">\n";
    // Get authorisation tokens to update and read from the Warehouse.
    $r .= data_entry_helper::get_auth($args['website_id'], $args['password']);
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"survey_id\" value=\"".$args['survey_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"occurrence:record_status\" value=\"C\" />\n";
    if ($args['interface']=='one_page') 
        $r .= '<legend>'.lang::get('other information').'</legend>';
    $r .= data_entry_helper::date_picker(array(
        'label'=>lang::get('Sighting Date'),
        'fieldname'=>'sample:date'
    ));
    $indicia_templates['timeFormat'] = '<label>hh:mm</label><br/>';
    $r .= data_entry_helper::text_input(array(
        'label'=>lang::get('Sighting Time'),
        'fieldname'=>'smpAttr:'.$args['sample_time_attr_id'],
        'class' => 'control-width-1',
        'suffixTemplate' => 'timeFormat'
    ));
    $r .= data_entry_helper::textarea(array(
        'label' => lang::get('Any other information'),
        'fieldname' => 'sample:comment',
        'class' => 'control-width-6',
        'helpText' => lang::get('Instructions for any other info')
    ));
    $r .= data_entry_helper::file_box(array(
        'caption' => 'Upload your photos',
        'readAuth' => $readAuth,
        'resizeWidth' => 1024,
        'resizeHeight' => 768,
        'table' => 'occurrence_image',
        'tabDiv' => 'other'
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
    $r .= "</fieldset></div>";
    $r .= "</form>";
    $r .= data_entry_helper::loading_block_end();
    return $r;
  }
 
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    // pass true to the submission build, as it will include rows with any data (as the checkbox is hidden).
    return data_entry_helper::build_sample_occurrences_list_submission($values, true);
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