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
 
require_once('helper_config.php');

/** 
 * Base class for the report and data entry helpers. Provides several generally useful methods and also includes 
 * resource management.
 */
class helper_base extends helper_config {

  /**
   * @var Array List of resources that have been identified as required by the controls used. This defines the
   * JavaScript and stylesheets that must be added to the page. Each entry is an array containing stylesheets and javascript
   * sub-arrays. This has public access so the Drupal module can perform Drupal specific resource output.
   */
  public static $required_resources=array();
  
  /**
   * @var Array List of all available resources known. Each resource is named, and contains a sub array of
   * deps (dependencies), stylesheets and javascripts.
   */
  private static $resource_list=null;
  
  /**
   * @var string Path to Indicia JavaScript folder. If not specified, then it is calculated from the Warehouse $base_url.
   */
  public static $js_path = null;

  /**
   * @var string Path to Indicia CSS folder. If not specified, then it is calculated from the Warehouse $base_url.
   */
  public static $css_path = null;

  /**
   * @var array List of resources that have already been dumped out, so we don't duplicate them.
   */
  protected static $dumped_resources=array();
  
  /**
   * @var string JavaScript text to be emitted after the data entry form. Each control that
   * needs custom JavaScript can append the script to this variable.
   */
  public static $javascript = '';

  /**
   * @var string JavaScript text to be emitted after the data entry form and all other JavaScript.
   */
  public static $late_javascript = '';

  /**
   * @var string JavaScript text to be emitted during window.onload.
   */
  public static $onload_javascript = '';
  
    /**
   * Array of html attributes. When replacing items in a template, these get automatically wrapped. E.g.
   * a template replacement for the class will be converted to class="value". The key is the parameter name,
   * and the value is the html attribute it will be wrapped into.
   */
  protected static $html_attributes = array(
    'class' => 'class',
    'outerClass' => 'class',
    'selected' => 'selected'
  );

  /**
   * Method to link up the external css or js files associated with a set of code.
   * This is normally called internally by the control methods to ensure the required files are linked into the page so
   * does not need to be called directly. However it can be useful when writing custom code that uses one of these standard
   * libraries such as jQuery. Ensures each file is only linked once. 
   *
   * @param string $resource Name of resource to link. The following options are available:
   * <ul>
   * <li>jquery</li>
   * <li>openlayers</li>
   * <li>addrowtogrid</li>
   * <li>indiciaMap</li>
   * <li>indiciaMapPanel</li>
   * <li>indiciaMapEdit</li>
   * <li>locationFinder</li>
   * <li>autocomplete</li>
   * <li>jquery_ui</li>
   * <li>json</li>
   * <li>treeview</li>
   * <li>googlemaps</li>
   * <li>multimap</li>
   * <li>virtualearth</li>
   * <li>google_search</li>
   * <li>flickr</li>
   * <li>defaultStylesheet</li>
   * </ul>
   */
  public static function add_resource($resource)
  {
    // If this is an available resource and we have not already included it, then add it to the list
    if (array_key_exists($resource, self::get_resources()) && !in_array($resource, self::$required_resources)) {
      $resourceList = self::get_resources();
      if (isset($resourceList[$resource]['deps'])) {
        foreach ($resourceList[$resource]['deps'] as $dep) {
          self::add_resource($dep);
        }
      }
      self::$required_resources[] = $resource;
    }
  }
  
