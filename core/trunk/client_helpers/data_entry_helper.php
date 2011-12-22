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
 * Link in other required php files.
 */
require_once('helper_config.php');
require_once('helper_base.php');
require_once('submission_builder.php');
require_once("libcurlEmulator/libcurlemu.inc.php");

/**
 * Static helper class that provides automatic HTML and JavaScript generation for Indicia online
 * recording website data entry controls.
 * Examples include auto-complete text boxes that are populated by Indicia species lists, maps
 * for spatial reference selection and date pickers. All controls in this class support the following
 * entries in their $options array parameter:
 * <ul>
 * <li><b>label</b><br/>
 * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
 * <li><b>helpText</b><br/>
 * Optional. Defines help text to be displayed alongside the control. The position of the text is defined by
 * helper_base::$helpTextPos, which can be set to before or after (default). The template is defined by
 * global $indicia_templates['helpText'] and can be replaced on an instance by instance basis by specifying an
 * option 'helpTextTemplate' for the control.
 * <li><b>helpTextTemplate</b>
 * If helpText is supplied but you need to change the template for this control only, set this to refer to the name of an
 * alternate template you have added to the $indicia_templates array. The template should contain a {helpText} replacement
 * string.</li>
 * <li><b>helpTextClass</b>
 * Specify helpTextClass to override the class normally applied to control help texts, which defaults to helpText.</li>
 * <li><b>prefixTemplate</b>
 * If you need to change the prefix for this control only, set this to refer to the name of an alternate template you
 * have added to the global $indicia_templates array. To change the prefix for all controls, you can update the value of
 * $indicia_templates['prefix'] before building the form.</li>
 * <li><b>suffixTemplate</b>
 * If you need to change the suffix for this control only, set this to refer to the name of an alternate template you
 * have added to the global $indicia_templates array. To change the suffix for all controls, you can update the value of
 * $indicia_templates['suffix'] before building the form.</li>
 * <li><b>afterControl</b>
 * Allows a piece of HTML to be specified which is inserted immediately after the control, before the suffix and
 * helpText. Ideal for inserting buttons that are to be displayed alongside a control such as a Go button
 * for a search box.
 * </li>
 * </ul>
 *
 * @package	Client
 */
class data_entry_helper extends helper_base {

  /**
   * When reloading a form, this can be populated with the list of values to load into the controls. E.g. set it to the
   * content of $_POST after submitting a form that needs to reload.
   * @var array
   */
  public static $entity_to_load=null;

  /**
   * @var integer Length of time in seconds after which cached Warehouse responses will start to expire.
   */
  public static $cache_timeout=3600;

  /**
   * @var integer On average, every 1 in $cache_chance_expire times the Warehouse is called for data which is
   * cached but older than the cache timeout, the cached data will be refreshed. This introduces a random element to
   * cache refreshes so that no single form load event is responsible for refreshing all cached content.
   */
  public static $cache_chance_refresh_file=5;

  /**
   * @var integer On average, every 1 in $cache_chance_purge times the Warehouse is called for data, all files
   * older than 5 times the cache_timeout will be purged, apart from the most recent $cache_allowed_file_count files.
   */
  public static $cache_chance_purge=100;

  /**
   * @var integer Number of recent files allowed in the cache which the cache will not bother clearing during a deletion operation.
   * They will be refreshed occasionally when requested anyway.
   */
  public static $cache_allowed_file_count=50;

  /**
   * @var integer On average, every 1 in $interim_image_chance_purge times the Warehouse is called for data, all interim images
   * older than $interim_image_expiry seconds will be deleted. These are images that should have uploaded to the warehouse but the form was not
   * finally submitted.
   */
  public static $interim_image_chance_purge=100;

  /**
   * @var integer On average, every 1 in $cache_chance_expire times the Warehouse is called for data which is
   */
  public static $interim_image_expiry=14400;

  /**
   * @var Array List of fields that are to be stored in a cookie and reloaded the next time a form is accessed. These
   * are populated by implementing a hook function called indicia_define_remembered_fields which calls set_remembered_fields.
   */
  private static $remembered_fields=null;

/**********************************/
/* Start of main controls section */
/**********************************/

 /**
  * Helper function to generate an autocomplete box from an Indicia core service query.
  * Because this generates a hidden ID control as well as a text input control, if you are outputting your own HTML label
  * then the label you associate with this control should be of the form "$id:$caption" rather than just the $id which
  * is normal for other controls. For example:
  * <code>
  * <label for='occurrence:taxa_taxon_list_id:taxon'>Taxon:</label>
  * <?php echo data_entry_helper::autocomplete(array(
  *     'fieldname' => 'occurrence:taxa_taxon_list_id',
  *     'table' => 'taxa_taxon_list',
  *     'captionField' => 'taxon',
  *     'valueField' => 'id',
  *     'extraParams' => $readAuth
  * )); ?>
  * </code>
  * Of course if you use the built in label option in the options array then this is handled for you.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. This should be left to its default value for
  * integration with other mapping controls to work correctly.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>defaultCaption</b><br/>
  * Optional. The default caption to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Required. Table name to get data from for the autocomplete options.</li>
  * <li><b>captionField</b><br/>
  * Required. Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>template</b><br/>
  * Optional. Name of the template entry used to build the HTML for the control. Defaults to autocomplete.</li>
  * <li><b>numValues</b><br/>
  * Optional. Number of returned values in the drop down list. Defaults to 10.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the autocomplete control.
  *
  * @link http://code.google.com/p/indicia/wiki/DataModel
  */
  public static function autocomplete() {
    global $indicia_templates;
    $options = self::check_arguments(func_get_args(), array(
        'fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'defaultCaption', 'default', 'numValues'
    ));
    if (!array_key_exists('id', $options)) $options['id']=$options['fieldname'];
    $options['inputId'] = $options['id'].':'.$options['captionField'];
    $options = array_merge(array(
      'template' => 'autocomplete',
      'url' => parent::$base_url."index.php/services/data",
      'inputId' => $options['id'].':'.$options['captionField'],
      // Escape the ids for jQuery selectors
      'escaped_input_id' => str_replace(':', '\\\\:', $options['inputId']),
      'escaped_id' => str_replace(':', '\\\\:', $options['id']),
      'defaultCaption' => self::check_default_value($options['inputId'],
          array_key_exists('defaultCaption', $options) ? $options['defaultCaption'] : ''),
      'max' => array_key_exists('numValues', $options) ? ', max : '.$options['numValues'] : ''
    ), $options);
    self::add_resource('autocomplete');
    // Escape the id for jQuery selectors
    $escaped_id=str_replace(':','\\\\:',$options['id']);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $options['sParams'] = substr($sParams, 0, -1);
    $replaceTags=array();
    foreach(array_keys($options) as $option) {
      array_push($replaceTags, '{'.$option.'}');
    }
    $options['extraParams']=null;
    self::$javascript .= str_replace($replaceTags, $options, $indicia_templates['autocomplete_javascript']);

    $r = self::apply_template($options['template'], $options);
    return $r;
  }

 /**
  * Helper function to output an HTML checkbox control. This includes re-loading of existing values
  * and displaying of validation error messages.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>template</b><br/>
  * Optional. Name of the template entry used to build the HTML for the control. Defaults to checkbox.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the checkbox control.
  */
  public static function checkbox($options) {
    $options = self::check_options($options);
    $default = isset($options['default']) ? $options['default'] : '';
    $value = self::check_default_value($options['fieldname'], $default);
    $options['checked'] = ($value==='on' || $value === 1 || $value === '1' || $value==='t') ? ' checked="checked"' : '';
    $options['template'] = array_key_exists('template', $options) ? $options['template'] : 'checkbox';
    return self::apply_template($options['template'], $options);
  }

 /**
  * Helper function to generate a list of checkboxes from a Indicia core service query.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><b>captionField</b><br/>
  * Optional. Field to draw values to show in the control from. Required unless lookupValues is specified.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField. </li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>lookupValues</b><br/>
  * If the group is to be populated with a fixed list of values, rather than via a service call, then the
  * values can be passed into this parameter as an associated array of key=>caption.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>template</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the outer control.</li>
  * <li><b>itemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each item in the control.</li>
  * <li><b>captionTemplate</b><br/>
  * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
  * with each database field represented as {fieldname}.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the group of checkboxes.
  */
  public static function checkbox_group() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'sep', 'default'));
    if (!isset($options['id']))
      $options['id'] = $options['fieldname'];
    if (substr($options['fieldname'],-2) !='[]')
      $options['fieldname'] .= '[]';
    return self::check_or_radio_group($options, 'checkbox');
  }

 /**
  * Helper function to insert a date picker control.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, for example 'sample:date'.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>allowFuture</b><br/>
  * Optional. If true, then future dates are allowed. Default is false.</li>
  * <li><b>dateFormat</b><br/>
  * Optional. Allows the date format string to be set, which must match a date format that can be parsed by the JavaScript Date object.
  * Default is dd/mm/yy.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the date picker control.
  */
  public static function date_picker() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'default'));
    $options = array_merge(array(
      'dateFormat'=>'dd/mm/yy'
    ), $options);
    self::add_resource('jquery_ui');
    $escaped_id=str_replace(':','\\\\:',$options['id']);
    // Don't set js up for the datepicker in the clonable row for the species checklist grid
    if ($escaped_id!='{fieldname}') {
      if (self::$validated_form_id!==null) {
        if (!isset($options['default']) || $options['default']=='')
          $options['default']=lang::get('click here');
        self::$javascript .= "if (typeof jQuery.validator !== \"undefined\") {
  jQuery.validator.addMethod('customDate',
    function(value, element) {
      // parseDate throws exception if the value is invalid
      try{jQuery.datepicker.parseDate( '".$options['dateFormat']."', value);return true;}
      catch(e){return false;}
    }, '".lang::get('Please enter a valid date')."'
  );
}\n";
      }
      self::$javascript .= "jQuery('#$escaped_id').datepicker({
    dateFormat : '".$options['dateFormat']."',
    changeMonth: true,
    changeYear: true,
    constrainInput: false";
      // Filter out future dates
      if (!array_key_exists('allow_future', $options) || $options['allow_future']==false) {
        self::$javascript .= ",
    maxDate: '0'";
      }
      // If the validation plugin is running, we need to trigger it when the datepicker closes.
      if (self::$validated_form_id) {
        self::$javascript .= ",
    onClose: function() {
      $(this).valid();
    }";
      }
      self::$javascript .= "\n});\n";
    }
    // Check for the special default value of today
    if (isset($options['default']) && $options['default']=='today')
      $options['default'] = date('d/m/Y');

    // Enforce a class on the control called date
    if (!array_key_exists('class', $options)) {
      $options['class']='';
    }
    return self::apply_template('date_picker', $options);
  }

