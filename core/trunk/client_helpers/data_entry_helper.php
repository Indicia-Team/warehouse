<?php

require_once('helper_config.php');

class data_entry_helper extends helper_config {

 /**
 * Removes any data entry values persisted into the $_SESSION by Indicia.
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
   */
  public static function add_post_to_session () {
    foreach ($_POST as $name=>$value) {
      $_SESSION['indicia:'.$name]=$value;
    }
  }

  /**
   * Returns an array constructed from all the indicia variables that have previously been stored
   * in the session.
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
* Helper function to support image upload by inserting a file path upload control.
*/
public static function image_upload($id){
  $r = "<input type='file' id='$id' name='$id' accept='png|jpg|gif'/>";

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
 * @param int list_id Database id of the taxon list to lookup against.
 * @param int[] occ_attrs Integer array, where each entry corresponds to the id of the
 * desired attribute in the occurrence_attributes table.
 * @param string[] readAuth The read authorisation key/value pair, needed for making
 * queries to the data services.
 * @param string[] extraParams Array of key=>value pairs which will be passed to the service
 * as GET parameters.
 */
public static function species_checklist($list_id, $occ_attrs, $readAuth, $extraParams = array(), $lookupList = null)
{
  self::add_resource('json');
  self::add_resource('autocomplete');
  $occAttrControls = array();
  $occAttrs = array();
  // Reference to the config file.
  global $javascript;
  // Declare the data service
  $url = parent::$base_url."/index.php/services/data";
  $termRequest = "$url/taxa_taxon_list?mode=json&taxon_list_id=$list_id&preferred=t";
  $termRequest .= self::array_to_query_string($readAuth);
  if ($extraParams)
    $termRequest .= self::array_to_query_string($extraParams);
  // Get the curl session object
  $session = curl_init($termRequest);
  curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
  $taxalist = curl_exec($session);
  $taxalist = json_decode(array_pop(explode("\r\n\r\n",$taxalist)), true);
  // Get the list of occurrence attributes
  foreach ($occ_attrs as $occAttr)
  {
    $occAttrRequest = "$url/occurrence_attribute/$occAttr?mode=json";
    $occAttrRequest .= self::array_to_query_string($readAuth);
    $session = curl_init($occAttrRequest);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $a = json_decode(array_pop(explode("\n\n", curl_exec($session))), true);
    if (! array_key_exists('error', $a))
    {
      $b = $a[0];
      $occAttrs[$occAttr] = $b['caption'];
      // Build the correct control
      switch ($b['data_type'])
      {
        case 'L':
          $tlId = $b['termlist_id'];
          $occAttrControls[$occAttr] =
          data_entry_helper::select(
            'oa:'.$occAttr, 'termlists_term', 'term', 'id',
            $readAuth + array('termlist_id' => $tlId)
          );
          break;
        case 'D' || 'V':
          // Date-picker control
          $occAttrControls[$occAttr] =
          "<input type='text' class='date' id='oa:$occAttr' name='oa:$occAttr' value='click here'/>";
          break;

        default:
          $occAttrControls[$occAttr] =
          "<input type='text' id='oa:$occAttr' name='oa:$occAttr'/>";
          break;
      }
    }
  }

  // Build the grid
  if (! array_key_exists('error', $taxalist))
  {
    $grid = "<table style='display: none'><tbody><tr id='scClonableRow'><td class='scTaxonCell'></td>".
    "<td class='scPresenceCell'><input type='checkbox' name='' value='' checked='true' /></td>";
    foreach ($occAttrControls as $oc) {
      $grid .= "<td class='scOccAttrCell'>$oc</td>";
    }
    $grid .= "</tr></tbody></table>";
    $grid .= "<table class='speciesCheckList'>";
    $grid .= "<thead><th>Species</th><th>Present (Y/N)</th>";
    foreach ($occAttrs as $a) {
      $grid .= "<th>$a</th>";
    }
    $grid .= "</thead><tbody>";
    foreach ($taxalist as $taxon) {
      $id = $taxon['id'];
      $grid .= "<tr>";
      $grid .= "<td class='scTaxonCell'>".$taxon['taxon']." ".$taxon['authority']."</td>";
      $grid .= "<td class='scPresenceCell'><input type='checkbox' name='sc:$id:present' ".
          "value='sc:$id:present' /></td>";
      foreach ($occAttrControls as $oc) {
        $oc = preg_replace('/oa:(\d+)/', "sc:$id:occAttr:$1", $oc);
        $grid .= "<td class='scOccAttrCell'>".$oc."</td>";
      }
      $grid .= "</tr>";
    }
    $grid .= "</tbody></table>";

    // Insert an autocomplete box if the termlist has a parent or an alternate
    // termlist has been given in the parameter.
    if ($lookupList == null) {
      $tlRequest = "$url/taxon_list/$list_id?mode=json&view=detail";
      $session = curl_init($tlRequest);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $tl = json_decode(array_pop(explode("\r\n\r\n",curl_exec($session))), true);
      if (! array_key_exists('error', $tl)) {
        $lookupList = $tl[0]['parent_id'];
      }
    }
    if ($lookupList != null) {
      // Javascript to add further rows to the grid
      self::add_resource('addrowtogrid');
      $javascript .= "var addRowFn = addRowToGrid('$url', {'auth_token' : '".
          $readAuth['auth_token']."', 'nonce' : '".$readAuth['nonce']."'});
      jQuery('#addRowButton').click(addRowFn);\r\n";

      // Drop an autocomplete box against the parent termlist
      $grid .= '<label for="addSpeciesBox">Enter additional species:</label>';
      $grid .= data_entry_helper::autocomplete('addSpeciesBox',
          'taxa_taxon_list', 'taxon', 'id', $readAuth +
          array('taxon_list_id' => $lookupList));
      $grid .= "<button type='button' id='addRowButton'>Add Row</button>";
    }
    return $grid;
  } else {
    return $taxalist['error'];
  }
}

  /**
  * Helper function to generate a treeview from a given list
  *
  * @param string $control_id id attribute for the returned hidden input control.
  * NB the tree itself will have an id of "tr$control_id"
  * @param string $entity Name (Kohana-style) of the database entity to be queried.
  * @param string $nameField Field to draw values to show in the control from.
  * @param string $valueField Field to draw values to return from the control from. Defaults
  * to the value of $nameField.
  * @param string $topField Field used in filter to define top level entries
  * @param string $topValue Value of $topField used in filter to define top level entries
  * @param string $parentField Field used to indicate parent within tree for a record.
  * to the value of $nameField.
  * @param string $defaultValue initial value to set the control to (not currently used).
  * @param string[] extraParams Array of key=>value pairs which will be passed to the service
  * as GET parameters. Needs to specify the read authorisation key/value pair, needed for making
  * queries to the data services. Can also be used to specify the "view" type e.g. "detail"
  * @param string extraClass : main class to be added to UL tag - currently can be treeview, treeview-red,
  * treeview_black, treeview-gray. The filetree class although present, does not work properly.
  *
  * TO DO
  * Need to do initial value.
  * Need to look at how the filetree can be implemented.
  */

  public static function treeview($control_id, $entity,
           $nameField, $valueField, $topField, $topValue, $parentField,
           $defaultValue, $extraParams,
           $extraClass = 'treeview')
           {
             self::add_resource('treeview');
             // Reference to the config file.
             global $javascript;
             // Declare the data service
             $url = parent::$base_url."/index.php/services/data";
             // If valueField is null, set it to $nameField
             if ($valueField == null) $valueField = $nameField;
             // Do stuff with extraParams
             $sParams = '';
             foreach ($extraParams as $a => $b){
               $sParams .= "$a : '$b',";
             }
             // lop the comma off the end
             $sParams = substr($sParams, 0, -1);

             $javascript .= "jQuery('#tr$control_id').treeview(
             {
               url: '$url/$entity',
                           extraParams :
                           {
                       orderby : '$nameField',
                           mode : 'json',
                           $sParams
                           },
                           valueControl: '$control_id',
                           nameField: '$nameField',
                           valueField: '$valueField',
                           topField: '$topField',
                           topValue: '$topValue',
                           parentField: '$parentField',
                           dataType: 'jsonp',
                           parse: function(data)
                           {
                       var results =
                       {
                         'data' : data,
                           'caption' : data.$nameField,
                           'value' : data.$valueField
                       };
                       return results;
                           }
             }
             );";

             $tree = '<input type="hidden" class="hidden" id="'.$control_id.'" name="'.$control_id.'" /><ul id="tr'.$control_id.'" class="'.$extraClass.'"></ul>';
             return $tree;
           }