  /**
   * List of external resources including stylesheets and js files used by the data entry helper class.
   */
  public static function get_resources()
  {
    if (self::$resource_list===null) {
      $base = parent::$base_url;
      if (!self::$js_path) {
        self::$js_path =$base.'media/js/';
      } else if (substr(self::$js_path,-1)!="/") {
        // ensure a trailing slash
        self::$js_path .= "/";
      }
      if (!self::$css_path) {
        self::$css_path =$base.'media/css/';
      } else if (substr(self::$css_path,-1)!="/") {
        // ensure a trailing slash
        self::$css_path .= "/";
      }
      global $indicia_theme, $indicia_theme_path;
      if (!isset($indicia_theme)) {
        // Use default theme if page does not specify it's own.
        $indicia_theme="default";
      }
      if (!isset($indicia_theme_path)) {
        // Use default theme path if page does not specify it's own.
        $indicia_theme_path="$base/media/themes";
      }

      self::$resource_list = array (
        'jquery' => array('javascript' => array(self::$js_path."jquery.js",self::$js_path."ie_vml_sizzlepatch_2.js")),
        'openlayers' => array('javascript' => array(self::$js_path."OpenLayers.js", self::$js_path."Proj4js.js", self::$js_path."Proj4defs.js")),
        'addrowtogrid' => array('javascript' => array(self::$js_path."addRowToGrid.js")),
        'indiciaMapPanel' => array('deps' =>array('jquery', 'openlayers', 'jquery_ui'), 'javascript' => array(self::$js_path."jquery.indiciaMapPanel.js")),
        'indiciaMapEdit' => array('deps' =>array('indiciaMap'), 'javascript' => array(self::$js_path."jquery.indiciaMap.edit.js")),
        'georeference_google_search_api' => array('javascript' => array("http://www.google.com/jsapi?key=".parent::$google_search_api_key)),
        'locationFinder' => array('deps' =>array('indiciaMapEdit'), 'javascript' => array(self::$js_path."jquery.indiciaMap.edit.locationFinder.js")),
        'autocomplete' => array('deps' => array('jquery'), 'stylesheets' => array(self::$css_path."jquery.autocomplete.css"), 'javascript' => array(self::$js_path."jquery.autocomplete.js")),
        'jquery_ui' => array('deps' => array('jquery'), 'stylesheets' => array("$indicia_theme_path/$indicia_theme/jquery-ui.custom.css"), 'javascript' => array(self::$js_path."jquery-ui.custom.min.js", self::$js_path."jquery-ui.effects.js")),
        'jquery_ui_fr' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path."jquery.ui.datepicker-fr.js")),
      	'json' => array('javascript' => array(self::$js_path."json2.js")),
        'treeview' => array('deps' => array('jquery'), 'stylesheets' => array(self::$css_path."jquery.treeview.css"), 'javascript' => array(self::$js_path."jquery.treeview.js", self::$js_path."jquery.treeview.async.js",
        self::$js_path."jquery.treeview.edit.js")),
        'googlemaps' => array('javascript' => array("http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=".parent::$google_api_key)),
        'multimap' => array('javascript' => array("http://developer.multimap.com/API/maps/1.2/".parent::$multimap_api_key)),
        'virtualearth' => array('javascript' => array('http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1')),
        'google_search' => array('stylesheets' => array(),
            'javascript' => array(
              "http://www.google.com/jsapi?key=".parent::$google_search_api_key,
              self::$js_path."google_search.js"
            )
        ),
        'fancybox' => array('deps' => array('jquery'), 'stylesheets' => array(self::$js_path.'fancybox/jquery.fancybox.css'), 'javascript' => array(self::$js_path.'fancybox/jquery.fancybox.pack.js')),
        'flickr' => array('deps' => array('fancybox'), 'javascript' => array(self::$js_path."jquery.flickr.js")),
        'treeBrowser' => array('deps' => array('jquery','jquery_ui'), 'javascript' => array(self::$js_path."jquery.treebrowser.js")),
        'defaultStylesheet' => array('deps' => array(''), 'stylesheets' => array(self::$css_path."default_site.css"), 'javascript' => array()),
        'validation' => array('deps' => array('jquery'), 'javascript' => array(self::$js_path.'jquery.validate.js')),
        'plupload' => array('deps' => array('jquery_ui','fancybox'), 'javascript' => array(
            self::$js_path.'jquery.uploader.js', self::$js_path.'/plupload/js/plupload.full.min.js')),
        'jqplot' => array('stylesheets' => array(self::$js_path.'jqplot/jquery.jqplot.css'), 'javascript' => array(self::$js_path.'jqplot/jquery.jqplot.min.js','[IE]'.self::$js_path.'jqplot/excanvas.min.js')),
        'jqplot_bar' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.barRenderer.min.js')),
        'jqplot_pie' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.pieRenderer.min.js')),
        'jqplot_category_axis_renderer' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.categoryAxisRenderer.min.js')),
        'reportgrid' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path.'jquery.reportgrid.js')),
        'tabs' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path.'tabs.js')),
        'wizardprogress' => array('deps' => array('tabs'), 'stylesheets' => array(self::$css_path."wizard_progress.css")),
      );
    }
    return self::$resource_list;
  }
  
  /**
   * Sends a POST using the cUrl library
   */
  public static function http_post($url, $postargs, $output_errors=true) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    if ($postargs!==null) {
      curl_setopt ($session, CURLOPT_POST, true);
      curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    }
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // Do the POST and then close the session
    $response = curl_exec($session);
    // Check for an error, or check if the http response was not OK. Note that the cUrl emulator only returns connection: close.
    if (curl_errno($session) || (strpos($response, 'HTTP/1.1 200 OK')===false && strpos($response, 'Connection: close')===false)) {
      if ($output_errors) {
        echo '<div class="error">cUrl POST request failed. Please check cUrl is installed on the server and the $base_url setting is correct.<br/>';
        if (curl_errno($session)) {
          echo 'Error number: '.curl_errno($session).'<br/>';
          echo 'Error message: '.curl_error($session).'<br/>';
        }
        echo "Server response<br/>";
        echo $response.'</div>';
      }
      $return = array(
          'result'=>false,
          'output'=> curl_errno($session) ? curl_error($session) : $response,
          'errno'=>curl_errno($session));
    } else {
      $arr_response = explode("\r\n\r\n",$response);
      // last part of response is the actual data
      $return = array('result'=>true,'output'=>array_pop($arr_response));
    }
    curl_close($session);
    return $return;
  }
  
  /**
   * Calculates the folder that submitted images end up in according to the helper_config.
   */
  public static function get_uploaded_image_folder() {
    if (!isset(self::$final_image_folder) || self::$final_image_folder=='warehouse')
      return self::$base_url.(isset(self::$indicia_upload_path) ? self::$indicia_upload_path : 'upload/');
    else {
      return dirname($_SERVER['PHP_SELF']) . '/' . self::relative_client_helper_path() . self::$final_image_folder;
    }      
  }
  
  /**
   * Calculates the relative path to the client_helpers folder from wherever the current PHP script is.
   */
  public static function relative_client_helper_path() {
    // get the paths to the client helper folder and php file folder as an array of tokens
    $clientHelperFolder = explode(DIRECTORY_SEPARATOR, realpath(dirname(__FILE__)));
    $currentPhpFileFolder = explode(DIRECTORY_SEPARATOR, realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
    // Find the first part of the paths that is not the same
    for($i = 0; $i<min(count($currentPhpFileFolder), count($clientHelperFolder)); $i++) {
      if ($clientHelperFolder[$i] != $currentPhpFileFolder[$i]) {
        break;
      }
    }
    // step back up the path to the point where the 2 paths differ
    $path = str_repeat('../', count($currentPhpFileFolder)-$i);
    // add back in the different part of the path to the client helper folder
    for ($j = $i; $j < count($clientHelperFolder); $j++) {
      $path .= $clientHelperFolder[$j] . '/';
    }
    return $path;
  }
  
  /**
   * Returns the HTML required for a parameters form, e.g. the form defined for input of report parameters or the 
   * default values for a csv import.
   * @param array $formArray Associative array defining the form structure.
   */
  public static function build_params_form($formArray) {
    $r = '';
    foreach($formArray as $key=>$info) {
      // Skip parameters if we have been asked to ignore them
      if (isset($options['ignoreParams']) && in_array($key, $options['ignoreParams'])) continue;
      $ctrlOptions = array(
        'label' => $info['display'],
        'helpText' => $info['description'],
        'fieldname' => 'param-' . (isset($options['id']) ? $options['id'] : '')."-$key"
      );
      // If this parameter is in the URL or post data, put it in the control
      if (isset($params[$key])) {
        $ctrlOptions['default'] = $params[$key];
      }
      if ($info['datatype']=='lookup' && isset($info['population_call'])) {
        // population call is colon separated, of the form direct|report:table|view|report:idField:captionField
        $popOpts = explode(':', $info['population_call']);
        $ctrlOptions = array_merge($ctrlOptions, array(
          'valueField'=>$popOpts[2],
          'captionField'=>$popOpts[3],
          'blankText'=>'<'.lang::get('please select').'>',
          'extraParams'=>$options['readAuth']
        ));
        if ($popOpts[0]=='direct') 
          $ctrlOptions['table']=$popOpts[1];
        else
          $ctrlOptions['report']=$popOpts[1];
        $r .= data_entry_helper::select($ctrlOptions);
      } elseif ($info['datatype']=='lookup' && isset($info['lookup_values'])) {
        // Convert the lookup values into an associative array
        $lookups = explode(',', $info['lookup_values']);
        $lookupsAssoc = array();
        foreach($lookups as $lookup) {
          $lookup = explode(':', $lookup);
          $lookupsAssoc[$lookup[0]] = $lookup[1];
        }

        $ctrlOptions = array_merge($ctrlOptions, array(
          'blankText'=>'<'.lang::get('please select').'>',
          'lookupValues' => $lookupsAssoc
        ));
        $r .= data_entry_helper::select($ctrlOptions);
      } elseif ($info['datatype']=='date') {
        $r .= data_entry_helper::date_picker($ctrlOptions);
      } else {
        $r .= data_entry_helper::text_input($ctrlOptions);
      }
    }
    return $r;
  }

  /**
   * Takes an associative array and converts it to a list of params for a query string. This is like
   * http_build_query but it does not url encode the & separator, and gives control over urlencoding the array values.
   */
  protected static function array_to_query_string($array, $encodeValues=false) {
    $params = array();
    if(is_array($array)) {
      arsort($array);
      foreach ($array as $a => $b)
      {
        if ($encodeValues) $b=urlencode($b);
        $params[] = "$a=$b";
      }
    }
    return implode('&', $params);
  }
  
    /**
   * Applies a output template to an array. This is used to build the output for each item in a list,
   * such as a species checklist grid or a radio group.
   *
   * @param array $item Array holding the item attributes.
   * @param string $template Name of the template to use, or actual template text if
   * $useTemplateAsIs is set to true.
   * @param boolean $useTemplateAsIs If true then the template parameter contains the actual
   * template text, otherwise it is the name of a template in the $indicia_templates array. Default false.
   * @param boolean $allowHtml If true then HTML is emitted as is from the parameter values inserted into the template,
   * otherwise they are escaped.
   * @return string HTML for the item label
   */
  protected static function mergeParamsIntoTemplate($params, $template, $useTemplateAsIs=false, $allowHtml=false) {
    global $indicia_templates;
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=array();
    $replaceValues=array();
    foreach ($params as $param=>$value) {
      if (!is_array($value)) {
        array_push($replaceTags, '{'.$param.'}');
        // allow sep to have <br/>
        $value = ($param == 'sep' || $allowHtml) ? $value : htmlSpecialChars($value);
        // HTML attributes get automatically wrapped
        if (in_array($param, self::$html_attributes) && !empty($value))
          $value = " $param=\"$value\"";
        array_push($replaceValues, $value);
      }
    }
    if (!$useTemplateAsIs) $template = $indicia_templates[$template];
    return str_replace($replaceTags, $replaceValues, $template);
  }


  
}

?>