/**
  * Outputs a file upload control suitable for linking images to records.
  * The control allows selection of multiple files, and depending on the browser functionality it gives progress feedback.
  * The control uses Google Gears, Flash, Silverlight, Browserplus or HTML5 to enhance the functionality
  * where available. The output of the control can be configured by changing the content of the templates called
  * file_box, file_box_initial_file_info, file_box_uploaded_image and button.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>table</b><br/>
  * Name of the image table to upload images into, e.g. occurrence_image, location_image, sample_image or taxon_image.
  * Defaults to occurrence_image.
  * </li>
  * <li><b>id</b><br/>
  * Optional. Provide a unique identifier for this image uploader control if more than one are required on the page.
  * </li>
  * <li><b>caption</b><br/>
  * Caption to display at the top of the uploader box. Defaults to the translated string for "Files".
  * </li>
  * <li><b>uploadSelectBtnCaption</b><br/>
  * Set this to override the caption for the button for selecting files to upload.
  * </li>
  * <li><b>flickrSelectBtnCaption</b><br/>
  * Set this to override the caption for the button for selecting files from Flickr.
  * </li>
  * <li><b>uploadStartBtnCaption</b><br/>
  * Set this to override the caption for the start upload button, which is only visible if autoUpload is false.
  * </li>
  * <li><b>useFancybox</b><br/>
  * Defaults to true. If true, then image previews use the Fancybox plugin to display a "lightbox" effect when clicked on.
  * </li>
  * <li><b>imageWidth</b><br/>
  * Defaults to 200. Number of pixels wide the image previews should be.
  * </li>
  * <li><b>resizeWidth</b><br/>
  * If set, then the file will be resized before upload using this as the maximum pixels width.
  * </li>
  * <li><b>resizeHeight</b><br/>
  * If set, then the file will be resized before upload using this as the maximum pixels height.
  * </li>
  * <li><b>resizeQuality</b><br/>
  * Defines the quality of the resize operation (from 1 to 100). Has no effect unless either resizeWidth or resizeHeight are non-zero.
  * </li>
  * <li><b>upload</b><br/>
  * Boolean, defaults to true. Set to false when implementing a Flickr image control without file upload capability.
  * </li>
  * <li><b>flickr</b><br/>
  * Not implemented.
  * </li>
  * <li><b>maxFileCount</b><br/>
  * Maximum number of files to allow upload for. Defaults to 4. Set to false to allow unlimited files.
  * </li>
  * <li><b>autoupload</b><br/>
  * Defaults to true. If false, then a button is displayed which the user must click to initiate upload of the files
  * currently in the queue.
  * </li>
  * <li><b>msgUploadError</b><br/>
  * Use this to override the message displayed for a generic file upload error.
  * </li>
  * <li><b>msgFileTooBig</b><br/>
  * Use this to override the message displayed when the file is larger than the size limit allowed on the Warehouse.
  * </li>
  * <li><b>msgTooManyFiles</b><br/>
  * Use this to override the message displayed when attempting to upload more files than the maxFileCount allows. Use a
  * replacement string [0] to specify the maxFileCount value.
  * </li>
  * <li><b>uploadScript</b><br/>
  * Specify the script used to handle image uploads on the server (relative to the client_helpers folder). You should not
  * normally need to change this. Defaults to upload.php.
  * </li>
  * <li><b>runtimes</b><br/>
  * Array of runtimes that the file upload component will use in order of priority. Defaults to
  * array('silverlight','flash','html5','gears','browserplus','html4'), though flash is removed for
  * Internet Explorer 6 and html5 is removed for Chrome. You should not normally need to change this.
  * </li>
  * <li><b>destinationFolder</b><br/>
  * Override the destination folder for uploaded files. You should not normally need to change this.
  * </li>
  * <li><b>swfAndXapFolder</b><br/>
  * Override the folder which the Plupload Flash (swf) and Silverlight (xap) files are loaded from. You should not
  * normally need to change this.
  * </li>
  * <li><b>codeGenerated</b>
  * If set to all (default), then this returns the HTML required and also inserts JavaScript in the document onload event. However, if you
  * need to delay the loading of the control until a certain event, e.g. when a radio button is checked, then this can be set
  * to php to return just the php and ignore the JavaScript, or js to return the JavaScript instead of inserting it into
  * document onload, in which case the php is ignored. this allows you to attach the JavaScript to any event you need to.
  * </li>
  * <li><b>tabDiv</b><br/>
  * If loading this control onto a set of tabs, specify the tab control's div ID here. This allows the control to
  * automatically generate code which only generates the uploader when the tab is shown, reducing problems in certain
  * runtimes. This has no effect if codeGenerated is not left to the default state of all.
  * </li>
  * </ul>
  *
  * @todo select file button pointer overriden by the flash shim
  * @todo flickr
  * @todo if using a normal file input, after validation, the input needs to show that the file upload has worked.
  * @todo Cleanup uploaded files that never got submitted because of validation failure elsewhere.
  */
  public static function file_box($options) {
    global $indicia_templates;
    // Upload directory defaults to client_helpers/upload, but can be overriden.
    $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
    $relpath = self::getRootFolder() . self::relative_client_helper_path();
    // Allow options to be defaulted and overridden
    $defaults = array(
      'caption' => lang::get('Files'),
      'id' => 'default',
      'upload' => true,
      'maxFileCount' => 4,
      'autoupload' => false,
      'flickr' => false,
      'uploadSelectBtnCaption' => lang::get('Select file(s)'),
      'flickrSelectBtnCaption' => lang::get('Choose photo from Flickr'),
      'startUploadBtnCaption' => lang::get('Start upload'),
      'msgUploadError' => lang::get('upload error'),
      'msgFileTooBig' => lang::get('file too big for warehouse'),
      'runtimes' => array('html5','silverlight','flash','gears','browserplus','html4'),
      'autoupload' => true,
      'imageWidth' => 200,
      'uploadScript' => $relpath . 'upload.php',
      'destinationFolder' => $relpath . $interim_image_folder,
      'finalImageFolder' => self::get_uploaded_image_folder(),
      'swfAndXapFolder' => $relpath . 'plupload/',
      'jsPath' => self::$js_path,
      'buttonTemplate' => $indicia_templates['button'],
      'table' => 'occurrence_image',
      'maxUploadSize' => self::convert_to_bytes(isset(parent::$maxUploadSize) ? parent::$maxUploadSize : '4M'),
      'codeGenerated' => 'all'
    );
    if (isset(self::$final_image_folder_thumbs))
      $defaults['finalImageFolderThumbs'] = $relpath . self::$final_image_folder_thumbs;
    $browser = self::get_browser_info();
    // Flash doesn't seem to work on IE6.
    if ($browser['name']=='msie' && $browser['version']<7)
      $defaults['runtimes'] = array_diff($defaults['runtimes'], array('flash'));
    if ($browser['name']=='chrome')
      $defaults['runtimes'] = array_diff($defaults['runtimes'], array('html5'));
    if ($indicia_templates['file_box']!='')
      $defaults['file_boxTemplate'] = $indicia_templates['file_box'];
    if ($indicia_templates['file_box_initial_file_info']!='')
      $defaults['file_box_initial_file_infoTemplate'] = $indicia_templates['file_box_initial_file_info'];
    if ($indicia_templates['file_box_uploaded_image']!='')
      $defaults['file_box_uploaded_imageTemplate'] = $indicia_templates['file_box_uploaded_image'];
    $options = array_merge($defaults, $options);
    $options['id'] = $options['table'] .'-'. $options['id'];
    $containerId = 'container-'.$options['id'];

    if ($options['codeGenerated']!='php') {
      // build the JavaScript including the required file links
      self::add_resource('plupload');
      foreach($options['runtimes'] as $runtime) {
        self::add_resource("plupload_$runtime");
      }
      // convert runtimes list to plupload format
      $options['runtimes'] = implode(',', $options['runtimes']);

      $javascript = "\n$('#".str_replace(':','\\\\:',$containerId)."').uploader({";
      // Just pass the options array through
      $idx = 0;
      foreach($options as $option=>$value) {
        if (is_array($value)) {
          $value = "{ " . implode(" : true, ",$value) . " : true }";
        }
        else
          // not an array, so wrap as string
          $value = "'$value'";
        $javascript .= "\n  $option : $value";
        // comma separated, except last entry
        if ($idx < count($options)-1) $javascript .= ',';
        $idx++;
      }
      // add in any reloaded items, when editing or after validation failure
      if (self::$entity_to_load) {
        $images = self::extract_image_data(self::$entity_to_load, $options['table']);
        $javascript .= ",\n  existingFiles : ".json_encode($images);
      }
      $javascript .= "\n});\n";
    }
    if ($options['codeGenerated']=='js')
      // we only want to return the JavaScript, so go no further.
      return $javascript;
    elseif ($options['codeGenerated']=='all') {
      if (isset($options['tabDiv'])) {
        // The file box is displayed on a tab, so we must only generate it when the tab is displayed.
        $javascript =
            "var tabHandler = function(event, ui) { \n".
            "  if (ui.panel.id=='".$options['tabDiv']."') {\n    ".
        $javascript.
            "    jQuery(jQuery('#".$options['tabDiv']."').parent()).unbind('tabsshow', tabHandler);\n".
            "  }\n};\n".
            "jQuery(jQuery('#".$options['tabDiv']."').parent()).bind('tabsshow', tabHandler);\n";
        // Insert this script at the beginning, because it must be done before the tabs are initialised or the
        // first tab cannot fire the event
        self::$javascript = $javascript . self::$javascript;
      }	else
        self::$onload_javascript .= $javascript;
    }
    // Output a placeholder div for the jQuery plugin. Also output a normal file input for the noscripts
    // version.
    return '<div class="file-box" id="'.$containerId.'"></div><noscript>'.self::image_upload(array(
      'label' => $options['caption'],
	  // Convert table into a psuedo field name for the images
      'id' => $options['id'],
      'fieldname' => str_replace('_', ':', $options['table'])
    )).'</noscript>';
  }

  /**
   * Calculates the folder that submitted images end up in according to the helper_config.
   */
  public static function get_uploaded_image_folder() {
    if (!isset(self::$final_image_folder) || self::$final_image_folder=='warehouse')
      return self::$base_url.(isset(self::$indicia_upload_path) ? self::$indicia_upload_path : 'upload/');
    else {
      return self::getRootFolder() . self::relative_client_helper_path() . self::$final_image_folder;
    }
  }

 /**
  * Generates a text input control with a search button that looks up an entered place against a georeferencing
  * web service. The control is automatically linked to any map panel added to the page.
  *
  * @param array $options Options array with the following possibilities:
  * <ul>
  * <li><b>fieldname</b><br/>
  * Optional. The name of the database field this control is bound to if any.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>georefPreferredArea</b><br/>
  * Optional. Hint provided to the locality search service as to which area to look for the place name in. Any example usage of this
  * would be to set it to the name of a region for a survey based in that region. Note that this is only a hint, and the search
  * service may still return place names outside the region. Defaults to gb.</li>
  * <li><b>georefCountry</b><br/>
  * Optional. Hint provided to the locality search service as to which country to look for the place name in. Defaults to United Kingdom.</li>
  * <li><b>georefLang</b><br/>
  * Optional. Language to request place names in. Defaults to en-EN for English place names.</li>
  * <li><b>driver</b><br/>
  * Optional. Driver to use for the georeferencing operation. Supported options are:<br/>
  *   geoplanet - uses the Yahoo! GeoPlanet place search. This is the default.<br/>
  *   google_search_api - uses the Google AJAX API LocalSearch service. This method requires both a
  *       georefPreferredArea and georefCountry to work correctly.<br/>
  *   geoportal_lu - Use the Luxembourg specific place name search provided by geoportal.lu.
  * </li>
  * </ul>
  * @link http://code.google.com/apis/ajaxsearch/terms.html Google AJAX Search API Terms of Use.
  * @link http://code.google.com/p/indicia/wiki/GeoreferenceLookupDrivers Documentation for the driver architecture.
  * @return string HTML to insert into the page for the georeference lookup control.
  */
  public static function georeference_lookup($options) {
    $options = self::check_options($options);
    $options = array_merge(array(
      'id' => 'imp-georef-search',
      'driver' => 'geoplanet',
      // Internationalise the labels here, because if we do this directly in the template setup code it is too early for any custom
      // language files to be loaded.
      'search' => lang::get('search'),
      'close' => lang::get('close'),
    ), $options);
    self::add_resource('indiciaMapPanel');
    // dynamically build a resource to link us to the driver js file.
    self::$required_resources[] = 'georeference_default_'.$options['driver'];
    self::$resource_list['georeference_default_'.$options['driver']] = array(
      'javascript' => array(self::$js_path.'drivers/georeference/'.$options['driver'].'.js')
    );
    // We need to see if there is a resource in the resource list for any special files required by this driver. This
    // will do nothing if the resource is absent.
    self::add_resource('georeference_'.$options['driver']);
    foreach ($options as $key=>$value) {
      // if any of the options are for the georeferencer driver, then we must set them in the JavaScript.
      if (substr($key, 0, 6)=='georef') {
        self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.$key='$value';\n";
      }
    }
    foreach (get_class_vars('helper_config') as $key=>$value) {
      // if any of the config settings are for the georeferencer driver, then we must set them in the JavaScript.
      if (substr($key, 0, strlen($options['driver']))==$options['driver']) {
        self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.$key='$value';\n";
      }
    }
    // If the lookup service driver uses cross domain JavaScript, this setting provides
    // a path to a simple PHP proxy script on the server.
    self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.proxy='".
        self::getRootFolder() . self::relative_client_helper_path() . "proxy.php';\n\n";
    return self::apply_template('georeference_lookup', $options);
  }

 /**
  * Simple file upload control suitable for uploading images to attach to occurrences.
  * Note that when using this control, it is essential that the form's HTML enctype attribute is
  * set to enctype="multipart/form-data" so that the image file is included in the form data. For multiple
  * image support and more advanced options, see the file_box control.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the file upload control.
  */
  public static function image_upload() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    $pathField = $pathField = str_replace(':image','_image:path', $options['fieldname']);
    $alreadyUploadedFile = self::check_default_value($pathField);
    $options = array_merge(array(
      'pathFieldName' => $pathField,
      'pathFieldValue' => $alreadyUploadedFile
    ), $options);
    $r = self::apply_template('image_upload', $options);
    if ($alreadyUploadedFile) {
      // The control is being reloaded after a validation failure. So we can display a thumbnail of the
      // already uploaded file, so the user knows not to re-upload.
      $interimImageFolder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      $r .= '<img width="100" src="$interimImageFolder$alreadyUploadedFile"/>'."\n";
    }
    return $r;
  }

  /**
   * A control for building JSON strings, based on http://robla.net/jsonwidget/. Dynamically
   * generates an input form for the JSON depending on a defined schema. This control
   * is not normally used for typical Indicia forms, but is used by the prebuilt
   * forms parameter entry forms for complex parameter structures such as the options
   * available for a chart.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * The name of the database or form parameter field this control is bound to, e.g. series_options.</li>
   * <li><b>if</b>
   * The HTML id of the output div.</li>
   * <li><b>schema</b>
   * Must be supplied with a schema string that defines the allowable structure of the JSON output. Schemas can be
   * automatically built using the schema generator at
   * http://robla.net/jsonwidget/example.php?sample=byexample&user=normal.</li>
   * <li><b>class</b>
   * Additional css class names to include on the outer div.</li>
   * </ul>
   * @return HTML string to insert in the form.
   */
  public static function jsonwidget($options) {
    $options = array_merge(array(
      'id' => 'jsonwidget_container',
      'fieldname' => 'jsonwidget',
      'schema' => '{}',
      'class'=> ''
    ), $options);
    $options['class'] = trim($options['class'].' control-box jsonwidget');

    self::add_resource('jsonwidget');
    extract($options, EXTR_PREFIX_ALL, 'opt');
    if (!isset($opt_default)) $opt_default = '';
    $opt_default = str_replace(array("\r","\n", "'"), array('\r','\n',"\'"), $opt_default);
    self::$javascript .= "$('#".$options['id']."').jsonedit({schema: $opt_schema, default: '$opt_default', fieldname: \"$opt_fieldname\"});\n";

    return self::apply_template('jsonwidget', $options);
  }

 /**
  * Outputs an autocomplete control that is dedicated to listing locations and which is bound to any map panel
  * added to the page. Although it is possible to set all the options of a normal autocomplete, generally
  * the table, valueField, captionField, id should be left uninitialised and the fieldname will default to the
  * sample's location_id field so can normally also be left.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Optional. The name of the database field this control is bound to.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>extraParams</b><br/>
  * Required. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the location select control.
  */
  public static function location_autocomplete($options) {
    $options = self::check_options($options);
    $options = array_merge(array(
        'table'=>'location',
        'fieldname'=>'sample:location_id',
        'valueField'=>'id',
        'captionField'=>'name',
        'id'=>'imp-location'
        ), $options);

    return self::autocomplete($options);
  }

 /**
  * Outputs a select control that is dedicated to listing locations and which is bound to any map panel
  * added to the page. Although it is possible to set all the options of a normal select control, generally
  * the table, valueField, captionField, id should be left uninitialised and the fieldname will default to the
  * sample's location_id field so can normally also be left. If you need to use a report to populate the list of
  * locations, for example when filtering by a custom attribute, then set the report option to the report name
  * (e.g. library/reports/locations_list) and provide report parameters in extraParams. You can also override
  * the captionField and valueField if required.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Optional. The name of the database field this control is bound to.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>extraParams</b><br/>
  * Required. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the location select control.
  */
  public static function location_select($options) {
    $options = self::check_options($options);
    // Apply location type filter if specified.
    if (array_key_exists('location_type_id', $options)) {
      $options['extraParams'] += array('location_type_id' => $options['location_type_id']);
    }
    $options = array_merge(array(
        'table'=>'location',
        'fieldname'=>'sample:location_id',
        'valueField'=>'id',
        'captionField'=>'name',
        'id'=>'imp-location'
        ), $options);
    return self::select($options);
  }

 /**
  * An HTML list box control.
  * Options can be either populated from a web-service call to the Warehouse, e.g. the contents of
  * a termlist, or can be populated from a fixed supplied array. The list box can
  * be linked to populate itself when an item is selected in another control by specifying the
  * parentControlId and filterField options.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Table name to get data from for the select options if the select is being populated by a service call.</li>
  * <li><b>captionField</b><br/>
  * Field to draw values to show in the control from if the select is being populated by a service call.</li>
  * <li><b>valueField</b><br/>
  * Field to draw values to return from the control from if the select is being populated by a service call. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array if the select is being populated by a service call.</li>
  * <li><b>lookupValues</b><br/>
  * If the select is to be populated with a fixed list of values, rather than via a service call, then the
  * values can be passed into this parameter as an associated array of key=>caption.
  * </li>
  * <li><b>size</b><br/>
  * Optional. Number of lines to display in the listbox. Defaults to 3.</li>
  * <li><b>multiselect</b><br/>
  * Optional. Allow multi-select in the list box. Defaults to false.</li>
  * <li><b>parentControlId}</b><br/>
  * Optional. Specifies a parent control for linked lists. If specified then this control is not
  * populated until the parent control's value is set. The parent control's value is used to
  * filter this control's options against the field specified by filterField.</li>
  * <li><b>parentControlLabel</b><br/>
  * Optional. Specifies the label of the parent control in a set of linked lists. This allows the child list
  * to display information about selecting the parent first.</li>
  * <li><b>filterField</b><br/>
  * Optional. Specifies the field to filter this control's content against when using a parent
  * control value to set up linked lists. Defaults to parent_id though this is not active
  * unless a parentControlId is specified.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>template</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the outer control.</li>
  * <li><b>itemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each item in the control.</li>
  * <li><b>captionTemplate</b><br/>
  * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
  * with each database field represented as {fieldname}.</li>
  * <li><b>listCaptionSpecialChars</b><br/>
  * Optional and only relevant when loading content from a data service call. Specifies whether to run the caption through
  * htmlspecialchars. In some cases there may be format info in the caption, and in others we may wish to keep those
  * characters as literal.
  * <li><b>selectedItemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the selected item in the control.</li></ul>
  * </ul>
  *
  * @return string HTML to insert into the page for the listbox control.
  */
  public static function listbox()
  {
    $options = self::check_arguments(func_get_args(), array(
        'fieldname', 'table', 'captionField', 'size', 'multiselect', 'valueField', 'extraParams', 'default'
    ));
    // blank text option not applicable to list box
    unset($options['blankText']);
    $options = array_merge(
      array(
        'template' => 'listbox',
        'itemTemplate' => 'listbox_item'
      ),
      $options
    );
    if(isset($options['multiselect']) && $options['multiselect']!=false && $options['multiselect']!=='false')
      $options['multiple']='multiple';
    return self::select_or_listbox($options);
  }

  /**
  * Helper function to list the output from a request against the data services, using an HTML template
  * for each item. As an example, the following outputs an unordered list of surveys:
  * <pre>echo data_entry_helper::list_in_template(array(
  *     'label'=>'template',
  *     'table'=>'survey',
  *     'extraParams' => $readAuth,
  *     'template'=>'<li>|title|</li>'
  * ));</pre>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Required. Table name to get data from for the select options.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>template</b><br/>
  * Required. HTML template which will be emitted for each item. Fields from the data are identified
  * by wrapping them in ||. For example, |term| would result in the field called term's value being placed inside
  * the HTML.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the generated list.
  */
  public static function list_in_template() {
    $options = self::check_arguments(func_get_args(), array('table', 'extraParams', 'template'));
    $response = self::get_population_data($options);
    $items = "";
    if (!array_key_exists('error', $response)){
      foreach ($response as $row){
        $item = $options['template'];
        foreach ($row as $field => $value) {
          $value = htmlspecialchars($value, ENT_QUOTES);
          $item = str_replace("|$field|", $value, $item);
        }
        $items .= $item;
      }
      $options['items']=$items;
      return self::apply_template('list_in_template', $options);
    }
    else
      return lang::get("error loading control");
  }

 /**
  * Generates a map control, with optional data entry fields and location finder powered by the
  * Yahoo! geoservices API. This is just a shortcut to building a control using a map_panel and the
  * associated controls.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>presetLayers</b><br/>
  * Array of preset layers to include. Options are 'google_physical', 'google_streets', 'google_hybrid',
  * 'google_satellite', 'openlayers_wms', 'nasa_mosaic', 'virtual_earth' (deprecated, use bing_aerial),
  * 'bing_aerial', 'bing_hybrid, 'bing_shaded', 'multimap_default', 'multimap_landranger', 
  * 'osm' (for OpenStreetMap), 'osm_th' (for OpenStreetMap Tiles@Home).</li>
  * <li><b>edit</b><br/>
  * True or false to include the edit controls for picking spatial references.</li>
  * <li><b>locate</b><br/>
  * True or false to include the geolocate controls.</li>
  * <li><b>wkt</b><br/>
  * Well Known Text of a spatial object to add to the map at startup.</li>
  * <li><b>tabDiv</b><br/>
  * If the map is on a tab or wizard interface, specify the div the map loads on.</li>
  * </ul>
  */
  public static function map() {
    $options = self::check_arguments(func_get_args(), array('div', 'presetLayers', 'edit', 'locate', 'wkt'));
    $options = array_merge(array(
        'div'=>'map',
        'edit'=>true,
        'locate'=>true,
        'wkt'=>null
    ), $options);
    $r = '';
    if ($options['edit']) {
      $r .= self::sref_and_system(array(
          'label'=>lang::get('spatial ref'),
      ));
    }
    if ($options['locate']) {
      $r .= self::georeference_lookup(array(
          'label'=>lang::get('search for place on map')
      ));
    }
    $mapPanelOptions = array('initialFeatureWkt' => $options['wkt']);
    if (array_key_exists('presetLayers', $options)) $mapPanelOptions['presetLayers'] = $options['presetLayers'];
    if (array_key_exists('tabDiv', $options)) $mapPanelOptions['tabDiv'] = $options['tabDiv'];
    $r .= self::map_panel($mapPanelOptions);
    return $r;
  }

 /**
  * Outputs a map panel.
  * @deprecated Use map_helper::map_panel instead.
  */
  public static function map_panel($options, $olOptions=null) {
    require_once('map_helper.php');
    return map_helper::map_panel($options, $olOptions);
  }

 /**
  * Helper function to output an HTML password input. For security reasons, this does not re-load existing values
  * or display validation error messages and no default can be set.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name by which the password will be passed to the authentication system.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the text input control.
  */
  public static function password_input() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    $options = array_merge(array(
      'default'=>''
    ), $options);
    return self::apply_template('password_input', $options);
  }

 /**
  * Helper function to output a textbox for determining a locality from an entered postcode.
  *
  * <p>The textbox optionally includes hidden fields for the latitude and longitude and can
  * link to an address control for automatic generation of address information. When the focus
  * leaves the textbox, the Google AJAX Search API is used to obtain the latitude and longitude
  * so they can be saved with the record.</p>
  *
  * <p>The following example displays a postcode box and an address box, which is auto-populated
  * when a postcode is given. The spatial reference controls are "hidden" from the user but
  * are available to post into the database.</p>
  * <code>
  * <?php echo data_entry_helper::postcode_textbox(array(
  *     'label'=>'Postcode',
  *     'fieldname'=>'smpAttr:8',
  *     'linkedAddressBoxId'=>'address'
  * );
  * echo data_entry_helper::textarea(array(
  *     'label' => 'Address',
  *     'id' => 'address',
  *     'fieldname' => 'smpAttr:9'
  * ));?>
  * </code>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. This should be left to its default value for
  * integration with other mapping controls to work correctly.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>hiddenFields</b><br/>
  * Optional. Set to true to insert hidden inputs to receive the latitude and longitude. Otherwise there
  * should be separate sref_textbox and sref_system_textbox controls elsewhere on the page. Defaults to true.
  * <li><b>srefField</b><br/>
  * Optional. Name of the spatial reference hidden field that will be output by this control if hidddenFields is true.</li>
  * <li><b>systemField</b><br/>
  * Optional. Name of the spatial reference system hidden field that will be output by this control if hidddenFields is true.</li>
  * <li><b>linkedAddressBoxId</b><br/>
  * Optional. Id of the separate textarea control that will be populated with an address when a postcode is looked up.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the postcode control.
  */
  public static function postcode_textbox($options) {
    // The id field default must take precedence over using the fieldname as the id
    $options = array_merge(array('id'=>'imp-postcode'), $options);
    $options = self::check_options($options);
    // Merge in the defaults
    $options = array_merge(array(
        'srefField'=>'sample:entered_sref',
        'systemField'=>'sample:entered_sref_system',
        'hiddenFields'=>true,
        'linkedAddressBoxId'=>''
        ), $options);
    self::add_resource('google_search');
    $r = self::apply_template('postcode_textbox', $options);
    if ($options['hiddenFields']) {
      $defaultSref=self::check_default_value($options['srefField']);
      $defaultSystem=self::check_default_value($options['systemField'], '4326');
      $r .= "<input type='hidden' name='".$options['srefField']."' id='imp-sref' value='$defaultSref' />";
      $r .= "<input type='hidden' name='".$options['systemField']."' id='imp-sref-system' value='$defaultSystem' />";
    }
    $r .= self::check_errors($options['fieldname']);
    return $r;
  }

 /**
  * Helper function to generate a radio group from a Indicia core service query.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>table</b><br/>
  * Optional. Table name to get data from for the select options. Required unless lookupValues is specified.</li>
  * <li><b>captionField</b><br/>
  * Optional. Field to draw values to show in the control from. Required unless lookupValues is specified.</li>
  * <li><b>valueField</b><br/>
  * Optional. Field to draw values to return from the control from. Defaults
  * to the value of captionField. </li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>lookupValues</b><br/>
  * If the group is to be populated with a fixed list of values, rather than via a service call, then the
  * values can be passed into this parameter as an associated array of key=>caption.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>template</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the outer control.</li>
  * <li><b>itemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each item in the control.</li>
  * <li><b>captionTemplate</b><br/>
  * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
  * with each database field represented as {fieldname}.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the group of radio buttons.
  */
  public static function radio_group() {
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'sep', 'default'));
    return self::check_or_radio_group($options, 'radio');
  }

  /**
   * Returns a simple HTML link to download the contents of a report defined by the options. The options arguments supported are the same as for the
   * report_grid method. Pagination information will be ignored (e.g. itemsPerPage).
   * @deprecated Use report_helper::report_download_link.
   */
  public static function report_download_link($options) {
    require_once('report_helper.php');
    return report_helper::report_download_link($options);
  }

  /**
   * Outputs a grid that loads the content of a report or Indicia table.
   * @deprecated Use report_helper::report_grid.
   */
  public static function report_grid($options) {
    require_once('report_helper.php');
    return report_helper::report_grid($options);
  }

  /**
   * Outputs a chart that loads the content of a report or Indicia table.
   * @deprecated Use report_helper::report_chart.
   */
  public static function report_chart($options) {
    require_once('report_helper.php');
    return report_helper::report_chart($options);
  }

 /**
  * Helper function to generate a select control from a Indicia core service query. The select control can
  * be linked to populate itself when an item is selected in another control by specifying the
  * parentControlId and filterField options.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>  *
  * <li><b>table</b><br/>
  * Table name to get data from for the select options if the select is being populated by a service call.</li>
  * <li><b>report</b><br/>
  * Report name to get data from for the select options if the select is being populated by a service call using a report.
  * Mutually exclusive with the table option.</li>
  * <li><b>captionField</b><br/>
  * Field to draw values to show in the control from if the select is being populated by a service call.</li>
  * <li><b>valueField</b><br/>
  * Field to draw values to return from the control from if the select is being populated by a service call. Defaults
  * to the value of captionField.</li>
  * <li><b>extraParams</b><br/>
  * Optional. Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array if the select is being populated by a service call.</li>
  * <li><b>lookupValues</b><br/>
  * If the select is to be populated with a fixed list of values, rather than via a service call, then the
  * values can be passed into this parameter as an associated array of key=>caption.
  * </li>
  * <li><b>parentControlId</b><br/>
  * Optional. Specifies a parent control for linked lists. If specified then this control is not
  * populated until the parent control's value is set. The parent control's value is used to
  * filter this control's options against the field specified by filterField.</li>
  * <li><b>parentControlLabel</b><br/>
  * Optional. Specifies the label of the parent control in a set of linked lists. This allows the child list
  * to display information about selecting the parent first.</li>
  * <li><b>filterField</b><br/>
  * Optional. Specifies the field to filter this control's content against when using a parent
  * control value to set up linked lists. Defaults to parent_id though this is not active
  * unless a parentControlId is specified.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>blankText</b><br/>
  * Optional. If specified then the first option in the drop down is the blank text, used when there is no value.</li>
  * <li><b>template</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the outer control.</li>
  * <li><b>itemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each item in the control.</li>
  * <li><b>captionTemplate</b><br/>
  * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
  * with each database field represented as {fieldname}.</li>
  * <li><b>listCaptionSpecialChars</b><br/>
  * Optional and only relevant when loading content from a data service call. Specifies whether to run the caption through
  * htmlspecialchars. In some cases there may be format info in the caption, and in others we may wish to keep those
  * characters as literal.
  * <li><b>selectedItemTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for the selected item in the control.</li></ul>
  *
  * @return string HTML code for a select control.
  */
  public static function select()
  {
    $options = self::check_arguments(func_get_args(), array(
        'fieldname', 'table', 'captionField', 'valueField', 'extraParams', 'default'
    ));
    $options = array_merge(
      array(
        'template' => 'select',
        'itemTemplate' => 'select_item'
      ),
      $options
    );
    return self::select_or_listbox($options);
  }

 /**
  * Outputs a spatial reference input box and a drop down select control populated with a list of
  * spatial reference systems for the user to select from. If there is only 1 system available then
  * the system drop down is ommitted since it is not required.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. Name of the database field that spatial reference will be posted to. Defaults to
  * sample:entered_sref. The system field is automatically constructed from this.</li>
  * <li><b>systems</b>
  * Optional. List of spatial reference systems to display. Associative array with the key
  * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
  * the description to display.</li>
  * <li><b>defaultSystem</b>
  * Optional. Code for the default system value to load.</li>
  * <li><b>defaultGeom</b>
  * Optional. WKT value for the default geometry to load (hidden).</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the spatial reference and system selection control.
  */
  public static function sref_and_system($options) {
    $options = array_merge(array(
      'fieldname'=>'sample:entered_sref'
    ), $options);
    // Force no separate lines for the 2 controls
    if (!array_key_exists('systems',$options) || count($options['systems'])!=1) {
      $srefOptions = array_merge($options, array('suffixTemplate'=>'nosuffix'));
      $srefOptions = array_merge($options, array('requiredsuffixTemplate'=>'requirednosuffix'));
    } else {
      $srefOptions = $options;
    }
    $r = self::sref_textbox($srefOptions);
    // tweak the options passed to the system selector
    $options['fieldname']=$options['fieldname']."_system";
    unset($options['label']);
    if (array_key_exists('systems', $options) && count($options['systems']) == 1) {
      // Hidden field for the system
      $keys = array_keys($options['systems']);
      $r .= "<input type=\"hidden\" id=\"imp-sref-system\" name=\"".$options['fieldname']."\" value=\"".$keys[0]."\" />\n";
    }
    else {
      if (isset($options['defaultSystem']))
        $options['default']=$options['defaultSystem'];
      $r .= self::sref_system_select($options);
    }
    return $r;
  }

 /**
  * Outputs a drop down select control populated with a list of spatial reference systems
  * for the user to select from.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to. Defaults to sample:entered_sref_system.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>systems</b>
  * Optional. List of spatial reference systems to display. Associative array with the key
  * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
  * the description to display.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the spatial reference systems selection control.
  */
  public static function sref_system_select($options) {
    global $indicia_templates;
    $options = array_merge(array(
        'fieldname'=>'sample:entered_sref_system',
        'systems'=>array('OSGB'=>lang::get('british national grid'), '4326'=>lang::get('lat long 4326')),
        'id'=>'imp-sref-system'
    ), $options);
    $options = self::check_options($options);
    $opts = "";
    foreach ($options['systems'] as $system=>$caption){
      $selected = ($options['default'] == $system ? 'selected' : '');
      $opts .= str_replace(
          array('{value}', '{caption}', '{selected}'),
          array($system, $caption, $selected),
          $indicia_templates['select_item']
      );
    }
    $options['items'] = $opts;
    return self::apply_template('select', $options);
  }

 /**
  * Creates a textbox for entry of a spatial reference.
  * Also generates the hidden geom field required to properly post spatial data. The
  * box is automatically linked to a map_panel if one is added to the page.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to. Defaults to sample:entered_sref.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>defaultGeom</b><br/>
  * Optional. The default geom (wkt) to store in a hidden input posted with the form data.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>splitLatLong</b><br/>
  * Optional. If set to true, then 2 boxes are created, one for the latitude and one for the longitude.</li>
  * <li><b>geomFieldname</b><br/>
  * Optional. Fieldname to use for the geom (table:fieldname format) where the geom field is not
  * just called geom, e.g. location:centroid_geom.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the spatial reference control.
  * @todo This does not work for reloading data at the moment, when using split lat long mode.
  */
  public static function sref_textbox($options) {
    // get the table and fieldname
    $tokens=explode(':', $options['fieldname']);
    // Merge the default parameters
    $options = array_merge(array(
        'srefField'=>'sample:entered_sref',
        'systemfield'=>'sample:entered_sref_system',
        'hiddenFields'=>true,
        'id'=>'imp-sref',
        'geomFieldname'=>$tokens[0].':geom',
        'default'=>self::check_default_value($options['fieldname']),
        'splitLatLong'=>false
    ), $options);
    if (!isset($options['defaultGeom']))
      $options['defaultGeom']=self::check_default_value($options['geomFieldname']);
    $options = self::check_options($options);
    if ($options['splitLatLong']) {
      // Outputting separate lat and long fields, so we need a few more options
      $parts = explode(' ',$options['default']);
      $parts[0] = explode(',', $parts[0]);
      $options = array_merge(array(
        'defaultLat' => $parts[0][0],
        'defaultLong' => $parts[1],
        'fieldnameLat' => $options['srefField'].'_lat',
        'fieldnameLong' => $options['srefField'].'_long',
        'labelLat' => lang::get('Latitude'),
        'labelLong' => lang::get('Longitude'),
        'idLat'=>'imp-sref-lat',
        'idLong'=>'imp-sref-long'
      ), $options);
      unset($options['label']);
      $r = self::apply_template('sref_textbox_latlong', $options);
    } else
      $r = self::apply_template('sref_textbox', $options);
    return $r;
  }

 /**
  * Helper function to generate a species checklist from a given taxon list.
  *
  * <p>This function will generate a flexible grid control with one row for each species
  * in the specified list. For each row, the control will display the list preferred term
  * for that species, a checkbox to indicate its presence, and a series of cells for a set
  * of occurrence attributes passed to the control.</p>
  *
  * <p>Further, the control will incorporate the functionality to add extra terms to the
  * control from the parent list of the one given. This will take the form of an autocomplete
  * box against the parent list which will add an extra row to the control upon selection.</p>
  *
  * <p>To change the format of the label displayed for each taxon in the grid rows that are pre-loaded into the grid,
  * use the global $indicia_templates variable to set the value for the entry 'taxon_label'. The tags available in the template are {taxon}, {preferred_name},
  * {authority} and {common}. This can be a PHP snippet if PHPtaxonLabel is set to true.</p>
  *
  * <p>To change the format of the label displayed for each taxon in the autocomplete used for searching for species to add to the grid,
  * use the global $indicia_templates variable to set the value for the entry 'format_species_autocomplete_fn'. This must be a JavaScript function
  * which takes a single parameter. The parameter is the item returned from the database with attributes taxon, preferred ('t' or 'f'),
  * preferred_name, common, authority, taxon_group, language. The function must return the string to display in the autocomplete list.</p>
  *
  * <p>To perform an action on the event of a new row being added to the grid, write a JavaScript function called hook_species_checklist_new_row(data), where data
  * is an object containing the details of the taxon row as loaded from the data services.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>listId</b><br/>
  * Optional. The ID of the taxon_lists record which is to be used to obtain the species or taxon list. This is
  * required unless lookupListId is provided.</li>
  * <li><b>occAttrs</b><br/>
  * Optional integer array, where each entry corresponds to the id of the desired attribute in the
  * occurrence_attributes table. If omitted, then all the occurrence attributes for this survey are loaded.</li>
  * <li><b>occAttrClasses</b><br/>
  * String array, where each entry corresponds to the css class(es) to apply to the corresponding
  * attribute control (i.e. there is a one to one match with occAttrs). If this array is shorter than
  * occAttrs then all remaining controls re-use the last class.</li>
  * <li><b>extraParams</b><br/>
  * Associative array of items to pass via the query string to the service. This
  * should at least contain the read authorisation array.</li>
  * <li><b>lookupListId</b><br/>
  * Optional. The ID of the taxon_lists record which is to be used to select taxa from when adding
  * rows to the grid. If specified, then an autocomplete text box and Add Row button are generated
  * automatically allowing the user to pick a species to add as an extra row.</li>
  * <li><b>header</b><br/>
  * Include a header row in the grid? Defaults to true.</li>
  * <li><b>columns</b><br/>
  * Number of repeating columns of output. For example, a simple grid of species checkboxes could be output in 2 or 3 columns.
  * Defaults to 1.</li>
  * <li><b>rowInclusionCheck</b><br/>
  * Defines how the system determines whether a row in the grid actually contains an occurrence or not. There are 3 options: <br/>
  * checkbox - a column is included in the grid containing a presence checkbox. If checked then an occurrence is created for the row. This is the default.<br/>
  * alwaysFixed - occurrences are created for all rows in the grid. Rows cannot be removed from the grid apart from newly added rows.<br/>
  * alwaysRemovable - occurrences are created for all rows in the grid. Rows can always be removed from the grid. Best used with no listId so there are
  * no default taxa in the grid, otherwise editing an existing sample will re-add all the existing taxa.<br/>
  * hasData - occurrences are created for any row which has a data value specified in at least one of its columns. <br/>
  * This option supercedes the checkboxCol option which is still recognised for backwards compatibility.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>survey_id</b><br/>
  * Optional. Used to determine which attributes are valid for this website/survey combination</li>
  * <li><b>occurrenceComment</b><br/>
  * Optional. If set to true, then an occurrence comment input field is included on each row.</li>
  * <li><b>occurrenceConfidential</b><br/>
  * Optional. If set to true, then an occurrence confidential checkbox is included on each row.</li>
  * <li><b>occurrenceImages</b><br/>
  * Optional. If set to true, then images can be uploaded for each occurrence row. Currently not supported for
  * multi-column grids.</li>
  * <li><b>resizeWidth</b><br/>
  * If set, then the image files will be resized before upload using this as the maximum pixels width.
  * </li>
  * <li><b>resizeHeight</b><br/>
  * If set, then the image files will be resized before upload using this as the maximum pixels height.
  * </li>
  * <li><b>resizeQuality</b><br/>
  * Defines the quality of the resize operation (from 1 to 100). Has no effect unless either resizeWidth or resizeHeight are non-zero.
  * <li><b>colWidths</b><br/>
  * Optional. Array containing percentage values for each visible column's width, with blank entries for columns that are not specified. If the array is shorter
  * than the actual number of columns then the remaining columns use the default width determined by the browser.</li>
  * <li><b>attrCellTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each cell containing an attribute input control. Valid replacements are {label}, {class} and {content}.
  * Default is attribute_cell.</li>
  * <li><b>language</b><br/>
  * language used to filter lookup list items in attributes..</li>
  * <li><b>PHPtaxonLabel</b></li>
  * If set to true, then the taxon_label template should contain a PHP statement that returns the HTML to display for each
  * taxon's label. Otherwise the template should be plain HTML. Defaults to false.
  * </ul>
  */
  public static function species_checklist()
  {
    global $indicia_templates;
    $options = self::check_arguments(func_get_args(), array('listId', 'occAttrs', 'readAuth', 'extraParams', 'lookupListId'));
    $options = self::get_species_checklist_options($options);
    if ($options['columns']>1 && $options['occurrenceImages'])
      throw new Exception('The species_checklist control does not support having more than one occurrence per row (columns option > 0) '.
          'at the same time has having the occurrenceImages option enabled.');
    self::add_resource('json');
    self::add_resource('autocomplete');
    if ($options['occurrenceImages']) {
      self::add_resource('plupload');
      // store some globals that we need later when creating uploaders
      $relpath = self::getRootFolder() . self::relative_client_helper_path();
      $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      self::$javascript .= "uploadSettings = {\n";
      self::$javascript .= "  uploadScript: '" . $relpath . "upload.php',\n";
      self::$javascript .= "  destinationFolder: '" . $relpath . $interim_image_folder."',\n";
      self::$javascript .= "  swfAndXapFolder: '" . $relpath . "plupload/',\n";
      self::$javascript .= "  jsPath: '".self::$js_path."'";
      if (isset($options['resizeWidth'])) {
        self::$javascript .= ",\n  resizeWidth: ".$options['resizeWidth'];
      }
      if (isset($options['resizeHeight'])) {
        self::$javascript .= ",\n  resizeHeight: ".$options['resizeHeight'];
      }
      if (isset($options['resizeQuality'])) {
        self::$javascript .= ",\n  resizeQuality: ".$options['resizeQuality'];
      }
      self::$javascript .= "\n}\n";
      if ($indicia_templates['file_box']!='')
        self::$javascript .= "file_boxTemplate = '".str_replace('"','\"', $indicia_templates['file_box'])."';\n";
      if ($indicia_templates['file_box_initial_file_info']!='')
        self::$javascript .= "file_box_initial_file_infoTemplate = '".str_replace('"','\"', $indicia_templates['file_box_initial_file_info'])."';\n";
      if ($indicia_templates['file_box_uploaded_image']!='')
        self::$javascript .= "file_box_uploaded_imageTemplate = '".str_replace('"','\"', $indicia_templates['file_box_uploaded_image'])."';\n";
    }
    $occAttrControls = array();
    $occAttrs = array();
    $taxaThatExist = array();

    // Load any existing sample's occurrence data into $entity_to_load
    if (isset(self::$entity_to_load['sample:id']))
      self::preload_species_checklist_occurrences(self::$entity_to_load['sample:id'], $options['readAuth'], $options['occurrenceImages']);
    // load the full list of species for the grid, including the main checklist plus any additional species in the reloaded occurrences.
	  $taxalist = self::get_species_checklist_taxa_list($options, $taxaThatExist);
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $taxalist)) {
      $attributes = self::getAttributes(array(
          'id' => null
           ,'valuetable'=>'occurrence_attribute_value'
           ,'attrtable'=>'occurrence_attribute'
           ,'key'=>'occurrence_id'
           ,'fieldprefix'=>"sc:-ttlId-::occAttr"
           ,'extraParams'=>$options['readAuth']
           ,'survey_id'=>array_key_exists('survey_id', $options) ? $options['survey_id'] : null
      ));
      // Get the attribute and control information required to build the custom occurrence attribute columns
      self::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrs);
      $grid = "\n";
      if (isset($options['lookupListId'])) {
        $grid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      }
      $grid .= '<table class="ui-widget ui-widget-content species-grid '.$options['class'].'" id="'.$options['id'].'">';
      $grid .= self::get_species_checklist_header($options, $occAttrs);
      $rows = array();
      $rowIdx = 0;
      foreach ($taxalist as $taxon) {
        $id = $taxon['id'];
        // Get the cell content from the taxon_label template
        $firstCell = self::mergeParamsIntoTemplate($taxon, 'taxon_label');
        // If the taxon label template is PHP, evaluate it.
        if ($options['PHPtaxonLabel']) $firstCell=eval($firstCell);
        // Now create the table cell to contain this.
        $colspan = isset($options['lookupListId']) && $options['rowInclusionCheck']!='alwaysRemovable' ? ' colspan="2"' : '';
        $row = '';
        // Add a X button if the user can remove rows
        if ($options['rowInclusionCheck']=='alwaysRemovable')
          $row .= '<td class="ui-state-default remove-row" style="width: 1%">X</td>';
        $row .= str_replace('{content}', $firstCell,
            str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell'])
        );

        $existing_record_id = false;
        if (is_array(self::$entity_to_load)) {
          $search = preg_grep("/^sc:$id:[0-9]*:present$/", array_keys(self::$entity_to_load));
          if (count($search)===1) {
            // we have to implode the search result as the key can be not zero, then strip out the stuff other than the occurrence Id.
            $existing_record_id = str_replace(array("sc:$id:",":present"), '', implode('', $search));
          }
        }
        $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
        // AlwaysFixed mode means all rows in the default checklist are included as occurrences. Same for
        // AlwayeRemovable except that the rows can be removed.
        if ($options['rowInclusionCheck']=='alwaysFixed' || $options['rowInclusionCheck']=='alwaysRemovable' ||
            (self::$entity_to_load!=null && array_key_exists("sc:$id:$existing_record_id:present", self::$entity_to_load))) {
          $checked = ' checked="checked"';
        } else {
          $checked='';
        }
        $row .= "\n<td class=\"scPresenceCell\"$hidden>";
        if ($options['rowInclusionCheck']!='hasData')
          // this includes a control to force out a 0 value when the checkbox is unchecked.
          $row .= "<input type=\"hidden\" class=\"scPresence\" name=\"sc:$id:$existing_record_id:present\" value=\"0\"/><input type=\"checkbox\" class=\"scPresence\" name=\"sc:$id:$existing_record_id:present\" $checked />";
        $row .= "</td>";
        foreach ($occAttrControls as $attrId => $control) {
          if ($existing_record_id) {
            $search = preg_grep("/^sc:$id:$existing_record_id:occAttr:$attrId".'[:[0-9]*]?$/', array_keys(self::$entity_to_load));
            $ctrlId = (count($search)===1) ? implode('', $search) : str_replace('-ttlId-:', $id.':'.$existing_record_id, $attributes[$attrId]['fieldname']);
          } else {
            $ctrlId = str_replace('-ttlId-', $id, $attributes[$attrId]['fieldname']);
          }
          if (isset(self::$entity_to_load[$ctrlId])) {
            $existing_value = self::$entity_to_load[$ctrlId];
          } elseif (array_key_exists('default', $attributes[$attrId])) {
            // this case happens when reloading an existing record
            $existing_value = $attributes[$attrId]['default'];
          } else
            $existing_value = '';
            // inject the field name into the control HTML
          $oc = str_replace('{fieldname}', $ctrlId, $control);
          if ($existing_value<>"") {
            // For select controls, specify which option is selected from the existing value
            if (substr($oc, 0, 7)=='<select') {
              $oc = str_replace('value="'.$existing_value.'"',
                  'value="'.$existing_value.'" selected="selected"', $oc);
            } else if(strpos($oc, 'checkbox') !== false) {
              if($existing_value=="1")
                $oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
            } else {
              $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
            }
            $error = self::check_errors("sc:$id::occAttr:$attrId");
            if (!$error)
              // double check in case there is an error against the whole column
              $error = self::check_errors("occAttr:$attrId");
            if ($error) {
              $oc = str_replace("class='", "class='ui-state-error ", $oc);
              $oc .= $error;
            }
          }
          $row .= str_replace(array('{label}', '{content}'), array(lang::get($attributes[$attrId]['caption']), $oc), $indicia_templates[$options['attrCellTemplate']]);
        }
        if ($options['occurrenceComment']) {
          $row .= "\n<td class=\"ui-widget-content scCommentCell\"><input class=\"scComment\" type=\"text\" name=\"sc:$id:$existing_record_id:occurrence:comment\" ".
          "id=\"sc:$id:$existing_record_id:occurrence:comment\" value=\"".self::$entity_to_load["sc:$id:$existing_record_id:occurrence:comment"]."\" /></td>";
        }
        if (isset($options['occurrenceConfidential']) && $options['occurrenceConfidential']) {
          $row .= "\n<td class=\"ui-widget-content scConfidentialCell\">";
          $row .= self::checkbox(array('fieldname'=>"sc:$id:$existing_record_id:occurrence:confidential"));
          $row .= "</td>\n";
        }
        if ($options['occurrenceImages']) {
          $existingImages = is_array(self::$entity_to_load) ? preg_grep("/^sc:$id:$existing_record_id:occurrence_image:id:[0-9]*$/", array_keys(self::$entity_to_load)) : array();
          if (count($existingImages)===0)
            $row .= "\n<td class=\"ui-widget-content scImageLinkCell\"><a href=\"\" class=\"add-image-link scImageLink\" id=\"add-images:$id:$existing_record_id\">".
                str_replace(' ','&nbsp;',lang::get('add images')).'</a></td>';
          else
            $row .= "\n<td class=\"ui-widget-content scImageLinkCell\"><a href=\"\" class=\"hide-image-link scImageLink\" id=\"hide-images:$id:$existing_record_id\">".
                str_replace(' ','&nbsp;',lang::get('hide images')).'</a></td>';
        }
        // Are we in the first column? Note multi-column grids are disabled if using occurrenceImages as it adds extra rows and messes things up.
        if ($options['occurrenceImages'] || $rowIdx < count($taxalist)/$options['columns']) {
          $rows[$rowIdx]=$row;
        } else {
          $rows[$rowIdx % (ceil(count($taxalist)/$options['columns']))] .= $row;
        }
        $rowIdx++;
        if ($options['occurrenceImages']) {
          // If there are existing images for this row, display the image control
          if (count($existingImages)>0) {
            $totalCols = ($options['lookupListId'] ? 2 : 1) + 1 /*checkboxCol*/ + ($options['occurrenceImages'] ? 1 : 0) + count($occAttrControls);
            $rows[$rowIdx]='<td colspan="'.$totalCols.'">'.data_entry_helper::file_box(array(
              'table'=>"sc:$id:$existing_record_id:occurrence_image",
              'label'=>lang::get('Upload your photos')
            )).'</td>';
            $rowIdx++;
          }
        }
      }
      $grid .= "\n<tbody>\n";
      if (count($rows)>0)
        $grid .= "<tr>".implode("</tr>\n<tr>", $rows)."</tr>\n";
      else
        $grid .= "<tr style=\"display: none\"><td></td></tr>\n";
      $grid .= "</tbody>\n</table>\n";
      // in hasData mode, the wrap_species_checklist method must be notified of the different default way of checking if a row is to be
      // made into an occurrence
      if ($options['rowInclusionCheck']=='hasData')
        $grid .= '<input name="rowInclusionCheck" value="hasData" type="hidden" />';
      if (isset($options['lookupListId']) || (isset($options['occurrenceImages']) && $options['occurrenceImages']))
        // include a js file that has code for handling grid rows, including adding image rows.
        self::add_resource('addrowtogrid');
      // If the lookupListId parameter is specified then the user is able to add extra rows to the grid,
      // selecting the species from this list. Add the required controls for this.
      if (isset($options['lookupListId'])) {
        // Javascript to add further rows to the grid
        if (isset($indicia_templates['format_species_autocomplete_fn'])) {
          self::$javascript .= 'var formatter = '.$indicia_templates['format_species_autocomplete_fn'];
        } else {
          self::$javascript .= "var formatter = '".$indicia_templates['taxon_label']."';\n";
        }
        self::$javascript .= "addRowToGrid('".parent::$base_url."index.php/services/data"."', '".
            $options['id']."', '".$options['lookupListId']."', {'auth_token' : '".
            $options['readAuth']['auth_token']."', 'nonce' : '".$options['readAuth']['nonce']."'},".
            " formatter);\r\n";
      }
      // If options contain a help text, output it at the end if that is the preferred position
      $options['helpTextClass'] = 'helpTextLeft';
      $r = self::get_help_text($options, 'before');
      $r = $grid;
      $r .= self::get_help_text($options, 'after');
      return $r;
    } else {
      return $taxalist['error'];
    }

  }

  /**
   * Normally, the species checklist will handle loading the list of occurrences from the database automatically.
   * However, when a form needs access to occurrence data before loading the species checklist, this method
   * can be called to preload the data. The data is loaded into data_entry_helper::$entity_to_load and the count
   * of occurrences loaded is returned.
   * @param int $sampleId ID of the sample to load
   * @param array $readAuth Read authorisation array
   * @return int Number of occurrences that were loaded.
   */
  public static function preload_species_checklist_occurrences($sampleId, $readAuth, $loadImages) {
    $occurrenceIds = array();
    // don't load from the db if there are validation errors, since the $_POST will already contain all the
    // data we need.
    if (is_null(self::$validation_errors)) {
      $occurrences = self::get_population_data(array(
        'table' => 'occurrence',
        'extraParams' => $readAuth + array('view'=>'detail','sample_id'=>$sampleId,'deleted'=>'f'),
        'nocache' => true
      ));
      foreach($occurrences as $occurrence){
        self::$entity_to_load['occurrence:record_status']=$occurrence['record_status'];
        self::$entity_to_load['sc:'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':present'] = true;
        self::$entity_to_load['sc:'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':occurrence:comment'] = $occurrence['comment'];
        self::$entity_to_load['sc:'.$occurrence['taxa_taxon_list_id'].':'.$occurrence['id'].':occurrence:confidential'] = $occurrence['confidential'];
        self::$entity_to_load['occurrence:taxa_taxon_list_id']=$occurrence['taxa_taxon_list_id'];
        self::$entity_to_load['occurrence:taxa_taxon_list_id:taxon']=$occurrence['taxon'];
        // Keep a list of all Ids
        $occurrenceIds[$occurrence['id']] = $occurrence['taxa_taxon_list_id'];
      }
      // load the attribute values into the entity to load as well
      $attrValues = self::get_population_data(array(
        'table' => 'occurrence_attribute_value',
        'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
        'nocache' => true
      ));
      foreach($attrValues as $attrValue) {
        self::$entity_to_load['sc:'.$occurrenceIds[$attrValue['occurrence_id']].':'.$attrValue['occurrence_id'].':occAttr:'.$attrValue['occurrence_attribute_id'].(isset($attrValue['id'])?':'.$attrValue['id']:'')]
            = $attrValue['raw_value'];
      }
      if ($loadImages) {
        $images = self::get_population_data(array(
          'table' => 'occurrence_image',
          'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
          'nocache' => true
        ));
        foreach($images as $image) {
          self::$entity_to_load['sc:'.$occurrenceIds[$image['occurrence_id']].':'.$image['occurrence_id'].':occurrence_image:id:'.$image['id']]
              = $image['id'];
          self::$entity_to_load['sc:'.$occurrenceIds[$image['occurrence_id']].':'.$image['occurrence_id'].':occurrence_image:path:'.$image['id']]
              = $image['path'];
          self::$entity_to_load['sc:'.$occurrenceIds[$image['occurrence_id']].':'.$image['occurrence_id'].':occurrence_image:caption:'.$image['id']]
              = $image['caption'];
        }
      }
    }
    return $occurrenceIds;
  }

  /**
   * Retrieve the grid header row for the species checklist grid control.
   * @param array $options Control options array.
   * @param array $occAttrs Array of custom attributes included in the grid.
   * @return string Html for the <thead> element.
   */
  public static function get_species_checklist_header($options, $occAttrs) {
    $r = '';
    $visibleColIdx = 0;
    if ($options['header']) {
      $r .= "<thead class=\"ui-widget-header\"><tr>";
      for ($i=0; $i<$options['columns']; $i++) {
        $colspan = isset($options['lookupListId']) || $options['rowInclusionCheck']=='alwaysRemovable' ? ' colspan="2"' : '';
        $r .= self::get_species_checklist_col_header(lang::get('species_checklist.species'), $visibleColIdx, $options['colWidths'], $colspan);
        $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
        $r .= self::get_species_checklist_col_header(lang::get('species_checklist.present'), $visibleColIdx, $options['colWidths'], $hidden);

        foreach ($occAttrs as $a) {
          $r .= self::get_species_checklist_col_header($a, $visibleColIdx, $options['colWidths']) ;
        }
        if ($options['occurrenceComment']) {
          $r .= self::get_species_checklist_col_header(lang::get('Comment'), $visibleColIdx, $options['colWidths']) ;
        }
        if ($options['occurrenceConfidential']) {
          $r .= self::get_species_checklist_col_header(lang::get('Confidential'), $visibleColIdx, $options['colWidths']) ;
        }
        if ($options['occurrenceImages']) {
          $r .= self::get_species_checklist_col_header(lang::get('Images'), $visibleColIdx, $options['colWidths']) ;
        }
      }
      $r .= '</tr></thead>';
      return $r;
    }
  }

  private static function get_species_checklist_col_header($caption, &$colIdx, $colWidths, $attrs='') {
    $width = count($colWidths)>$colIdx && $colWidths[$colIdx] ? ' style="width: '.$colWidths[$colIdx].'%;"' : '';
    if (!strpos($attrs, 'display:none')) $colIdx++;
    return "<th$attrs$width>".$caption."</th>";
  }

  /**
   * Private method to build the list of taxa to add to a species checklist grid.
   * @param array $options Options array for the control
   * @param array $taxaThatExist Array that is modified by this method to contain a list of
   * the taxa_taxon_list_ids for rows which have existing data to load.
   * @return array The taxon list to store in the grid.
   */
  private static function get_species_checklist_taxa_list($options, &$taxaThatExist) {
    // Get the list of species that are always added to the grid
    if (isset($options['listId']) && !empty($options['listId'])) {
      $taxalist = self::get_population_data($options);
    } else
      $taxalist = array();
    // build a list of the ids we have got from the default list.
    $taxaLoaded = array();
    foreach ($taxalist as $taxon)
      $taxaLoaded[] = $taxon['id'];
    // If there are any extra taxa to add to the list from the lookup list/add rows feature, get their details
    if(self::$entity_to_load && !empty($options['lookupListId'])) {
      // copy the options array so we can modify it
      $extraTaxonOptions = array_merge(array(), $options);
      // We don't want to filter the taxa to be added, because if they are in the sample, then they must be included whatever.
      unset($extraTaxonOptions['extraParams']['taxon_list_id']);
      unset($extraTaxonOptions['extraParams']['preferred']);
      unset($extraTaxonOptions['extraParams']['language_iso']);
      // create an array to hold the IDs, so that get_population_data can construct a single IN query, faster
      // than multiple requests.
      $extraTaxonOptions['extraParams']['id'] = array();
      foreach(self::$entity_to_load as $key => $value) {
        $parts = explode(':', $key);
        // Is this taxon attribute data?
        if (count($parts) > 2 && $parts[0] == 'sc' && $parts[1]!='-ttlId-') {
          // track that this taxon row has existing data to load
          if (!in_array($parts[1], $taxaThatExist)) $taxaThatExist[] = $parts[1];
          // If not already loaded
          if(!in_array($parts[1], $taxaLoaded)) {
            $taxaLoaded[] = $parts[1];
            // store the id of the taxon in the array, so we can load them all in one go later
            $extraTaxonOptions['extraParams']['id'][]=$parts[1];
          }
        }
      }
      // append the taxa to the list to load into the grid
      $taxalist = array_merge($taxalist, self::get_population_data($extraTaxonOptions));
    }
  	return $taxalist;
  }

  /**
   * Internal method to prepare the options array for a species_checklist control.
   *
   * @param array $options Options array passed to the control
   * @return array Options array prepared with defaults and other values required by the control.
   */
  public static function get_species_checklist_options($options) {
    // validate some options
    if (!isset($options['listId']) && !isset($options['lookupListId']))
      throw new Exception('Either the listId or lookupListId parameters must be provided for a species checklist.');
    // Apply default values
    $options = array_merge(array(
        'header'=>'true',
        'columns'=>1,
        'rowInclusionCheck'=>isset($options['checkboxCol']) && $options['checkboxCol']==false ? 'hasData' : 'checkbox',
        'attrCellTemplate'=>'attribute_cell',
        'PHPtaxonLabel' => false,
        'occurrenceComment' => false,
        'occurrenceConfidential' => false,
        'occurrenceImages' => false,
        'id' => 'species-grid-'.rand(0,1000),
        'colWidths' => array()
    ), $options);
    // If filtering for a language, then use any taxa of that language. Otherwise, just pick the preferred names.
    if (!isset($options['extraParams']['language_iso']))
      $options['extraParams']['preferred'] = 't';
    if (array_key_exists('listId', $options) && !empty($options['listId'])) {
      $options['extraParams']['taxon_list_id']=$options['listId'];
    }
    if (array_key_exists('readAuth', $options)) {
      $options['extraParams'] += $options['readAuth'];
    } else {
      $options['readAuth'] = array(
          'auth_token' => $options['extraParams']['auth_token'],
          'nonce' => $options['extraParams']['nonce']
      );
    }
    $options['table']='taxa_taxon_list';
  	return $options;
  }

  /**
   * Internal function to prepare the list of occurrence attribute columns for a species_checklist control.
   */
  public static function species_checklist_prepare_attributes($options, $attributes, &$occAttrControls, &$occAttrs) {
    $idx=0;
    if (array_key_exists('occAttrs', $options))
      $attrs = $options['occAttrs'];
    else
      // There is no specified list of occurrence attributes, so use all available for the survey
      $attrs = array_keys($attributes);
    foreach ($attrs as $occAttrId) {
      // test that this occurrence attribute is linked to the survey
      if (!isset($attributes[$occAttrId]))
        throw new Exception("The occurrence attribute $occAttrId requested for the grid is not linked with the survey.");
      $attrDef = array_merge($attributes[$occAttrId]);
      $occAttrs[$occAttrId] = $attrDef['caption'];
      // Get the control class if available. If the class array is too short, the last entry gets reused for all remaining.
      $ctrlOptions = array(
        'class'=>self::species_checklist_occ_attr_class($options, $idx, $attrDef['caption']) .
            (isset($attrDef['class']) ? ' '.$attrDef['class'] : ''),
        'extraParams' => $options['readAuth'],
        'suffixTemplate' => 'nosuffix',
        'language' => $options['language'] // required for lists eg radio boxes: kept separate from options extra params as that is used to indicate filtering of species list by language
      );
      if(isset($options['lookUpKey'])) $ctrlOptions['lookUpKey']=$options['lookUpKey'];
      if(isset($options['blankText'])) $ctrlOptions['blankText']=$options['blankText'];
      // Don't want captions in the grid
      unset($attrDef['caption']);
      $attrDef['fieldname'] = '{fieldname}';
      $attrDef['id'] = '{fieldname}';
      $occAttrControls[$occAttrId] = self::outputAttribute($attrDef, $ctrlOptions);
      $idx++;
    }
  }

  /**
   * Returns the class to apply to a control for an occurrence attribute, identified by an index.
   * @access private
   */
  private static function species_checklist_occ_attr_class($options, $idx, $caption) {
    return (array_key_exists('occAttrClasses', $options) && $idx<count($options['occAttrClasses'])) ?
          $options['occAttrClasses'][$idx] :
          'sc'.str_replace(' ', '', ucWords($caption)); // provide a default class based on the control caption
  }

  /**
   * When the species checklist grid has a lookup list associated with it, this is a
   * secondary checklist which you can pick species from to add to the grid. As this happens,
   * a hidden table is used to store a clonable row which provides the template for new rows
   * to be added to the grid.
   */
  public static function get_species_checklist_clonable_row($options, $occAttrControls, $attributes) {
    global $indicia_templates;
    $r = '<table style="display: none"><tbody><tr class="scClonableRow" id="'.$options['id'].'-scClonableRow">';
    $colspan = isset($options['lookupListId']) || $options['rowInclusionCheck']=='alwaysRemovable' ? ' colspan="2"' : '';
    $r .= str_replace('{colspan}', $colspan, $indicia_templates['taxon_label_cell']);
    $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
    $r .= '<td class="scPresenceCell"'.$hidden.'><input type="checkbox" class="scPresence" name="" value="" /></td>';
    $idx = 0;
    foreach ($occAttrControls as $attrId=>$oc) {
      $class = self::species_checklist_occ_attr_class($options, $idx, $attributes[$attrId]['caption']);
      if (isset($attributes[$attrId]['default']) && !empty($attributes[$attrId]['default'])) {
        $existing_value=$attributes[$attrId]['default'];
        // For select controls, specify which option is selected from the existing value
        if (substr($oc, 0, 7)=='<select') {
          $oc = str_replace('value="'.$existing_value.'"',
              'value="'.$existing_value.'" selected="selected"', $oc);
        } else {
          $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
        }
      }
      $r .= str_replace(array('{content}', '{class}'),
          array(str_replace('{fieldname}', "sc:-ttlId-::occAttr:$attrId", $oc), $class.'Cell'),
          $indicia_templates['attribute_cell']
      );
      $idx++;
    }
    if ($options['occurrenceComment']) {
      $r .= '<td class="ui-widget-content scCommentCell"><input class="scComment" type="text" ' .
          'id="sc:-ttlId-::occurrence:comment" name="sc:-ttlId-::occurrence:comment" value="" /></td>';
    }
    if (isset($options['occurrenceConfidential']) && $options['occurrenceConfidential']) {
      $r .= '<td class="ui-widget-content scConfidentialCell">'.
          self::checkbox(array('fieldname'=>'sc:-ttlId-::occurrence:confidential')).
          '</td>';
    }
    if ($options['occurrenceImages']) {
      // Add a link, but make it display none for now as we can't link images till we know what species we are linking to.
      $r .= '<td class="ui-widget-content scImageLinkCell"><a href="" class="add-image-link scImageLink" style="display: none" id="add-images:-ttlId-:">'.
          lang::get('add images').'</a><span class="add-image-select-species">'.lang::get('select a species first').'</span></td>';
    }
    $r .= "</tr></tbody></table>\n";
    return $r;
  }

 /**
  * Helper function to output an HTML textarea. This includes re-loading of existing values
  * and displaying of validation error messages.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>rows</b><br/>
  * Optional. HTML rows attribute. Defaults to 4.</li>
  * <li><b>cols</b><br/>
  * Optional. HTML cols attribute. Defaults to 80.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the textarea control.
  */
  public static function textarea() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    $options = array_merge(array(
        'cols'=>'80',
        'rows'=>'4'
    ), $options);
    return self::apply_template('textarea', $options);
  }

 /**
  * Helper function to output an HTML text input. This includes re-loading of existing values
  * and displaying of validation error messages.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the text input control.
  */
  public static function text_input() {
    $options = self::check_arguments(func_get_args(), array('fieldname'));
    $options = array_merge(array(
      'default'=>''
    ), $options);
    return self::apply_template('text_input', $options);
  }

 /**
  * A control for inputting a time value. Provides a text input with a spin control that allows
  * the time to be input. Reverts to a standard text input when JavaScript disabled.
  * @param array $options Options array with the following possibilities:
  * <ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to.</li>
  * <li><b>id</b><br/>
  * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
  * <li><b>default</b><br/>
  * Optional. The default value to assign to the control. This is overridden when reloading a
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>beforeSetTime</b><br/>
  * Optional. Set this to the name of a JavaScript function which is called when the user tries to set a time value. This
  * can be used, for example, to display a warning label when an out of range time value is input. See <a '.
  * href="http://keith-wood.name/timeEntry.html">jQuery Time Entry</a> then click on the Restricting tab for more information.</li>
  * <li><b>timeSteps</b><br/>
  * Optional. An array containing 3 values for the allowable increments in time for hours, minutes and seconds respectively. Defaults to
  * 1, 15, 0 meaning that the increments allowed are in 15 minute steps and seconds are ignored.</li>
  * </ul>
  */
  public static function time_input($options) {
    $options = array_merge(array(
      'id' => $options['fieldname'],
      'default' => '',
      'timeSteps' => array(1,15,0)
    ), $options);
    self::add_resource('timeentry');
    $steps = implode(', ', $options['timeSteps']);
    $imgPath = empty(self::$images_path) ? self::relative_client_helper_path()."../media/images" : self::$images_path;
    // build a list of options to pass through to the jQuery widget
    $jsOpts = array(
      "timeSteps: [$steps]",
      "spinnerImage: '".$imgPath."/spinnerGreen.png'"
    );
    if (isset($options['beforeSetTime']))
      $jsOpts[] = "beforeSetTime: ".$options['beforeSetTime'];
    // ensure ID is safe for jQuery selectors
    $safeId = str_replace(':','\\\\:',$options['id']);
    self::$javascript .= "$('#".$safeId."').timeEntry({".implode(', ', $jsOpts)."});\n";
    return self::apply_template('text_input', $options);
  }

  /**
  * Helper function to generate a treeview from a given list
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, for example 'occurrence:taxa_taxon_list_id'.
  * NB the tree itself will have an id of "tr$fieldname".</li>
  * <li><b>id</b><br/>
  * Optional. ID of the control. Defaults to the fieldname.</li>
  * <li><b>table</b><br/>
  * Required. Name (Kohana-style) of the database entity to be queried.</li>
  * <li><b>view</b><br/>
  * Name of the view of the table required (list, detail).</li>
  * <li><b>captionField</b><br/>
  * Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Field to draw values to return from the control from. Defaults
  * to the value of $captionField.</li>
  * <li><b>parentField</b><br/>
  * Field used to indicate parent within tree for a record.</li>
  * <li><b>default</b><br/>
  * Initial value to set the control to (not currently used).</li>
  * <li><b>extraParams</b><br/>
  * Array of key=>value pairs which will be passed to the service
  * as GET parameters. Needs to specify the read authorisation key/value pair, needed for making
  * queries to the data services.</li>
  * <li><b>extraClass</b><br/>
  * main class to be added to UL tag - currently can be treeview, treeview-red,
  * treeview_black, treeview-gray. The filetree class although present, does not work properly.</li>
  * </ul>
  *
  * TODO
  * Need to do initial value.
  */
  public static function treeview()
  {
    global $indicia_templates;
    $options = self::check_arguments(func_get_args(), array('fieldname', 'table', 'captionField', 'valueField',
        'topField', 'topValue', 'parentField', 'default', 'extraParams', 'class'));
    self::add_resource('treeview_async');
    // Declare the data service
    $url = parent::$base_url."index.php/services/data";
    // Setup some default values
    $options = array_merge(array(
      'valueField'=>$options['captionField'],
      'class'=>'treeview',
      'id'=>$options['fieldname'],
      'view'=>'list'
    ), $options);
    $default = self::check_default_value($options['fieldname'],
        array_key_exists('default', $options) ? $options['default'] : null);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $sParams = substr($sParams, 0, -1);
    extract($options, EXTR_PREFIX_ALL, 'o');
    self::$javascript .= "jQuery('#tr$o_fieldname').treeview({
      url: '$url/$o_table',
      extraParams : {
        orderby : '$o_captionField',
        mode : 'json',
        $sParams
      },
      valueControl: '$o_fieldname',
      valueField: '$o_valueField',
      captionField: '$o_captionField',
      view: '$o_view',
      parentField: '$o_parentField',
      dataType: 'jsonp',
      nodeTmpl: '".$indicia_templates['treeview_node']."'
    });\n";

    $tree = '<input type="hidden" class="hidden" id="'.$o_id.'" name="'.$o_fieldname.'" /><ul id="tr'.$o_id.'" class="'.$o_class.'"></ul>';
    $tree .= self::check_errors($o_fieldname);
    return $tree;
  }

  /**
  * Helper function to generate a browser control from a given list. The browser
  * behaves similarly to a treeview, except that the child lists are appended to the control
  * rather than inserted as list children. This allows controls to be created which allow
  * selection of an item, then the control is updated with the new list of options after each
  * item is clicked.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to, for example 'occurrence:taxa_taxon_list_id'.
  * NB the tree itself will have an id of "tr$fieldname".</li>
  * <li><b>id</b><br/>
  * Optional. ID of the hidden input which contains the value. Defaults to the fieldname.</li>
  * <li><b>divId</b><br/>
  * Optional. ID of the outer div. Defaults to div_ plus the fieldname.</li>
  * <li><b>table</b><br/>
  * Required. Name (Kohana-style) of the database entity to be queried.</li>
  * <li><b>view</b><br/>
  * Name of the view of the table required (list, detail).</li>
  * <li><b>captionField</b><br/>
  * Field to draw values to show in the control from.</li>
  * <li><b>valueField</b><br/>
  * Field to draw values to return from the control from. Defaults
  * to the value of $captionField.</li>
  * <li><b>parentField</b><br/>
  * Field used to indicate parent within tree for a record.</li>
  * <li><b>default</b><br/>
  * Initial value to set the control to (not currently used).</li>
  * <li><b>extraParams</b><br/>
  * Array of key=>value pairs which will be passed to the service
  * as GET parameters. Needs to specify the read authorisation key/value pair, needed for making
  * queries to the data services.</li>
  * <li><b>outerClass</b><br/>
  * Class to be added to the control's outer div.</li>
  * <li><b>class</b><br/>
  * Class to be added to the input control (hidden).</li>
  * <li><b>default</b><br/>
  * Optional. The default value for the underlying control.</li>
  * </ul>
  *
  * TODO
  * Need to do initial value.
  */
  public static function tree_browser($options) {
    global $indicia_templates;
    self::add_resource('treeBrowser');
    // Declare the data service
    $url = parent::$base_url."index.php/services/data";
    // Apply some defaults to the options
    $options = array_merge(array(
      'valueField' => $options['captionField'],
      'id' => $options['fieldname'],
      'divId' => 'div_'.$options['fieldname'],
      'singleLayer' => true,
      'outerClass' => 'ui-widget ui-corner-all ui-widget-content tree-browser',
      'listItemClass' => 'ui-widget ui-corner-all ui-state-default',
      'default' => self::check_default_value($options['fieldname'],
          array_key_exists('default', $options) ? $options['default'] : ''),
      'view'=>'list'
    ), $options);
    $escaped_divId=str_replace(':','\\\\:',$options['divId']);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $sParams = substr($sParams, 0, -1);
    extract($options, EXTR_PREFIX_ALL, 'o');
    self::$javascript .= "
$('div#$escaped_divId').indiciaTreeBrowser({
  url: '$url/$o_table',
  extraParams : {
    orderby : '$o_captionField',
    mode : 'json',
    $sParams
  },
  valueControl: '$o_id',
  valueField: '$o_valueField',
  captionField: '$o_captionField',
  view: '$o_view',
  parentField: '$o_parentField',
  nodeTmpl: '".$indicia_templates['tree_browser_node']."',
  singleLayer: '$o_singleLayer',
  backCaption: '".lang::get('back')."',
  listItemClass: '$o_listItemClass',
  defaultValue: '$o_default'
});\n";
    return self::apply_template('tree_browser', $options);
  }

  /**
  * Insert buttons which, when clicked, displays the next or previous tab. Insert this inside the tab divs
  * on each tab you want to have a next or previous button, excluding the last tab.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>divId</b><br/>
  * The id of the div which is tabbed and whose next tab should be selected.</li>
  * <li><b>captionNext</b><br/>
  * Optional. The untranslated caption of the next button. Defaults to next step.</li>
  * <li><b>captionPrev</b><br/>
  * Optional. The untranslated caption of the previous button. Defaults to prev step.</li>
  * <li><b>class</b><br/>
  * Optional. Additional classes to add to the div containing the buttons. Use left, right or
  * centre to position the div, making sure the containing element is either floated, or has
  * overflow: auto applied to its style. Default is right.</li>
  * <li><b>buttonClass</b><br/>
  * Class to add to the button elements.</li>
  * <li><b>page</b><br/>
  * Specify first, middle or last to indicate which page this is for. Use middle (the default) for
  * all pages other than the first or last.</li>
  * </ul>
  *
  * @link http://docs.jquery.com/UI/Tabs
  */
  public static function wizard_buttons($options=array()) {
    // Default captions
    $options = array_merge(array(
      'captionNext' => 'next step',
      'captionPrev' => 'prev step',
      'captionSave' => 'save',
      'buttonClass' => 'ui-widget-content ui-state-default ui-corner-all indicia-button',
      'class'       => 'right',
      'page'        => 'middle',
      'suffixTemplate' => 'nosuffix'
    ), $options);
    $options['class'] .= ' buttons';
    // localise the captions
    $options['captionNext'] = lang::get($options['captionNext']);
    $options['captionPrev'] = lang::get($options['captionPrev']);
    $options['captionSave'] = lang::get($options['captionSave']);
    // Output the buttons
    $r = '<div class="'.$options['class'].'">';
    $buttonClass=$options['buttonClass'];
    if (array_key_exists('divId', $options)) {
      if ($options['page']!='first') {
        $options['class']=$buttonClass." tab-prev";
        $r .= self::apply_template('tab_prev_button', $options);
      }
      if ($options['page']!='last') {
        $options['class']=$buttonClass." tab-next";
        $r .= self::apply_template('tab_next_button', $options);
      } else {
        $options['class']=$buttonClass." tab-submit";
        $r .= self::apply_template('submit_button', $options);
      }
    }
    $r .= '</div><div style="clear:both"></div>';
    return $r;
  }

