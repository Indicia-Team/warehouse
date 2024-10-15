<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Post a comment</title>
  <link href="vendor-other/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
  <link href="vendor-other/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet" type="text/css" />
  <link href="modules/rest_api/media/css/rest_api.css" rel="stylesheet" type="text/css" />
</head>
<body>
  <div class="container">

<?php
if ((empty($_GET['user_id']) && empty($_GET['email_address'])) || empty($_GET['occurrence_id']) || empty($_GET['auth'])) {
  echo '<p>Invalid link</p>';
}
else {
  require_once 'client_helpers/data_entry_helper.php';
  // Request the saved authorisation number
  // from the database for this particular occurrence.
  $auth = occcurrenceCommentQuickReplyPage::getAuth('Indicia');
  $tokenDbData = data_entry_helper::get_population_data(array(
    'table' => 'comment_quick_reply_page_auth',
    'caching' => FALSE,
    'extraParams' => $auth['read'] + [
      'occurrence_id' => $_GET['occurrence_id'],
      'token' => $_GET['auth'],
    ],
    'nocache' => TRUE,
  ));
  if (count($tokenDbData) !== 1) {
    echo '<p>Invalid link</p>';
  }
  else {
    $tokenDetails = $tokenDbData[0];
    $configuration = occcurrenceCommentQuickReplyPage::getPageConfiguration();
    // If there is a POST, then the user has saved, so process this.
    if (!empty($_POST)) {
      $response = occcurrenceCommentQuickReplyPage::buildSubmission($configuration, $tokenDetails['id']);
      $decodedResponse = json_decode($response);
      if (isset($decodedResponse->error)) {
        echo 'Error occurred';
        ?><h2>A problem seems to have occurred, the response from the server is as follows:</h2><?php
        echo print_r($response, TRUE);
        ?><br><form><input type=button value="Return To Record Comments Screen" onClick="window.location = document.URL;"></form><?php
      }
      else {
        ?>
        <div class="container">
        <h2>Thank You</h2>
        <p>Your comment has been saved successfully.</p>
        <p>You posted the following comment:</p>
        <blockquote><?php
        $commentText = $_POST['comment-text'];
        echo $commentText;
        ?><blockquote>
        </div>
        <br>
        <?php
      }
    }
    else {
      // The authorisation number must exist and
      // also match the one in the database if we are going to continue.
      if (empty($tokenDetails['token']) || $_GET['auth'] !== $tokenDetails['token']) {
        var_export($tokenDetails);
        echo '<p>Authorisation failed. It may be that the link has already been used.</p>';
      }
      else {
        // Get the record details if we are going to display the page and
        // then pass this to the functions.
        $auth = occcurrenceCommentQuickReplyPage::getAuth($configuration['privateKey']);
        $occurrenceDetails = data_entry_helper::get_population_data(array(
          'table' => 'occurrence',
          'caching' => FALSE,
          'extraParams' => $auth['read'] + array('view' => 'cache', 'id' => $_GET['occurrence_id']),
        ));
        if (count($occurrenceDetails) === 0) {
          echo '<em style="color:red">The record associated with this link cannot be found.</em><br>';
        }
        else {
          $thisRecord = $occurrenceDetails[0];
          echo '<h1>Record details and comments</h1>';
          if ($thisRecord['confidential'] === 't') {
            echo '<em style="color:red">This record is marked as confidential so the details are unavailable. You can still comment using the form below.</em><br>';
          }
          else {
            if ($thisRecord['query'] !== 'Q') {
              echo '<em style="color:red">This record no longer has a queried status and therefore doesn\'t require you to make a comment at this present time.</em><br>';
            }
            echo occcurrenceCommentQuickReplyPage::displayOccurrenceDetails($configuration, $thisRecord);
            echo occcurrenceCommentQuickReplyPage::displayExistingOccurrenceComments($configuration);
          }
          echo occcurrenceCommentQuickReplyPage::commentForm($thisRecord['query']);
        }
      }
    }
  }
}

/**
 * Allow a reply to an occurrence comment after user receives an email.
 */
class OcccurrenceCommentQuickReplyPage {

  /**
   * Store various information.
   *
   * (such as paths to things like CSS files in re-usable variables).
   */
  public static function getPageConfiguration() {
    $privateKey = 'Indicia';
    $auth = self::getAuth($privateKey);
    if (!empty($_GET['user_id'])) {
      $userDetails = data_entry_helper::get_population_data(array(
        'table' => 'user',
        'extraParams' => $auth['read'] + array('id' => $_GET['user_id']),
      ));
      $configuration['username'] = $userDetails[0]['username'];
    }
    $configuration['privateKey'] = $privateKey;
    $configuration['dataEntryHelperPath'] = 'client_helpers/data_entry_helper.php';
    if (!empty($_GET['person_name'])) {
      $configuration['person_name'] = $_GET['person_name'];
    }
    if (!empty($_GET['email_address'])) {
      $configuration['email_address'] = $_GET['email_address'];
    }
    return $configuration;
  }

