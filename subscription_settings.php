<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Email notification settings</title>
<style>
body { font-family: Verdana, Geneva, sans-serif; }
form {
  max-width: 1200px;
  margin: auto;
}
td, th {
  border: solid silver 1px;
  padding: 0.2em 0.8em;
}
form label {
  width: 400px !important;
}
fieldset {
  margin: 1em 0;
}
legend {
  font-weight: bold;
}
</style>
</head>

<body>
<form method="POST">
<?php
if (empty($_GET['user_id']) || empty($_GET['warehouse_url'])) {
  echo '<p>Invalid link</p>';
} else {
  //If there is a POST, then the user has saved, so process this
  if (!empty($_POST)) {
    $response = subscription_settings::build_submission();
    $decodedResponse=json_decode($response);
    if (isset($decodedResponse->error)) {   
      ?><h2>A problem seems to have occurred, the response from the server is as follows:</h2><?php
      echo print_r($response,true);
      ?><form><input type=button value="Return To Subscription Settings Screen" onClick="window.location = document.URL;"></form><?php
    } else {
      ?><h2>Your Subscription Changes Have Been Saved</h2><?php
      ?><form><input type=button value="Return To Subscription Settings Screen" onClick="window.location = document.URL;"></form><?php
    }
  } else {
    echo subscription_settings::notificationEmailSettings();
    echo subscription_settings::speciesAlertSettings(); ?>
    <input type="submit"value="Save Changes">
    </form><?php
  }
}

class subscription_settings {
  private function get_page_configuration() {
    $configuration['frequencies']=array('NONE'=>'NONE', 'IH'=>'Immediate/Hourly','D'=>'Daily','W'=>'Weekly');
    $configuration['sourceTypes']=array('S'=>'Species alerts','C'=>'Comments on your records','V'=>'Verification of your records','A'=>'Record Cleaner results for your records','VT'=>'Incoming records for you to verify','M'=>'Milestones and achievements you\'ve attained');
    $configuration['privateKey']='Indicia';
    $configuration['cssPath']='media/css/default_site.css';
    $configuration['dataEntryHelperPath']='client_helpers/data_entry_helper.php';
    return $configuration;
  }

  //Display the notification settings boxes
  public function notificationEmailSettings() {
    $configuration = self::get_page_configuration();
    $cssPath=$configuration['cssPath'];
    $dataEntryHelperPath=$configuration['dataEntryHelperPath'];
    echo "<style>\n";
    include $cssPath;
    echo "</style>\n";
    require_once $dataEntryHelperPath;?>
    <h1>Notification Email Settings</h1>
    <fieldset><legend>Email digest frequencies</legend>
    Use the following boxes to select how often you would like to receive emails containing details of new notifications. You can select a different frequency depending on the notification type.</br></br><?php
    
    
    $frequencies = $configuration['frequencies'];
    $sourceTypes = $configuration['sourceTypes'];
    $auth=self::getAuth(0-$_GET['user_id'],$configuration['privateKey']);
    $notificationEmailSettings = self::get_population_data(array(
      'table' => 'user_email_notification_setting',
      'extraParams' => $auth['read']+array('user_id'=>$_GET['user_id']),
    ));
    //Set up the data from the user_email_notifcation_setting table so that the source_type is the array key, then we can access the data by source_type
    foreach ($notificationEmailSettings as $notificationEmailSetting) {
      $notificationEmailSettingSorted[$notificationEmailSetting['notification_source_type']]=array($notificationEmailSetting['id'],$notificationEmailSetting['notification_frequency']);
    }
    //Loop through each notification source type available and create a drop-down so that the user can select the frequency they want.
    foreach ($sourceTypes as $sourceType=>$sourceTypeFullName) {
      $selectSettings=array(
        'label' => $sourceTypeFullName,
        'lookupValues' =>$frequencies,
      );    
      //If there is existing data then set a default for the select drop-downs
      //The ID of an existing database record is tagged onto the end of the fieldname for use in the submission, get this from the notification data, this is stored as an array where the key
      //is the source type and the id is at index 0
      if (!empty($notificationEmailSettingSorted[$sourceType])) {
        $selectSettings['fieldname']='notification_setting:'.$sourceType.':'.$notificationEmailSettingSorted[$sourceType][0];
        $selectSettings['default']=$notificationEmailSettingSorted[$sourceType][1];
      } else {
        $selectSettings['fieldname']='notification_setting:'.$sourceType;
      }
      echo data_entry_helper::select($selectSettings);
    }
    echo "</fieldset>\n";
  }