/********************************/
/* End of main controls section */
/********************************/

  /**
   * Returns the browser name and version information
   * @param $agent Agent string, optional. If not suplied, then the http user agent is used.
   * @return array Browser information array. Contains name and version elements.
   */
  public static function get_browser_info($agent=null) {
    $browsers = array("firefox", "msie", "opera", "chrome", "safari",
                            "mozilla", "seamonkey",    "konqueror", "netscape",
                            "gecko", "navigator", "mosaic", "lynx", "amaya",
                            "omniweb", "avant", "camino", "flock", "aol");
    if (!$agent)
      $agent = $_SERVER['HTTP_USER_AGENT'];
    $agent = strtolower($agent);
    foreach($browsers as $browser)
    {
        if (preg_match("#($browser)[/ ]?([0-9.]*)#", $agent, $match))
        {
            $r['name'] = $match[1] ;
            $r['version'] = $match[2] ;
            break ;
        }
    }
    return $r;
  }

  /**
   * Removes any data entry values persisted into the $_SESSION by Indicia.
   *
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function clear_session() {
    foreach ($_SESSION as $name=>$value) {
      if (substr($name, 0, 8)=='indicia:') {
        unset($_SESSION[$name]);
      }
    }
  }

  /**
   * Adds the data from the $_POST array into the session. Call this method when arriving at the second
   * and subsequent pages of a data entry wizard to keep the previous page's data available for saving later.
   *
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function add_post_to_session () {
    foreach ($_POST as $name=>$value) {
      $_SESSION['indicia:'.$name]=$value;
    }
  }

  /**
   * Returns an array constructed from all the indicia variables that have previously been stored
   * in the session.
   *
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function extract_session_array () {
    $result = array();
    foreach ($_SESSION as $name=>$value) {
      if (substr($name, 0, 8)=='indicia:') {
        $result[substr($name, 8)]=$value;
      }
    }
    return $result;
  }

  /**
  * Retrieves a data value from the Indicia Session data
  *
  * @param string $name Name of the session value to retrieve
  * @param string $default Default value to return if not set or empty
  * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
  */
  public static function get_from_session($name, $default='') {
    $result = '';
    if (array_key_exists("indicia:$name", $_SESSION)) {
      $result = $_SESSION["indicia:$name"];
    }
    if (!$result) {
      $result = $default;
    }
    return $result;
  }

  /**
   * Internal method to handle the deprecated use of a list of arguments, for backwards compatibility.
   * Converts the list of arguments to an options array unless the first argument is already
   * an options array. The arguments are mapped to the array in the order specified by the mapping.
   */
  public static function check_arguments(array $args, array $mapping=null) {
    if (count($args)>0) {
      if (is_array($args[0])) {
        // First argument is an options array
        $options = $args[0];
      } elseif ($mapping) {
        // arguments are passed individuall using deprecated method - so for backward compatibility we'll
        // map them to an options array
        $options=array();
        $i=0;
        foreach ($args as $arg) {
          $options[$mapping[$i]]=$arg;
          $i++;
        }
      }
    }
    if (isset($options)) {
      return self::check_options($options);
    } else {
      return array();
    }
  }

  /**
   * Checks that an Id is supplied, if not, uses the fieldname as the id. Also checks if a
   * captionField is supplied, and if not uses a valueField if available. Finally, gets the control's
   * default value.
   * If the control is set to be remembered, then adds it to the list of remembered fields.
   */
  private static function check_options($options) {
    // force some defaults to be present in the options
    $options = array_merge(array(
        'class'=>'',
        'multiple'=>''
        ), $options);
    // If fieldname is supplied but not id, then use the fieldname as the id
    if (!array_key_exists('id', $options) && array_key_exists('fieldname', $options)) {
      $options['id']=$options['fieldname'];
    }
    // If captionField is supplied but not valueField, use the captionField as the valueField
    if (!array_key_exists('valueField', $options) && array_key_exists('captionField', $options)) {
      $options['valueField']=$options['captionField'];
    }
    // Get a default value - either the supplied value in the options, or the loaded value, or nothing.
    if (array_key_exists('fieldname', $options)) {
      $options['default'] = self::check_default_value($options['fieldname'],
          array_key_exists('default', $options) ? $options['default'] : '');
    }
    return $options;
  }

  /**
   * Private function to fetch a validated timeout value from passed in options array
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>cachetimeout</b><br/>
   * Optional. The length in seconds before the cache times out and is refetched.</li></ul>
   * @return Timeout in number of seconds, else FALSE if data is not to be cached.
   */
  private static function _getCacheTimeOut($options)
  {
    if (is_numeric(self::$cache_timeout) && self::$cache_timeout > 0) {
      $ret_value = self::$cache_timeout;
    } else {
      $ret_value = false;
    }
    if (isset($options['cachetimeout'])) {
      if (is_numeric($options['cachetimeout']) && $options['cachetimeout'] > 0) {
        $ret_value = $options['cachetimeout'];
      } else {
        $ret_value = false;
      }
    }
    return $ret_value;
  }

  /**
   * Private function to generate a filename to be used as the cache file for this data
   * @param string $path directory path for file
   * @param array $options Options array : contents are used along with md5 to generate the filename.
   * @param number $timeout - will be false if no caching to take place
   * @return string filename, else FALSE if data is not to be cached.
   */
  private static function _getCacheFileName($path, $options, $timeout)
  {
    /* If timeout is not set, we're not caching */
    if (!$timeout)
      return false;
    if(!is_dir($path) || !is_writeable($path))
      return false;

    $cacheFileName = $path.'cache_'.self::$website_id.'_';
    $cacheFileName .= md5(self::array_to_query_string($options));

    return $cacheFileName;
  }

  /**
   * Private function to return the cached data stored in the specified local file.
   * @param string $file Cache file to be used, includes path
   * @param number $timeout - will be false if no caching to take place
   * @param array $options Options array : contents used to confirm what this data is.
   * @return array equivalent of call to http_post, else FALSE if data is not to be cached.
   */
  private static function _getCachedResponse($file, $timeout, $options)
  {
    // Note the random element, we only timeout a cached file sometimes.
    if (($timeout && $file && is_file($file) &&
        (rand(1, self::$cache_chance_refresh_file)!=1 || filemtime($file) >= (time() - $timeout)))
    ) {
      $response = array();
      $handle = fopen($file, 'rb');
      if(!$handle) return false;
      $tags = fgets($handle);
      $response['output'] = fread($handle, filesize($file));
      fclose($handle);
      if ($tags == self::array_to_query_string($options)."\n")
        return($response);
    } else {
      self::_timeOutCacheFile($file, $timeout);
    }
    return false;
  }

  /**
   * Private function to remove a cache file if it has timed out.
   * @param string $file Cache file to be removed, includes path
   * @param number $timeout - will be false if no caching to take place
   */
  private static function _timeOutCacheFile($file, $timeout)
  {
    if ($file && is_file($file) && filemtime($file) < (time() - $timeout)) {
      unlink($file);
    }
  }

  /**
   * Private function to create a cache file provided it does not already exist.
   * @param string $file Cache file to be removed, includes path - will be false if no caching to take place
   * @param array $response http_post return value
   * @param array $options Options array : contents used to tag what this data is.
   */
  private static function _cacheResponse($file, $response, $options)
  {
    // need to create the file as a binary event - so create a temp file and move across.
    if ($file && !is_file($file) && isset($response['output'])) {
      $handle = fopen($file.getmypid(), 'wb');
      fputs($handle, self::array_to_query_string($options)."\n");
      fwrite($handle, $response['output']);
      fclose($handle);
      rename($file.getmypid(),$file);
    }
  }

  /**
   * Method which populates data_entry_helper::$entity_to_load with the values from an existing
   * record. Useful when reloading data to edit.
   */
  public static function load_existing_record($readAuth, $model, $id, $view='detail') {
    $url = self::$base_url."index.php/services/data/$model/$id";
    $url .= "?mode=json&view=$view&auth_token=".$readAuth['auth_token']."&nonce=".$readAuth['nonce'];
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $entity = json_decode(curl_exec($session), true);
    if (isset($entity['error'])) throw new Exception($entity['error']);
    // populate the entity to load with the record data
    foreach($entity[0] as $key => $value) {
      self::$entity_to_load["$model:$key"] = $value;
    }
    if ($model=='sample') {
      self::$entity_to_load['sample:geom'] = ''; // value received from db in geom is not WKT, which is assumed by all the code.
      self::$entity_to_load['sample:date'] = self::$entity_to_load['sample:date_start']; // bit of a bodge to get around vague dates.
    } elseif ($model=='occurrence') {
      // prepare data to work in autocompletes
      if (!empty(self::$entity_to_load['occurrence:taxon']) && empty(self::$entity_to_load['occurrence:taxa_taxon_list:taxon']))
        self::$entity_to_load['occurrence:taxa_taxon_list_id:taxon'] = self::$entity_to_load['occurrence:taxon'];
    }
  }

  /**
   * Issue a post request to get the population data required for a control. Depends on the
   * options' table and extraParams values what is requested. This is now cacheable.
   * NB that this function only uses the 'table', 'report' and 'extraParams' of $options. If $options
   * contains a value for nocache=true then caching is skipped as well.
   * When generating the cache for this data we need to use the table and
   * any extra params, excluding the read_auth and the nonce. The cache should be
   * used by all accesses to the DB.
   */
  public static function get_population_data($options) {
    if (isset($options['report']))
      $serviceCall = 'report/requestReport?report='.$options['report'].'.xml&reportSource=local&mode=json';
    elseif (isset($options['table']))
      $serviceCall = 'data/'.$options['table'].'?mode=json';
    $request = parent::$base_url."index.php/services/$serviceCall";
    if (array_key_exists('extraParams', $options)) {
      // make a copy of the extra params
      $params = array_merge($options['extraParams']);
      $cacheOpts = array();
      // process them to turn any array parameters into a query parameter for the service call
      $filterToEncode = array('where'=>array(array()));
      $otherParams = array();
      foreach($params as $param=>$value) {
        if (is_array($value))
          $filterToEncode['in'] = array($param, $value);
        elseif ($param=='orderby' || $param=='sortdir' || $param=='auth_token' || $param=='nonce' || $param=='view')
          // these params are not filters, so can't go in the query
          $otherParams[$param] = $value;
        else
          $filterToEncode['where'][0][$param] = $value;
        // implode array parameters (for IN clauses) in the cache options, since we need a single depth array
        $cacheOpts[$param]= is_array($value) ? implode('|',$value) : $value;
      }
      // use advanced querying technique if we need to
      if (isset($filterToEncode['in']))
        $request .= '&query='.json_encode($filterToEncode).'&'.self::array_to_query_string($otherParams, true);
      else
        $request .= '&'.self::array_to_query_string($options['extraParams'], true);
    } else
      $cacheOpts = array();
    if (isset($options['report']))
      $cacheOpts['report'] = $options['report'];
    else
      $cacheOpts['table'] = $options['table'];
    $cacheOpts['indicia_website_id'] = self::$website_id;
    /* If present 'auth_token' and 'nonce' are ignored as these are session dependant. */
    if (array_key_exists('auth_token', $cacheOpts)) {
      unset($cacheOpts['auth_token']);
    }
    if (array_key_exists('nonce', $cacheOpts)) {
      unset($cacheOpts['nonce']);
    }
    if (self::$nocache || isset($_GET['nocache']) || (isset($options['nocache']) && $options['nocache'])) {
      $cacheFile = false;
      $response = self::http_post($request, null);
    }
    else {
      $cacheTimeOut = self::_getCacheTimeOut($options);
      $cacheFolder = self::relative_client_helper_path() . (isset(parent::$cache_folder) ? parent::$cache_folder : 'cache/');
      $cacheFile = self::_getCacheFileName($cacheFolder, $cacheOpts, $cacheTimeOut);
      if (!($response = self::_getCachedResponse($cacheFile, $cacheTimeOut, $cacheOpts)))
        $response = self::http_post($request, null);
    }
    $r = json_decode($response['output'], true);
    if (!is_array($r)) {
      throw new Exception('Invalid response received from Indicia Warehouse. '.print_r($response, true));
    }
    // Only cache valid responses
    if (!isset($r['error']))
      self::_cacheResponse($cacheFile, $response, $cacheOpts);
    self::_purgeCache();
    self::_purgeImages();
    return $r;
  }

  /**
   * Helper function to clear the Indicia cache files.
   */
  public function clear_cache() {
    $cacheFolder = self::relative_client_helper_path() . (isset(parent::$cache_folder) ? parent::$cache_folder : 'cache/');
    if(!$dh = @opendir($cacheFolder)) {
      return;
    }
    while (false !== ($obj = readdir($dh))) {
      if($obj != '.' && $obj != '..')
        @unlink($cacheFolder . '/' . $obj);
    }
    closedir($dh);
  }

  /**
   * Internal function to ensure old cache files are purged periodically.
   */
  private static function _purgeCache() {
    $cacheFolder = self::relative_client_helper_path() . (isset(parent::$cache_folder) ? parent::$cache_folder : 'cache/');
    self::_purgeFiles(self::$cache_chance_purge, $cacheFolder, self::$cache_timeout * 5, self::$cache_allowed_file_count);
  }

  /**
   * Internal function to ensure old image files are purged periodically.
   */
  private static function _purgeImages() {
    $interimImageFolder = self::relative_client_helper_path() . (isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/');
    self::_purgeFiles(self::$cache_chance_purge, $interimImageFolder, self::$interim_image_expiry);
  }

  private static function _purgeFiles($chanceOfPurge, $folder, $timeout, $allowedFileCount=0) {
    // don't do this every time.
    if (rand(1, $chanceOfPurge)===1) {
      // First, get an array of files sorted by date
      $files = array();
      $dir =  opendir($folder);
      if ($dir) {
        while ($filename = readdir($dir)) {
          if ($filename == '.' || $filename == '..' || is_dir($filename))
            continue;
          $lastModified = filemtime($folder . $filename);
          $files[] = array($folder .$filename, $lastModified);
        }
      }
      // sort the file array by date, oldest first
      usort($files, array('data_entry_helper', '_DateCmp'));
      // iterate files, ignoring the number of files we allow in the cache without caring.
      for ($i=0; $i<count($files)-$allowedFileCount; $i++) {
        // if we have reached a file that is not old enough to expire, don't go any further
        if ($files[$i][1] > (time() - $timeout)) {
          break;
        }
        // clear out the old file
        if (is_file($files[$i][0]))
          unlink($files[$i][0]);
      }
    }
  }

  private static function _DateCmp($a, $b)
  {
    if ($a[1]<$b[1])
      $r = -1;
    else if ($a[1]>$b[1])
      $r = 1;
    else $r=0;
    return $r;
  }

  /**
   * Internal function to output either a select or listbox control depending on the templates
   * passed.
   *
   * @access private
   */
  private static function select_or_listbox($options) {
    global $indicia_templates;
    self::add_resource('json');
    $options = array_merge(array(
      'filterField'=>'parent_id',
      'size'=>3
    ), $options);
    if (array_key_exists('parentControlId', $options)) {
      // no options for now
      $options['items'] = '';
      self::init_linked_lists($options);
    } else {
      $lookupItems = self::get_list_items_from_options($options);
      $opts = "";
      if (array_key_exists('blankText', $options)) {
        $opts .= str_replace(
            array('{value}', '{caption}', '{selected}'),
            array('', htmlentities($options['blankText'])),
            $indicia_templates[$options['itemTemplate']]
        );
      }
      foreach ($lookupItems as $value => $template){
        if(isset($options['default'])){
          if(is_array($options['default'])){
            $item['selected'] = in_array($value, $options['default']) ? 'selected' : '';
          } else
            $item['selected'] = ($options['default'] == $value) ? 'selected' : '';
        } else $item['selected'] = '';
        $opts .= self::mergeParamsIntoTemplate($item, $template, true);
      }
      $options['items'] = $opts;
    }
    if (isset($response['error']))
      return $response['error'];
    else
      return self::apply_template($options['template'], $options);
  }

  /**
  * When populating a list control (select, listbox, checkbox or radio group), use either the
  * table, captionfield and valuefield to build the list of values as an array, or if lookupValues
  * is in the options array use that instead of making a database call.
  * @param array $options Options array for the control.
  * @return array Associative array of the lookup values and captions.
  */
  private static function get_list_items_from_options($options) {
    $r = array();
    global $indicia_templates;
    if (isset($options['lookupValues'])) {
      // lookup values are provided, so run these through the item template
      foreach ($options['lookupValues'] as $key=>$value) {
        $r[$key] = str_replace(
            array('{value}', '{caption}'),
            array($key, $value),
            $indicia_templates[$options['itemTemplate']]
        );
      }
    } else {
      // lookup values need to be obtained from the database
      $response = self::get_population_data($options);
      // if the response is empty, and a language has been set, try again without the language but asking for the preferred values.
      if(count($response)==0 && array_key_exists('iso', $options['extraParams'])){
        unset($options['extraParams']['iso']);
        $options['extraParams']['preferred']='t';
        $response = self::get_population_data($options);
      }
      $lookupValues = array();
      if (!array_key_exists('error', $response)) {
        foreach ($response as $record) {
          if (array_key_exists($options['valueField'], $record)) {
            if (isset($options['captionTemplate']))
              $caption = self::mergeParamsIntoTemplate($record, $options['captionTemplate']);
            else
              $caption = $record[$options['captionField']];
            if(isset($options['listCaptionSpecialChars'])) {
            	$caption=htmlspecialchars($caption);
            }
            $item = str_replace(
                array('{value}', '{caption}'),
                array($record[$options['valueField']], $caption),
                $indicia_templates[$options['itemTemplate']]
            );
            $r[$record[$options['valueField']]] = $item;
          }
        }
      }
    }
    return $r;
  }

 /**
  * Where there are 2 linked lists on a page, initialise the JavaScript required to link the lists.
  *
  * @param array Options array of the child linked list.
  */
  private static function init_linked_lists($options) {
    global $indicia_templates;
    // setup JavaScript to do the population when the parent control changes
    $parentControlId = str_replace(':','\\\\:',$options['parentControlId']);
    $escapedId = str_replace(':','\\\\:',$options['id']);
    $fn = preg_replace("/[^A-Za-z0-9]/", "", $options['id'])."_populate";
    $url = parent::$base_url."index.php/services/data";
    $request = "$url/".$options['table']."?mode=json";
    if (isset($options['parentControlLabel']))
      $instruct = str_replace('{0}', $options['parentControlLabel'], lang::get('Please select a {0} first'));
    else
      $instruct = lang::get('Awaiting selection...');
    if (array_key_exists('extraParams', $options)) {
      $request .= '&'.self::array_to_query_string($options['extraParams']);
    }
    self::$javascript .= str_replace(
        array('{fn}','{escapedId}','{request}','{filterField}','{valueField}','{captionField}','{parentControlId}', '{instruct}'),
        array($fn, $escapedId, $request,$options['filterField'],$options['valueField'],$options['captionField'],$parentControlId, $instruct),
        $indicia_templates['linked_list_javascript']
    );
  }

  /**
   * Internal method to output either a checkbox group or a radio group.
   */
  private static function check_or_radio_group($options, $type) {
    // checkboxes are inherantly multivalue, whilst radio buttons are single value
    global $indicia_templates;
    $options = array_merge(
      array(
        'sep' => '',
        'template' => 'check_or_radio_group',
        'itemTemplate' => 'check_or_radio_group_item',
        'id' => $options['fieldname'],
        'class' => ''
      ),
      $options
    );
    // class picks up a default of blank, so we can't use array_merge to overwrite it
    $options['class'] = trim($options['class'] . ' control-box');
    // We want to apply validation to the inner items, not the outer control
    if (array_key_exists('validation', $options)) {
      $itemClass = self::build_validation_class($options);
      unset($options['validation']);
    } else {
      $itemClass='';
    }
    $lookupItems = self::get_list_items_from_options($options);
    $items = "";
    $idx = 0;
    foreach ($lookupItems as $value => $template) {
      $fieldName = $options['fieldname'];
      if (isset($options['default'])) {
        if (is_array($options['default'])) {
          $checked = false;
          foreach ($options['default'] as $defVal) {
            if(is_array($defVal)){
              if($defVal['default'] == $value) {
                $checked = true;
                $fieldName = $defVal['fieldname'];
              }
            } else if($value == $defVal) $checked = true;
          }
        } else
          $checked = ($options['default'] == $value);
      } else
        $checked=false;
      $item = array_merge(
        $options,
        array(
          'disabled' => isset($options['disabled']) ? $options['disabled'] : '',
          'checked' => $checked ? ' checked="checked" ' : '', // cant use === as need to compare an int with a string representation
          'type' => $type,
          'value' => $value,
          'class' => $itemClass,
          'itemId' => $options['id'].':'.$idx
        )
      );
      $item['fieldname']=$fieldName;
      $items .= self::mergeParamsIntoTemplate($item, $template, true, true);
      $idx++;
    }
    $options['items']=$items;
    // We don't want to output for="" in the top label, as it is not directly associated to a button
    $lblTemplate = $indicia_templates['label'];
    $indicia_templates['label'] = str_replace(' for="{id}"', '', $lblTemplate);
    if (isset($itemClass) && !empty($itemClass) && strpos($itemClass, 'required')!==false) {
      $options['suffixTemplate'] = 'requiredsuffix';
    }
    $r = self::apply_template($options['template'], $options);
    // reset the old template
    $indicia_templates['label'] = $lblTemplate;
    return $r;
  }

 /**
  * Helper method to enable the support for tabbed interfaces for a div.
  * The jQuery documentation describes how to specify a list within the div which defines the tabs that are present.
  * This method also automatically selects the first tab that contains validation errors if the form is being
  * reloaded after a validation attempt.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>divId</b><br/>
  * Optional. The id of the div which will be tabbed. If not specified then the caller is
  * responsible for calling the jQuery tabs plugin - this method just links the appropriate
  * jQuery files.</li>
  * <li><b>style</b><br/>
  * Optional. Possible values are tabs (default) or wizard. If set to wizard, then the tab header
  * is not displayed and the navigation should be provided by the tab_button control. This
  * must be manually added to each tab page div.</li>
  * <li><b>progressBar</b><br/>
  * Optional. Set to true to output a progress header above the tabs/wizard which shows which
  * stage the user is on out of the sequence of steps in the wizard.</li>
  * </ul>
  *
  * @link http://docs.jquery.com/UI/Tabs
  */
  public static function enable_tabs($options) {
    // A jquery selector for the element which must be at the top of the page when moving to the next page. Could be the progress bar or the
    // tabbed div itself.
    if (isset($options['progressBar']) && $options['progressBar']==true)
      $topSelector = '.wiz-prog';
    else
      $topSelector = '#'.$options['divId'];
    // Only do anything if the id of the div to be tabified is specified
    if (array_key_exists('divId', $options)) {
      $divId = $options['divId'];
      // Scroll to the top of the page. This may be required if subsequent tab pages are longer than the first one, meaning the
        // browser scroll bar is too long making it possible to load the bottom blank part of the page if the user accidentally
      // drags the scroll bar while the page is loading.
      self::$javascript .= "\nscroll(0,0);";
      self::$javascript .= "\n$('.tab-submit').click(function() {\n";
      self::$javascript .= "  var current=$('#$divId').tabs('option', 'selected');\n";
      // Use a selector to find the inputs and selects on the current tab and validate them.
      if (isset(self::$validated_form_id)) {
        self::$javascript .= "  var tabinputs = $('#".self::$validated_form_id." div > .ui-tabs-panel:eq('+current+')').find('input,select').not(':disabled');\n";
        self::$javascript .= "  if (!tabinputs.valid()) {\n";
        self::$javascript .= "    return;";
        self::$javascript .= "  }\n";
      }
      // If all is well, submit.
      self::$javascript .= "      var form = $(this).parents('form:first');
        form.submit();
      });";
      self::$javascript .= "\n$('.tab-next').click(function() {\n";
      self::$javascript .= "  var current=$('#$divId').tabs('option', 'selected');\n";
      // Use a selector to find the inputs and selects on the current tab and validate them.
      if (isset(self::$validated_form_id)) {
        self::$javascript .= "  var tabinputs = $('#".self::$validated_form_id." div > .ui-tabs-panel:eq('+current+')').find('input,select').not(':disabled');\n";
        self::$javascript .= "  if (!tabinputs.valid()) {\n";
        self::$javascript .= "    return;";
        self::$javascript .= "  }\n";
      }
      // If all is well, move to the next tab. Note the code detects if the top of the tabset is not visible, if so
      // it forces it into view. This helps a lot when the tabs vary in height.
      self::$javascript .= "  var a = $('ul.ui-tabs-nav a')[current+1];
  $(a).click();
  scrollTopIntoView('$topSelector');
});";

      self::$javascript .= "\n$('.tab-prev').click(function() {
  var current=$('#$divId').tabs('option', 'selected');
  var a = $('ul.ui-tabs-nav a')[current-1];
  $(a).click();
  scrollTopIntoView('$topSelector');
});\n";

      // We put this javascript into $late_javascript so that it can come after the other controls.
      // This prevents some obscure bugs - e.g. OpenLayers maps cannot be centered properly on hidden
      // tabs because they have no size.
      self::$late_javascript .= "var tabs = $(\"#$divId\").tabs();\n";
      // find any errors on the tabs.
      self::$late_javascript .= "var errors=$(\"#$divId .ui-state-error\");\n";
      // select the tab containing the first error, if validation errors are present
      self::$late_javascript .= "