           /**
           * Helper function to insert a date picker control.
           */
           public static function date_picker($id, $default = '') {
             self::add_resource('datepicker');
             global $javascript;
             $javascript .=
             "jQuery('.$id').datepicker({dateFormat : 'yy-mm-dd', constrainInput: false});\r\n ";
             $r =
             "<input type='text' size='30' value='click here' class='date' id='$id' name='$id' value='$default'/>" .
             '<style type="text/css">.embed + img { position: relative; left: -21px; top: -1px; }</style> ';
             return $r;
           }


           /**
           * Helper function to generate a select control from a Indicia core service query.
           *
           * @param int $id id attribute for the returned control.
           * @param string $entity Name (Kohana-style) of the database entity to be queried.
           * @param string $nameField Field to draw values to show in the control from.
           * @param string $valueField Field to draw values to return from the control from. Defaults
           * to the value of $nameField.
           * @param map<string, string> $extraParams Associative array of items to pass via the query
           * string to the service.
           *
           * @return string HTML code for a select control.
           */
           public static function select($id, $entity, $nameField, $valueField = null, $extraParams = null, $default = '')
           {
             self::add_resource('json');
             $url = parent::$base_url."/index.php/services/data";
             // If valueField is null, set it to $nameField
             if ($valueField == null) $valueField = $nameField;
             // Execute a request to the service
             $request = "$url/$entity?mode=json";
             $request .= self::array_to_query_string($extraParams);
             // Get the curl session object
             $session = curl_init($request);
             curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
             $response = curl_exec($session);
             $response = json_decode(array_pop(explode("\r\n\r\n",$response)), true);
             $r = "";
             if (!array_key_exists('error', $response)){
               $r .= "<select name='$id' id='$id' >";
               foreach ($response as $item){
           if (array_key_exists($nameField, $item) &&
             array_key_exists($valueField, $item))
             {
               $selected = ($default == $item[$valueField]) ? "selected = 'selected'" : '';
               $r .= "<option value='$item[$valueField]' $selected >";
               $r .= $item[$nameField];
               $r .= "</option>";
             }
               }
               $r .= "</select>";
             }
             else
               echo "Error loading control";

             return $r;
           }