  /*
   * Draw a species alerts grid so the user can delete species alerts
   */
  public function speciesAlertSettings() {
    $configuration = self::get_page_configuration();
    $auth=self::getAuth(0-$_GET['user_id'],$configuration['privateKey']);
    $extraParams=array('user_id'=>$_GET['user_id'],'view'=>'gv');

    //Get data to display on grid
    $speciesAlertData = self::get_population_data(array(
      'table' => 'species_alert',
      'extraParams' => $auth['read'] + $extraParams,
    ));
    //Create a grid
    if (!empty($speciesAlertData)) {
      ?><fieldset><legend>Species alerts</legend>
      <p>Select the checkbox against any species alert settings you want to remove your subscription for.</p>
      <table><?php
      ?><tr><?php
      //Draw the column headers
      $wantCols = array('alert_on_entry','alert_on_verify','preferred_taxon','default_common_name','location_name');
      foreach ($speciesAlertData[0] as $headerName=>$speciesAlertColumnData) {
        if (in_array($headerName, $wantCols)) {
          echo '<th>';
          $headerName = str_replace('Id', 'ID', ucwords(str_replace('_', ' ', $headerName)));
          echo $headerName; 
          echo "</th>\n";
        }
      }    
      ?></tr><?php
      //Create a row for each species alert item related to the user
      foreach ($speciesAlertData as $speciesAlertItem) {
        echo '<tr>';
        foreach ($speciesAlertItem as $field => $speciesAlertColumnData) {
          if (in_array($field, $wantCols)) {
            switch ($speciesAlertColumnData) {
              case 't': $speciesAlertColumnData = 'Yes'; break;
              case 'f': $speciesAlertColumnData = 'No'; break;
              case '': $speciesAlertColumnData = '-'; break;
            }
            echo "<td>$speciesAlertColumnData</td>";
          }
        }
        echo "<td><input id=\"remove:$speciesAlertItem[id]\" name=\"remove:$speciesAlertItem[id]\" type=\"checkbox\"></td>";
        echo "</tr>";
      }
      ?></table></fieldset><?php
    }
  }

  //Get an authentication
  private function getAuth($website_id,$password) {
    $postargs = "website_id=$website_id";
    $response = self::http_post($_GET['warehouse_url'].'/index.php/services/security/get_read_write_nonces', $postargs);
    $nonces = json_decode($response, true);
    return array(
      'read'=>array(
        'auth_token' => sha1("$nonces[read]:".$password),
        'nonce' => $nonces['read']
       ),
      'write'=>array(
          'auth_token' => sha1("$nonces[write]:".$password),
          'nonce' => $nonces['write']
      )
    );
  }