if (errors.length>0) {
  tabs.tabs('select',$(errors[0]).parents('.ui-tabs-panel')[0].id);
  var panel;
  for (var i=0; i<errors.length; i++) {
    panel = $(errors[i]).parents('.ui-tabs-panel')[0];
    $('#'+panel.id+'-tab').addClass('ui-state-error');
  }
}\n";
      if (array_key_exists('active', $options)) {
        self::$late_javascript .= "else {tabs.tabs('select','".$options['active']."');}\n";
      }
      if (array_key_exists('style', $options) && $options['style']=='wizard') {
        self::$late_javascript .= "$('#$divId .ui-tabs-nav').hide();\n";
      }
    }
    // add a progress bar to indicate how many steps are complete in the wizard
    if (isset($options['progressBar']) && $options['progressBar']==true) {
      data_entry_helper::add_resource('wizardprogress');
      data_entry_helper::$javascript .= "wizardProgressIndicator({divId:'$divId'});\n";
    } else {
      data_entry_helper::add_resource('tabs');
    }
  }

  /**
   * Outputs the ul element that needs to go inside a tabified div control to define the header tabs.
   * This is required for wizard interfaces as well.
   * @param array $options Options array with the following possibilities:<ul>
  * <li><b>tabs</b><br/>
  * Array of tabs, with each item being the tab title, keyed by the tab ID including the #.</li>
  */
  public static function tab_header($options) {
    $options = self::check_options($options);
    // Convert the tabs array to a string of <li> elements
    $tabs = "";
    foreach($options['tabs'] as $link => $caption) {
      $tabId=substr("$link-tab",1);
      //rel="address:..." enables use of jQuery.address module (http://www.asual.com/jquery/address/)
      if ($tabs == ""){
        $address = "";
      } else {
        $address = (substr($link, 0, 1) == '#') ? substr($link, 1) : $link;
      }
      $tabs .= "<li id=\"$tabId\"><a href=\"$link\" rel=\"address:/$address\"><span>$caption</span></a></li>";
    }
    $options['tabs'] = $tabs;
    $options['suffixTemplate']="nosuffix";
    return self::apply_template('tab_header', $options);
  }

  /* Insert a button which, when clicked, displays the previous tab. Insert this inside the tab divs
  * on each tab you want to have a next button, excluding the first tab.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>divId</b><br/>
  * The id of the div which is tabbed and whose next tab should be selected.</li>
  * <li><b>caption</b><br/>
  * Optional. The untranslated caption of the button. Defaults to previous step.</li>
  *
  * @link http://docs.jquery.com/UI/Tabs
  */
  public static function tab_prev_button($options) {
    if (!array_key_exists('caption', $options)) $options['caption'] = 'previous step';
    $options['caption'] = lang::get($options['caption']);
    if (!array_key_exists('class', $options)) $options['class'] = 'ui-widget-content ui-state-default ui-corner-all indicia-button prev-tab';
    if (array_key_exists('divId', $options)) {
      return self::apply_template('tab_prev_button', $options);
    }
  }

  /**
   * <p>Allows the demarcation of the start of a region of the page HTML to be declared which will be replaced by
   * a loading message whilst the page is loading.</p>
   * <p>If JavaScript is disabled then this has no effect. Note that hiding the block is achieved by setting
   * it's left to move it off the page, rather than display: none. This is because OpenLayers won't initialise
   * properly on a div that is display none.</p>
   * <p><b>Warning.</b> To use this function, always insert a call to dump_header in the <head> element of your
   * HTML page to ensure that JQuery is loaded first. Otherwise this will not work.</p>
   *
   * @return string HTML and JavaScript to insert into the page at the start of the block
   * which is replaced by a loading panel while the page is loading.
   */
  public static function loading_block_start() {
    global $indicia_templates;
    self::add_resource('jquery_ui');
    // For clean code, the jquery_ui stuff should have gone out in the page header, but just in case.
    // Don't bother from inside Drupal, since the header is added after the page code runs
    if (!in_array('jquery_ui', self::$dumped_resources) && !defined('DRUPAL_BOOTSTRAP_CONFIGURATION')) {
      $r = self::internal_dump_resources(array('jquery_ui'));
      array_push(self::$dumped_resources, 'jquery_ui');
    } else {
      $r = '';
    }
    $r .= $indicia_templates['loading_block_start'];
    return $r;
  }

  /**
   * Allows the demarcation of the end of a region of the page HTML to be declared which will be replaced by
   * a loading message whilst the page is loading.
   *
   * @return string HTML and JavaScript to insert into the page at the start of the block
   * which is replaced by a loading panel while the page is loading.
   */
  public static function loading_block_end() {
    global $indicia_templates;
    // First hide the message, then hide the form, slide it into view, then show it.
    // This script must precede the other scripts onload, otherwise they may have problems because
    // of assumptions that the controls are visible.
    self::$onload_javascript = "$('.loading-panel').remove();\n".
        "var panel=$('.loading-hide')[0];\n".
        "$(panel).hide();\n".
        "$(panel).removeClass('loading-hide');\n".
        "$(panel).fadeIn('slow');\n" .
        self::$onload_javascript;
    return $indicia_templates['loading_block_end'];
  }

  /**
   * Either takes the passed in submission, or creates it from the post data if this is null, and forwards
   * it to the data services for saving as a member of the entity identified.
   * @param string $entity Name of the top level entity being submitted, e.g. sample or occurrence.
   * @param array $submission The wrapped submission structure. If null, then this is automatically constructer
   * from the form data in $_POST.
   * @param array $writeTokens Array containing auth_token and nonce for the write operation, plus optionally persist_auth=true
   * to prevent the authentication tokens from expiring after use. If null then the values are read from $_POST.
   */
  public static function forward_post_to($entity, $submission = null, $writeTokens = null) {
    if (self::$validation_errors==null) {
      $remembered_fields = self::get_remembered_fields();

      if ($submission == null)
        $submission = submission_builder::wrap($_POST, $entity);
      if ($remembered_fields !== null) {
        // the form is configured to remember fields
        if ( (!isset($_POST['cookie_optin'])) || ($_POST['cookie_optin'] === '1') ) {
          // if given a choice, the user opted for fields to be remembered
          $arr=array();
          foreach ($remembered_fields as $field) {
            $arr[$field]=$_POST[$field];
          }
            // put them in a cookie with a 30 day expiry
          setcookie('indicia_remembered', serialize($arr), time()+60*60*24*30);
          // cookies are only set when the page is loaded. So if we are reloading the same form after submission,
          // we need to fudge the cookie
          $_COOKIE['indicia_remembered'] = serialize($arr);
        } else {
          // the user opted out of having a cookie - delete one if present.
          setcookie('indicia_remembered', '');
        }
      }

      $images = self::extract_image_data($_POST);
      $request = parent::$base_url."index.php/services/data/$entity";
      $postargs = 'submission='.urlencode(json_encode($submission));
      // passthrough the authentication tokens as POST data. Use parameter writeTokens, or current $_POST if not supplied.
      if ($writeTokens) {
        $postargs .= '&auth_token='.$writeTokens['auth_token'];
        $postargs .= '&nonce='.$writeTokens['nonce'];
        if (isset($writeTokens['persist_auth']) && $writeTokens['persist_auth'])
          $postargs .= '&persist_auth=true';
      } else {
        if (array_key_exists('auth_token', $_POST))
          $postargs .= '&auth_token='.$_POST['auth_token'];
        if (array_key_exists('nonce', $_POST))
          $postargs .= '&nonce='.$_POST['nonce'];
      }
      // if there are images, we will send them after the main post, so we need to persist the write nonce
      if (count($images)>0)
        $postargs .= '&persist_auth=true';
      $response = self::http_post($request, $postargs);
      // The response should be in JSON if it worked
      $output = json_decode($response['output'], true);
      // If this is not JSON, it is an error, so just return it as is.
      if (!$output)
        $output = $response['output'];
      if (is_array($output) && array_key_exists('success', $output))  {
        if (isset(self::$final_image_folder) && self::$final_image_folder!='warehouse') {
          // moving the files on the local machine. Find out where from and to
          $interim_image_folder = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . self::relative_client_helper_path().
              (isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/');
          $final_image_folder = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . self::relative_client_helper_path().
              parent::$final_image_folder;
        }

        // submission succeeded. So we also need to move the images to the final location
        foreach ($images as $image) {
          // no need to resend an existing image.
          if (!isset($image['id']) || empty($image['id'])) {
            if (!isset(self::$final_image_folder) || self::$final_image_folder=='warehouse') {
              // Final location is the Warehouse
              // @todo Set PERSIST_AUTH false if last file
              $success = self::send_file_to_warehouse($image['path'], true);
            } else {
              $success = rename($interim_image_folder.$image['path'], $final_image_folder.$image['path']);
            }
            if ($success!==true) {
              return array('error' => lang::get('submit ok but file transfer failed').
                  "<br/>$success");
            }
          }
        }
      }
      return $output;
    }
    else
      return array('error' => 'Pre-validation failed', 'errors' => self::$validation_errors);
  }

  /**
  * Wraps data from a species checklist grid (generated by
  * data_entry_helper::species_checklist) into a suitable format for submission. This will
  * return an array of submodel entries which can be dropped directly into the subModel
  * section of the submission array. If there is a field occurrence:determiner_id or
  * occurrence:record_status in the main form data, then these values are applied to each
  * occurrence created from the grid. For example, place a hidden field in the form named
  * "occurrence:record_status" with a value "C" to set all occurrence records to completed
  * as soon as they are entered.
  *
  * @param array $arr Array of data generated by data_entry_helper::species_checklist method.
  * @param boolean $include_if_any_data If true, then any list entry which has any data
  * set will be included in the submission. This defaults to false, unless the grid was
  * created with rowInclusionCheck=hasData.
  * in the grid.
  */
  public static function wrap_species_checklist($arr, $include_if_any_data=false){
    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    // determiner and record status can be defined globally for the whole list.
    if (array_key_exists('occurrence:determiner_id', $arr)){
      $determiner_id = $arr['occurrence:determiner_id'];
    }
    if (array_key_exists('occurrence:record_status', $arr)){
      $record_status = $arr['occurrence:record_status'];
    }
    // Set the default method of looking for rows to include - either using data, or the checkbox (which could be hidden)
    $include_if_any_data = $include_if_any_data || (isset($arr['rowInclusionCheck']) && $arr['rowInclusionCheck']=='hasData');
    // Species checklist entries take the following format
    // sc:<taxa_taxon_list_id>:[<occurrence_id>]:occAttr:<occurrence_attribute_id>[:<occurrence_attribute_value_id>]
    // or
    // sc:<taxa_taxon_list_id>:[<occurrence_id>]:occurrence:comment
    // or
    // sc:<taxa_taxon_list_id>:[<occurrence_id>]:occurrence_image:fieldname:uniqueImageId
    $records = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      if (substr($key, 0, 3)=='sc:'){
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 4);
        $records[$a[1]][$a[3]] = $value;
        // store any id so update existing record
        if($a[2]) {
          $records[$a[1]]['id'] = $a[2];
        }
      }
    }
    foreach ($records as $id => $record) {
      $present = self::wrap_species_checklist_record_present($record, $include_if_any_data);
      if (array_key_exists('id', $record) || $present) { // must always handle row if already present in the db
        if (!$present) {
          // checkboxes do not appear if not checked. If uncheck, delete record.
          $record['deleted'] = 't';
        }

        $record['taxa_taxon_list_id'] = $id;
        $record['website_id'] = $website_id;
        if (isset($determiner_id)) {
            $record['determiner_id'] = $determiner_id;
        }
        if (isset($record_status)) {
          $record['record_status'] = $record_status;
        }
        $occ = data_entry_helper::wrap($record, 'occurrence');
        self::attachOccurrenceImagesToModel($occ, $record);
        $subModels[] = array(
          'fkId' => 'sample_id',
          'model' => $occ
        );
      }
    }
    return $subModels;
  }

  /**
   * Test whether the data extracted from the $_POST for a species_checklist grid row refers to an occurrence record.
   * @access Private
   */
  private static function wrap_species_checklist_record_present($record, $include_if_any_data) {
    // as we are working on a copy of the record, discard the ID so it is easy to check if there is any other data for the row.
    unset($record['id']);
    $recordData=implode('',$record);
    return ($include_if_any_data && $recordData!='' && !preg_match("/^[0]*$/", $recordData)) ||       // inclusion of record is detected from having a non-zero value in any cell
      (!$include_if_any_data && array_key_exists('present', $record) && $record['present']!='0'); // inclusion of record detected from the presence checkbox
  }

  /**
   * When wrapping a species checklist submission, scan the contents of the data for a single grid row to
   * look for attached images. If found they are attached to the occurrence model as sub-models.
   */
  private static function attachOccurrenceImagesToModel(&$occ, $record) {
    $images = array();
    foreach ($record as $key=>$value) {
      if (substr($key, 0, 17)=='occurrence_image:') {
        $tokens = explode(':', $key);
        // build an array of the data keyed by the unique image id (token 2)
        $images[$tokens[2]][$tokens[1]] = array('value' => $value);
      }
    }
    foreach($images as $image => $data) {
      $occ['subModels'][] = array(
          'fkId' => 'occurrence_id',
          'model' => array(
            'id' => 'occurrence_image',
            'fields' => $data
        )
      );
    }
  }

  /**
   * Wraps an array (e.g. Post or Session data generated by a form) into a structure
   * suitable for submission.
   * <p>The attributes in the array are all included, unless they
   * named using the form entity:attribute (e.g. sample:date) in which case they are
   * only included if wrapping the matching entity. This allows the content of the wrap
   * to be limited to only the appropriate information.</p>
   * <p>Do not prefix the survey_id or website_id attributes being posted with an entity
   * name as these IDs are used by Indicia for all entities.</p>
   *
   * @param array $array Array of data generated from data entry controls.
   * @param string $entity Name of the entity to wrap data for.
   */
  public static function wrap($array, $entity)
  {
    return submission_builder::wrap($array, $entity);
  }

   /**
   * Wraps a set of values for a model into JSON suitable for submission to the Indicia data services,
   * and also grabs the custom attributes (if there are any) and links them to the model.
   *
   * @param array $values Array of form data (e.g. $_POST).
   * @param string $modelName Name of the model to wrap data for. If this is sample, occurrence or location
   * then custom attributes will also be wrapped. Furthermore, any attribute called $modelName:image can
   * contain an image upload (as long as a suitable entity is available to store the image in).
   * @deprecated
   */
  public static function wrap_with_attrs($values, $modelName) {
    return submission_builder::wrap_with_attrs($values, $modelName);
  }

  /**
   * Helper function to simplify building of a submission that contains a single sample
   * and occurrence record.
   * @param array $values List of the posted values to create the submission from. Each entry's
   * key should be occurrence:fieldname, sample:fieldname, occAttr:n, smpAttr:n or taxAttr:n 
   * to be correctly identified.
   */
  public static function build_sample_occurrence_submission($values) {
    $structure = array(
        'model' => 'sample',
        'subModels' => array(
          'occurrence' => array('fk' => 'sample_id')
        )
    );
    // Either an uploadable file, or a link to a Flickr external detail means include the submodel
    if ((array_key_exists('occurrence:image', $values) && $values['occurrence:image'])
        || array_key_exists('occurrence_image:external_details', $values) && $values['occurrence_image:external_details']) {
      $structure['submodel']['submodel'] = array(
          'model' => 'occurrence_image',
          'fk' => 'occurrence_id'
      );
    }
    return submission_builder::build_submission($values, $structure);
  }

  /**
   * Helper function to simplify building of a submission that contains a single sample
   * and multiple occurrences records generated by a species_checklist control.
   *
   * @param array $values List of the posted values to create the submission from.
   * @param boolean $include_if_any_data If true, then any list entry which has any data
   * set will be included in the submission. Set this to true when hiding the select checkbox
   * in the grid.
   * @return array Sample submission array
   */
  public static function build_sample_occurrences_list_submission($values, $include_if_any_data=false) {
    // We're mainly submitting to the sample model
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    $occurrences = data_entry_helper::wrap_species_checklist($values, $include_if_any_data);

    // Add the occurrences in as subModels
    $sampleMod['subModels'] = $occurrences;

    return $sampleMod;
  }

  /**
   * Helper function to simplify building of a submission. Does simple submissions that do not involve
   * species checklist grids.
   * @param array $values List of the posted values to create the submission from.
   * @param array $structure Describes the structure of the submission. The form should be:
   * array(
   *     'model' => 'main model name',
   *     'subModels' => array('child model name' =>  array(
   *         'fieldPrefix'=>'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name',
   *         'image_entity' => 'name of image entity if present'
   *     )),
   *     'superModels' => array('child model name' =>  array(
   *         'fieldPrefix'=>'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name',
   *         'image_entity' => 'name of image entity if present'
   *     )),
   *     'metaFields' => array('fieldname1', 'fieldname2', ...)
   * )
   */
  public static function build_submission($values, $structure) {
    return submission_builder::build_submission($values, $structure);
  }

  /**
  * Takes a response from a call to forward_post_to() and outputs any errors from it onto the screen.
  *
  * @param string $response Return value from a call to forward_post_to().
  * @param boolean $inline Set to true if the errors are to be placed alongside the controls rather than at the top of the page.
  * Default is true.
  * @see forward_post_to()
  * @link http://code.google.com/p/indicia/wiki/TutorialBuildingBasicPage#Build_a_data_entry_page
  */
  public static function dump_errors($response, $inline=true)
  {
    $r = "";
    if (is_array($response)) {
      if (array_key_exists('error',$response) || array_key_exists('errors',$response)) {
        if ($inline && array_key_exists('errors',$response)) {
          // Setup an errors array that the data_entry_helper can output alongside the controls
          self::$validation_errors = $response['errors'];
          // And tell the helper to reload the existing data.
          self::$entity_to_load = $_POST;
        } else {
          $r .= "<div class=\"ui-state-error ui-corner-all\">\n";
          $r .= "<p>An error occurred when the data was submitted.</p>\n";
          if (is_array($response['error'])) {
            $r .=  "<ul>\n";
            foreach ($response['error'] as $field=>$message)
              $r .=  "<li>$field: $message</li>\n";
            $r .=  "</ul>\n";
          } else {
            $r .= "<p class=\"error_message\">".$response['error']."</p>\n";
          }
          if (array_key_exists('file', $response) && array_key_exists('line', $response)) {
            $r .= "<p>Error occurred in ".$response['file']." at line ".$response['line']."</p>\n";
          }
          if (array_key_exists('errors', $response)) {
            $r .= "<pre>".print_r($response['errors'], true)."</pre>\n";
          }
          if (array_key_exists('trace', $response)) {
            $r .= "<pre>".print_r($response['trace'], true)."</pre>\n";
          }
          $r .= "</div>\n";
        }
      }
      elseif (array_key_exists('warning',$response)) {
        $r .= 'A warning occurred when the data was submitted.';
        $r .= '<p class="error">'.$response['error']."</p>\n";
      }
      elseif (array_key_exists('success',$response)) {
        $r .= "<div class=\"ui-widget ui-corner-all ui-state-highlight page-notice\">Thank you for submitting your data.</div>\n";
      }
    }
    else
      $r .= "<div class=\"ui-state-error ui-corner-all\">$response</div>\n";
    return $r;
  }

  /**
   * Retrieves any errors that have not been emitted alongside a form control and adds them to the page.
   * This is useful when added to the bottom of a form, because occasionally an error can be returned which is not associated with a form
   * control, so calling dump_errors with the inline option set to true will not emit the errors onto the page.
   * @return string HTML block containing the error information, built by concatenating the
   * validation_message template for each error.
   */
  public static function dump_remaining_errors()
  {
    global $indicia_templates;
    $r="";
    if (self::$validation_errors!==null) {
      foreach (self::$validation_errors as $errorKey => $error) {
        if (!in_array($error, self::$displayed_errors)) {
          $r .= str_replace('{error}', lang::get($error), $indicia_templates['validation_message']);
          $r .= "[".$errorKey."]";
        }
      }
      $r .= '<br/>';
    }
    return $r;
  }

  /**
  * Private method to find an option from an associative array of options. If not present, returns the default.
  */
  private static function option($key, array $opts, $default)
  {
    if (array_key_exists($key, $opts)) {
      $r = $opts[$key];
    } else {
      $r = $default;
    }
    return $r;
  }

  /**
   * Returns the default value for the control with the supplied Id.
   * The default value is taken as either the $_POST value for this control, or the first of the remaining
   * arguments which contains a non-empty value.
   *
   * @param string $id Id of the control to select the default for.
   * $param [string, [string ...]] Variable list of possible default values. The first that is
   * not empty is used.
   */
  public static function check_default_value($id) {
    $remembered_fields = self::get_remembered_fields();
    if (self::$entity_to_load!=null && array_key_exists($id, self::$entity_to_load)) {
      return self::$entity_to_load[$id];
    } else if ($remembered_fields !== null && in_array($id, $remembered_fields) && array_key_exists('indicia_remembered', $_COOKIE)) {
      $arr = unserialize($_COOKIE['indicia_remembered']);
      if (isset($arr[$id]))
        return $arr[$id];
    }

    $return = null;
    if (is_null($return) || $return == '') { // need to be careful about valid zero values!
      // iterate the variable arguments and use the first one with a real value
      for ($i=1; $i<func_num_args(); $i++) {
        $return = func_get_arg($i);
        if (!is_null($return) && $return != '') {
          break;
        }
      }
    }
    return $return;
  }

  /**
   * Output a DIV which lists configuration problems and is useful for diagnostics.
   * Currently, tests the PHP version and that the cUrl library is installed.
   *
   * @param boolean $fullInfo If true, then successful checks are also output.
   */
  public static function system_check($fullInfo=true) {
    // PHP_VERSION_ID is available as of PHP 5.2.7, if our
    // version is lower than that, then emulate it
    if(!defined('PHP_VERSION_ID'))
    {
        $version = PHP_VERSION;
        define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
    }
    $r = '<div class="ui-widget ui-widget-content ui-state-highlight ui-corner-all">' .
        '<p class="ui-widget-header"><strong>System check</strong></p><ul>';
    // Test PHP version.
    if (PHP_VERSION_ID<50200) {
      $r .= '<li class="ui-state-error">Warning: PHP version is '.phpversion().' which does not support JSON communication with the Indicia Warehouse.</li>';
    } elseif ($fullInfo) {
      $r .= '<li>Success: PHP version is '.phpversion().'.</li>';
    }
    // Test cUrl library installed
    if (!function_exists('curl_exec')) {
      $r .= '<li class="ui-state-error">Warning: The cUrl PHP library is not installed on the server and is required for communication with the Indicia Warehouse.</li>';
    } else {
      if ($fullInfo) {
        $r .= '<li>Success: The cUrl PHP library is installed.</li>';
      }
      // Test we have full access to the server - it doesn't matter what website id we pass here.'
      $postargs = "website_id=0";
      $curl_check = self::http_post(parent::$base_url.'index.php/services/security/get_read_nonce', $postargs, false);
      if ($curl_check['result']) {
        if ($fullInfo) {
          $r .= '<li>Success: Indicia Warehouse URL responded to a POST request.</li>';
        }
      } else {
        // Some sort of cUrl problem occurred
        if ($curl_check['errno']) {
          $r .= '<li class="ui-state-error">Warning: The cUrl PHP library could not access the Indicia Warehouse. The error was reported as:';
          $r .= $curl_check['output'].'<br/>';
          $r .= 'Please ensure that this web server is not prevented from accessing the server identified by the ' .
              'helper_config.php $base_url setting by a firewall. The current setting is '.parent::$base_url.'</li>';
        } else {
          $r .= '<li class="ui-widget ui-state-error">Warning: A request sent to the Indicia Warehouse URL did not respond as expected. ' .
                'Please ensure that the helper_config.php $base_url setting is correct. ' .
                'The current setting is '.parent::$base_url.'<br></li>';
        }
      }
      $missing_configs = array();
      $blank_configs = array();
      // Run through the expected configuration settings, checking they are present and not empty
      self::check_config('$base_url', isset(self::$base_url), empty(self::$base_url), $missing_configs, $blank_configs);
      self::check_config('$upload_path', isset(self::$upload_path), empty(self::$upload_path), $missing_configs, $blank_configs);
      // don't test $indicia_upload_path as it is assumed to be upload/ if missing.
      self::check_config('$geoserver_url', isset(self::$geoserver_url), empty(self::$geoserver_url), $missing_configs, $blank_configs);
      if (substr(self::$geoserver_url, 0, 4) != 'http') {
         $r .= '<li class="ui-widget ui-state-error">Warning: The $geoserver_url setting in helper_config.php should include the protocol (e.g. http://).</li>';
      }
      self::check_config('$geoplanet_api_key', isset(self::$geoplanet_api_key), empty(self::$geoplanet_api_key), $missing_configs, $blank_configs);
      self::check_config('$google_search_api_key', isset(self::$google_search_api_key), empty(self::$google_search_api_key), $missing_configs, $blank_configs);
      self::check_config('$bing_api_key', isset(self::$bing_api_key), empty(self::$bing_api_key), $missing_configs, $blank_configs);
      self::check_config('$multimap_api_key', isset(self::$multimap_api_key), empty(self::$multimap_api_key), $missing_configs, $blank_configs);
      self::check_config('$flickr_api_key', isset(self::$flickr_api_key), empty(self::$flickr_api_key), $missing_configs, $blank_configs);
      self::check_config('$flickr_api_secret', isset(self::$flickr_api_secret), empty(self::$flickr_api_secret), $missing_configs, $blank_configs);
      // Warn the user of the missing ones - the important bit.
      if (count($missing_configs)>0) {
        $r .= '<li class="ui-widget ui-state-error">Error: The following configuration entries are missing from helper_config.php : '.
            implode(', ', $missing_configs).'. This may prevent the data_entry_helper class from functioning normally.</li>';
      }
      // Also warn them of blank ones - not so important as it should only affect the one area of functionality
      if (count($blank_configs)>0) {
        $r .= '<li class="ui-widget ui-state-error">Warning: The following configuration entries are not specified in helper_config.php : '.
            implode(', ', $blank_configs).'. This means the respective areas of functionality will not be available.</li>';
      }
    // Test we have a writeable cache directory
    $cacheFolder = self::relative_client_helper_path() . (isset(parent::$cache_folder) ? parent::$cache_folder : 'cache/');
    if (!is_dir($cacheFolder)) {
      $r .= '<li class="ui-state-error">The cache path setting in helper_config.php points to a missing directory. This will result in slow form loading performance.</li>';
    } elseif (!is_writeable($cacheFolder)) {
      $r .= '<li class="ui-state-error">The cache path setting in helper_config.php points to a read only directory (' . $cacheFolder . '). This will result in slow form loading performance.</li>';
    } elseif ($fullInfo) {
        $r .= '<li>Success: Cache directory is present and writeable.</li>';
      }
    }
    $r .= '</ul></div>';
    return $r;
  }

  private static function check_config($name, $isset, $empty, &$missing_configs, &$blank_configs) {
    if (!$isset) {
      array_push($missing_configs, $name);
    } else if ($empty) {
      array_push($blank_configs, $name);
    }
  }

  /**
  * Helper function to fetch details of attributes associated with a survey.
  * This can be used to auto-generated the forum structure for a survey for example.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>survey_id</b><br/>
  * Optional. The survey that custom attributes are to tbe loaded for.</li>
  * <li><b>website_ids</b><br/>
  * Optional. Used instead of survey_id, allows retrieval of all possible custom attributes
  * for a set of websites.</li>
  * <li><b>sample_method_id</b><br/>
  * Optional. Can be set to the id of a sample method when loading just the attributes that are restricted to
  * that sample method or are unrestricted, otherwise only loads unrestricted attributes. Ignored unless
  * loading sample attributes.</li>
  * <li><b>location_type_id</b><br/>
  * Optional. Can be set to the id of a location_type when loading just the attributes that are restricted to
  * that type or are unrestricted, otherwise only loads unrestricted attributes. Ignored unless
  * loading location attributes.</li>
  * <li><b>attrtable</b><br/>
  * Required. Singular name of the table containing the attributes, e.g. sample_attribute.</li>
  * <li><b>valuetable</b><br/>
  * Required. Singular name of the table containing the attribute values, e.g. sample_attribute_value.</li>
  * <li><b>fieldprefix</b><br/>
  * Required. Prefix to be given to the returned control names, e.g. locAttr:</li>
  * <li><b>extraParams</b><br/>
  * Required. Additional parameters used in the web service call, including the read authorisation.</li>
  * <li><b>multiValue</b><br/>
  * Defaults to false, in which case this assumes that each attribute only allows one value, and the response array is keyed
  * by attribute ID. If set to true, multiple values are enabled and the response array is keyed by <attribute ID>:<attribute value ID>
  * in the cases where there is any data for the attribute.
  * </ul>
  * @param optional boolean $indexedArray default true. Determines whether the return value is an array indexed by PK, or whether it
  * is ordered as it comes from the database (ie block weighting). Needs to be set false if data is to be used by get_attribute_html.
  *
  * @return Associative array of attributes, keyed by the attribute ID (multiValue=false) or <attribute ID>:<attribute value ID> if multiValue=true.
  */
  public static function getAttributes($options, $indexedArray = true) {
    $attrs = array();
    $query = array();
    self::add_resource('json');
    if (isset($options['website_ids'])) {
      $query['in']=array('website_id'=>$options['website_ids']);
    } else {
      $surveys = array(NULL);
      if (isset($options['survey_id']))
        $surveys[] = $options['survey_id'];
      $query['in']=array('restrict_to_survey_id'=>$surveys);
    }
    if ($options['attrtable']=='sample_attribute') {
      // for sample attributes, we want all which have null in the restrict_to_sample_method_id,
      // or where the supplied sample method matches the attribute's.
      $methods = array(null);
      if (isset($options['sample_method_id']))
        $methods[] = $options['sample_method_id'];
      $query['in']['restrict_to_sample_method_id'] = $methods;
    }
    if ($options['attrtable']=='location_attribute') {
      // for location attributes, we want all which have null in the restrict_to_location_type_id,
      // or where the supplied location type matches the attribute's.
      $methods = array(null);
      if (isset($options['location_type_id']))
        $methods[] = $options['location_type_id'];
      $query['in']['restrict_to_location_type_id'] = $methods;
    }
    if (count($query))
      $query = urlencode(json_encode($query));
    $attrOptions = array(
          'table'=>$options['attrtable'],
           'extraParams'=> $options['extraParams']+ array(
             'deleted' => 'f',
             'website_deleted' => 'f',
             'query'=>$query,
             'orderby'=>'weight'
           )
    );
    $response = self::get_population_data($attrOptions);
    if (array_key_exists('error', $response))
      return $response;
    if(isset($options['id'])){
      $options['extraParams'][$options['key']] = $options['id'];
      $existingValuesOptions = array(
        'table'=>$options['valuetable'],
        'cachetimeout' => 0, // can't cache
        'extraParams'=> $options['extraParams']);
      $valueResponse = self::get_population_data($existingValuesOptions);
      if (array_key_exists('error', $valueResponse))
        return $valueResponse;
    } else
      $valueResponse = array();
    foreach ($response as $item){
      $itemId=$item['id'];
      unset($item['id']);
      $item['fieldname']=$options['fieldprefix'].':'.$itemId.($item['multi_value'] == 't' ? '[]' : '');
      $item['id']=$options['fieldprefix'].':'.$itemId;
      $item['untranslatedCaption']=$item['caption'];
      $item['caption']=lang::get($item['caption']);
      $item['default'] = self::attributes_get_default($item);
      $item['attributeId'] = $itemId;
      $item['values'] = array();
      if(count($valueResponse) > 0){
        foreach ($valueResponse as $value){
          $attrId = $value[$options['attrtable'].'_id'];
          if($attrId == $itemId && $value['id']) {
            // for multilanguage look ups we get > 1 record for the same attribute.
            $fieldname = $options['fieldprefix'].':'.$itemId.':'.$value['id'];
            $found = false;
            foreach ($item['values'] as $prev)
              if($prev['fieldname'] == $fieldname && $prev['default'] == $value['raw_value'])
                $found = true;
            if(!$found)
              $item['values'][] = array('fieldname' => $options['fieldprefix'].':'.$itemId.':'.$value['id'],
                                'default' => $value['raw_value']);
            $item['displayValue'] = $value['value']; //bit of a bodge but not using multivalue for this at the moment.
          }
        }
      }
      if(count($item['values'])==1 && $item['multi_value'] != 't'){
        $item['fieldname'] = $item['values'][0]['fieldname'];
        $item['default'] = $item['values'][0]['default'];
      }
      if($item['multi_value'] == 't'){
        $item['default'] = $item['values'];
      }
      unset($item['values']);
      if($indexedArray)
        $attrs[$itemId] = $item;
      else
        $attrs[] = $item;
    }
    return $attrs;
  }

  /**
   * For a single sample or occurrence attribute array loaded from the database, find the appropriate default value depending on the
   * data type.
   * @todo Handle vague dates. At the moment we just use the start date.
   */
  private static function attributes_get_default($item) {
    switch ($item['data_type']) {
      case 'T':
        return $item['default_text_value'];
      case 'F':
        return $item['default_float_value'];
      case 'I':
      case 'L':
        return $item['default_int_value'];
      case 'D':
      case 'V':
        return $item['default_date_start_value'];
      default:
        return '';
    }
  }

  private static function boolean_attribute($ctrl, $options) {
    global $indicia_templates;
    $options = array_merge(
      array(
        'sep' => '',
        'class' => 'control-box'
      ),
      $options
    );
    unset($options['validation']);
    $default = self::check_default_value($options['fieldname'],
        array_key_exists('default', $options) ? $options['default'] : '', '0');
    $options['default'] = $default;
    $options = array_merge(array('sep' => ''), $options);
    if ($options['class']=='') {
      // default class is control-box
      $options['class']='control-box';
    }
    $items = "";
    $buttonList = array(lang::get('No') => '0', lang::get('Yes') => '1');
    $disabled = isset($options['disabled']) ?  $options['disabled'] : '';
    foreach ($buttonList as $caption => $value) {
          $checked = ($default == $value) ? ' checked="checked" ' : '';
          $items .= str_replace(
              array('{type}', '{fieldname}', '{value}', '{checked}', '{caption}', '{sep}', '{disabled}', '{itemId}', '{class}'),
              array($ctrl, $options['fieldname'], $value, $checked, $caption, $options['sep'], $disabled, $options['fieldname'].':'.$value, ''),
              $indicia_templates['check_or_radio_group_item']
          );
    }
    $options['items']=$items;
    $lblTemplate = $indicia_templates['label'];
    $indicia_templates['label'] = str_replace(' for="{id}"', '', $lblTemplate);
    $r = self::apply_template('check_or_radio_group', $options);
    // reset the old template
    $indicia_templates['label'] = $lblTemplate;
    return $r;
  }

  /**
  * Helper function to output an attribute
  *
  * @param array $item Attribute definition as returned by a call to getAttributes.
  * @param array $options Additional options for the attribute to be output. Array entries can be:
  *    disabled
  *    suffixTemplate
  *    default
  *    class
  *    validation
  *    noBlankText
  *    extraParams
  *    booleanCtrl - radio or checkbox for boolean attribute output, default is checkbox. Can also be a checkbox_group, used to
  *    allow selection of both yes and no, e.g. on a filter form.
  *    language - iso 639:3 code for the language to output for terms in a termlist. If not set no language filter is used.
  * @return string HTML to insert into the page for the control.
  * @todo full handling of the control_type. Only works for text data at the moment.
  */
  public static function outputAttribute($item, $options=array()) {
    $options = array_merge(array(
      'extraParams' => array()
    ), $options);
    $attrOptions = array(
        'fieldname'=>$item['fieldname'],
        'id'=>$item['id'],
        'disabled'=>'');
    if (isset($item['caption']))
      $attrOptions['label']=$item['caption'];
    $attrOptions = array_merge($attrOptions, $options);
    // build validation rule classes from the attribute data
    if (isset($item['validation_rules'])) {
      $validation = explode("\n", $item['validation_rules']);
      $attrOptions['validation']=array_merge(isset($attrOptions['validation'])?$attrOptions['validation']:array(), $validation);
    }
    if(isset($item['default']) && $item['default']!="")
      $attrOptions['default']= $item['default'];
    switch ($item['data_type']) {
        case 'Text':
        case 'T':
          if (isset($item['control_type']) &&
              ($item['control_type']=='text_input' || $item['control_type']=='textarea'
              || $item['control_type']=='postcode_textbox' || $item['control_type']=='time_input')) {
            $ctrl = $item['control_type'];
          } else {
            $ctrl = 'text_input';
          }
          $output = self::$ctrl($attrOptions);
          break;
        case 'Float':
        case 'F':
        case 'Integer':
        case 'I':
          $output = self::text_input($attrOptions);
          break;
        case 'Boolean':
        case 'B':
          // A change in template means we can now use a checkbox if desired: in fact this is now the default.
          // Can also use checkboxes (eg for filters where none selected is a possibility) or radio buttons.
            $attrOptions['class'] = array_key_exists('class', $options) ? $options['class'] : 'control-box';
            if(array_key_exists('booleanCtrl', $options) && $options['booleanCtrl']=='radio') {
              $output = self::boolean_attribute('radio', $attrOptions);
            } elseif(array_key_exists('booleanCtrl', $options) && $options['booleanCtrl']=='checkbox_group') {
              $output = self::boolean_attribute('checkbox', $attrOptions);
            } else {
              $output = self::checkbox($attrOptions);
            }
            break;
        case 'D': // Date
        case 'Specific Date': // Date
        case 'V': // Vague Date
        case 'Vague Date': // Vague Date
            $attrOptions['class'] = ($item['data_type'] == 'D' ? "date-picker " : "vague-date-picker ");
            $output = self::date_picker($attrOptions);
            break;
        case 'Lookup List':
        case 'L':
          if(!array_key_exists('noBlankText', $options)){
            $attrOptions = $attrOptions + array('blankText' => (array_key_exists('blankText', $options)? $options['blankText'] : ''));
          }
          if (array_key_exists('class', $options))
            $attrOptions['class'] = $options['class'];
          $dataSvcParams = array('termlist_id' => $item['termlist_id'], 'view' => 'detail');
          if (array_key_exists('language', $options)) {
            $dataSvcParams = $dataSvcParams + array('iso'=>$options['language']);
          }
          if (!array_key_exists('orderby', $options['extraParams'])) {
            $dataSvcParams = $dataSvcParams + array('orderby'=>'sort_order');
          }
          // control for lookup list can be overriden in function call options
          if(array_key_exists('lookUpListCtrl', $options)){
            $ctrl = $options['lookUpListCtrl'];
          } else {
            // or specified by the attribute in survey details
            if (isset($item['control_type']) &&
              ($item['control_type']=='autocomplete' || $item['control_type']=='checkbox_group'
              || $item['control_type']=='listbox' || $item['control_type']=='radio_group' || $item['control_type']=='select')) {
              $ctrl = $item['control_type'];
            } else {
              $ctrl = 'select';
            }
          }
          if(array_key_exists('lookUpKey', $options)){
            $lookUpKey = $options['lookUpKey'];
          } else {
            $lookUpKey = 'id';
          }
          $output = call_user_func(array('data_entry_helper', $ctrl), array_merge($attrOptions, array(
                  'table'=>'termlists_term',
                  'captionField'=>'term',
                  'valueField'=>$lookUpKey,
                  'extraParams' => array_merge($options['extraParams'] + $dataSvcParams))));
          break;
        default:
            if ($item)
              $output = '<strong>UNKNOWN DATA TYPE "'.$item['data_type'].'" FOR ID:'.$item['id'].' CAPTION:'.$item['caption'].'</strong><br />';
            else
              $output = '<strong>Requested attribute is not available</strong><br />';
            break;
    }

    return $output;
  }

  /**
   * Retrieves an array of just the image data from a $_POST or set of control values.
   *
   * @param array $values Pass the $_POST data or other array of form values in this parameter.
   * @param string $modelName The singular name of the image table, e.g. location_image or occurrence_image etc. If
   * null, then any image model will be used.
   * @param boolean $simpleFileInputs If true, then allows a file input with name=occurrence:image (or similar)
   * to be used to point to an image file. The file is uploaded to the interim image folder to ensure that it
   * can be handled in the same way as a pre-uploaded file.
   * @param boolean $moveSimpleFiles If true, then any file uploaded by normal means to the server (via multipart form submission
   * for a field named occurrence:image[:n] or similar) will be moved to the interim image upload folder.
   */
  public static function extract_image_data($values, $modelName=null, $simpleFileInputs=false, $moveSimpleFiles=false) {
    $r = array();
    foreach ($values as $key => $value) {
      if (!empty($value)) {
        // If the field is a path, and the model name matches or we are not filtering on model name
        $pathPos = strpos($key, ':path:');
        if ($pathPos !== false)
          // Found an image path. Anything after path is the unique id. We include the colon in this.
          $uniqueId = substr($key, $pathPos + 5);
        else {
          // look for a :path field with no suffix (i.e. a single image upload field after a validation failure,
          // when it stores the path in a hidden field so it is not lost).
          if (substr($key, -5)==':path') {
            $uniqueId = '';
            $pathPos = strlen($key)-5;
          }
        }
        if ($pathPos !==false && ($modelName === null || $modelName == substr($key, 0, strlen($modelName)))) {
          $prefix = substr($key, 0, $pathPos);
          $r[] = array(
            // Id is set only when saving over an existing record.
            'id' => array_key_exists($prefix.':id'.$uniqueId, $values) ?
                $values[$prefix.':id'.$uniqueId] : '',
            'path' => $value,
            'caption' => isset($values[$prefix.':caption'.$uniqueId]) ? utf8_encode($values[$prefix.':caption'.$uniqueId]) : ''
          );
        }
      }
    }

    // Now look for image file inputs, called something like occurrence:image[:n]
    if ($simpleFileInputs) {
      foreach($_FILES as $key => $file) {
        if (substr($key, 0, strlen($modelName))==str_replace('_', ':', $modelName)) {
          if ($file['error']=='1') {
            // file too big error dur to php.ini setting
            if (self::$validation_errors==null) self::$validation_errors = array();
            self::$validation_errors[$key] = lang::get('file too big for webserver');
          }
          elseif (!self::check_upload_size($file)) {
            // even if file uploads Ok to interim location, the Warehouse may still block it.
            if (self::$validation_errors==null) self::$validation_errors = array();
            self::$validation_errors[$key] = lang::get('file too big for warehouse');
          }
          elseif ($file['error']=='0') {
            // no file upload error
            $fname = isset($file['tmp_name']) ? $file['tmp_name'] : '';
            if ($fname && $moveSimpleFiles) {
              // Get the original file's extension
              $parts = explode(".",$file['name']);
              $fext = array_pop($parts);
              // Generate a file id to store the image as
              $destination = time().rand(0,1000).".".$fext;
              $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
              $uploadpath = self::relative_client_helper_path().$interim_image_folder;
              if (move_uploaded_file($fname, $uploadpath.$destination)) {
                $r[] = array(
                  // Id is set only when saving over an existing record. This will always be a new record
                  'id' => '',
                  'path' => $destination,
                  'caption' => ''
                );
                // record the new file name, also note it in the $_POST data so it can be tracked after a validation failure
                $_FILES[$key]['name'] = $destination;
                $pathField = str_replace(':image','_image:path', $key);
                $_POST[$pathField] = $destination;
              }
            } else {
              // Not moving the file, as it should already be moved.
              $r[] = array(
                // Id is set only when saving over an existing record. This will always be a new record
                'id' => '',
                // This should be a file already in the interim image upload folder.
                'path' => $_FILES[$key]['name'],
                'caption' => ''
              );
            }
          }
        }
      }
    }
    return $r;
  }