           /**
           * Helper function to generate a list box from a Indicia core service query.
           */
           public static function listbox($id, $entity, $nameField, $size = 3, $multival = false, $valueField = null, $extraParams = null, $default = '')
           {
             $url = parent::$base_url."/index.php/services/data";
             // If valueField is null, set it to $nameField
             if ($valueField == null) $valueField = $nameField;
             // Execute a request to the service
             $request = "$url/$entity?mode=json";
             $request .= self::array_to_query_string($extraParams);
             // Get the curl session object
             $session = curl_init($request);
             curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
             $response = curl_exec($session);
             $response = json_decode(array_pop(explode("\r\n\r\n",$response)), true);
             $r = "";
             if (!array_key_exists('error', $response))
             {
               $r .= "<select id='$id' name='$id' multiple='$multival' size='$size'>";
               foreach ($response as $item)
               {
           if (array_key_exists($nameField, $item) &&
             array_key_exists($valueField, $item))
           {
             $selected = ($default == $item[$valueField]) ? 'selected="selected"' : '';
             $r .= "<option value='$item[$valueField]' $selected >";
             $r .= $item[$nameField];
             $r .= "</option>";
           }
               }
               $r .= "</select>";
             }
             else echo "Error loading control";
             return $r;
           }


           /**
           * Helper function to generate an autocomplete box from an Indicia core service query.
           */
           public static function autocomplete($id, $entity, $nameField, $valueField = null, $extraParams = null, $defaultName = '', $defaultValue = '') {
             self::add_resource('autocomplete');
             global $javascript;
             $url = parent::$base_url."/index.php/services/data";
             // If valueField is null, set it to $nameField
             if ($valueField == null) $valueField = $nameField;
             // Do stuff with extraParams
             $sParams = '';
             foreach ($extraParams as $a => $b){
               $sParams .= "$a : '$b',";
             }
             // lop the comma off the end
             $sParams = substr($sParams, 0, -1);

             // First create an id for our visible input control, then make it autocomplete. Strip colons
             // as they mess up the jQuery selectors
             $inputId = 'ac'.str_replace(':', '', $id);
             $javascript .= "jQuery('input#$inputId').autocomplete('$url/$entity',
      {
        minChars : 1,
      mustMatch : true,
      extraParams :
      {
        orderby : '$nameField',
      mode : 'json',
      qfield : '$nameField',
      $sParams
           },
      dataType: 'jsonp',
      parse: function(data)
      {
        var results = [];
        jQuery.each(data, function(i, item)
        {
          results[results.length] =
          {
            'data' : item,
       'result' : item.$nameField,
       'value' : item.$valueField
          };
        });
        return results;
        },
       formatItem: function(item)
       {
         return item.$nameField;
        },
       formatResult: function(item) {
  return item.$valueField;
        }
        });
        jQuery('input#$inputId').result(function(event, data){
  jQuery('input#$id').attr('value', data.id);
      });\r\n";
      $r = "<input type='hidden' class='hidden' id='$id' name='$id' value='$defaultValue' />".
      "<input id='$inputId' name='$inputId' value='$defaultName' />";
      return $r;
        }

        /**
        * Helper function to list the output from a request against the data services, using an HTML template
        * for each item.
        *
        * @param string $entity Name of the data entity that is being requested.
        * @param array $extraParams Additional parameters passed to the data services in the URL request. For example, this
        * can be used to specify the read authorisation, select only entries which match a certain field value, and
        * select the details view by specifying: $readAuth + array('field to test' => value,'view' => 'details').
        * @param string $template HTML template which will be emitted for each item. Fields from the data are identified
        * by wrapping them in ||. For example, <li>|term|</li> would result in the field called term's value being placed inside
        * <li> tags.
        * @return string HTML code for the list of items.
        */
        public static function list_in_template($entity, $extraParams = null, $template) {
          $url = parent::$base_url."/index.php/services/data";
          // Execute a request to the service
          $request = "$url/$entity?mode=json";
          $request .= self::array_to_query_string($extraParams);
          // Get the curl session object
          $session = curl_init($request);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $response = curl_exec($session);
          $response = json_decode(array_pop(explode("\r\n\r\n",$response)), true);
          $r = "";
          if (!array_key_exists('error', $response)){
      $r .= "<ul>";
      foreach ($response as $row){
  $item = $template;
  foreach ($row as $field => $value) {
      $value = htmlspecialchars($value, ENT_QUOTES);
      $item = str_replace("|$field|", $value, $item);
    }
    $r .= $item;
      }
      $r .= "</ul>";
    }
    else
      echo "Error loading control";

    return $r;
        }

  /**
  * Helper function to generate a radio group from a Indicia core service query.
  *
  * @param string $id Name of the field that will be populated by this control.
  * @param string $entity Name of the data entity that is being used to generate the list, e.g. termlists_term.
  * @param string $nameField Name of the field used to generate the caption for each radio item.
  * @param string $valueField Name of the field used to generate the stored value for each radio item. Defaults to same as $nameField.
  * @param array $extraParams Associative array of extra parameters appended to the web service request for the list of items. For example,
  * specifying $readAuth + array('termlist_id' => 1) would filter the terms generated to termlist 1.
  * @param string $sep Separator inserted betweeen each radio item, if required. For example,
  * '<br/>' causes radio buttons to appear on separate lines.
  * @param string $default Value of the radio button that should be selected when loaded. This can be used to specify a default, or to re-load
  * a value from an existing record.
  */
  public static function radio_group($id, $entity, $nameField, $valueField = null, $extraParams = null, $sep='', $default = '') {
    return self::check_or_radio_group(false, $id, $entity, $nameField, $valueField, $extraParams, $sep, $default);
  }

 /**
  * Helper function to generate a list of checkboxes from a Indicia core service query.
  *
  * @param string $id Name of the field that will be populated by this control.
  * @param string $entity Name of the data entity that is being used to generate the list, e.g. termlists_term.
  * @param string $nameField Name of the field used to generate the caption for each radio item.
  * @param string $valueField Name of the field used to generate the stored value for each radio item. Defaults to same as $nameField.
  * @param array $extraParams Associative array of extra parameters appended to the web service request for the list of items. For example,
  * specifying $readAuth + array('termlist_id' => 1) would filter the terms generated to termlist 1.
  * @param string $sep Separator inserted betweeen each radio item, if required. For example,
  * '<br/>' causes radio buttons to appear on separate lines.
  * @param string $default Value of the radio button that should be selected when loaded. This can be used to specify a default, or to re-load
  * a value from an existing record.
  */
  public static function checkbox_group($id, $entity, $nameField, $valueField = null, $extraParams = null, $sep='', $default = '') {
    return self::check_or_radio_group(true, $id, $entity, $nameField, $valueField, $extraParams, $sep, $default);
  }

  private static function check_or_radio_group($checkbox, $id, $entity, $nameField, $valueField, $extraParams, $sep, $default) {
    $url = parent::$base_url."/index.php/services/data";
    // If valueField is null, set it to $nameField
    if ($valueField == null) $valueField = $nameField;
    // Execute a request to the service
    $request = "$url/$entity?mode=json";
    $request .= self::array_to_query_string($extraParams);
    // Get the curl session object
    $session = curl_init($request);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($session), true);
    $r = "";
    if ($checkbox)
      $type="checkbox";
    else
      $type="radio";

    if (!array_key_exists('error', $response)){
      foreach ($response as $item) {
        if (array_key_exists($nameField, $item) && array_key_exists($valueField, $item)) {
          $name = htmlspecialchars($item[$nameField], ENT_QUOTES);
          $checked = ($default == $item[$valueField]) ? 'checked="checked"' : '' ;
          $r .= "<span><input type='$type' id='$id' name='$id' value='$item[$valueField]' $checked />";
          $r .= "$name</span>$sep";
        }
      }
    }
    return $r;
  }

  /**
   * Either takes the passed in array, or the post data if this is null, and forwards it to the data services
   * for saving as a member of the entity identified.
   */
  public static function forward_post_to($entity, $array = null) {
    if ($array == null)
      $array = self::wrap($_POST, $entity);
    $request = parent::$base_url."/index.php/services/data/$entity";
    $postargs = 'submission='.json_encode($array);
    // passthrough the authentication tokens as POST data
    if (array_key_exists('auth_token', $_POST))
      $postargs .= '&auth_token='.$_POST['auth_token'];
    if (array_key_exists('nonce', $_POST))
      $postargs .= '&nonce='.$_POST['nonce'];
    // Get the curl session object
    $session = curl_init($request);
    // Set the POST options.
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // Do the POST and then close the session
    $response = curl_exec($session);
    curl_close($session);
    // The last block of text in the response is the body
    $output = json_decode(array_pop(explode("\r\n",$response)), true);
    // If this is not JSON, it is an error, so just return it as is.
    if (!$output)
      $output = array_pop(explode("\r\n",$response));
    return $output;
  }

  public static function handle_media($media_id = 'imgUpload') {
    if (array_key_exists($media_id, $_FILES)) {
      syslog(LOG_DEBUG, "SITE: Media id $media_id to upload.");
      $uploadpath = parent::$upload_path;
      $target_url = parent::$base_url."/index.php/services/data/handle_media";

      $name = $_FILES[$media_id]['name'];
      $fname = $_FILES[$media_id]['tmp_name'];
      $fext = array_pop(explode(".", $name));
      $bname = basename($fname, ".$fext");

      // Generate a file id to store the image as
      $destination = time().rand(0,1000).".".$fext;

      if (move_uploaded_file($fname, $uploadpath.$destination)) {
        $postargs = array();
        if (array_key_exists('auth_token', $_POST)) {
               $postargs['auth_token'] = $_POST['auth_token'];
        }
        if (array_key_exists('nonce', $_POST)) {
          $postargs['nonce'] = $_POST['nonce'];
        }
        $file_to_upload = array('media_upload'=>'@'.$uploadpath.$destination);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_to_upload + $postargs);
        $result=curl_exec ($ch);
        curl_close ($ch);
        return $destination;
      } else {
        //TODO error messaging
        return false;
      }
    }
  }

  /**
  * Wraps data from a species checklist grid (generated by
  * data_entry_helper::species_checklist) into a suitable format for submission. This will
  * return an array of submodel entries which can be dropped directly into the subModel
  * section of the submission array.
  *
  * @param array $arr Array of data generated by data_entry_helper::species_checklist method.
  */
  public static function wrap_species_checklist($arr){
    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    if (array_key_exists('determiner_id', $arr)){
      $determiner_id = $arr['determiner_id'];
    }
    $records = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      if (strpos($key, 'sc') !== false){
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 3);
        $records[$a[1]][$a[2]] = $value;
      }
    }
    foreach ($records as $id => $record){
      if (! array_key_exists('present', $record) || !$record['present']){
        unset ($records[$id]);
        break;
      }
      $record['taxa_taxon_list_id'] = $id;
      $record['website_id'] = $website_id;
      $record['determiner_id'] = $determiner_id;
      $occAttrs = data_entry_helper::wrap_attributes($record, 'occurrence');
      $occ = data_entry_helper::wrap($record, 'occurrence');
      $occ['metaFields']['occAttributes']['value'] = $occAttrs;
      $subModels[] = array(
        'fkId' => 'sample_id',
        'model' => $occ
      );
    }

    return $subModels;
  }

  /**
  * Wraps attribute fields (entered as normal) into a suitable container for submission.
  * Throws an error if $entity is not something for which attributes are known to exist.
  * @return array
  */
  public static function wrap_attributes($arr, $entity) {
    switch ($entity) {
      case 'occurrence':
        $prefix = 'occAttr';
        break;
      case 'location':
        $prefix = 'locAttr';
        break;
      case 'sample':
        $prefix = 'smpAttr';
        break;
      default:
        throw new Exception('Unknown attribute type. Unable to wrap.');
    }
    $oap = array();
    $occAttrs = array();
    foreach ($arr as $key => $value) {
      if (strpos($key, $prefix) !== false) {
        $a = explode(':', $key);
        // Attribute in the form occAttr:36 for attribute with attribute id
        // of 36.
        $oap[] = array(
        $entity."_attribute_id" => $a[1],
            'value' => $value
        );
      }
    }
    foreach ($oap as $oa) {
      $occAttrs[] = data_entry_helper::wrap($oa, "$entity"."_attribute");
    }
    return $occAttrs;
  }

  /**
   * Wraps an array (e.g. Post or Session data generated by a form) into a structure
   * suitable for submission.
   *
   * @param array $array Array of data generated from data entry controls.
   * @param string $entity Name of the entity to wrap data for.
   */
  public static function wrap($array, $entity)
  {
    // Initialise the wrapped array
    $sa = array(
        'id' => $entity,
        'fields' => array()
    );

    // Iterate through the array
    foreach ($array as $a => $b)
    {
      // Don't wrap the authentication tokens
      if ($a!='auth_token' && $a!='nonce')
      {
        // This should be a field in the model.
        // Add a new field to the save array
        $sa['fields'][$a] = array('value' => $b);
      }
    }
    return $sa;
  }


  /**
  * Generates a map control, with optional data entry fields and location finder powered by the
  * Yahoo! geoservices API.
  *
  * @param string $div Id of a div to add the map into
  * @param array $layers Array of preset layers to include
  * @param bool $edit Include editable controls
  * @param bool $locate Include location finder
  * @param bool $defaultJs Automatically generate default javascript - otherwise leaves you to do this.
  */
  public static function map($div, $layers = array('google_physical', 'google_satellite', 'google_hybrid', 'google_streets', 'openlayers_wms', 'virtual_earth'), $edit = false, $locate = false, $wkt = null, $defaultJs = true)
  {
    global $javascript;
    self::add_resource('indiciaMap');
    if ($edit) self::add_resource('indiciaMapEdit');
    if ($locate) self::add_resource('locationFinder');

    foreach ($layers as $layer)
    {
      $a = explode('_', $layer);
      $a = strtolower($a[0]);
      switch($a)
      {
        case 'google':
          self::add_resource('googlemaps');
          break;
        case 'multimap':
          self::add_resource('multimap');
          break;
        case 'virtual':
          self::add_resource('virtualearth');
          break;
      }
    }

    if ($defaultJs)
    {
      $jsLayers = "[ '".implode('\', \'', $layers)."' ]";
      $javascript .= "jQuery('#$div').indiciaMap({ presetLayers : $jsLayers })";
      if ($edit)
      {
        $foo = ($wkt != null) ? "{ wkt : $wkt }" : '';
        $javascript .= ".indiciaMapEdit($foo)";
        if ($locate)
        {
          $api = parent::$geoplanet_api_key;
          $indicia = parent::$base_url;
          $javascript .= ".locationFinder( { indiciaSvc: '$indicia', apiKey : '$api' } )";
        }
      }
      $javascript .= ";";
    }
    $r = "<div id='$div'></div>";
    echo $r;
  }

  /**
  * Helper function to collect javascript code in a single location. Should be called at the end of each HTML
  * page which uses the data entry helper so output all JavaScript required by previous calls.
  */
  public static function dump_javascript() {
    global $javascript;
    global $res;
    $libraries = '';
    $stylesheets = '';
    if (isset($res)) {
      $RESOURCES = self::_RESOURCES();
      foreach ($res as $resource)
      {
        foreach ($RESOURCES[$resource]['stylesheets'] as $s)
        {
          $stylesheets .= "<link rel='stylesheet' type='text/css' href='$s' />\n";
        }
        foreach ($RESOURCES[$resource]['javascript'] as $j)
        {
          $libraries .= "<script type='text/javascript' src='$j'></script>\n";
        }
      }
    }
    $script = "<script type='text/javascript'>
    jQuery(document).ready(function() {
    $javascript
    });
    </script>";
    return $stylesheets.$libraries.$script;
  }

  /**
  * Takes a response, and outputs any errors from it onto the screen.
  *
  * @todo method of placing the errors alongside the controls.
  */
  public static function dump_errors($response)
  {
    if (is_array($response)) {
      if (array_key_exists('error',$response)) {
        echo '<div class="error">';
        echo '<p>An error occurred when the data was submitted.</p>';
        if (is_array($response['error'])) {
          echo '<ul>';
          foreach ($response['error'] as $field=>$message)
            echo "<li>$field: $message</li>";
          echo '</ul>';
        } else {
          echo '<p class="error_message">'.$response['error'].'</p>';
        }
        if (array_key_exists('file', $response) && array_key_exists('line', $response)) {
          echo '<p>Error occurred in '.$response['file'].' at line '.$response['line'].'</p>';
        }
        if (array_key_exists('errors', $response)) {
          echo '<pre>'.print_r($response['errors'], true).'</pre>';
        }
        if (array_key_exists('trace', $response)) {
          echo '<pre>'.print_r($response['trace'], true).'</pre>';
        }
        echo '</div>';
      }
      elseif (array_key_exists('warning',$response)) {
        echo 'A warning occurred when the data was submitted.';
        echo '<p class="error">'.$response['error'].'</p>';
      }
      elseif (array_key_exists('success',$response)) {
        echo '<div class="success">Thank you for submitting your data.</div>';
      }
    }
  else
    echo "<div class=\"error\">$response</div>";
  }


  /**
  * Retrieves a token and inserts it into a data entry form which authenticates that the
  * form was submitted by this website.
  *
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  */
  public static function get_auth($website_id, $password) {
    $postargs = "website_id=$website_id";
    // Get the curl session object
    $session = curl_init(parent::$base_url.'/index.php/services/security/get_nonce');
    // Set the POST options.
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // Do the POST and then close the session
    $response = curl_exec($session);
    list($response_headers,$nonce) = explode("\r\n\r\n",$response,2);
    curl_close($session);
    $result = '<input id="auth_token" name="auth_token" type="hidden" class="hidden" ' .
    'value="'.sha1("$nonce:$password").'" />'."\r\n";
    $result .= '<input id="nonce" name="nonce" type="hidden" class="hidden" ' .
    'value="'.$nonce.'" />'."\r\n";
    return $result;
  }

  /**
  * Retrieves a read token and passes it back as an array suitable to drop into the
  * 'extraParams' options for an Ajax call.
  *
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  */
  public static function get_read_auth($website_id, $password) {
    $postargs = "website_id=$website_id";
    // Get the curl session object
    $session = curl_init(parent::$base_url.'/index.php/services/security/get_read_nonce');
    // Set the POST options.
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // Do the POST and then close the session
    $response = curl_exec($session);
    list($response_headers,$nonce) = explode("\r\n\r\n",$response,2);
    return array(
        'auth_token' => sha1("$nonce:$password"),
        'nonce' => $nonce
    );
  }

  /**
   * Takes an associative array and converts it to a list of params for a query string.
   */
  private static function array_to_query_string($array) {
    $r = '';
    foreach ($array as $a => $b)
    {
      $r .= "&$a=$b";
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
   * List of external resources including stylesheets and js files used by the data entry helper class.
   */
  private static function _RESOURCES()
  {
    $base = parent::$base_url;
    return array
    (
    'jquery' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.js")),
    'openlayers' => array('deps' =>array(), 'stylesheets' => array(), 'javascript' => array("$base/media/js/OpenLayers.js")),
    'addrowtogrid' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("$base/client_helpers/addRowToGrid.js")),
    'indiciaMap' => array('deps' =>array('jquery', 'openlayers'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.js")),
    'indiciaMapEdit' => array('deps' =>array('indiciaMap'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.edit.js")),
    'locationFinder' => array('deps' =>array('indiciaMapEdit'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/jquery.indiciaMap.edit.locationFinder.js")),
    'autocomplete' => array('deps' => array('jquery'), 'stylesheets' => array("$base/media/css/jquery.autocomplete.css"), 'javascript' => array("$base/media/js/jquery.autocomplete.js")),
    'ui_core' => array('deps' => array('jquery'), 'stylesheets' => array(), 'javascript' => array("$base/media/js/ui.core.js")),
    'datepicker' => array('deps' => array('ui_core'), 'stylesheets' => array("$base/media/css/ui.datepicker.css"), 'javascript' => array("$base/media/js/ui.datepicker.js")),
    'json' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("$base/media/js/json2.js")),
    'treeview' => array('deps' => array('jquery'), 'stylesheets' => array("$base/media/css/jquery.treeview.css"), 'javascript' => array("$base/media/js/jquery.treeview.js", "$base/media/js/jquery.treeview.async.js",
    "$base/media/js/jquery.treeview.edit.js")),
    'googlemaps' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("http://maps.google.com/maps?file=api&v=2&key=".parent::$google_api_key)),
    'multimap' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array("http://developer.multimap.com/API/maps/1.2/".parent::$multimap_api_key)),
    'virtualearth' => array('deps' => array(), 'stylesheets' => array(), 'javascript' => array('http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1')),
    );
  }

  /**
   * Internal method to link up the external css or js files associated with a set of code.
   * Ensures each file is only linked once.
   *
   * @param string $resource Name of resource to link.
   */
  private static function add_resource($resource)
  {
    global $res;
    if (!isset($res)) $res = array();
    if (array_key_exists($resource, self::_RESOURCES()))
    {
      if (!in_array($resource, $res))
      {
        $RESOURCES = self::_RESOURCES();
        foreach ($RESOURCES[$resource]['deps'] as $dep)
        {
          self::add_resource($dep);
        }
        $res[] = $resource;
      }
    }
  }

}

?>