  /**
   * Display the details of the occurrence.
   */
  public static function displayOccurrenceDetails($configuration, $thisRecord) {
    require_once $configuration['dataEntryHelperPath'];
    ?>
    <fieldset class="fieldset-auto-width"><legend>Details</legend>
    <?php
    echo "<p>Species: " . $thisRecord['taxon'] . "</p>";
    $vagueDate = self::vagueDateToString(array(
      $thisRecord['date_start'],
      $thisRecord['date_end'],
      $thisRecord['date_type'],
    ));
    echo '<p>Date: ' . $vagueDate . "</p>";
    // Needs blurred output as don't know user's rights.
    if (!empty($thisRecord['public_entered_sref'])) {
      $srefData = $thisRecord['public_entered_sref'] . ' (' . $thisRecord['entered_sref_system'] . ')';
    }
    else {
      // Note: Get population data not returning output_sref_system at moment,
      // hence added check for this in case this changes.
      if (!empty($thisRecord['output_sref_system'])) {
        $srefData = "$thisRecord[output_sref] ($thisRecord[output_sref_system])";
      }
      else {
        $srefData = $thisRecord['output_sref'];
      }
    }
    echo "<p>Spatial reference: " . $srefData . "</p>";
    echo "</fieldset>\n";
  }

  /**
   * Display details of the occurrence from the database.
   */
  public static function displayExistingOccurrenceComments($configuration) {
    $configuration = self::getPageConfiguration();
    $auth = self::getAuth($configuration['privateKey']);
    $r = '<div>';
    $comments = data_entry_helper::get_population_data(array(
      'table' => 'occurrence_comment',
      'caching' => FALSE,
      'extraParams' => $auth['read'] + array(
        'occurrence_id' => $_GET['occurrence_id'],
        'sortdir' => 'DESC',
        'orderby' => 'updated_on',
      ),
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
        // Output the comment time.
        // Skip if in future (i.e. server/client date settings don't match).
        if ($commentTime < time()) {
          $r .= self::ago($commentTime);
        }
        $r .= '</div>';
        $c = str_replace("\n", '<br/>', $comment['comment']);
        $r .= "<div>$c</div>";
        $r .= '</div><br>';
      }
    }
    $r .= '</div>';
    echo '<div class="detail-panel" id="detail-panel-comments"><h3>Comments</h3>' . $r . '</div>';
  }

  /**
   * Returns the HTML for the comment form,
   *
   * @param string $recordQueriedFlag
   *   Query status flag for the record.
   *
   * @return string
   *   HTML for the form.
   */
  public static function commentForm($recordQueriedFlag) {
    // Only allow commenting for queried records.
    if ($recordQueriedFlag === 'Q') {
      return <<<HTML
<form id="comment-form" method="POST" >
  <fieldset>
    <div class="form-group">
      <label for="comment-text">Add new comment</label>
      <textarea id="comment-text" name="comment-text" class="form-control"></textarea>
    </div>
    <div class="alert alert-info">Comments will be added to the record on iRecord, and are publicly visible - please do
      not include personal information such as addresses or phone numbers.</div>
    <input type="button" class="btn btn-primary" value="Save" onclick="
      if (document.getElementById('comment-text').value) {
        var r = confirm('Are you sure you want to save the comment?');
        if (r == true) {
          document.getElementById('comment-form').submit();
        }
      } else {
        alert('Please enter a comment before saving');
      }">
  </fieldset>
</form>
HTML;
    }
    return '';
  }

  /**
   * Get an authentication.
   */
  public static function getAuth($password) {
    if (!empty($_GET['user_id'])) {
      $userId = $_GET['user_id'];
    }
    else {
      $userId = 1;
    }
    $website_id = 0 - $userId;
    $postargs = "website_id=$website_id";
    $response = self::httpPost(self::getWarehouseUrl() . 'index.php/services/security/get_read_write_nonces', $postargs);
    $nonces = json_decode($response, TRUE);
    return array(
      'read' => array(
        'auth_token' => sha1("$nonces[read]:" . $password),
        'nonce' => $nonces['read'],
      ),
      'write' => array(
        'auth_token' => sha1("$nonces[write]:" . $password),
        'nonce' => $nonces['write'],
      ),
    );
  }