/**
   * Validation rule to test if an uploaded file is allowed by file size.
   * File sizes are obtained from the helper_config maxUploadSize, and defined as:
   * SB, where S is the size (1, 15, 300, etc) and
   * B is the byte modifier: (B)ytes, (K)ilobytes, (M)egabytes, (G)igabytes.
   * Eg: to limit the size to 1MB or less, you would use "1M".
   *
   * @param   array    $_FILES item
   * @param   array    maximum file size
   * @return  bool
   */
  public static function check_upload_size(array $file)
  {
    if ((int) $file['error'] !== UPLOAD_ERR_OK)
      return TRUE;

    if (isset(parent::$maxUploadSize))
      $size = parent::$maxUploadSize;
    else
      $size = '1M'; // default

    if ( ! preg_match('/[0-9]++[BKMG]/', $size))
      return FALSE;

    $size = self::convert_to_bytes($size);

    // Test that the file is under or equal to the max size
    return ($file['size'] <= $size);
  }

  /**
   * Utility method to convert a memory size string (e.g. 1K, 1M) into the number of bytes.
   *
   * @param string $size Size string to convert. Valid suffixes as G (gigabytes), M (megabytes), K (kilobytes) or nothing.
   * @return integer Number of bytes.
   */
  private static function convert_to_bytes($size) {
    // Make the size into a power of 1024
    switch (substr($size, -1))
    {
      case 'G': $size = intval($size) * pow(1024, 3); break;
      case 'M': $size = intval($size) * pow(1024, 2); break;
      case 'K': $size = intval($size) * pow(1024, 1); break;
      default:  $size = intval($size);                break;
    }
    return $size;
  }

  /**
   * Method that retrieves the data from a report or a table/view, ready to display in a chart or grid.
   * @deprecated, use report_helper::get_report_data instead.
   */
  public static function get_report_data($options, $extra='') {
    require_once('report_helper.php');
    return report_helper::get_report_data($options, $extra);
  }

  /**
   * Provides access to a list of remembered field values from the last time the form was used.
   * Accessor for the $remembered_fields variable. This is a list of the fields on the form
   * which are to be remembered the next time the form is loaded, e.g. for values that do not change
   * much from record to record. This creates the list on demand, by calling a hook indicia_define_remembered_fields
   * if it exists. indicia_define_remembered_fields should call data_entry_helper::set_remembered_fields to give it
   * an array of field names.
   * Note that this hook architecture is required to allow the list of remembered fields to be made available
   * before the form is constructed, since it is used by the code which saves a submitted form to store the
   * remembered field values in a cookie.
   * @return Array List of the fields to remember.
   */
  public static function get_remembered_fields() {
    if (self::$remembered_fields == null && function_exists('indicia_define_remembered_fields')) {
      indicia_define_remembered_fields();
    }
    return self::$remembered_fields;
  }

  /**
   * Accessor to set the list of remembered fields.
   * Should only be called by the hook method indicia_define_remembered_fields.
   * @see get_rememebered_fields
   * @param $arr Array of field names
   */
  public static function set_remembered_fields($arr) {
    self::$remembered_fields = $arr;
  }

  /**
  * While cookies may be offered for the convenience of clients, an option to prevent
  * the saving of personal data should also be present.
  *
  * Helper function to output an HTML checkbox control. Defaults to false unless
  * values are loaded from cookie.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * record with existing data for this control.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>template</b><br/>
  * Optional. Name of the template entry used to build the HTML for the control. Defaults to checkbox.</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the cookie optin control.
  */
  public static function remembered_fields_optin($options) {
    $options['fieldname'] = 'cookie_optin';
    $options = self::check_options($options);
    $options['checked'] = array_key_exists('indicia_remembered', $_COOKIE) ? ' checked="checked"' : '';
    $options['template'] = array_key_exists('template', $options) ? $options['template'] : 'checkbox';
    return self::apply_template($options['template'], $options);
  }

}
?>