  //Allow us to POST a submission
  private function http_post($url, $postargs=null) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    if ($postargs!==null) {
      curl_setopt ($session, CURLOPT_POST, true);
      curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    }
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // Do the POST
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    // Check for an error, or check if the http response was not OK.
    if (curl_errno($session) || $httpCode!==200) {
      if (curl_errno($session))
        throw new exception(curl_errno($session) . ' - ' . curl_error($session));
      else {
        throw new exception($httpCode . ' - ' . $response);
      }
    }
    curl_close($session);
    return $response;
  }

  //Get data from a database view. Simplified version of the standard indicia function with elements I don't need removed
  private static function get_population_data($options) {
    $serviceCall = 'data/'.$options['table'].'?mode=json';
    $request = "index.php/services/$serviceCall";
    if (array_key_exists('extraParams', $options)) {
      // make a copy of the extra params
      $params = array_merge($options['extraParams']);
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
      }
      // use advanced querying technique if we need to
      if (isset($filterToEncode['in']))
        $request .= '&query='.urlencode(json_encode($filterToEncode)).'&'.self::array_to_query_string($otherParams, true);
      else
        $request .= '&'.self::array_to_query_string($options['extraParams'], true);
    }
    if (!isset($response) || $response===false) {
      $response = self::http_post($_GET['warehouse_url'].'/'.$request, null);
    }
    $r = json_decode($response, true);
    if (!is_array($r)) {
      $response['request'] = $request;
      throw new Exception('Invalid response received from Indicia Warehouse. '.print_r($response, true));
    }
    return $r;
  }

  /**
   * Takes an associative array and converts it to a list of params for a query string. This is like
   * http_build_query but it does not url encode the & separator, and gives control over urlencoding the array values.
   * @param array $array Associative array to convert.
   * @param boolean $encodeValues Default false. Set to true to URL encode the values being added to the string.
   * @return string The query string.
   */
  private static function array_to_query_string($array, $encodeValues=false) {
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

  /*
   * Create data structure to submit when user saves
   */
  public static function build_submission() {
    $submission['id']='user_email_notification_setting';
    $submission['submission_list']['entries'] = array();
    foreach ($_POST as $fieldname => $value) {
      $name = explode(':',$fieldname);
      //The fieldname tells us whether we are looking at a field associated with the notification frequency settings or the species alerts grid
      if ($name[0]=='notification_setting') {
        //If the drop-down is set to NONE, and it is a new item rather than a drop-down the user has changed to being NONE from an existing selection, then we can ignore this code as we don't need to take any action.
        //$name[2] is the record id which is taken off the end of the fieldname, if this is missing we know it is a new record
        if (!($_POST[$fieldname]==='NONE'&&empty($name[2]))) {
          $sourceType=$name[1]; 
          $data['id']='user_email_notification_setting';
          //The third part of the fieldname in the notifications settings select lists is the id of the exsiting user_email_notification_setting record. Use this id to save otherwise a new
          //record would be created rather than editing of the old record
          if (!empty($name[2]))
            $data['fields']['id']['value'] = $name[2];
          if ($_POST[$fieldname]==='NONE') {
            $data['fields']['deleted']['value']='t';
          } else {
            $data['fields']['user_id']['value'] = $_GET['user_id'];
            $data['fields']['notification_source_type']['value'] = $sourceType;
            //The source frequency to use is simply the value from the select drop-down, so grab from post for the fieldname
            $data['fields']['notification_frequency']['value'] = $_POST[$fieldname];
          }
          $submission['submission_list']['entries'][] = $data;
          //Set the data holder to empty once we have given it to the submissions list so we can re-use it
          $data=array();
        }
      } else {
        $data['id']='species_alert';
        //The id of the species alert record is at the end of the fieldname
        $data['fields']['id']['value'] = $name[1];
        $data['fields']['deleted']['value'] = 't';
        $submission['submission_list']['entries'][] = $data;
        //Set the data holder to empty once we have given it to the submissions list so we can re-use it
        $data=array();
      }
    }
    $response = self::do_submission('save', $submission);
    return $response;
  }
  
  //Take the submission structure and give it to data services
  private static function do_submission($entity, $submission = null, $writeTokens = null) {
    $configuration = self::get_page_configuration();
    $auth=self::getAuth(0-$_GET['user_id'],$configuration['privateKey']);
    $writeTokens=$auth['write'];
    $request = $_GET['warehouse_url']."/index.php/services/data/$entity";
    $postargs = 'submission='.urlencode(json_encode($submission));
    // passthrough the authentication tokens as POST data. Use parameter writeTokens
    foreach($writeTokens as $token => $value){
      $postargs .= '&'.$token.'='.($value === true ? 'true' : ($value === false ? 'false' : $value));
    } 
    $postargs .= '&user_id='.$_GET['user_id'];
    $response = self::http_post($request, $postargs);
    return $response;
  }
}
?>
</body>
</html>