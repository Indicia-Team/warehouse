<!DOCTYPE html>
<html>  
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Post a comment</title>
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
if (empty($_GET['user_id']) || empty($_GET['warehouse_url']) || empty($_GET['occurrence_id'])) {
  echo '<p>Invalid link</p>';
} else {
  $configuration = record_details_and_comments::get_page_configuration();
  //If there is a POST, then the user has saved, so process this
  if (!empty($_POST)) {
    $response = record_details_and_comments::build_submission($configuration);
    $decodedResponse=json_decode($response);
    if (isset($decodedResponse->error)) {   
      echo 'Error occurred';  
      ?><h2>A problem seems to have occurred, the response from the server is as follows:</h2><?php
      echo print_r($response,true);
      ?><form><input type=button value="Return To Record Comments Screen" onClick="window.location = document.URL;"></form><?php
    } else {
      ?><h2>Your Comment Has Been Saved</h2><?php
      ?><form><input type=button value="Return To Record Comments Screen" onClick="window.location = document.URL;"></form><?php
    }
  } else {
    echo record_details_and_comments::displayOccurrenceDetails($configuration);
    echo record_details_and_comments::displayExistingOccurrenceComments($configuration); ?>
    </form><?php
  }
}

class record_details_and_comments {
  public static function get_page_configuration() {
    $auth=self::getAuth(0-$_GET['user_id'],'Indicia');
    $userDetails = self::get_population_data(array(
      'table' => 'user',
      'extraParams' => $auth['read']+array('id'=>$_GET['user_id']),
    )); 
    $configuration['privateKey']='Indicia';
    $configuration['cssPath']='media/css/default_site.css';
    $configuration['dataEntryHelperPath']='client_helpers/data_entry_helper.php';
    $configuration['username']=$userDetails[0]['username'];
    return $configuration;
  }

  //Display the details of the occurrence
  public static function displayOccurrenceDetails($configuration) {
    echo "<style>\n";
    include $configuration['cssPath'];
    echo "</style>\n";
    require_once $configuration['dataEntryHelperPath'];
    ?>  
    <h1>Record details and comments</h1>
    <fieldset><legend>Details</legend>
    <?php
    $auth=self::getAuth(0-$_GET['user_id'],$configuration['privateKey']);
    $occurrenceDetails = self::get_population_data(array(
      'table' => 'occurrence',
      'extraParams' => $auth['read']+array('id'=>$_GET['occurrence_id']),
    ));  
    echo "<p>Species: ".$occurrenceDetails[0]['taxon']."</p>";
    $vagueDate = self::vague_date_to_string(array(
      $occurrenceDetails[0]['date_start'],
      $occurrenceDetails[0]['date_end'],
      $occurrenceDetails[0]['date_type']
    ));
    echo '<p>Date: '.$vagueDate."</p>";
    echo "<p>Spatial reference: ".$occurrenceDetails[0]['entered_sref'].' ('.$occurrenceDetails[0]['entered_sref_system'].')'."</p>";         
    echo "</fieldset>\n";
  }
  
  public static function displayExistingOccurrenceComments($configuration) {
    $configuration = self::get_page_configuration();
    $auth=self::getAuth(0-$_GET['user_id'],$configuration['privateKey']);
    $r = '<div>';
    $comments = self::get_population_data(array(
      'table' => 'occurrence_comment',
      'extraParams' => $auth['read'] + array(
          'occurrence_id' => $_GET['occurrence_id'],
          'sortdir' => 'DESC',
          'orderby' => 'updated_on'
      )
    ));
    $r .= '<div id="comment-list">';
    if (count($comments) === 0) {
      $r .= '<p id="no-comments">No comments have been made.</p>';
    }
    else {
      foreach ($comments as $comment) {
        $r .= '<div class="comment">';
        $r .= '<div class="header">';
        $r .= "<strong>$comment[person_name]</strong> ";
        $commentTime = strtotime($comment['updated_on']);
        // Output the comment time. Skip if in future (i.e. server/client date settings don't match).
        if ($commentTime < time()) {
          $r .= self::ago($commentTime);
        }
        $r .= '</div>';
        $c = str_replace("\n", '<br/>', $comment['comment']);
        $r .= "<div>$c</div>";
        $r .= '</div>';
      }
    }
    $r .= '</div>';
    $r .= '<form><fieldset><legend>Add new comment</legend>';
    $r .= '<textarea id="comment-text" name="comment-text"></textarea><br/>';
    $r .= '<input type="submit" class="default-button" value="Save">';
    $r .= '</fieldset></form>';
    $r .= '</div>';
    echo '<div class="detail-panel" id="detail-panel-comments"><h3>Comments</h3>' . $r . '</div>';
  }

