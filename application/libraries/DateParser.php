<?php

/**
 * Class to parse a string containing a single date and extract the date.
 */
class DateParser_Core {

  private $timeStamp;
  private $format;
  private $locale;

  // Set everything to null so we know what has actually been parsed.
  private $aResult = [
    'tm_sec'   => NULL,
    'tm_min'   => NULL,
    'tm_hour'  => NULL,
    'tm_mday'  => NULL,
    'tm_mon'   => NULL,
    'tm_year'  => NULL,
    'tm_wday'  => NULL,
    'tm_yday'  => NULL,
    'tm_season' => NULL,
    'tm_century' => NULL,
    'unparsed' => NULL,
  ];

  /**
   * Constructs a date parser for a specific format.
   */
  public function __construct($format) {
    $this->format = $format;
  }

  /**
   * Convenience methods to access the array.
   */
  public function __get($data) {
    if (array_key_exists($data, $this->aResult)) {
      return $this->aResult[$data];
    }
  }

  public function strptime($string) {
    $sFormat = $this->format;
    $sDate = $string;
    while ($sFormat != "") {
      // If we run out of date before we run out of format, DON'T match.
      if ($sDate == '') {
        return FALSE;
      }

      // ===== Search a %x element, Check the static string before the %x =====
      $nIdxFound = strpos($sFormat, '%');
      if ($nIdxFound === FALSE) {

        // There is no more format. Check the last static string.
        $this->aResult['unparsed'] = ($sFormat == $sDate) ? "" : $sDate;
        break;
      }

      $sFormatBefore = substr($sFormat, 0, $nIdxFound);
      $sDateBefore   = substr($sDate, 0, $nIdxFound);

      if ($sFormatBefore != $sDateBefore) {
        return FALSE;
      }

      // ===== Read the value of the %x found =====
      $sFormat = substr($sFormat, $nIdxFound);
      $sDate   = substr($sDate, $nIdxFound);

      $this->aResult['unparsed'] = $sDate;

      $sFormatCurrent = substr($sFormat, 0, 2);
      $sFormatAfter   = substr($sFormat, 2);

      $nValue = NULL;
      $sDateAfter = "";
      switch ($sFormatCurrent) {
        case '%S':
          // Seconds after the minute (0-59)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (($nValue < 0) || ($nValue > 59) || ($nValue == NULL)) {
            return FALSE;
          }

          $this->aResult['tm_sec'] = $nValue;
          break;

        // ----------
        case '%M':
          // Minutes after the hour (0-59)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (($nValue < 0) || ($nValue > 59) || ($nValue == NULL)) {
            return FALSE;
          }

          $this->aResult['tm_min'] = $nValue;
          break;

        // ----------
        case '%H':
          // Hour since midnight (0-23)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (($nValue < 0) || ($nValue > 23) || ($nValue == NULL)) {
            return FALSE;
          }

          $this->aResult['tm_hour'] = $nValue;
          break;

