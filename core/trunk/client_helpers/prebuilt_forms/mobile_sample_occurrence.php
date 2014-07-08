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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of jQuery Mobile module. See
 * https://drupal.org/project/jquerymobile. This should be configured with
 * libraries
 *    jQuery >= 1.8, as required by jQuery Mobile,
 *    jQuery Mobile <= 1.3, as required by the sub page plugin
 * This also requires the mobile_indicia theme.
 *
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('includes/map.php');
require_once('includes/user.php');
require_once('includes/language_utils.php');
require_once('includes/form_generation.php');

//JQM constants
define("JQM_HEADER", "header");
define("JQM_CONTENT", "content");
define("JQM_FOOTER", "footer");
define("JQM_ATTR", "attr");

/**
 * Store remembered field settings, since these need to be accessed from a hook
 * function which runs outside the class.
 * @var string
 */
global $remembered;

class iform_mobile_sample_occurrence {

  // Hold the single species name to be shown on the page to the user.
  protected static $singleSpeciesName;

  // The node id upon which this form appears.
  protected static $node;

  // The class upon which a function has been called which may be a subclass
  // of this.
  protected static $called_class;

  // The authorisation tokens for accessing the warehouse.
  protected static $auth = array();

  /**
   * The list of sample attributes. Keep a class level variable,
   * so that we can track the ones we have already emitted into the form.
   * @var array
   */
  protected static $smpAttrs;

  /**
   * The list of occurrence attributes. Keep a class level variable,
   * so that we can track the ones we have already emitted into the form.
   * @var array
   */
  protected static $occAttrs;