  /**
   * Need the warehouse url for various functions.
   */
  private static function getWarehouseUrl() {
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = rtrim($_SERVER['HTTP_HOST'], '/');
    return $protocol . $host . '/' . trim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/') . '/';
  }

  /**
   * Allow us to POST a submission.
   */
  private static function httpPost($url, $postargs = NULL) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt($session, CURLOPT_URL, $url);
    if ($postargs !== NULL) {
      curl_setopt($session, CURLOPT_POST, TRUE);
      curl_setopt($session, CURLOPT_POSTFIELDS, $postargs);
    }
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    // Do the POST.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    // Check for an error, or check if the http response was not OK.
    if (curl_errno($session) || $httpCode !== 200) {
      if (curl_errno($session)) {
        throw new exception(curl_errno($session) . ' - ' . curl_error($session));
      }
      else {
        throw new exception($httpCode . ' - ' . $response);
      }
    }
    curl_close($session);
    return $response;
  }

  /**
   * Convert a timestamp into readable format for use on a comment list.
   *
   * @param int $timestamp
   *   The date time to convert.
   *
   * @return string
   *   The output string.
   */
  public static function ago($timestamp) {
    $difference = time() - $timestamp;
    $periods = [
      lang::get('{1} second ago'),
      lang::get('{1} minute ago'),
      lang::get('{1} hour ago'),
      lang::get('Yesterday'),
      lang::get('{1} week ago'),
      lang::get('{1} month ago'),
      lang::get('{1} year ago'),
      lang::get('{1} decade ago'),
    ];
    $periodsPlural = [
      lang::get('{1} seconds ago'),
      lang::get('{1} minutes ago'),
      lang::get('{1} hours ago'),
      lang::get('{1} days ago'),
      lang::get('{1} weeks ago'),
      lang::get('{1} months ago'),
      lang::get('{1} years ago'),
      lang::get('{1} decades ago'),
    ];
    $lengths = ['60', '60', '24', '7', '4.35', '12', '10'];

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

  /**
   * Create data structure to submit when user saves.
   */
  public static function buildSubmission($configuration, $authIdToDelete) {
    $submission = array();
    $submission['id'] = 'occurrence_comment';
    if (!empty($_GET['user_id'])) {
      $submission['fields']['created_by_id']['value'] = $_GET['user_id'];
      $submission['fields']['updated_by_id']['value'] = $_GET['user_id'];
    }
    else {
      // If we don't know who the updater is, just use the admin account.
      $submission['fields']['created_by_id']['value'] = 1;
      $submission['fields']['updated_by_id']['value'] = 1;
    }
    $submission['fields']['occurrence_id']['value'] = $_GET['occurrence_id'];
    $submission['fields']['comment']['value'] = $_POST['comment-text'];
    if (!empty($configuration['username'])) {
      $submission['fields']['person_name']['value'] = $configuration['username'];
    }
    // If a specific person_name is supplied,
    // then always use this (e.g. for anonymous users).
    if (!empty($configuration['person_name'])) {
      $submission['fields']['person_name']['value'] = $configuration['person_name'];
    }
    if (!empty($configuration['email_address'])) {
      $submission['fields']['email_address']['value'] = $configuration['email_address'];
    }
    // Add main submission to the list.
    $submission['submission_list']['entries'][0] = $submission;
    // Delete the authorisation from the database as well during submission.
    $submission['submission_list']['entries'][1]['id'] = 'comment_quick_reply_page_auth';
    $submission['submission_list']['entries'][1]['fields']['id']['value'] = $authIdToDelete;
    $submission['submission_list']['entries'][1]['fields']['deleted']['value'] = 't';
    $response = self::doSubmission('save', $submission);
    return $response;
  }

  /**
   * Take the submission structure and give it to data services.
   */
  private static function doSubmission($entity, $submission = NULL, $writeTokens = NULL) {
    $configuration = self::getPageConfiguration();
    $auth = self::getAuth($configuration['privateKey']);
    $writeTokens = $auth['write'];
    $request = self::getWarehouseUrl() . "index.php/services/data/$entity";
    $postargs = 'submission=' . urlencode(json_encode($submission));
    // Passthrough the authentication tokens as POST data.
    // Use parameter writeTokens.
    foreach ($writeTokens as $token => $value) {
      $postargs .= '&' . $token . '=' . ($value === TRUE ? 'true' : ($value === FALSE ? 'false' : $value));
    }
    if (!empty($_GET['user_id'])) {
      $postargs .= '&user_id=' . $_GET['user_id'];
    }
    $response = self::httpPost($request, $postargs);
    return $response;
  }

  /**
   * List of regex strings used to try to capture date ranges.
   */
  private static function dateRangeStrings() {
    return array(
      array(
        // Date to date or date - date.
        'regex' => '/(?P<sep> to | - )/i',
        'start' => -1,
        'end' => 1,
      ),
      array(
        // dd/mm/yy(yy)-dd/mm/yy(yy) or dd.mm.yy(yy)-dd.mm.yy(yy)
        'regex' => '/^\d{2}[\/\.]\d{2}[\/\.]\d{2}(\d{2})?(?P<sep>-)\d{2}[\/\.]\d{2}[\/\.]\d{2}(\d{2})?$/',
        'start' => -1,
        'end' => 1,
      ),
      array(
        // mm/yy(yy)-mm/yy(yy) or mm.yy(yy)-mm.yy(yy)
        'regex' => '/^\d{2}[\/\.]\d{2}(\d{2})?(?P<sep>-)\d{2}[\/\.]\d{2}(\d{2})?$/',
        'start' => -1,
        'end' => 1,
      ),
      array(
        // yyyy-yyyy.
        'regex' => '/^\d{4}(?P<sep>-)\d{4}$/',
        'start' => -1,
        'end' => 1,
      ),
      array(
        // Century to century.
        'regex' => '/^\d{2}c-\d{2}c?$/',
        'start' => -1,
        'end' => 1,
      ),
      array(
        'regex' => '/^(?P<sep>to|pre|before[\.]?)/i',
        'start' => 0,
        'end' => 1,
      ),
      array(
        'regex' => '/(?P<sep>from|after)/i',
        'start' => 1,
        'end' => 0,
      ),
      array(
        'regex' => '/(?P<sep>-)$/',
        'start' => -1,
        'end' => 0,
      ),
      array(
        'regex' => '/^(?P<sep>-)/',
        'start' => 0,
        'end' => 1,
      ),
    );
  }

  /**
   * Array of formats used to parse a string looking for a single day.
   *
   * Uses the strptime() function.
   *
   * See http://uk2.php.net/manual/en/function.strptime.php.
   */
  private static function singleDayFormats() {
    return array(
      '%Y-%m-%d',
      '%d/%m/%Y',
      '%d/%m/%y',
      '%d.%m.%Y',
      '%d.%m.%y',
      '%A %e %B %Y',
      '%a %e %B %Y',
      '%A %e %b %Y',
      '%a %e %b %Y',
      '%A %e %B %y',
      '%a %e %B %y',
      '%A %e %b %y',
      '%a %e %b %y',
      '%A %e %B',
      '%a %e %B',
      '%A %e %b',
      '%a %e %b',
      '%e %B %Y',
      '%e %b %Y',
      '%e %B %y',
      '%e %b %y',
      '%m/%d/%y',
    );
  }

  /**
   * Used to parse a string looking for a single month in a year.
   *
   * Uses the strptime() function
   * See http://uk2.php.net/manual/en/function.strptime.php.
   */
  private static function singleMonthInYearFormats() {
    return array(
      '%Y-%m',
      '%m/%Y',
      '%m/%y',
      '%B %Y',
      '%b %Y',
      '%B %y',
      '%b %y',
    );
  }

  /**
   * Format single month.
   */
  private static function singleMonthFormats() {
    return array(
      '%B',
      '%b',
    );
  }

  /**
   * Format single year.
   */
  private static function singleYearFormats() {
    return array(
      '%Y',
      '%y',
    );
  }

  /**
   * Format year with season.
   */
  private static function seasonInYearFormats() {
    return array(
      '%K %Y',
      '%K %y',
    );
  }

  /**
   * Format season.
   */
  private static function seasonFormats() {
    return array(
      '%K',
    );
  }

  /**
   * Format century.
   */
  private static function centuryFormats() {
    return array(
      '%C',
    );
  }

  /**
   * Convert a vague date in the form of array(start, end, type) to a string.
   *
   * @param array $date
   *   Vague date in the form array(start_date, end_date, date_type),
   *   where start_date and end_date are DateTime objects or strings.
   *
   * @return string
   *   Vague date expressed as a string.
   */
  public static function vagueDateToString(array $date) {
    $start = empty($date[0]) ? NULL : $date[0];
    $end = empty($date[1]) ? NULL : $date[1];
    $type = $date[2];
    if (is_string($start)) {
      $start = DateTime::createFromFormat('d/m/Y', $date[0]);
      if (!$start) {
        // If not in warehouse default date format,
        // allow PHP standard processing.
        $start = new DateTime($date[0]);
      }
    }
    if (is_string($end)) {
      $end = DateTime::createFromFormat('d/m/Y', $date[1]);
      if (!$end) {
        // If not in warehouse default date format,
        // allow PHP standard processing.
        $end = new DateTime($date[1]);
      }
    }
    self::validate($start, $end, $type);
    switch ($type) {
      case 'D':
        return self::vagueDateToDay($start, $end);

      case 'DD':
        return self::vagueDateToDays($start, $end);

      case 'O':
        return self::vagueDateToMonthInYear($start, $end);

      case 'OO':
        return self::vagueDateToMonthsInYear($start, $end);

      case 'P':
        return self::vagueDateToSeasonInYear($start, $end);

      case 'Y':
        return self::vagueDateToYear($start, $end);

      case 'YY':
        return self::vagueDateToYears($start, $end);

      case 'Y-':
        return self::vagueDateToYearFrom($start, $end);

      case '-Y':
        return self::vagueDateToYearTo($start, $end);

      case 'M':
        return self::vagueDateToMonth($start, $end);

      case 'S':
        return self::vagueDateToSeason($start, $end);

      case 'U':
        return self::vagueDateToUnknown($start, $end);

      case 'C':
        return self::vagueDateToCentury($start, $end);

      case 'CC':
        return self::vagueDateToCenturies($start, $end);

      case 'C-':
        return self::vagueDateToCenturyFrom($start, $end);

      case '-C':
        return self::vagueDateToCenturyTo($start, $end);
    }
    throw new exception("Invalid date type $type");
  }

  /**
   * Convert a string into a vague date.
   *
   * Returns an array with 3 entries, the start date, end date and date type.
   */
  public static function stringToVagueDate($string) {
    $parseFormats = array_merge(
      self::singleDayFormats(),
      self::singleMonthInYearFormats(),
      self::singleMonthFormats(),
      self::seasonInYearFormats(),
      self::seasonFormats(),
      self::centuryFormats(),
      self::singleYearFormats()
    );

    /* Our approach shall be to gradually pare down
    from the most complex possible dates to the simplest, and match as fast as
    possible to try to grab the most information.
    First we consider the potential ways that a range may be represented. */
    $range = FALSE;
    $startDate = FALSE;
    $endDate = FALSE;
    $matched = FALSE;
    foreach (self::dateRangeStrings() as $a) {
      if (preg_match($a['regex'], $string, $regs) != FALSE) {
        switch ($a['start']) {
          case -1:
            $start = trim(substr($string, 0, strpos($string, $regs['sep'])));
            break;

          case 1:
            $start = trim(substr($string, strpos($string, $regs['sep']) + strlen($regs['sep'])));
            break;

          default:
            $start = FALSE;
        }
        switch ($a['end']) {
          case -1:
            $end = trim(substr($string, 0, strpos($string, $regs['sep'])));
            break;

          case 1:
            $end = trim(substr($string, strpos($string, $regs['sep']) + strlen($regs['sep'])));
            break;

          default:
            $end = FALSE;
        }
        $range = TRUE;
        break;
      }
    }

    if (!$range) {
      $a = self::parseSingleDate($string, $parseFormats);
      if ($a) {
        $startDate = $endDate = $a;
        $matched = TRUE;
      }
    }
    else {
      if ($start) {
        $a = self::parseSingleDate($start, $parseFormats);
        if ($a !== NULL) {
          $startDate = $a;
          $matched = TRUE;
        }
      }
      if ($end) {
        $a = self::parseSingleDate($end, $parseFormats);
        if ($a !== NULL) {
          $endDate = $a;
          $matched = TRUE;
        }
      }
      if ($matched) {
        if ($start && !$end) {
          $endDate = $startDate;
        }
        elseif ($end && !$start) {
          $startDate = $endDate;
        }
      }
    }
    if (!$matched) {
      if (trim($string) == 'U' || trim($string) == Kohana::lang('dates.unknown')) {
        return array(NULL, NULL, 'U');
      }
      else {
        return FALSE;
      }
    }

    try {

      if ($endDate->tm_season !== NULL) {
        // We're a season. That means we could be P (if we have a year) or
        // S (if we don't).
        if ($endDate->tm_year !== NULL) {
          // We're a P.
          $vagueDate = array(
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'P',
          );
          return $vagueDate;
        }
        else {
          // No year, so we're an S.
          $vagueDate = array(
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'S',
          );
          return $vagueDate;
        }
      }
      // Do we have day precision?
      if ($endDate->tm_mday !== NULL) {
        if (!$range) {
          // We're a D.
          $vagueDate = array(
            $endDate->getIsoDate(),
            $endDate->getIsoDate(),
            'D',
          );
          return $vagueDate;
        }
        else {
          // Type is DD. We copy across any data not set in the
          // start date.
          if ($startDate->getPrecision() == $endDate->getPrecision()) {
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'DD',
            );
          }
          else {
            // Less precision in the start date -
            // try and massage them together.
            return FALSE;
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
      if ($endDate->tm_mon !== NULL) {
        if (!$range) {
          // Either a month in a year or just a month.
          if ($endDate->tm_year !== NULL) {
            // Then we have a month in a year- type O.
            $vagueDate = array(
              $endDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'O',
            );
            return $vagueDate;
          }
          else {
            // Month without a year - type M.
            $vagueDate = array(
              $endDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'M',
            );
            return $vagueDate;
          }
        }
        else {
          // We do have a range, OO.
          if ($endDate->tm_year !== NULL) {
            // We have a year - so this is OO.
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'OO',
            );
            return $vagueDate;
          }
          else {
            // MM is not an allowed type
            // TODO think about this.
            return FALSE;
          }
        }
      }
      /*
       * No day, no month. We're some kind of year representation - Y,YY,Y- or
       * -Y, C, CC, C- or -C.
       */

      // Are we a century?
      if ($endDate->tm_century !== NULL) {
        // CC, C, C- or -C.
        if (!$range) {
          // Type C.
          $vagueDate = array(
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'C',
          );
          return $vagueDate;
        }
        else {
          if ($start && $end) {
            // We're CC.
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'CC',
            );
            return $vagueDate;
          }
          elseif ($start && !$end) {
            // We're C-.
            $vagueDate = array(
              $endDate->getImpreciseDateStart(),
              NULL,
              'C-',
            );
            return $vagueDate;
          }
          elseif ($end && !$start) {
            // We're -C.
            $vagueDate = array(
              NULL,
              $endDate->getImpreciseDateEnd(),
              '-C',
            );
            return $vagueDate;
          }
        }
      }

      // Okay, we're one of the year representations.
      if ($endDate->tm_year !== NULL) {
        if (!$range) {
          // We're Y.
          $vagueDate = array(
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'Y',
          );
          return $vagueDate;
        }
        else {
          if ($start && $end) {
            // We're YY.
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'YY',
            );
            return $vagueDate;
          }
          elseif ($start && !$end) {
            // We're Y-.
            $vagueDate = array(
              $startDate->getImpreciseDateStart(),
              NULL,
              'Y-',
            );
            return $vagueDate;
          }
          elseif ($end && !$start) {
            // We're -Y.
            $vagueDate = array(
              NULL,
              $endDate->getImpreciseDateEnd(),
              '-Y',
            );
            return $vagueDate;
          }
        }
      }
      else {
        return FALSE;
      }
    }
    catch (Exception $e) {
      return FALSE;
    }
  }

  /**
   * Parses a single date from a string.
   */
  protected static function parseSingleDate($string, $parseFormats) {
    $parsedDate = NULL;

    foreach ($parseFormats as $a) {
      $dp = new DateParser($a);

      if ($dp->strptime($string)) {
        $parsedDate = $dp;
        break;
      }
    }

    return $parsedDate;
  }

  /**
   * Convert a vague date to a string representing a fixed date.
   */
  protected static function vagueDateToDay($start, $end) {
    self::check(self::areDatesEqual($start, $end), 'Day vague dates should have the same date for the start and end of the date range');
    return $start->format('d/m/Y');
  }

  /**
   * Convert a vague date to a string representing a range of days.
   */
  protected static function vagueDateToDays($start, $end) {
    self::check(self::isFirstDateFirstOrEqual($start, $end), 'Day ranges should be presented in vague dates in the correct sequence. Start was %s, end was %s.', $start, $end);
    return $start->format('d/m/Y') .
      Kohana::lang('dates.range_separator') .
      $end->format('d/m/Y');
  }

  /**
   * Convert a vague date to a string representing a fixed month.
   */
  protected static function vagueDateToMonthInYear($start, $end) {
    self::check(self::isMonthStart($start) && self::isMonthEnd($end) && self::isSameMonth($start, $end),
      'Month dates should be represented by the first day and last day of the same month. Start was %s, end was %s.', $start, $end);
    return $start->format(Kohana::lang('dates.format_m_y'));
  }

  /**
   * Convert a vague date to a string representing a range of months.
   */
  protected static function vagueDateToMonthsInYear($start, $end) {
    self::check(self::isMonthStart($start) && self::isMonthEnd($end) && self::isFirstDateFirst($start, $end),
      'Month ranges should be represented by the first day of the first month and last day of the last month. Start was %s, end was %s.', $start, $end);
    return $start->format(Kohana::lang('dates.format_m_y')) .
      Kohana::lang('dates.range_separator') .
      $end->format(Kohana::lang('dates.format_m_y'));
  }

  /**
   * Convert a vague date to a string representing a season in a given year.
   */
  protected static function vagueDateToSeasonInYear($start, $end) {
    return self::convertToSeasonString($start, $end) . ' ' . $end->format('Y');
  }

  /**
   * Convert a vague date to a string representing a year.
   */
  protected static function vagueDateToYear($start, $end) {
    self::check(self::isYearStart($start) && self::isYearEnd($end) && self::isSameYear($start, $end),
      'Years should be represented by the first day and last day of the same year. Start was %s, end was %s.', $start, $end);
    return $start->format('Y');
  }

  /**
   * Convert a vague date to a string representing a range of years.
   */
  protected static function vagueDateToYears($start, $end) {
    self::check(self::isYearStart($start) && self::isYearEnd($end) && self::isFirstDateFirst($start, $end),
      'Year ranges should be represented by the first day of the first year to the last day of the last year. Start was %s, end was %s.', $start, $end);
    return $start->format('Y') . Kohana::lang('dates.range_separator') . $end->format('Y');
  }

  /**
   * Convert a vague date to a string representing any date after a given year.
   */
  protected static function vagueDateToYearFrom($start, $end) {
    self::check(self::isYearStart($start) && $end === NULL,
      'From year date should be represented by just the first day of the first year.');
    return sprintf(Kohana::lang('dates.from_date'), $start->format('Y'));
  }

  /**
   * Convert a VD to a date string up to and including a given year.
   */
  protected static function vagueDateToYearTo($start, $end) {
    self::check($start === NULL && self::isYearEnd($end),
      "To year date should be represented by just the last day of the last year. Start was %s and end was %s.", $start, $end);
    return sprintf(Kohana::lang('dates.to_date'), $end->format('Y'));
  }

  /**
   * Convert a vague date to a string representing a month in an unknown year.
   */
  protected static function vagueDateToMonth($start, $end) {
    self::check(self::isMonthStart($start) && self::isMonthEnd($end) && self::isSameMonth($start, $end),
      'Month dates should be represented by the start and end of the month.');
    return $start->format('F');
  }

  /**
   * Convert a vague date to a string representing a season in an unknown year.
   */
  protected static function vagueDateToSeason($start, $end) {
    return self::convertToSeasonString($start, $end);
  }

  /**
   * Convert a vague date to a string representing an unknown date.
   */
  protected static function vagueDateToUnknown($start, $end) {
    self::check($start === NULL && $end === NULL,
      'Unknown dates should not have a start or end specified');
    return Kohana::lang('dates.unknown');
  }

  /**
   * Convert a vague date to a string representing a century.
   */
  protected static function vagueDateToCentury($start, $end) {
    self::check(self::isCenturyStart($start) && self::isCenturyEnd($end) && self::isSameCentury($start, $end),
      'Century dates should be represented by the first day and the last day of the century');
    return sprintf(Kohana::lang('dates.century', ($start->format('Y') - 1) / 100 + 1));
  }

  /**
   * Convert a vague date to a string representing a century.
   */
  protected static function vagueDateToCenturies($start, $end) {
    self::check(self::isCenturyStart($start) && self::isCenturyEnd($end) && self::isFirstDateFirst($start, $end),
      'Century ranges should be represented by the first day of the first century and the last day of the last century');
    return sprintf(Kohana::lang('dates.century', ($start->format('Y') - 1) / 100 + 1)) .
      Kohana::lang('dates.range_separator') .
      sprintf(Kohana::lang('dates.century', ($end->format('Y') - 1) / 100 + 1));
  }

  /**
   * Convert a VD to a date string during or after a specified century.
   */
  protected static function vagueDateToCenturyFrom($start, $end) {
    self::check(self::isCenturyStart($start) && $end === NULL,
      'From Century dates should be represented by the first day of the century only');
    return sprintf(Kohana::lang('dates.from_date'), sprintf(Kohana::lang('dates.century', ($start->format('Y') - 1) / 100 + 1)));
  }

  /**
   * Convert VD to a date string before or during a given century.
   */
  protected static function vagueDateToCenturyTo($start, $end) {
    self::check($start === NULL && self::isCenturyEnd($end),
      'To Century dates should be represented by the last day of the century only');
    return sprintf(Kohana::lang('dates.to_date'), sprintf(Kohana::lang('dates.century', ($end->format('Y') - 1) / 100 + 1)));
  }

  /**
   * Returns true if the supplied date is the first day of the month.
   */
  protected static function isMonthStart($date) {
    return ($date->format('j') == 1);
  }

  /**
   * Returns true if the supplied date is the last day of the month.
   */
  protected static function isMonthEnd($date) {
    // Format t gives us the last day of the given date's month.
    return ($date->format('j') == $date->format('t'));
  }

  /**
   * Returns true if the supplied dates are the same.
   *
   * Early versions of PHP5.2 do not have valid binary comparison functions.
   */
  protected static function areDatesEqual($date1, $date2) {
    return (!strcmp($date1->format('Ymd'), $date2->format('Ymd')));
  }

  /**
   * True if the first supplied date is before second.
   *
   * Early versions of PHP5.2 do not have valid binary comparison functions.
   */
  protected static function isFirstDateFirst($date1, $date2) {
    return (strcmp($date1->format('Ymd'), $date2->format('Ymd')) < 0);
  }

  /**
   * True if the first supplied date is before second or they are the same.
   *
   * Early versions of PHP5.2 do not have valid binary comparison functions.
   */
  protected static function isFirstDateFirstOrEqual($date1, $date2) {
    return $date1 == $date2 || (strcmp($date1->format('Ymd'), $date2->format('Ymd')) < 0);
  }

  /**
   * Returns true if the supplied dates are in the same month.
   */
  protected static function isSameMonth($date1, $date2) {
    return ($date1->format('m') == $date2->format('m'));
  }

  /**
   * Returns true if the supplied date is the first day of the year.
   */
  protected static function isYearStart($date) {
    return ($date->format('j') == 1 && $date->format('m') == 1);
  }

  /**
   * Returns true if the supplied date is the last day of the year.
   */
  protected static function isYearEnd($date) {
    return ($date->format('j') == 31 && $date->format('m') == 12);
  }

  /**
   * Returns true if the supplied dates are in the same year.
   */
  protected static function isSameYear($date1, $date2) {
    return ($date1->format('Y') == $date2->format('Y'));
  }

  /**
   * True if the date is the first day of the century (starts in year nn01!)
   */
  protected static function isCenturyStart($date) {
    return ($date->format('j') == 1 && $date->format('m') == 1 && $date->format('y') == 1);
  }

  /**
   * Returns true if the supplied date is the last day of the century.
   */
  protected static function isCenturyEnd($date) {
    return ($date->format('j') == 31 && $date->format('m') == 12 && $date->format('y') == 0);
  }

  /**
   * Returns true if the supplied dates are in the same century.
   */
  protected static function isSameCentury($date1, $date2) {
    return floor(($date1->format('Y') - 1) / 100) == floor(($date2->format('Y') - 1) / 100);
  }

  /**
   * Retrieve the string that describes a season.
   */
  protected static function convertToSeasonString($start, $end) {
    self::check(self::isMonthStart($start) && self::isMonthEnd($end),
      'Seasons should be represented by the start of the first month of the season, to the end of the last month.');
    // Ensure the season spans 3 months.
    self::check(($start->format('Y') * 12 + $start->format('m') + 2)
      ==
      ($end->format('Y') * 12 + $end->format('m')),
        'Seasons should be 3 months long');
    switch ($start->format('m')) {
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
  protected static function validate($start, $end, $type) {

  }

  /**
   * Tests that a check passed, and if not throws an exception with message.
   *
   * Replacements in the message can be supplied as string parameters,
   * with %s used in the message. The replacements can also be null or
   * datetime objects which are then converted to strings.
   */
  protected static function check($pass, $message) {
    if (!$pass) {
      $args = func_get_args();
      // Any args after the message are string format inputs for the message.
      unset($args[0]);
      unset($args[1]);
      $inputs = array();
      foreach ($args as $arg) {
        kohana::log('debug', 'arg ' . gettype($arg));
        if (gettype($arg) == 'object') {
          $inputs[] = $arg->format('d/m/Y');
        }
        elseif (gettype($arg) === 'NULL') {
          $inputs[] = 'null';
        }
        else {
          $inputs[] = $arg;
        }
      }
      throw new Exception(vsprintf($message, $inputs));
    }
  }

}
?>
  </div>
  <script src="media/js/jquery.js"></script>
  <script src="vendor-other/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