        // ----------
        case '%d':
          // Day of the month (1-31)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (($nValue < 1) || ($nValue > 31) || ($nValue == NULL)) {
            return FALSE;
          }

          $this->aResult['tm_mday'] = $nValue;
          break;

        // ----------
        case '%m':
          // Months since January (0-11)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (($nValue < 1) || ($nValue > 12) || ($nValue == NULL)) {
            return FALSE;
          }

          $this->aResult['tm_mon'] = ($nValue - 1);
          break;

        // ----------
        case '%Y':
          // Year.
          sscanf($sDate, "%4d%[^\\n]", $nValue, $sDateAfter);

          if (strlen($nValue) != 4) {
            return FALSE;
          }

          $this->aResult['tm_year'] = ($nValue);
          break;

        // ----------
        case '%y':
          // 2-digit year.
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (!isset($nValue)) {
            return FALSE;
          }
          if (strlen($nValue) == 1) {
            // Must be in range 0-9.
            $nValue = '0' . $nValue;
          }
          // Get the century as %C not supported on Windows.
          $c = substr(strftime("%Y"), 0, 2);
          if ($nValue <= strftime("%y")) {
            // This century.
            $nValue = "$c$nValue";
          } else {
            // Last century.
            $nValue = ($c - 1) . $nValue;
          }

          $this->aResult['tm_year'] = $nValue;
          break;

        // ----------
        case '%A':
          // Full weekday.
          // sscanf isn't powerful enough for this.
          // Get locale specific day names.
          $dayStr = '';
          for ($i = 0; $i < 7; $i++) {
            $a = strtolower(Kohana::lang('dates.days.' . $i));
            $weekdays[$a] = $i;
            $dayStr .= ($i == 0) ?
              Kohana::lang('dates.days.' . $i) :
              "|" . Kohana::lang('dates.days.' . $i);
          }
          $a = preg_match("/^(" . $dayStr . ")(.*)/i", $sDate, $refs);
          if ($a) {
            $nValue = $weekdays[strtolower($refs[1])];
            $this->aResult['tm_wday'] = $nValue;
            $sDateAfter = $refs[2];
          }
          else {
            return FALSE;
          }
          break;

        // ----------
        case '%a':
          // Abbreviated weekday according to current locale
          // sscanf isn't powerful enough for this.
          // Get locale specific day names.
          $dayStr = '';
          for ($i = 0; $i < 7; $i++) {
            $weekdays[strtolower(Kohana::lang('dates.abbrDays.' . $i))] = $i;
            $dayStr .= ($i == 0) ?
               Kohana::lang('dates.abbrDays.' . $i) :
               "|" . Kohana::lang('dates.abbrDays.' . $i);
          }
          $a = preg_match("/^(" . $dayStr . ")(.*)/i", $sDate, $refs);
          if ($a) {
            $nValue = $weekdays[strtolower($refs[1])];
            $this->aResult['tm_wday'] = $nValue;
            $sDateAfter = $refs[2];
          }
          else {
            return FALSE;
          }
          break;

        // ----------
        case '%e':
          // Day of the month as decimal number, single digit preceeded by a space.
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (($nValue < 1) || ($nValue > 31) || ($nValue == NULL)) {
            return FALSE;
          }
          $this->aResult['tm_mday'] = $nValue;
          break;

        // ----------
        case '%B':
          // Full month according to current locale.
          // sscanf isn't powerful enough for this.
          // Get locale specific day names.
          $dayStr = '';
          for ($i = 0; $i < 12; $i++) {
            $weekdays[strtolower(Kohana::lang('dates.months.' . $i))] = $i;
            $dayStr .= ($i == 0) ?
              Kohana::lang('dates.months.' . $i) :
              "|" . Kohana::lang('dates.months.' . $i);
          }

          $a = preg_match("/^(" . $dayStr . ")(.*)/i", $sDate, $refs);
          if ($a) {
            $nValue = $weekdays[strtolower($refs[1])];
            $this->aResult['tm_mon'] = $nValue;
            $sDateAfter = $refs[2];
          }
          else {
            return FALSE;
          }
          break;

        // ----------
        case '%b': // Abbreviated month according to current locale.
          // sscanf isn't powerful enough for this.
          // Get locale specific day names.
          $dayStr = '';
          for ($i = 0; $i < 12; $i++) {
            $weekdays[strtolower(Kohana::lang('dates.abbrMonths.' . $i))] = $i;
            $dayStr .= ($i == 0) ?
              Kohana::lang('dates.abbrMonths.' . $i) :
              "|" . Kohana::lang('dates.abbrMonths.' . $i);
          }
          $a = preg_match("/^(" . $dayStr . ")(.*)/i", $sDate, $refs);
          if ($a) {
            $nValue = $weekdays[strtolower($refs[1])];
            $this->aResult['tm_mon'] = $nValue;
            $sDateAfter = $refs[2];
          }
          else {
            return FALSE;
          }
          break;

        // ----------
        case '%K':
          // Season
          // Get locale specific season names.
          $sRegex = '';
          $first = TRUE;
          foreach (Kohana::lang('dates.seasons') as $key => $season) {
            $seasons[strtolower($season)] = $key;
            $sRegex .= ($first) ? $season : "|" . $season;
            $first = FALSE;
          }
          $a = preg_match("/^(" . $sRegex . ")(.*)/i", $sDate, $refs);
          if ($a) {
            $nValue = strtolower($refs[1]);
            $this->aResult['tm_season'] = $seasons[strtolower($nValue)];
            $sDateAfter = $refs[2];
          }
          else {
            return FALSE;
          }
          break;

        // ----------
        case '%k':
          // Season (short form) in year.
          break;

        // ----------
        case '%C':
          // Century
          // Use a regex for this.
          $a = preg_match("/^(\d{1,2})c(.*)/i", $sDate, $refs);
          if ($a) {
            $nValue = $refs[1];
            $this->aResult['tm_century'] = $nValue;
            $sDateAfter = $refs[2];
          }
          else {
            return FALSE;
          }
          break;

        default:
          // Bad pattern.
          return FALSE;
      }

      // ===== Next please =====
      $sFormat = $sFormatAfter;
      $sDate   = $sDateAfter;

      $this->aResult['unparsed'] = $sDate;

    } // END while($sFormat != "")
    return empty($this->aResult['unparsed']);
  }

  public function getIsoDate() {
    if ($this->aResult['tm_year'] == NULL) {
      return NULL;
    }
    return $this->formatDate($this->tm_year, $this->tm_mon + 1, $this->tm_mday);
  }

  public function getImpreciseDateStart() {
    // Copy the date array.
    $aStart = $this->aResult;
    // If we're a century...
    if (($a = $aStart['tm_century']) != NULL) {
      $aStart['tm_year'] = 100 * ($a - 1);
      $aStart['tm_mon'] = 0;
      $aStart['tm_mday'] = 1;
      return $this->formatDate($aStart['tm_year'], $aStart['tm_mon'] + 1, $aStart['tm_mday']);
    }

    // Do we have a year, else set it to this year.
    if ($aStart['tm_year'] == NULL) {
      $aStart['tm_year'] = date("Y");
    }

    // Is this a season?
    if (($a = $aStart['tm_season']) != NULL) {
      switch ($a) {
        case 'spring':
          return $this->formatDate($aStart['tm_year'], 3, 1);

        case 'summer':
          return $this->formatDate($aStart['tm_year'], 6, 1);

        case 'autumn':
          return $this->formatDate($aStart['tm_year'], 9, 1);

        case 'winter':
          // End of winter into previous year.
          // E.g Winter 2010 is from 1/12/2009 to 28/2/2010.
          return $this->formatDate($aStart['tm_year'] - 1, 12, 1);
      }
    }

    // If no month is given, set it to January.
    if ($aStart['tm_mon'] == NULL) {
      $aStart['tm_mon'] = 0;
    }

    // If no day is given, set it to the first.
    if ($aStart['tm_mday'] == NULL) {
      $aStart['tm_mday'] = 1;
    }

    return $this->formatDate($aStart['tm_year'], $aStart['tm_mon'] + 1, $aStart['tm_mday']);
  }

  public function getImpreciseDateEnd() {
    // Copy the date array.
    $aStart = $this->aResult;
    // If we're a century...
    if (($a = $aStart['tm_century']) !== NULL) {
      return $this->formatDate(100 * ($a) - 1, 12, 31);
    }

    // Do we have a year, else set it to this year.
    if ($aStart['tm_year'] === NULL) {
      $aStart['tm_year'] = date("Y");
    }

    // Is this a season?
    if (($a = $aStart['tm_season']) !== NULL) {
      switch ($a) {
        case 'spring':
          return $this->formatDate($aStart['tm_year'], 5, 31);

        case 'summer':
          return $this->formatDate($aStart['tm_year'], 8, 31);

        case 'autumn':
          return $this->formatDate($aStart['tm_year'], 11, 30);

        case 'winter':
          $year = $aStart['tm_year'];
          $day = $this->isLeapYear($year) ? 29 : 28;
          return $this->formatDate($year, 2, $day);
      }
    }

    // If no month is given, set it to December (indexed to 0)
    if ($aStart['tm_mon'] === NULL) {
      $aStart['tm_mon'] = 11;
    }

    // If no day is given, set day to end of the month using the 't' format
    // which gets the days in the month. Since we can't use mktime for historic
    // dates, use the year 2000 arbitrarily and handle feb specially.
    if ($aStart['tm_mday'] === NULL) {
      if ($aStart['tm_mon'] + 1 === 2) {
        $aStart['tm_mday'] = $this->isLeapYear($aStart['tm_year']) ? 29 : 28;
      }
      else {
        $aStart['tm_mday'] = date('t', mktime(0, 0, 0, $aStart['tm_mon'] + 1, 1, 2000));
      }
    }

    // Build our date.
    return $this->formatDate($aStart['tm_year'], $aStart['tm_mon'] + 1, $aStart['tm_mday']);
  }

  /**
   * Gets the precision of this date - that is, the lowest element (from
   * 'tm_sec' up to 'tm_year') which is not reported as null.
   */
  public function getPrecision() {
    foreach ($this->aResult as $key => $res) {
      if ($res != NULL) {
        return $key;
      }
    }
    return NULL;
  }

  /**
   * Formats a year, month and day as a Y-m-d format date string.
   * @param integer $year
   * @param integer $month
   * @param integer $day
   * @param string $format Date format string, defaults to Y-m-d
   * @return string
   */
  private function formatDate($year, $month, $day, $format = 'Y-m-d') {
    if (!checkdate($month, $day, $year)) {
      throw new InvalidArgumentException('Invalid date');
    }
    // Avoid mktime approach because of need to support historic dates.
    $date = new DateTime();
    return $date->setDate($year, $month, $day)->format($format);
  }

  private function isLeapYear($year) {
    return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0)));
  }

}