  //Get an authentication
  private static function getAuth($website_id,$password) {
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
  private static function http_post($url, $postargs=null) {
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
      if (curl_errno($session)) {
        throw new exception(curl_errno($session) . ' - ' . curl_error($session));
      } else {
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
      $info = array('request' => $request, 'response' => $response);
      throw new Exception('Invalid response received from Indicia Warehouse. '.print_r($info, true));
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
  
  /**
   * Convert a timestamp into readable format (... ago) for use on a comment list.
   *
   * @param timestamp $timestamp
   *   The date time to convert.
   *
   * @return string
   *   The output string.
   */
  public static function ago($timestamp) {
    $difference = time() - $timestamp;
    // Having the full phrase means that it is fully localisable if the phrasing is different.
    $periods = array(
      lang::get("{1} second ago"),
      lang::get("{1} minute ago"),
      lang::get("{1} hour ago"),
      lang::get("Yesterday"),
      lang::get("{1} week ago"),
      lang::get("{1} month ago"),
      lang::get("{1} year ago"),
      lang::get("{1} decade ago")
    );
    $periodsPlural = array(
      lang::get("{1} seconds ago"),
      lang::get("{1} minutes ago"),
      lang::get("{1} hours ago"),
      lang::get("{1} days ago"),
      lang::get("{1} weeks ago"),
      lang::get("{1} months ago"),
      lang::get("{1} years ago"),
      lang::get("{1} decades ago")
    );
    $lengths = array("60", "60", "24", "7", "4.35", "12", "10");

    for ($j = 0; $difference >= $lengths[$j]; $j++) {
      $difference /= $lengths[$j];
    }
    $difference = round($difference);
    if ($difference == 1) {
      $text = str_replace('{1}', $difference, $periods[$j]);
    }
    else {
      $text = str_replace('{1}', $difference, $periodsPlural[$j]);
    }
    return $text;
  }
  
  /*
   * Create data structure to submit when user saves
   */
  public static function build_submission($configuration) {
    $submission = array();
    $submission['id']='occurrence_comment';
    $submission['fields']['created_by_id']['value'] = $_GET['user_id'];
    $submission['fields']['updated_by_id']['value'] = $_GET['user_id'];
    $submission['fields']['occurrence_id']['value'] = $_GET['occurrence_id'];
    $submission['fields']['comment']['value'] = $_POST['comment-text'];
    $submission['fields']['person_name']['value'] = $configuration['username'];
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
  
  //Note: from this point, the code is the same as the equivalent functions in the vague_date.php file
  
  /**
   * List of regex strings used to try to capture date ranges. The regex key should, naturally,
   * point to the regular expression. Start should point to the backreference for the string to
   * be parsed for the 'start' date, 'end' to the backreference of the string to be parsed
   * for the 'end' date. -1 means grab the text before the match, 1 means after, 0 means set the
   * value to empty. Types are not determined here. Should either 'start' or 'end' contain
   * the string '...', this will be interpreted as one-ended range.
   */
  private static function dateRangeStrings() {
    return Array(
      array(
          // date to date or date - date
          'regex' => '/(?P<sep> to | - )/i',
          'start' => -1,
          'end' => 1
      ),
      array(
        // dd/mm/yy(yy)-dd/mm/yy(yy) or dd.mm.yy(yy)-dd.mm.yy(yy)
        'regex' => '/^\d{2}[\/\.]\d{2}[\/\.]\d{2}(\d{2})?(?P<sep>-)\d{2}[\/\.]\d{2}[\/\.]\d{2}(\d{2})?$/',
        'start' => -1,
        'end' => 1
      ),
      array(
        // mm/yy(yy)-mm/yy(yy) or mm.yy(yy)-mm.yy(yy)
        'regex' => '/^\d{2}[\/\.]\d{2}(\d{2})?(?P<sep>-)\d{2}[\/\.]\d{2}(\d{2})?$/',
        'start' => -1,
        'end' => 1
      ),
      array(
        // yyyy-yyyy
        'regex' => '/^\d{4}(?P<sep>-)\d{4}$/',
        'start' => -1,
        'end' => 1
      ),
      array(
        // century to century
        'regex' => '/^\d{2}c-\d{2}c?$/',
        'start' => -1,
        'end' => 1
      ),
      array(
          'regex' => '/^(?P<sep>to|pre|before[\.]?)/i',
          'start' => 0,
          'end' => 1
      ),
      array(
          'regex' => '/(?P<sep>from|after)/i',
          'start' => 1,
          'end' => 0
      ),
      array(
          'regex' => '/(?P<sep>-)$/',
          'start' => -1,
          'end' => 0
      ),
      array(
          'regex' => '/^(?P<sep>-)/',
          'start' => 0,
          'end' => 1
      ),
    );
  }

  /**
   * Array of formats used to parse a string looking for a single day with the strptime()
   * function - see http://uk2.php.net/manual/en/function.strptime.php
   */
  private static function singleDayFormats() { return Array(
    '%Y-%m-%d', // ISO 8601 date format 1997-10-12
    '%d/%m/%Y', // 12/10/1997
    '%d/%m/%y', // 12/10/97
    '%d.%m.%Y', // 12.10.1997
    '%d.%m.%y', // 12.10.97
    '%A %e %B %Y', // Monday 12 October 1997
    '%a %e %B %Y', // Mon 12 October 1997
    '%A %e %b %Y', // Monday 12 Oct 1997
    '%a %e %b %Y', // Mon 12 Oct 1997
    '%A %e %B %y', // Monday 12 October 97
    '%a %e %B %y', // Mon 12 October 97
    '%A %e %b %y', // Monday 12 Oct 97
    '%a %e %b %y', // Mon 12 Oct 97
    '%A %e %B', // Monday 12 October
    '%a %e %B', // Mon 12 October
    '%A %e %b', // Monday 12 Oct
    '%a %e %b', // Mon 12 Oct
    '%e %B %Y', // 12 October 1997
    '%e %b %Y', // 12 Oct 1997
    '%e %B %y', // 12 October 97
    '%e %b %y', // 12 Oct 97
    '%m/%d/%y', // American date format
  );
  }

  /**
   * Array of formats used to parse a string looking for a single month in a year
   * with the strptime() function - see http://uk2.php.net/manual/en/function.strptime.php
   */
  private static function singleMonthInYearFormats() { return Array(
    '%Y-%m', // ISO 8601 format - truncated to month 1998-06
    '%m/%Y', // 06/1998
    '%m/%y', // 06/96
    '%B %Y', // June 1998
    '%b %Y', // Jun 1998
    '%B %y', // June 98
    '%b %y', // Jun 98
  );
  }

  private static function singleMonthFormats() { return Array(
    '%B', // October
    '%b', // Oct
  );
  }

  private static function singleYearFormats() { return Array(
    '%Y', // 1998
    '%y', // 98
  );
  }

  private static function seasonInYearFormats() {
    return array(
      '%K %Y', // Autumn 2008
      '%K %y', // Autumn 08
    );
  }

  private static function seasonFormats() {
    return array(
      '%K', //August
    );
  }

  private static function centuryFormats() {
    return array(
      '%C', //20C
    );
  }


  /**
   * Convert a vague date in the form of array(start, end, type) to a string.
   *
   * @param array $date
   *   Vague date in the form array(start_date, end_date, date_type), where start_date and end_date are DateTime
   *   objects or strings.
   *
   * @return string
   *   Vague date expressed as a string.
   */
  public static function vague_date_to_string(array $date) {
    $start = empty($date[0]) ? NULL : $date[0];
    $end = empty($date[1]) ? NULL : $date[1];
    $type = $date[2];
    if (is_string($start)) {
      $start = DateTime::createFromFormat('d/m/Y', $date[0]);
      if (!$start) {
        // If not in warehouse default date format, allow PHP standard processing.
        $start = new DateTime($date[0]);
      }
    }
    if (is_string($end)) {
      $end = DateTime::createFromFormat('d/m/Y', $date[1]);
      if (!$end) {
        // If not in warehouse default date format, allow PHP standard processing.
        $end = new DateTime($date[1]);
      }
    }
    self::validate($start, $end, $type);
    switch ($type) {
      case 'D':
        return self::vague_date_to_day($start, $end);

      case 'DD':
        return self::vague_date_to_days($start, $end);

      case 'O':
        return self::vague_date_to_month_in_year($start, $end);

      case 'OO':
        return self::vague_date_to_months_in_year($start, $end);

      case 'P':
        return self::vague_date_to_season_in_year($start, $end);

      case 'Y':
        return self::vague_date_to_year($start, $end);

      case 'YY':
        return self::vague_date_to_years($start, $end);

      case 'Y-':
        return self::vague_date_to_year_from($start, $end);

      case '-Y':
        return self::vague_date_to_year_to($start, $end);

      case 'M':
        return self::vague_date_to_month($start, $end);

      case 'S':
        return self::vague_date_to_season($start, $end);

      case 'U':
        return self::vague_date_to_unknown($start, $end);

      case 'C':
        return self::vague_date_to_century($start, $end);

      case 'CC':
        return self::vague_date_to_centuries($start, $end);

      case 'C-':
        return self::vague_date_to_century_from($start, $end);

      case '-C':
        return self::vague_date_to_century_to($start, $end);
    }
    throw new exception("Invalid date type $type");
  }

  /**
   * Convert a string into a vague date. Returns an array with 3 entries, the start date, end date and date type.
   */
  public static function string_to_vague_date($string) {
    $parseFormats = array_merge(
      self::singleDayFormats(),
      self::singleMonthInYearFormats(),
      self::singleMonthFormats(),
      self::seasonInYearFormats(),
      self::seasonFormats(),
      self::centuryFormats(),
      self::singleYearFormats()
    );
    // Our approach shall be to gradually pare down from the most complex possible
    // dates to the simplest, and match as fast as possible to try to grab the most
    // information. First we consider the potential ways that a range may be
    // represented.

    $range = false;
    $startDate = false;
    $endDate = false;
    $matched = false;
    foreach (self::dateRangeStrings() as $a) {
      if (preg_match($a['regex'], $string, $regs) != false) {
        switch ($a['start']) {
        case -1:
          $start = trim(substr($string,0,strpos($string, $regs['sep'])));
          break;
        case 1:
          $start = trim(substr($string, strpos($string, $regs['sep']) + strlen($regs['sep'])));
          break;
        default:
          $start = false;
        }
        switch ($a['end']){
        case -1:
          $end = trim(substr($string,0,strpos($string, $regs['sep'])));
          break;
        case 1:
          $end = trim(substr($string, strpos($string, $regs['sep']) + strlen($regs['sep'])));
          break;
        default:
          $end = false;
        }
        $range = true;
        break;
      }
    }

    if (!$range) {
      $a = self::parseSingleDate($string, $parseFormats);
      if ($a) {
        $startDate = $endDate = $a;
        $matched = true;
      }
    } else {
      if ($start) {
        $a = self::parseSingleDate($start, $parseFormats);
        if ($a !== null) {
          $startDate = $a;
          $matched = true;
        }
      }
      if ($end) {
        $a = self::parseSingleDate($end, $parseFormats);
        if ($a !== null) {
          $endDate = $a;
          $matched = true;
        }
      }
      if ($matched) {
        if ($start && !$end) {
          $endDate = $startDate;
        } else if ($end && !$start) {
          $startDate = $endDate;
        }
      }
    }
    if (!$matched) {
      if (trim($string)=='U' || trim($string)==Kohana::lang('dates.unknown'))
        return array(null, null, 'U');
      else {
        return false;
      }
    }
    // Okay, now we try to determine the type - we look mostly at $endDate because
    // this is more likely to contain more info e.g. 15 - 18 August 2008
    // Seasons are parsed specially - i.e. we'll have seen the word 'Summer'
    // or the like.
    try {

      if ($endDate->tm_season !== null){
        //We're a season. That means we could be P (if we have a year) or
        //S (if we don't).
        if ($endDate->tm_year !== null){
          // We're a P
          $vagueDate = array(
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'P'
          );
          return $vagueDate;
        } else {
          // No year, so we're an S
          $vagueDate = array(
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'S'
          );
          return $vagueDate;
        }
      }
      // Do we have day precision?

      if ($endDate->tm_mday !== null) {
        if (!$range) {
          // We're a D
          $vagueDate = array(
            $endDate->getIsoDate(),
            $endDate->getIsoDate(),
            'D'
          );
          return $vagueDate;
        } else {
          // Type is DD. We copy across any data not set in the
          // start date.
          if ($startDate->getPrecision() == $endDate->getPrecision()){
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'DD'
            );
          } else {
            // Less precision in the start date -
            // try and massage them together
            return false;
          }
          return $vagueDate;

        }
      }
      /* Right, scratch the possibility of days. Months are next - there are
       * various possibilities with months,
       * because months don't necessarily have years. Months can be:
       * Type 'O' - month, year, !range
       * Type 'OO' - month, year, range
       * Type 'M' - month, !range
       *
       */
      if ($endDate->tm_mon !== null) {
        if (!$range) {
          // Either a month in a year or just a month
          if ($endDate->tm_year !== null) {
            // Then we have a month in a year- type O
            $vagueDate = array(
              $endDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'O'
            );
            return $vagueDate;
          } else {
            // Month without a year - type M
            $vagueDate = array(
              $endDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'M'
            );
            return $vagueDate;
          }
        } else {
          // We do have a range, OO
          if ($endDate->tm_year !== null){
            // We have a year - so this is OO
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'OO'
            );
            return $vagueDate;
          } else {
            // MM is not an allowed type
            // TODO think about this
            return false;
          }
        }
      }
      /*
       * No day, no month. We're some kind of year representation - Y,YY,Y- or
       * -Y, C, CC, C- or -C.
       */

      // Are we a century?
      if ($endDate->tm_century !== null){
        // CC, C, C- or -C
        if (!$range){
          // Type C
          $vagueDate = array(
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'C'
          );
          return $vagueDate;
        } else {
          if ($start && $end) {
            // We're CC
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'CC'
            );
            return $vagueDate;
          } else if ($start && !$end) {
            // We're C-
            $vagueDate = array(
              $endDate->getImpreciseDateStart(),
              null,
              'C-'
            );
            return $vagueDate;
          } else if ($end && !$start) {
            // We're -C
            $vagueDate = array(
              null,
              $endDate->getImpreciseDateEnd(),
              '-C'
            );
            return $vagueDate;
          }
        }
      }

      //Okay, we're one of the year representations.
      if ($endDate->tm_year !== null){
        if (!$range){
          // We're Y
          $vagueDate = array(
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'Y'
          );
          return $vagueDate;
        } else {
          if ($start && $end){
            // We're YY
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'YY'
            );
            return $vagueDate;
          } else if ($start && !$end){
            // We're Y-
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              null,
              'Y-'
            );
            return $vagueDate;
          } else if ($end && !$start){
            // We're -Y
            $vagueDate = array(
              null,
              $endDate->getImpreciseDateEnd(),
              '-Y'
            );
            return $vagueDate;
          }
        }
      } else {
        return false;
      }
    } catch (Exception $e) {
      return false;
    }
  }

  /**
   * Parses a single date from a string.
   */
  protected static function parseSingleDate($string, $parseFormats){
    $parsedDate = null;

    foreach ($parseFormats as $a){
      $dp = new DateParser($a);

      if ($dp->strptime($string)){
        $parsedDate = $dp;
        break;
      }
    }

    return $parsedDate;
  }

  /**
   * Convert a vague date to a string representing a fixed date.
   */
  protected static function vague_date_to_day($start, $end)
  {
    self::check(self::are_dates_equal($start, $end), 'Day vague dates should have the same date for the start and end of the date range');
    return $start->format('d/m/Y');
  }

  /**
   * Convert a vague date to a string representing a range of days.
   */
  protected static function vague_date_to_days($start, $end)
  {
    self::check(self::is_first_date_first_or_equal($start, $end), 'Day ranges should be presented in vague dates in the correct sequence. Start was %s, end was %s.', $start, $end);
    return 	$start->format('d/m/Y').
      Kohana::lang('dates.range_separator').
      $end->format('d/m/Y');
  }

  /**
   * Convert a vague date to a string representing a fixed month.
   */
  protected static function vague_date_to_month_in_year($start, $end)
  {
    self::check(self::is_month_start($start) && self::is_month_end($end) && self::is_same_month($start, $end),
      'Month dates should be represented by the first day and last day of the same month. Start was %s, end was %s.', $start, $end);
    return $start->format(Kohana::lang('dates.format_m_y'));
  }

  /**
   * Convert a vague date to a string representing a range of months.
   */
  protected static function vague_date_to_months_in_year($start, $end)
  {
    self::check(self::is_month_start($start) && self::is_month_end($end) && self::is_first_date_first($start, $end),
      'Month ranges should be represented by the first day of the first month and last day of the last month. Start was %s, end was %s.', $start, $end);
    return 	$start->format(Kohana::lang('dates.format_m_y')).
      Kohana::lang('dates.range_separator').
      $end->format(Kohana::lang('dates.format_m_y'));
  }

  /*
   * Convert a vague date to a string representing a season in a given year
   */
  protected static function vague_date_to_season_in_year($start, $end)
  {
    return self::convert_to_season_string($start, $end).' '.$end->format('Y');
  }

  /**
   * Convert a vague date to a string representing a year
   */
  protected static function vague_date_to_year($start, $end)
  {
    self::check(self::is_year_start($start) && self::is_year_end($end) && self::is_same_year($start, $end),
      'Years should be represented by the first day and last day of the same year. Start was %s, end was %s.', $start, $end);
    return $start->format('Y');
  }

  /**
   * Convert a vague date to a string representing a range of years
   */
  protected static function vague_date_to_years($start, $end)
  {
    self::check(self::is_year_start($start) && self::is_year_end($end) && self::is_first_date_first($start, $end),
      'Year ranges should be represented by the first day of the first year to the last day of the last year. Start was %s, end was %s.', $start, $end);
    return $start->format('Y').Kohana::lang('dates.range_separator').$end->format('Y');
  }

  /**
   * Convert a vague date to a string representing any date after a given year
   */
  protected static function vague_date_to_year_from($start, $end)
  {
    self::check(self::is_year_start($start) && $end===null,
      'From year date should be represented by just the first day of the first year.');
    return sprintf(Kohana::lang('dates.from_date'), $start->format('Y'));
  }

  /**
   * Convert a vague date to a string representing any date up to and including a given year
   */
  protected static function vague_date_to_year_to($start, $end)
  {
    self::check($start===null && self::is_year_end($end),
      "To year date should be represented by just the last day of the last year. Start was %s and end was %s.", $start, $end);
    return sprintf(Kohana::lang('dates.to_date'), $end->format('Y'));
  }

  /**
   * Convert a vague date to a string representing a month in an unknown year
   */
  protected static function vague_date_to_month($start, $end)
  {
    self::check(self::is_month_start($start) && self::is_month_end($end) && self::is_same_month($start, $end),
      'Month dates should be represented by the start and end of the month.');
    return $start->format('F');
  }

  /*
   * Convert a vague date to a string representing a season in an unknown year
   */
  protected static function vague_date_to_season($start, $end)
  {
    return self::convert_to_season_string($start, $end);
  }

  /*
   * Convert a vague date to a string representing an unknown date
   */
  protected static function vague_date_to_unknown($start, $end)
  {
    self::check($start===null && $end===null,
      'Unknown dates should not have a start or end specified');
    return Kohana::lang('dates.unknown');
  }

  /*
   * Convert a vague date to a string representing a century
   */
  protected static function vague_date_to_century($start, $end)
  {
    self::check(self::is_century_start($start) && self::is_century_end($end) && self::is_same_century($start, $end),
      'Century dates should be represented by the first day and the last day of the century');
    return sprintf(Kohana::lang('dates.century', ($start->format('Y')-1)/100+1));
  }

  /*
   * Convert a vague date to a string representing a century
   */
  protected static function vague_date_to_centuries($start, $end)
  {
    self::check(self::is_century_start($start) && self::is_century_end($end) && self::is_first_date_first($start, $end),
      'Century ranges should be represented by the first day of the first century and the last day of the last century');
    return 	sprintf(Kohana::lang('dates.century', ($start->format('Y')-1)/100+1)).
      Kohana::lang('dates.range_separator').
      sprintf(Kohana::lang('dates.century', ($end->format('Y')-1)/100+1));
  }

  /*
   * Convert a vague date to a string representing a date during or after a specified century
   */
  protected static function vague_date_to_century_from($start, $end)
  {
    self::check(self::is_century_start($start) && $end===null,
      'From Century dates should be represented by the first day of the century only');
    return sprintf(Kohana::lang('dates.from_date'), sprintf(Kohana::lang('dates.century', ($start->format('Y')-1)/100+1)));
  }

  /*
   * Convert a vague date to a string representing a date before or during a specified century
   */
  protected static function vague_date_to_century_to($start, $end)
  {
    self::check($start===null && self::is_century_end($end),
      'To Century dates should be represented by the last day of the century only');
    return sprintf(Kohana::lang('dates.to_date'), sprintf(Kohana::lang('dates.century', ($end->format('Y')-1)/100+1)));
  }


  /**
   * Returns true if the supplied date is the first day of the month
   */
  protected static function is_month_start($date)
  {
    return ($date->format('j')==1);
  }

  /**
   * Returns true if the supplied date is the last day of the month
   */
  protected static function is_month_end($date)
  {
    // format t gives us the last day of the given date's month
    return ($date->format('j')==$date->format('t'));
  }

  /**
   * Returns true if the supplied dates are the same. Early versions of PHP5.2 do not have valid binary comparison functions
   */
  protected static function are_dates_equal($date1, $date2)
  {
    return (!strcmp($date1->format('Ymd'),$date2->format('Ymd')));
  }

  /**
   * Returns true if the first supplied date is before second. Early versions of PHP5.2 do not have valid binary comparison functions
   */
  protected static function is_first_date_first($date1, $date2)
  {
    return (strcmp($date1->format('Ymd'),$date2->format('Ymd'))<0);
  }

  /**
   * Returns true if the first supplied date is before second or they are the same. Early versions of PHP5.2 do not have valid
   * binary comparison functions
   */
  protected static function is_first_date_first_or_equal($date1, $date2)
  {
    return $date1==$date2 || (strcmp($date1->format('Ymd'),$date2->format('Ymd'))<0);
  }

  /**
   * Returns true if the supplied dates are in the same month
   */
  protected static function is_same_month($date1, $date2)
  {
    return ($date1->format('m')==$date2->format('m'));
  }

  /**
   * Returns true if the supplied date is the first day of the year
   */
  protected static function is_year_start($date)
  {
    return ($date->format('j')==1 && $date->format('m')==1);
  }

  /**
   * Returns true if the supplied date is the last day of the year
   */
  protected static function is_year_end($date)
  {
    return ($date->format('j')==31 && $date->format('m')==12);
  }

  /**
   * Returns true if the supplied dates are in the same year
   */
  protected static function is_same_year($date1, $date2)
  {
    return ($date1->format('Y')==$date2->format('Y'));
  }

  /**
   * Returns true if the supplied date is the first day of the century (starts in year nn01!)
   */
  protected static function is_century_start($date)
  {
    return ($date->format('j')==1 && $date->format('m')==1 && $date->format('y')==1);
  }

  /**
   * Returns true if the supplied date is the last day of the century
   */
  protected static function is_century_end($date)
  {
    return ($date->format('j')==31 && $date->format('m')==12 && $date->format('y')==0);
  }

  /**
   * Returns true if the supplied dates are in the same century
   */
  protected static function is_same_century($date1, $date2)
  {
    return floor(($date1->format('Y')-1)/100)==floor(($date2->format('Y')-1)/100);
  }

  /**
   * Retrieve the string that describes a season (spring, summer, autumn, winter)
   * for a start and end date.
   */
  protected static function convert_to_season_string($start, $end)
  {
    self::check(self::is_month_start($start) && self::is_month_end($end),
      'Seasons should be represented by the start of the first month of the season, to the end of the last month.');
    // ensure the season spans 3 months.
    self::check( ($start->format('Y')*12 + $start->format('m') + 2)
      ==
      ($end->format('Y')*12 + $end->format('m')),
        'Seasons should be 3 months long');
    switch ($start->format('m'))
    {
    case 3:
      return Kohana::lang('dates.seasons.spring');
    case 6:
      return Kohana::lang('dates.seasons.summer');
    case 9:
      return Kohana::lang('dates.seasons.autumn');
    case 12:
      return Kohana::lang('dates.seasons.winter');
    default:
      throw new Exception('Season date does not start on the month a known season starts on.');
    }
  }


  /**
   * Ensure a vague date array is well-formed.
   */
  protected static function validate($start, $end, $type)
  {

  }

  /**
   * Tests that a check passed, and if not throws an exception containing the message. Replacements
   * in the message can be supplied as additional string parameters, with %s used in the message. The
   * replacements can also be null or datetime objects which are then converted to strings.
   */
  protected static function check($pass, $message)
  {
    if (!$pass) {
      $args = func_get_args();
      // any args after the message are string format inputs for the message
      unset($args[0]);
      unset($args[1]);
      $inputs = array();
      foreach ($args as $arg) {
        kohana::log('debug', 'arg '.gettype($arg));
        if (gettype($arg)=='object')
          $inputs[] = $arg->format('d/m/Y');
        elseif (gettype($arg)==='NULL')
          $inputs[] = 'null';
        else
          $inputs[] = $arg;
      }
      throw new Exception(vsprintf($message, $inputs));
    }
  }
}
?>
</body>
</html>