  /**
   * The list of JQM pages in a structured array.
   *
   * ATTR
   *
   * Array element format:
   *  ATTR => [],
      CONTENT => [
                  HEADER =>  [
                               ATTR => [],
                               CONTENT => []
                             ],
                  CONTENT => [
                               ATTR => [],
                               CONTENT => []
                             ],
                  FOOTER =>  [
                               ATTR => [],
                               CONTENT => []
                             ]
                  ]
      ]
   * @var array
   */
  protected $pages_array = array();

  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_mobile_sample_occurrence_definition() {
    return array(
      'title' => 'Mobile sample with occurrences form',
      'category' => 'General Purpose Data Entry Forms',
      'helpLink' => 'http://code.google.com/p/indicia/wiki/TutorialDynamicForm',
      'description' => 'A sample and occurrence entry form for mobile devices. '
        . 'Can be used for entry of a single occurrence. The attributes on the '
        . 'form are dynamically generated from the survey setup on the Indicia '
        . 'Warehouse.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      array(
        array(
          'name'=>'interface',
          'caption'=>'Interface Style Option',
          'description'=>'Choose the style of user interface, either dividing the form up onto separate tabs, '.
              'wizard pages or having all controls on a single page.',
          'type'=>'select',
          'options' => array(
              'tabs' => 'Tabs',
              'one_page' => 'All One Page'
          ),
          'group' => 'User Interface',
          'default' => 'tabs'
        ),
        array(
          'name' => 'attribute_termlist_language_filter',
          'caption' => 'Internationalise lookups',
          'type' => 'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface',
          'description' => <<<'EOD'
            In lookup custom attribute controls, use the language associated
            with the current user account to filter to show only the terms in
            that language.
EOD
        ),
        array(
          'name' => 'survey_id',
          'caption' => 'Survey',
          'type' => 'select',
          'table' => 'survey',
          'captionField' => 'title',
          'valueField' => 'id',
          'siteSpecific' => true,
          'description' => <<<'EOD'
            The survey that data will be posted into and that defines custom
            attributes
EOD
        ),
        array(
          'name' => 'emailShow',
          'caption' => 'Show email field even if logged in',
          'type' => 'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface',
          'description' => <<<'EOD'
            If the survey requests an email address, it is sent implicitly for
            logged in users. Check this box to show it explicitly.
EOD
        ),
        array(
          'name' => 'nameShow',
          'caption' => 'Show user profile fields even if logged in',
          'type' => 'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface',
          'description' => <<<'EOD'
            If the survey requests first name and last name or any field which
            matches a field in the users profile, these are hidden. Check this
            box to show these fields. Always show these fields if they are
            required at the warehouse unless the profile module is enabled,
            <em>copy field values from user profile</em> is selected and the
            fields are required in the profile.
EOD
        ),
        array(
          'name' => 'copyFromProfile',
          'caption' => 'Copy field values from user profile',
          'type' => 'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface',
          // Note that we can't test Drupal module availability whilst loading
          // this form for a new iform, using Ajax. So in this case we show the
          // control even though it is not usable (the help text explains the
          // module requirement).
          'visible' => !function_exists('module_exists') ||
                 (module_exists('profile') && substr(VERSION, 0, 1) == '6') ||
                 (module_exists('field') && substr(VERSION, 0, 1) == '7'),
          'description' => <<<'EOD'
            Copy any matching fields from the user\'s profile into the fields
            with matching names in the sample data. This works for fields
            defined in the Drupal Profile module (version 6) or Fields module
            (version 7) which must be enabled to use this feature. Applies
            whether fields are shown or not.
EOD
        ),
        array(
          'name' => 'structure',
          'caption' => 'Form Structure',
          'type' => 'textarea',
          'default' => "=Species=\r\n".
              "?Please enter the species you saw and any other information "
              . "about them.?\r\n".
              "[species]\r\n".
              "@resizeWidth=1500\r\n".
              "@resizeHeight=1500\r\n".
              "[species attributes]\r\n".
              "[*]\r\n".
              "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can "
              . "enter the reference directly, or search for a place then "
              . "click on the map to set it.?\r\n".
              "[spatial reference]\r\n".
              "[place search]\r\n".
              "[map]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[date]\r\n".
              "[sample comment]\r\n".
              "[*]\r\n".
              "=*=",
          'group' => 'User Interface',
          'description' => <<<'EOD'
            Define the structure of the form. Each component goes on a new line
            and is nested inside the previous component where appropriate. The
            following types of component can be specified.
            <br/>
            <strong>=page name=</strong> is used to specify the name of an app
            page. (Alpha-numeric characters only)
            <br/>
            <strong>=*=</strong> indicates a placeholder for putting any custom
            attribute tabs not defined in this form structure.
            <br/>
            <strong>[control name]</strong> indicates a predefined control is to
            be added to the form with the following predefined controls
            available:
            <br/>
            &nbsp;&nbsp;<strong>[species]</strong> - a species grid or input
            control. You can change any of the control options for an individual
            custom attribute control in a grid by putting @control|option=value
            on the subsequent line(s). For example, if a control is for
            occAttr:4 then you can set its default value by specifying
            @occAttr:4|default=7 on the line after the [species]
            <br/>
            If you want to specify a custom template for a grid\'s species label
            cell, then override the taxon_label template. If you have multiple
            grids on the form, you can override each one individually by setting
            the @taxonLabelTemplate for each grid to the name of a template that
            you\'ve added to the \$indicia_templates global array. If in single
            species entry mode and using a select box for data input, you can
            put a taxon group select above the species input select by setting
            the option @taxonGroupSelect=true. Control the label and helptext
            for this control using the options @taxonGroupSelectLabel and
            @taxonGroupSelectHelpText.
            <br/>
            &nbsp;&nbsp;<strong>[species map]</strong> - a species grid or input
            control: this is the same as the species control, but the sample is
            broken down into subsamples, each of which has its own location
            picked from the map. Only the part of the species grid which is
            being added to or modified at the time is displayed. This control
            should be placed after the map control, with which it integrates.
            Species recording must be set to a List (grid mode) rather than
            single entry. This control does not currently support mixed spatial
            reference systems, only the first specified will be used. You do not
            need a [spatial reference] control on the page when using a
            [species map] control.
            <br/>
            &nbsp;&nbsp;<strong>[species map summary]</strong> - a read only
            grid showing a summary of the data entered using the species map
            control.
            <br/>
            "&nbsp;&nbsp;<strong>[species attributes]</strong> - any custom
            attributes for the occurrence, if not using the grid. Also includes
            a file upload box and sensitivity input control if relevant. The
            attrubutes @resizeWidth and @resizeHeight can specified on
            subsequent lines, otherwise they default to 1600. Note that this
            control provides a quick way to output all occurrence custom
            attributes plus photo and sensitivity input controls and outputs all
            attributes irrespective of the form block or tab. For finer control
            of the output, see the [occAttr:n], [photos] and [sensitivity]
            controls.
            <br/>
            "&nbsp;&nbsp;<strong>[date]</strong> - a sample must always have a
            date.
            <br/>
            &nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial
            reference and location select/autocomplete controls
            <br/>
            "&nbsp;&nbsp;<strong>[spatial reference]</strong> - a sample must
            always have a spatial reference.
            <br/>
            "&nbsp;&nbsp;<strong>[location name]</strong> - a text box to enter
            a place name.
            <br/>
            &nbsp;&nbsp;<strong>[location autocomplete]</strong> - an 
            autocomplete control for picking a stored location. A spatial 
            reference is still required.
            <br/>
            &nbsp;&nbsp;<strong>[location url param]</strong> - a set of hidden
            inputs that insert the location ID read from a URL parameter called
            location_id into the form. Uses the location\'s centroid as the
            sample map reference.
            <br/>
            &nbsp;&nbsp;<strong>[location select]</strong> - a select control
            for picking a stored location. A spatial reference is still
            required.
            <br/>
            &nbsp;&nbsp;<strong>[location map]</strong> - combines location 
            select, map and spatial reference controls for recording only at
            stored locations.
            <br/>
            &nbsp;&nbsp;<strong>[photos]</strong> - use when in single record
            entry mode to provide a control for uploading occurrence photos.
            Alternatively use the [species attributes] control to output all 
            input controls for the species automatically. The [photos] control
            overrides the setting <strong>Occurrence Images</strong>.
            <br/>
            &nbsp;&nbsp;<strong>[place search]</strong> - zooms the map to the
            entered location.
            <br/>
            &nbsp;&nbsp;<strong>[recorder names]</strong> - a text box for
            names. The logged-in user's id is always stored with the record.
            <br/>
            &nbsp;&nbsp;<strong>[record status]</strong> - allow recorder to
            mark record as in progress or complete
            <br/>
            &nbsp;&nbsp;<strong>[sample comment]</strong> - a text box for
            sample level comment. (Each occurrence may also have a comment.)
            <br/>
            &nbsp;&nbsp;<strong>[sample photo]</strong>. - a photo upload for
            sample level images. (Each occurrence may also have photos.)
            <br/>
            &nbsp;&nbsp;<strong>[sensitivity]</strong> - outputs a control for
            setting record sensitivity and the public viewing precision. This
            control will also output any other occurrence custom attributes
            which are on an outer block called Sensitivity. Any such attributes
            will then be disabled when the record is not sensitive, so they can
            be used to capture information that only relates to sensitive
            records.
            <br/>
            &nbsp;&nbsp;<strong>[zero abundance]</strong>. - use when in single
            record entry mode to provide a checkbox for specifying negative
            records.
            <br/>
            <strong>@option=value</strong> on the line(s) following any control
            allows you to override one of the options passed to the control. The
            options available depend on the control. For example
            @label=Abundance would set the untranslated label of a control to
            Abundance. Where the option value is an array, use valid JSON to
            encode the value. For example an array of strings could be passed
            as @occAttrClasses=["class1","class2"] or a keyed array as
            @extraParams={"preferred":"true","orderby":"term"}. Other common
            options include helpText (set to a piece of additional text to
            display alongside the control) and class (to add css classes to the
            control such as control-width-3).
            <br/>
            <strong>[*]</strong> is used to make a placeholder for putting any
            custom attributes that should be inserted into the current tab. When
            this option is used, you can change any of the control options for
            an individual custom attribute control by putting
            @control|option=value on the subsequent line(s). For example, if a
            control is for smpAttr:4 then you can update it's label by
            specifying @smpAttr:4|label=New Label on the line after the [*]. You
            can also set an option for all the controls output by the [*] block
            by specifying @option=value as for non-custom controls, e.g. set
            @label=My label to define the same label for all controls in this
            custom attribute block. You can define the value for a control using
            the standard replacement tokens for user data, namely {user_id},
            {username}, {email} and {profile_*}; replace * in the latter to
            construct an existing profile field name. For example you could set
            the default value of an email input using @smpAttr:n|default={email}
            where n is the attribute ID.
            <br/>
            <strong>[smpAttr:<i>n</i>]</strong> is used to insert a particular
            custom sample attribute identified by its ID number
            <br/>
            <strong>[occAttr:<i>n</i>]</strong> is used to insert a particular
            custom occurrence attribute identified by its ID number when
            inputting single records at a time. Or use [species attributes] to
            output the whole lot.
            <br/>
            <strong>?help text?</strong> is used to define help text to add to
            the tab, e.g. ?Enter the name of the site.?
            <br/>
            <strong>|</strong> is used insert a split so that controls before
            the split go into a left column and controls after the split go into
            a right column.
            <br/>
            <strong>all else</strong> is copied to the output html so you can
            add structure for styling.
EOD
        ),
        array(
          'name' => 'edit_taxa_names',
          'caption' => 'Include option to edit entered taxa',
          'type' => 'checkbox',
          'default' => false,
          'required' => false,
          'group' => 'User Interface',
          'description' => <<<'EOD'
            Include an icon to allow taxa to be edited after they has been
            entered into the species grid.
EOD
        ),
        array(
          'fieldname' => 'list_id',
          'label' => 'Species List ',
          'type' => 'select',
          'table' => 'taxon_list',
          'valueField' => 'id',
          'captionField' => 'title',
          'required' => false,
          'group' => 'Species',
          'siteSpecific' => true,
          'helpText' => <<<'EOD'
            The species list that species can be selected from. This list is
            pre-populated into the grid when doing grid based data entry, or
            provides the list which a species can be picked from when doing
            single occurrence data entry.
EOD
        ),
        array(
          'fieldname' => 'cache_lookup',
          'label' => 'Cache lookups',
          'type' => 'checkbox',
          'required' => false,
          'group' => 'Species',
          'siteSpecific' => false,
          'helpText' => <<<'EOD'
            Tick this box to select to use a cached version of the
            lookup list when searching for extra species names to add to the
            grid, or set to false to use the live version (default). The latter
            is slower and places more load on the warehouse so should only be
            used during development or when there is a specific need to reflect
            taxa that have only just been added to the list.
EOD
        ),
        array(
          'name' => 'species_ctrl',
          'caption' => 'Single Species Selection Control Type',
          'type' => 'select',
          'options' => array(
            'autocomplete' => 'Autocomplete',
            'select' => 'Select',
            'hierarchical_select' => 'Hierarchical select',
            'collapsible_select' => 'Collapsible select',
            'listbox' => 'List box',
            'radio_group' => 'Radio group',
          ),
          'default' => 'autocomplete',
          'group' => 'Species',
          'description' => <<<'EOD'
            The type of control that will be available to select a single
            species.
EOD
        ),
        array(
          'name' => 'species_include_both_names',
          'caption' => 'Include both names in species controls and added rows',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species',
          'description' => <<<'EOD'
            When using a species grid with the ability to add new rows, the
            autocomplete control by default shows just the searched taxon name
            in the drop down. Set this to include both the latin and common
            names, with the searched one first. This also controls the label
            when adding a new taxon row into the grid.
EOD
        ),
        array(
          'name' => 'species_include_taxon_group',
          'caption' => 'Include taxon group name in species autocomplete and '
            . 'added rows',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species',
          'description' => <<<'EOD'
            When using a species grid with the ability to add new rows, the
            autocomplete control by default shows just the searched taxon name
            in the drop down. Set this to include the taxon group title. This
            also controls the label when adding a new taxon row into the grid.
EOD
        ),
        array(
          'name' => 'occurrence_comment',
          'caption' => 'Occurrence Comment',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Species',
          'description' => <<<'EOD'
            Should an input box be present for a comment against each
            occurrence?
EOD
        ),
        array(
          'name' => 'occurrence_sensitivity',
          'caption' => 'Occurrence Sensitivity',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Species',
          'description' => <<<'EOD'
            Should a control be present for sensitivity of each record?  This
            applies when using grid entry mode or when using the [species
            attributes] control to output all the occurrence related input
            controls automatically. The [sensitivity] control outputs a
            sensitivity input control independently of this setting.
EOD
        ),
        array(
          'name' => 'occurrence_images',
          'caption' => 'Occurrence Images',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Species',
          'description' => <<<'EOD'
            Should occurrences allow images to be uploaded? This applies when
            using grid entry mode or when using the [species attributes]
            control to output all the occurrence related input controls
            automatically. The [photos] control outputs a photos input control
            independently of this setting.
EOD
        ),
        array(
          'name' => 'single_species_message',
          'caption' => 'Include a message stating which species you are '
            . 'recording in single species mode?',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Species',
          'description' => <<<'EOD'
            Message which displays the species you are recording against in
            single species mode. When selected, this will automatically be
            displayed where applicable.
EOD
        ),
        array(
          'name' => 'species_names_filter',
          'caption' => 'Species Names Filter',
          'type' => 'select',
          'options' => array(
            'all' => 'All names are available',
            'language' => 'Only allow selection of species using common names '
              . 'in the user\'s language',
            'preferred' => 'Only allow selection of species using names which '
              . 'are flagged as preferred',
            'excludeSynonyms' => 'Allow common names or preferred latin names'
          ),
          'default' => 'all',
          'group' => 'Species',
          'description' => <<<'EOD'
            Select the filter to apply to the species names which are available
            to choose from.
EOD
        ),
        array(
          'name' => 'sample_method_id',
          'caption' => 'Sample Method',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'extraParams' => array(
              'termlist_external_key' => 'indicia:sample_methods'
              ),
          'required' => false,
          'helpText' => 'The sample method that will be used for created samples.'
        ),
        array(
          'name' => 'defaults',
          'caption' => 'Default Values',
          'type' => 'textarea',
          'default' => 'occurrence:record_status=C',
          'description' => <<<'EOD'
            Supply default values for each field as required. On each line,
            enter fieldname=value. For custom attributes, the fieldname is the
            untranslated caption. For other fields, it is the model and
            fieldname, e.g. occurrence.record_status. For date fields, use
            today to dynamically default to today\'s date. NOTE, currently
            only supports occurrence:record_status and sample:date but will be
            extended in future.
EOD
        ),
        array(
          'name' => 'remembered',
          'caption' => 'Remembered Fields',
          'type' => 'textarea',
          'required' => false,
          'description' => <<<'EOD'
            Supply a list of field names that should be remembered in a cookie,
            saving re-inputting them if they are likely to repeat. For greater
            flexibility use the @lockable=true option on each control instead.
EOD
        ),
      )
    );
    return $retVal;
  }

    /**
   * Return the generated form output.
   * @param array $args The form settings.
   * @param array $node
   * @return string Form HTML.
   */
  public static function get_form($args, $node) {
    iform_load_helpers(array('mobile_entry_helper'));
    data_entry_helper::$website_id = $args['website_id'];
    self::$node = $node;
    self::$called_class = 'iform_' . $node->iform;

  // Convert parameter, $args['defaults'], into structured array.
    self::parse_defaults($args);

    // Check permissions to access form.
    $func = get_user_func(self::$called_class, 'enforcePermissions');
    if ($func) {
      if(call_user_func($func) &&
              !user_access('IForm n'.$node->nid.' admin') &&
              !user_access('IForm n'.$node->nid.' user')) {
        return lang::get('LANG_no_permissions');
      }
    }

    // Get authorisation tokens to update and read from the Warehouse. We allow
    // child classes to generate this first if subclassed.
    if (self::$auth)
      $auth = self::$auth;
    else {
      $auth = data_entry_helper::get_read_write_auth(
              $args['website_id'], $args['password']);
      self::$auth = $auth;
    }

    // Load custom attribute definitions from warehouse.
    self::loadSmpAttrs($auth['read'], $args['survey_id']);
    self::loadOccAttrs($auth['read'], $args['survey_id']);

    // Build a structured array describing the form.
    // Attribute definitions on the warehouse may specify some tabs.
    $attrTabs = get_attribute_tabs(self::$smpAttrs);
    // They are combined with those in the Form Structure.
    $tabs = self::structureTabs($args['structure'], $attrTabs);
    // A second pass organises the content within the tabs
    $structure = self::structureTabsContent($tabs);

    // Render the form
    $func = self::$called_class . '::renderForm';
    $r = call_user_func($func, $structure, $args, $auth);

    return $r;
  }

  /**
   * Load the list of sample attributes into a static variable.
   * By maintaining a single list of attributes we can track which have already
   * been output.
   * @param array $readAuth Read authorisation tokens.
   * @param integer $surveyId ID of the survey to load sample attributes for.
   */
  protected static function loadSmpAttrs($readAuth, $surveyId) {
    if (!isset(self::$smpAttrs)) {
      $attrArgs = array(
        'valuetable' => 'sample_attribute_value',
        'attrtable' => 'sample_attribute',
        'fieldprefix' => 'smpAttr',
        'extraParams' => $readAuth,
        'survey_id' => $surveyId
      );
      if (!empty($args['sample_method_id'])) {
        // Select only the custom attributes that are for this sample method.
        $attrOpts['sample_method_id'] = $args['sample_method_id'];
      }
      self::$smpAttrs = data_entry_helper::getAttributes($attrArgs, false);
    }
  }

  /**
   * Load the list of occurrence attributes into a static variable.
   * By maintaining a single list of attributes we can track which have already
   * been output.
   * @param array $readAuth Read authorisation tokens.
   * @param integer $surveyId ID of the survey to load occurrence attributes for.
   */
  protected static function loadOccAttrs($readAuth, $surveyId) {
    if (!isset(self::$occAttrs)) {
      $attrArgs = array(
         'valuetable' => 'occurrence_attribute_value',
         'attrtable' => 'occurrence_attribute',
         'key' => 'occurrence_id',
         'fieldprefix' => 'occAttr',
         'extraParams' => $readAuth,
         'survey_id' => $surveyId
      );
      self::$occAttrs = data_entry_helper::getAttributes($attrArgs, false);
    }
  }

  /**
   * Assembles the different bits of the form html in to the final item
   * @global string $remembered
   * @param array $structure
   * @param array $args The form settings.
   * @param array $auth Authorisation to access the warehouse.
   * @return string $r The form html.
   */
  protected static function renderForm($structure, $args, $auth) {
    // Store the list of fields in the form whose values will be remembered from
    // one use to the next.
    global $remembered;
    $remembered = isset($args['remembered']) ? $args['remembered'] : '';

    // Output the header html.
    $r = call_user_func(array(self::$called_class, 'renderHeader'), $args);

    // Output the hidden inputs html.
    $func = get_user_func(self::$called_class, 'renderHiddenInputs');
    $params = array($args, $auth);
    $r .= $func ? call_user_func_array($func, $params) : '';

    // Output form structure html.
    switch($args['interface']){
        case 'tabs':
            $func = get_user_func(self::$called_class, 'renderTabs');
            break;
        case 'one_page':
            $func = get_user_func(self::$called_class, 'renderPages');
            break;
        case 'wizard':
        default:
            echo 'ERROR: Interface style unknown.';
    }

    $params = array($structure, $args, $auth);
    $r .= $func ? call_user_func_array($func, $params) : '';

    // Ouput footer html.
    $func = get_user_func(self::$called_class, 'renderFooter');
    $r .= $func ? call_user_func($func, $args) : '';

    $func = get_user_func(self::$called_class, 'link_species_popups');
    $r .= $func ? call_user_func($func, $args) : '';

    return $r;
  }


  /**
   * Renders the configured indicia form into HTML JQM pages.
   *
   *
   * @param array $form_inputs The configuration.
   * @param array $args The form settings.
   * @param array $auth Authorisation to access the warehouse.
   * @return string
   */
  public function renderPages($form_inputs, $args, $auth){
    //generate pages into $pages_array
    self::generatePages($form_inputs, $args, $auth);

    //render pages into HTML
    global $pages_array;
    $r = '';
    foreach($pages_array as $page){
      $r .= self::renderOnePage($page);
    }
    return $r;
  }

  /**
   * Generates JQM pages array which can be then passed to render or further
   * modify.
   *
   * By calling 'get_control_ + CONTROLLER NAME' functions it creates initial
   * JQM page with the form inputs (form input elements and if needed parallel
   * JQM pages with specified inputs).
   *
   * @param array $form_inputs The configuration.
   * @param array $args The form settings.
   * @param array $auth Authorisation to access the warehouse.
   */
  protected static function generatePages($form_inputs, $args, $auth){

    //generate form content
    $content = array();
    if(!empty($form_inputs)){
      //build page content & initiate child pages
      foreach(array_values($form_inputs) as $section){
        foreach($section as $element){
          //'get_control_ + CONTROLLER NAME' function call
          $func = get_user_func(self::$called_class, $element['method']);
          if($func) {
              $options = $element['options'];
              $content[] = call_user_func($func, $auth, $args, "", $options);
          }
        }
      }
    }

    //create blank JQM page and attach new content
    $page = self::getFixedBlankPage();
    $page[JQM_CONTENT][JQM_HEADER][JQM_CONTENT][] = "<h1></h1>";
    $page[JQM_CONTENT][JQM_CONTENT][JQM_CONTENT] = $content;

    //submit button
    $options = array();
    $options['id'] = "entry-form-submit";
    $options['align'] = "right";
    $options['caption'] = "Save";

    $page[JQM_CONTENT][JQM_FOOTER][JQM_CONTENT][] =
      mobile_entry_helper::apply_template('jqmControlSubmitButton', $options);

    //add to pages array
    self::push_pages_array($page);
  }

  /**
   * Adds a JQM page into the global pages_array's beginning.
   */
  protected static function push_pages_array($page){
    global $pages_array;
    if(!empty($pages_array)){
      array_unshift($pages_array, $page);
    } else{
      $pages_array[] = $page;
    }
  }

    /**
     * Renders JQM page's array into HTML code.
     */
    protected static function renderOnePage($page){
        $attr = $content = "";

        //build attributes
        foreach($page[JQM_ATTR] as $attribute => $value){
            $attr .= $attribute . '="' . $value .'"';
        }

        //build content
        foreach($page[JQM_CONTENT] as $role => $element){
            $element_content = implode('', $element[JQM_CONTENT]);
            $element_attr = "";
            foreach($element[JQM_ATTR] as $attribute => $value){
                $element_attr .= $attribute . '="' . $value .'"';
            }

            //put content together
            $options = array();
            $options['role'] = $role;
            $options[JQM_ATTR] = $element_attr;
            $options[JQM_CONTENT] = $element_content;
            $content .= mobile_entry_helper::apply_template('jqmPage', $options);
        }

        //put it all together
        $options = array();
        $options['role'] = 'page';
        $options[JQM_ATTR] = $attr;
        $options[JQM_CONTENT] = $content;
        $r = mobile_entry_helper::apply_template('jqmPage', $options);

        return $r;
    }

  /**
   * Returns a blank JQM page with fixed Header and Footer and generic default
   * back button.
   * @return array
   */
  protected static function getFixedBlankPage(){
    $options = array();
    $options['href'] = '#';
    $options['caption'] = 'Back';
    $back_button = mobile_entry_helper::apply_template('jqmBackButton', $options);

    return [
      JQM_ATTR => array(),
      JQM_CONTENT => [
        JQM_HEADER => [
          JQM_ATTR => array("data-position" => "fixed"),
          JQM_CONTENT => array($back_button)
        ],
        JQM_CONTENT => [
          JQM_ATTR => array(),
          JQM_CONTENT => array()
        ],
        JQM_FOOTER => [
          JQM_ATTR => array("data-position" => "fixed"),
          JQM_CONTENT => array()
        ]
      ]
    ];
  }

  /**
   * Overridable function to retrieve the HTML to appear above the dynamically
   * constructed form,
   * which by default is an HTML form for data submission
   * @param array $args The form settings.
   * @return string
   */
  protected static function renderHeader($args) {
    $r = "";
    // request automatic JS validation
    data_entry_helper::enable_validation('entry_form');
    return $r;
  }

  /**
   * Get authorisation tokens to update the Warehouse, plus any other hidden
   * form inputs.
   * @param array $args The form settings.
   * @param array $auth Authorisation to access the warehouse.
   * @return string
   */
  protected static function renderHiddenInputs($args, $auth) {
    // Authorisation tokens.
    $r = $auth['write'];
    $r .= '<input type="hidden" id="website_id" name="website_id" value="'
            . $args['website_id'] . '" />' . PHP_EOL;
    $r .= '<input type="hidden" id="survey_id" name="survey_id" value="'
            . $args['survey_id'] . '" />' . PHP_EOL;

    // Sample method
    if (!empty($args['sample_method_id'])) {
      $r .= '<input type="hidden" name="sample:sample_method_id" value="'
              . $args['sample_method_id'] . '"/>' . PHP_EOL;
    }

    // Check if Record Status is included as a control. If not, then add it as
    // a hidden.
    $arr = helper_base::explode_lines($args['structure']);
    if (!in_array('[record status]', $arr)) {
      $value = isset($args['defaults']['occurrence:record_status']) ?
              $args['defaults']['occurrence:record_status'] : 'C';
      $r .= '<input type="hidden" id="occurrence:record_status" '
              . 'name="occurrence:record_status" value="'
              . $value . '" />' . PHP_EOL;
    }

    // User profile fields such as username, id etc.
    $exists = isset(data_entry_helper::$entity_to_load['sample:id']);
    $r .= get_user_profile_hidden_inputs(self::$smpAttrs, $args, $exists, $auth['read']);

    return $r;
  }

  /**
   * Construct html represented by the Form Structure argument and the warehouse
   * attribute configuration. The inputs are grouped on 'tabs'.
   * @param array $tabs
   * @param array $args The form settings.
   * @param array $auth Authorisation to access the warehouse.
   * @return string
   */
  protected static function renderTabs($tabs, $args, $auth) {
    $tabHtml = array();
    $tabaliases = array();
    foreach ($tabs as $tab => $tabContent) {
      // keep track on if the tab actually has real content, so we can avoid
      // floating instructions if all the controls were removed by user profile
      // integration for example.
      $hasControls = false;
      // get a machine readable alias for the heading, if we are showing tabs
      $tabalias = 'tab-' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $html = '';
      $html .= self::renderOneTabContent(
              $auth, $args, $tab, $tabContent, $tabalias, $hasControls);
      if (!empty($html) && $hasControls) {
        $tabHtml[$tab] = $html;
        $tabaliases[] = $tabalias;
      }
    }

    // Output the dynamic tab content
    $pageIdx = 0;
    $r = '';
    foreach ($tabHtml as $tab => $tabContent) {
      $tabalias = $tabaliases[$pageIdx];
      $r .= '<div id="' . $tabalias . '" data-role="page">' . PHP_EOL;
      $r .= '<div data-role="header" data-position="fixed">' . PHP_EOL;
      $r .= '<a href="#" data-rel="back" data-icon="arrow-l" ';
      $r .= 'data-iconpos="left">Back</a>' . PHP_EOL;

      $r .= '<h2>' . $tab . '</h2>' . PHP_EOL;

      $r .= '
        <a href="#" class="geoloc_icon" onclick="
               var coords = jQuery(\'#imp-sref\').val();
               var accuracy = jQuery(\'#sref_accuracy\').val();
               makePopup(\'<center><h2>GPS</h2></center><h3><b>Your coordinates:</b> \' + coords + \'</h3><h3><b>Accuracy:</b> \' + accuracy + \'m</h3> \');
               jQuery(\'#app-popup\').popup().popup(\'open\');" style="display:none; width: 27px; height: 27px; padding:4px;">
          <div style="width: 3px; height: 3px; background: transparent; box-shadow:
              12px   6px   #518b41,12px   12px  #518b41,  12px  15px  #518b41 ,
              12px   21px  #518b41,  12px  18px  #518b41,  12px   9px  #518b41 ,
              15px   12px  #518b41 ,18px   12px  #518b41 ,24px   12px  #518b41,
              9px  12px   #518b41,  6px  12px  #518b41,   0px   12px  #518b41 ,
              12px   0px   #518b41,  12px   24px   #518b41,  3px   12px  #518b41,
              3px   9px   #518b41,  6px   6px   #518b41,  12px  3px   #518b41,
              9px   3px   #518b41,  15px  3px   #518b41 , 21px   12px   #518b41 ,
              21px  9px   #518b41,  18px  6px   #518b41,  3px   15px  #518b41,
              6px   18px  #518b41,  9px   21px  #518b41,  15px  21px    #518b41,
              18px  18px  #518b41,  21px  15px  #518b41, 18px 3px #518b41,
              21px 6px #518b41, 6px 3px #518b41, 3px 6px #518b41, 18px 21px #518b41,
              21px 18px #518b41,6px 21px #518b41,3px 18px #518b41;">
         </div>
       </a>' . PHP_EOL;
      $r .= '</div>' . PHP_EOL;
      $r .= '<div role="main" class="ui-content">' . PHP_EOL;
      $r .= $tabContent;
      $r .= "</div>\n";

      // Add any buttons required in a jQM footer
      if(count($tabHtml) == 1){
        $prev = '';
        $next = '';
      }
      elseif ($pageIdx == 0){
        $prev = '';
        $next = '#' . $tabaliases[$pageIdx + 1];
      }
      elseif ($pageIdx == count($tabHtml) - 1){
        $prev = '#' . $tabaliases[$pageIdx - 1];
        $next = '';
      }
      else {
        $prev = '#' . $tabaliases[$pageIdx - 1];
        $next = '#' . $tabaliases[$pageIdx + 1];
      }
      $r .= mobile_entry_helper::wizard_buttons(array(
        'prev' => $prev,
        'next' => $next,
      ));

      $pageIdx++;
      // End of jQM page.
      $r .= "</div>\n";
    }
   return $r;
  }

  protected static function renderOneTabContent($auth, $args, $tab, $tabContent,
          $tabalias, &$hasControls) {

    $r='';

    foreach($tabContent as $component) {
      switch ($component['type']) {
        case 'help':
          $r .= '<div class="page-notice ui-state-highlight ui-corner-all">'
                . lang::get($component['value']) . "</div>";
          break;
        case 'control':
          $func = get_user_func(self::$called_class, $component['method']);
          if($func) {
            $options = $component['options'];
            $r .= call_user_func($func, $auth, $args, $tabalias, $options);
            $hasControls = true;
          }
          break;
        case 'wildctrl':
          $options = $component['options'];
          // Some of the options may be targetted at specific attributes whereas
          // others apply to all. We need to separate the two.
          $sharedOpts = array('extraParams' => $auth['read']);
          $specificOpts = array();
          self::parseForAttrSpecificOptions($options, $sharedOpts, $specificOpts);
          $attrHtml = get_attribute_html(self::$smpAttrs, $args, $sharedOpts, $tab, $specificOpts, '', 'mobile_entry_helper');
          if (!empty($attrHtml)) {
            $hasControls = true;
          }
          $r .= $attrHtml;
          break;
        default:
          $r .= $component;
      }
    }

    return $r;
  }

  /**
   * Overridable function to retrieve the HTML to appear below the dynamically
   * constructed form, which by default is the closure of the HTML form for data
   * submission
   * @param string $args
   * @return string
   */
  protected static function renderFooter($args) {
    $r = '';
    if(!empty(data_entry_helper::$validation_errors)){
      $r .= data_entry_helper::dump_remaining_errors();
    }
    return $r;
  }

  /**
   * The top level of form structure are called tabs.
   * Finds the list of all tab names that are going to be required, either by
   * the form structure, or by custom attributes.
   * @param array $strucText The form structure texy.
   * @param array $attrTabs Tabs required by custom attributes.
   * @return array Returns an array of tab arrays where each tab array
   * contains the tab name, alias and an array of all the components in the form
   * structure to be placed in that tab.
   */
  protected static function structureTabs($strucText, $attrTabs) {
    $strucArray = helper_base::explode_lines($strucText);
    // An array to contain the tabs defined in $structure.
    $strucTabs = array();
    // The name of the current tab
    $name = '-';

    // A tab in the form structurre appears as =tabname=.
    $regexTab = '/^=[A-Za-z0-9, \'\-\*\?]+=$/';

    // Loop through the lines of the form structure
    foreach ($strucArray as $component) {
      $component = trim($component);
      // Skip blank lines.
      if($component == '') continue;
      // Search for components of type '=tab name='
      if (preg_match($regexTab, $component, $matches) === 1) {
        // Found a tab. Trim the '=' to get the tab name.
        $name = substr($matches[0], 1, -1);
        $strucTabs[$name] = array();
      } else {
        // Found some other component so add it to the array for the tab that
        // it occurs on.
          $strucTabs[$name][] = $component;
      }
    }

    // If any additional tabs are required by attributes, add them to the
    // position marked by a dummy tab named *.
    // First get rid of any tabs already in the structure
    $strucTabnames = array_map('strtolower', array_keys($strucTabs));
    foreach ($attrTabs as $attrTabname => $attrTabContent) {
      // case-insensitive check if attribute tab already in form structure
      if (in_array(strtolower($attrTabname), $strucTabnames)) {
        unset($attrTabs[$attrTabname]);
      }
    }

    // Now we have a list of form structure tabs, with the position of the
    // $attrTabs marked by *. So join it all together.
    $allTabs = array();
    foreach($strucTabs as $strucTabname => $strucTabContent) {
      if ($strucTabname == '*') {
        $allTabs += $attrTabs;
      }
      else {
        $allTabs[$strucTabname] = $strucTabContent;
      }
    }
    return $allTabs;
  }

  /**
   * Takes the output from structureTabs and structures the elements within
   * each tab.
   * @param array $tabs The array of tabs with unstructured content.
   * @return array Returns an array of tab names
   * with each element containing a structured array of all the components in
   * the form structure to be placed in that tab.
   */
  protected static function structureTabsContent($tabs) {
    $structure = array();
    foreach($tabs as $name => $content) {
      $structure[$name] = self::structureOneTabContent($content);
    }
    return $structure;
  }

  /**
   * Takes the output from structureTabs and structures the elements within
   * one tab.
   * @param array $tabContent The array of tabs with unstructured content.
   * @return array Returns an array of components
   * with each control component containing a structured array of all the
   * options for it.
   */
  protected static function structureOneTabContent($tabContent) {
    // The structure within the tab.
    $structure = array();
    // An index of our current position in the structure
    $i = -1;

    // Help in the form structurre appears as ?help text?.
    $regexHelp = '/\A\?[^�]*\?\z/';
    // A control in the form structurre appears as [control].
    $regexCtrl = '/\A\[[^�]*\]\z/';
    // An attribute control in the form appears as [attr:id]
    $regexAttrCtrl = '/^\[(?P<attrType>[a-zA-Z]+):(?P<attrId>[0-9]+)\]/';

    // Loop through the lines of the form structure for this tab
    foreach ($tabContent as $component) {
      if (preg_match($regexHelp, $component) === 1) {
        // Found a component of type '?help text?'
        $value = substr($component, 1, -1);
        $structure[] = array(
            'type' => 'help',
            'value' => $value);
        $i++;
      }
      elseif ($component == '[*]') {
        // Found a control wild card component
        $structure[] = array(
            'type' => 'wildctrl',
            'options' => array(),
            'attropts' => array());

        $i++;
      }
      elseif (preg_match($regexAttrCtrl, $component, $matches) === 1) {
        // Found a control component of type '[smpAttr:n] or [occAttr:n]'
        $value = substr($component, 1, -1);
        $value = strtolower($value);
        $method = 'get_control_';
        $method .= strtolower($matches['attrType']);
        $structure[] = array(
            'type' => 'control',
            'name' => $value,
            'method' => $method,
            'options' => array('attrId' => $matches['attrId']));
        $i++;
      }
      elseif (preg_match($regexCtrl, $component) === 1) {
        // Found a component of type '[control]'
        $value = substr($component, 1, -1);
        $value = strtolower($value);
        $method = 'get_control_';
        $method .= preg_replace('/[^a-zA-Z0-9]/', '', $value);
        $structure[] = array(
            'type' => 'control',
            'name' => $value,
            'method' => $method,
            'options' => array(),
            'attropts' => array());
        $i++;
      }
      elseif(substr($component, 0, 1) == '@') {
        // Found a control option. Trim the '@' symbol.
        $component = substr($component, 1);
        // Split the option in two at the first = symbol
        $option = explode('=', $component, 2);
        $optName = $option[0];
        $optValue = $option[1];

        if (strtolower($optValue) === 'false') {
          // Convert a value of false to a boolean
            $optValue = FALSE;
        }
        else {
          // Attempt to JSON decode all other values.
          $optDecoded = json_decode($optValue, TRUE);
          if ($optDecoded !== NULL) {
            // We decoded some json.
            $optValue = $optDecoded;
          }
        }

        // Add the option to the control.
        $structure[$i]['options'][$optName] = $optValue;
      }
      else {
        // Found some component which is not a control.
        $structure[] = $component;
      }
    }
    return $structure;
  }

  /**
   * Convert the unstructured textarea of default values into a structured array.
   */
    protected static function parse_defaults(&$args) {
    $result=array();
    if (isset($args['defaults']))
      $result = helper_base::explode_lines_key_value_pairs($args['defaults']);
    $args['defaults'] = $result;
  }

  protected static function getReloadPath () {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['location_id']);
    unset($reload['params']['new']);
    unset($reload['params']['newLocation']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) {
      // decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param) {
        $reload['params'][$key] = urldecode($param);
      }
      $reloadPath .= '?'.http_build_query($reload['params']);
    }
    return $reloadPath;
  }

  /**
   * Get the spatial reference control.
   * Defaults to sample:entered_sref. Supply $options['fieldname'] for
   * submission to other database fields.
   */
  protected static function get_control_spatialreference(
          $auth, $args, $tabalias, $options) {
    if($args['interface'] === 'tabs'){
      return mobile_entry_helper::sref_now($options, true);
    }

    $id = 'sref';
    $caption = 'GPS';

    //generate a new page
    $page = self::getFixedBlankPage();
    $page[JQM_ATTR]['id'] = $id;
    $page[JQM_CONTENT][JQM_HEADER][JQM_CONTENT][] = "<h1>" . $caption . "</h1>";
    $page[JQM_CONTENT][JQM_CONTENT][JQM_CONTENT][] =
      mobile_entry_helper::sref_now($options, false);
    self::push_pages_array($page);

    $options = array();
    $options['class'] = '';
    $options['href'] = '#' . $id;
    $options['caption'] = $caption;
    $button = mobile_entry_helper::apply_template('jqmRightButton', $options);

    return $button;

  }

  /**
   * The species filter can be taken from the edit tab or overridden by a URL
   * filter. This method determines the filter to be used.
   * @param array $args Form arguments
   * @return array List of items to filter against, e.g. species names or
   * meaning IDs.
   */
  protected static function get_species_filter($args) {
    // we must have a filter field specified in order to apply a filter
    if (!empty($args['taxon_filter_field'])) {
      // if URL params are enabled and we have one, then this is the top
      // priority filter to apply
      if (!empty($_GET['taxon']) && $args['use_url_taxon_parameter'])
        // convert commas to newline, so url provided filters are the same
        // format as those on the edit tab, also allowing for url encoding.
        return explode(',', urldecode($_GET['taxon']));
      elseif (!empty($args['taxon_filter']))
        // filter is provided on the edit tab
        return helper_base::explode_lines($args['taxon_filter']);
    }
    // default - no filter to apply
    return array();
  }

  /**
   * Get the species data for the page in single species mode
   */
  protected static function get_single_species_data($auth, $args, $filterLines) {
    //The form is configured for filtering by taxon name, meaning id or external
    // key. If there is only one specified, then the form cannot display a
    // species checklist, as there is no point. So, convert our preferred taxon
    // name, meaning ID or external_key to find the preferred taxa_taxon_list_id
    // from the selected checklist
    if (empty($args['list_id']))
      throw new exception(lang::get('Please configure the Initial Species List '
        . 'parameter to define which list the species to record is selected '
        . 'from.'));
    $filter = array(
      'preferred' => 't',
      'taxon_list_id'=>$args['list_id']
    );
    if ($args['taxon_filter_field']=='preferred_name') {
      $filter['taxon']=$filterLines[0];
    } else {
      $filter[$args['taxon_filter_field']]=$filterLines[0];
    }
    $options = array(
      'table' => 'taxa_taxon_list',
      'extraParams' => $auth['read'] + $filter
    );
    $response =data_entry_helper::get_population_data($options);
    // Call code that handles the error logs
    self::get_single_species_logging($auth, $args, $filterLines, $response);
    return $response;
  }

  /**
   * Error logging code for the page in single species mode
   * @param $auth
   * @param $args
   * @param $filterLines
   * @param $response
   * @throws exception
   */
  protected static function get_single_species_logging(
          $auth, $args, $filterLines, $response) {
    //Go through each filter line and add commas between the values so it looks
    // nice in the log
    $filters = implode(', ', $filterLines);
    // If only one filter is supplied but more than one match is found, we can't
    // continue as we don't know which one to match against.
    if (count($response) > 1 &&
            count($filterLines) == 1 &&
            empty($response['error']) &&
            function_exists('watchdog')) {
      watchdog('indicia', 'Multiple matches have been found when using the '
        . 'filter \'' . $args['taxon_filter_field'] . '\'. '
        . 'The filter was passed the following value(s)' . $filters);
      throw new exception(lang::get('This form is setup for single species '
        . 'recording, but more than one species matching the criteria exists '
        . 'in the list.'));
    }
    // If our filter returns nothing at all, we log it, we return string
    // 'no matches' which the system then uses to clear the filter
    if (count($response) == 0) {
      if (function_exists('watchdog'))
        watchdog('missing sp.', 'No matches were found when using the '
          . 'filter \'' . $args['taxon_filter_field'] . '\'. '
          . 'The filter was passed the following value(s)' . $filters);
    }
  }

  /**
   * Get the control for species input, either a grid or a single species input
   * control.
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_species(
    $auth, $args, $tabAlias, $options) {
    if (!isset($args['cache_lookup']) ||
      ($args['species_ctrl'] !== 'autocomplete')) {
      // Default for old form configurations or when not using an autocomplete
      $args['cache_lookup']=false;
    }
    // The filter can be a URL or on the edit tab, so do the processing to work
    // out the filter to use.
    $filterLines = self::get_species_filter($args);
    // Store in the argument so that it can be used elsewhere.
    $args['taxon_filter'] = implode("\n", $filterLines);
    $extraParams = $auth['read'];
    return self::get_control_species_single($auth, $args, $extraParams, $options);
  }

  /**
   * Returns a control for picking a single species
   * @global array $indicia_templates
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $extraParams Extra parameters pre-configured with taxon and
   * taxon name type filters.
   * @param array $options additional options for the control, e.g. those
   * configured in the form structure.
   * @return string HTML for the control.
   */
  protected static function get_control_species_single(
          $auth, $args, $extraParams, $options) {
    $r = '';
    $extraParams['taxon_list_id'] = empty($args['extra_list_id']) ?
            $args['list_id'] : $args['extra_list_id'];

    // Add a taxon group selector if that option was chosen
    if (isset($options['taxonGroupSelect']) && $options['taxonGroupSelect']) {
      $label = isset($options['taxonGroupSelectLabel']) ?
              $options['taxonGroupSelectLabel'] : 'Species Group';
      $helpText = isset($options['taxonGroupSelectHelpText']) ?
              $options['taxonGroupSelectHelpText'] :
              'Choose which species group you want to pick a species from.';
      if (!empty(data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id'])) {
        // need to find the default value
        $ttlid = data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id'];
        $species = data_entry_helper::get_population_data(array(
          'table' => 'cache_taxa_taxon_list',
          'extraParams' => $auth['read'] +  array('id' => $ttlid)
        ));
        data_entry_helper::$entity_to_load['taxon_group_id'] =
                $species[0]['taxon_group_id'];
      }
      $r .= data_entry_helper::select(array(
        'fieldname' => 'taxon_group_id',
        'id' => 'taxon_group_id',
        'label' => lang::get($label),
        'helpText' => lang::get($helpText),
        'report' => 'library/taxon_groups/taxon_groups_used_in_checklist',
        'valueField' => 'id',
        'captionField' => 'title',
        'extraParams' => $auth['read'] + array(
            'taxon_list_id' => $extraParams['taxon_list_id']
            ),
      ));
      // Update the select box to link to the species group picker.
      // It must be a select box!
      $args['species_ctrl'] = 'select';
      $options['parentControlId'] = 'taxon_group_id';
      $options['parentControlLabel'] = lang::get($label);
      $options['filterField'] = 'taxon_group_id';
    }

    // Set up options for control
    $options['speciesNameFilterMode'] = self::getSpeciesNameFilterMode($args);
    $ctrl = $args['species_ctrl'] === 'autocomplete' ?
            'species_autocomplete' : $args['species_ctrl'];
    $species_ctrl_opts = array_merge(array(
        'fieldname' => 'occurrence:taxa_taxon_list_id',
        'label'=>lang::get('occurrence:taxa_taxon_list_id'),
        'columns'=>2, // applies to radio buttons
        'parentField' => 'parent_id', // applies to tree browsers
        'view' => 'detail', // required for tree browsers to get parent id
        'blankText'=>lang::get('Please select'), // applies to selects
        'cacheLookup'=>$args['cache_lookup']
    ), $options);
    if (isset($species_ctrl_opts['extraParams'])) {
      $species_ctrl_opts['extraParams'] =
            array_merge($extraParams, $species_ctrl_opts['extraParams']);
    }
    else {
      $species_ctrl_opts['extraParams']=$extraParams;
    }
    $species_ctrl_opts['extraParams'] = array_merge(array(
        'view' => 'detail', //required for hierarchical select to get parent id
        'orderby' => 'taxonomic_sort_order',
        'sortdir' => 'ASC'
    ), $species_ctrl_opts['extraParams']);

    if (!empty($args['taxon_filter'])) {
      // applies to autocompletes
      $species_ctrl_opts['taxonFilterField'] = $args['taxon_filter_field'];
      $species_ctrl_opts['taxonFilter'] =
              helper_base::explode_lines($args['taxon_filter']);
    }

    // obtain table to query and hence fields to use
    $db = data_entry_helper::get_species_lookup_db_definition($args['cache_lookup']);


    if ($ctrl!=='species_autocomplete') {
      // The species autocomplete has built in support for the species name
      // filter. For other controls we need to apply the species name filter to
      // the params used for population.
      if (!empty($species_ctrl_opts['taxonFilter']) ||
              $options['speciesNameFilterMode']) {
        $species_ctrl_opts['extraParams'] = array_merge(
            $species_ctrl_opts['extraParams'],
            data_entry_helper::get_species_names_filter($species_ctrl_opts));
      }

      // for controls which don't know how to do the lookup, we need to tell them
      $species_ctrl_opts = array_merge(array(
        'table' => $db['tblTaxon'],
        'captionField' => $db['colTaxon'],
        'valueField' => $db['colId'],
      ), $species_ctrl_opts);
    }
    // if using something other than an autocomplete, then set the caption
    // template to include the appropriate names. Autocompletes use a JS
    // function instead.
    global $indicia_templates;
    if ($ctrl!=='autocomplete' &&
            isset($args['species_include_both_names']) &&
            $args['species_include_both_names']) {
      if ($args['species_names_filter'] === 'all')
        $indicia_templates['species_caption'] = "{{$db['colTaxon']}}";
      elseif ($args['species_names_filter'] === 'language')
        $indicia_templates['species_caption'] = "{{$db['colTaxon']}} - {{$db['colPreferred']}}";
      else
        $indicia_templates['species_caption'] = "{{$db['colTaxon']}} - {{$db['colCommon']}}";
      $species_ctrl_opts['captionTemplate'] = 'species_caption';
    }

    if ($ctrl=='tree_browser') {
      // change the node template to include images
      $indicia_templates['tree_browser_node'] = '<div><img src="'
           . data_entry_helper::$base_url
           . '/upload/thumb-{image_path}" alt="Image of {caption}" width="80" />'
           . '</div><span>{caption}</span>';
    }

    // Dynamically generate the species selection control required.
    $r .= call_user_func(array('mobile_entry_helper', $ctrl), $species_ctrl_opts);
    return $r;
  }

  /**
   * Function to map from the species_names_filter argument to the
   * speciesNamesFilterMode required by the checklist grid. For legacy reasons
   * they don't quite match.
   * @param $args
   * @return bool|string
   */
  protected static function getSpeciesNameFilterMode($args) {
    if (isset($args['species_names_filter'])) {
      switch ($args['species_names_filter']) {
        case 'language':
          return 'currentLanguage';
        default:
          return $args['species_names_filter'];
      }
    }
    // default is no species name filter.
    return false;
  }


  /**
   * Get the sample comment control
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_samplecomment(
          $auth, $args, $tabAlias, $options) {
    if($args['interface'] === 'tabs'){
      return data_entry_helper::textarea(array_merge(array(
        'fieldname' => 'sample:comment',
        'label'=>lang::get('Overall Comment')
      ), $options));
    }

    $id = 'comment';
    $caption = 'Comment';

    //generate a new page
    $page = self::getFixedBlankPage();
    $page[JQM_ATTR]['id'] = $id;
    $page[JQM_CONTENT][JQM_HEADER][JQM_CONTENT][] = "<h1>" . $caption . "</h1>";
    $page[JQM_CONTENT][JQM_CONTENT][JQM_CONTENT][] =
      data_entry_helper::textarea(array_merge(array(
        'fieldname' => 'sample:comment'
      ), $options));

    self::push_pages_array($page);

    $options = array();
    $options['class'] = '';
    $options['href'] = '#' . $id;
    $options['caption'] = $caption;

    $button = mobile_entry_helper::apply_template('jqmRightButton', $options);
    return $button;
  }

  /**
   * Get the sample photo control
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_samplephoto(
          $auth, $args, $tabAlias, $options) {
    $defaults = array(
      'fieldname' => 'sample:image',
      'label' => lang::get('Sample photos'),
    );
    $opts = array_merge($defaults, $options);
    return data_entry_helper::image_upload($opts);
  }

  /**
   * Get the block of custom attributes at the species (occurrence) level
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_speciesattributes(
          $auth, $args, $tabAlias, $options) {
    $ctrlOptions = array('extraParams'=>$auth['read']);
    $attrSpecificOptions = array();
    self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);
    $r = '';
    if ($args['occurrence_sensitivity']) {
      $sensitivity_controls = get_attribute_html(
        self::$occAttrs, $args, $ctrlOptions, 'sensitivity', $attrSpecificOptions);
      $r .= data_entry_helper::sensitivity_input(array(
        'additionalControls' => $sensitivity_controls
      ));
    }
    $r .=
      get_attribute_html(self::$occAttrs, $args, $ctrlOptions, '', $attrSpecificOptions);
    if ($args['occurrence_comment'])
      $r .= data_entry_helper::textarea(array(
        'fieldname' => 'occurrence:comment',
        'label'=>lang::get('Record Comment')
      ));
    if ($args['occurrence_images']){
      $r .= self::occurrence_photo_input($options);
    }
    return $r;
  }

  /**
  * Get the date control.
  * @param $auth
  * @param $args
  * @param $tabAlias
  * @param $options
  * @return string
  */
  protected static function get_control_date(
    $auth, $args, $tabAlias, $options) {

    //Tabs
    if($args['interface'] === 'tabs'){
      return mobile_entry_helper::date_now(array_merge(array(
        'fieldname' => 'sample:date',
        'default' => isset($args['defaults']['sample:date']) ?
            $args['defaults']['sample:date'] : ''
      ), $options), true);
    }

    //One Page
    $id = 'date';
    $caption = 'Date';

    //generate a new page
    $page = self::getFixedBlankPage();
    $page[JQM_ATTR]['id'] = $id;
    $page[JQM_CONTENT][JQM_HEADER][JQM_CONTENT][] = "<h1>" . $caption . "</h1>";
    $page[JQM_CONTENT][JQM_CONTENT][JQM_CONTENT][] =
      mobile_entry_helper::date_now($options, false);
    self::push_pages_array($page);

    $options = array();
    $options['class'] = '';
    $options['href'] = '#' . $id;
    $options['caption'] = $caption;

    $button = mobile_entry_helper::apply_template('jqmRightButton', $options);
    return $button;
  }

  /**
   * Get the location name control.
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_locationname(
          $auth, $args, $tabAlias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Name'),
      'fieldname' => 'sample:location_name',
      'class' => 'control-width-5'
    ), $options));
  }

  /**
   * Get an occurrence attribute control.
   */
  /**
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_smpattr(
          $auth, $args, $tabAlias, $options) {
    $attribName = 'smpAttr:' . $options['attrId'];
    foreach (self::$smpAttrs as $idx => $attr) {
      if ($attr['id'] === $attribName) {
        self::$smpAttrs[$idx]['handled'] = true;
        return data_entry_helper::outputAttribute(self::$smpAttrs[$idx], $options);
      }
    }
    return "Sample attribute $attribName not found.";
  }

  /**
   * Get an occurrence attribute control.
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_occattr(
          $auth, $args, $tabAlias, $options) {
    $attribName = 'occAttr:' . $options['attrId'];
    foreach (self::$occAttrs as $idx => $attr) {
      if ($attr['id'] === $attribName) {
        self::$occAttrs[$idx]['handled'] = true;
        return data_entry_helper::outputAttribute(self::$occAttrs[$idx], $options);
      }
    }
    return "Occurrence attribute $attribName not found.";
  }

  /**
   * Get the photos control
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_photos(
          $auth, $args, $tabAlias, $options) {
    return self::occurrence_photo_input($options);
  }

  /**
   * Get the recorder names control
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_recordernames(
          $auth, $args, $tabAlias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname' => 'sample:recorder_names',
      'label'=>lang::get('Recorder names')
    ), $options));
  }


  /**
   * Get the sensitivity control
   * @param $auth
   * @param $args
   * @param $tabAlias
   * @param $options
   * @return string
   */
  protected static function get_control_sensitivity(
          $auth, $args, $tabAlias, $options) {
    $ctrlOptions = array('extraParams'=>$auth['read']);
    $attrSpecificOptions = array();

    self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);

    $sensitivity_controls = get_attribute_html(
      self::$occAttrs, $args, $ctrlOptions, 'sensitivity', $attrSpecificOptions);

    return data_entry_helper::sensitivity_input(array(
      'additionalControls' => $sensitivity_controls
    ));
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    // Any remembered fields need to be made available to the hook function
    // outside this class.
    global $remembered;
    $remembered = isset($args['remembered']) ? $args['remembered'] : '';
    // default for forms setup on old versions is grid - list of occurrences
    // Can't call getGridMode in this context as we might not have the $_GET
    // value to indicate grid
    if (isset($values['speciesgridmapmode']))
      $submission =
        data_entry_helper::build_sample_subsamples_occurrences_submission($values);
    else if (isset($values['gridmode']))
      $submission =
        data_entry_helper::build_sample_occurrences_list_submission($values);
    else
      $submission =
        data_entry_helper::build_sample_occurrence_submission($values);
    return($submission);
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to
   * the standard Drupal, theme or Indicia ones.
   *
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('mobile_sample_occurrence.css');
  }

  /**
   * Provides a control for inputting photos against the record, when in single
   * record mode.
   *
   * @param array $options Options array for the control.
   * @return string
   */
  protected static function occurrence_photo_input($options) {
    $defaults = array(
      'fieldname' => 'occurrence:image',
      'label' => lang::get('Species photos'),
    );
    $opts = array_merge($defaults, $options);
    return data_entry_helper::image_upload($opts);
  }


  /**
   * Parses the options provided to a control in the user interface definition
   * and splits the options which apply to the entire control (@label=Grid Ref)
   * from ones which apply to a specific custom attribute
   * (smpAttr:3|label=Quantity).
   *
   * @param $options
   * @param $ctrlOptions
   * @param $attrSpecificOptions
   */
  protected static function parseForAttrSpecificOptions($options, &$ctrlOptions,
          &$attrSpecificOptions) {
    // look for options specific to each attribute
    foreach ($options as $option => $value) {
      // split the id of the option into the control name and option name.
      if (strpos($option, '|') !== false) {
        $optionId = explode('|', $option);
        if (!isset($attrSpecificOptions[$optionId[0]])) {
          $attrSpecificOptions[$optionId[0]]=array();
        }
        $attrSpecificOptions[$optionId[0]][$optionId[1]] = $value;
      }
      else {
        $ctrlOptions[$option] = $value;
      }
    }
  }

  /**
   * A hook function to setup remembered fields whose values are stored in a cookie.
   */
  public static function indicia_define_remembered_fields() {
    global $remembered;
    $remembered = trim($remembered);
    if (!empty($remembered)) {
      data_entry_helper::set_remembered_fields(helper_base::explode_lines($remembered));
    }
  }

}

/**
 * Utility function  to see if a method exists in the given class.
 * @param string $class the class containing the method
 * @param string $method the name of the method
 * @return string Either a function name suitable for passing to call_user_func or
 * FALSE if the method does not exist.
 */
function get_user_func($class, $method){
  if(method_exists($class, $method)) {
    return $class . '::' . $method;
  }
  else {
    return FALSE;
  }
}
