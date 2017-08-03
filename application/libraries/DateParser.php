<?php
/**
 * Class to parse a string containing a single date and extract the date.
 */
class DateParser_Core {

  private $timeStamp;
  private $format;
  private $locale;

  // Set everything to null so we know what has actually been parsed.
  private $aResult = Array(
    'tm_sec'   => null,
    'tm_min'   => null,
    'tm_hour'  => null,
    'tm_mday'  => null,
    'tm_mon'   => null,
    'tm_year'  => null,
    'tm_wday'  => null,
    'tm_yday'  => null,
    'tm_season' => null,
    'tm_century' => null,
    'unparsed' => null
  );

  /**
   * Constructs a date parser for a specific format.
   */
  public function __construct($format){
    $this->format = $format;
  }

  /**
   * Convenience methods to access the array.
   */
  public function __get($data){
    if (array_key_exists($data, $this->aResult)){
      return $this->aResult[$data];
    }
  }

  public function strptime($string){
    $sFormat = $this->format;
    $sDate = $string;
    while($sFormat != "") {
      // If we run out of date before we run out of format, DON'T match
      if ($sDate == '') return false;

      // ===== Search a %x element, Check the static string before the %x =====
      $nIdxFound = strpos($sFormat, '%');
      if($nIdxFound === false)
      {

        // There is no more format. Check the last static string.
        $this->aResult['unparsed'] = ($sFormat == $sDate) ? "" : $sDate;
        break;
      }

      $sFormatBefore = substr($sFormat, 0, $nIdxFound);
      $sDateBefore   = substr($sDate,   0, $nIdxFound);

      if($sFormatBefore != $sDateBefore) return false;

      // ===== Read the value of the %x found =====
      $sFormat = substr($sFormat, $nIdxFound);
      $sDate   = substr($sDate,   $nIdxFound);

      $this->aResult['unparsed'] = $sDate;

      $sFormatCurrent = substr($sFormat, 0, 2);
      $sFormatAfter   = substr($sFormat, 2);

      $nValue = null;
      $sDateAfter = "";
      switch($sFormatCurrent)
      {
        case '%S': // Seconds after the minute (0-59)

          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if(($nValue < 0) || ($nValue > 59) || ($nValue == null)) return false;

          $this->aResult['tm_sec']  = $nValue;
          break;

          // ----------
        case '%M': // Minutes after the hour (0-59)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if(($nValue < 0) || ($nValue > 59) || ($nValue == null)) return false;

          $this->aResult['tm_min']  = $nValue;
          break;

          // ----------
        case '%H': // Hour since midnight (0-23)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if(($nValue < 0) || ($nValue > 23) || ($nValue == null)) return false;

          $this->aResult['tm_hour']  = $nValue;
          break;

          // ----------
        case '%d': // Day of the month (1-31)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if(($nValue < 1) || ($nValue > 31) || ($nValue == null)) return false;

          $this->aResult['tm_mday']  = $nValue;
          break;

          // ----------
        case '%m': // Months since January (0-11)
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if(($nValue < 1) || ($nValue > 12) || ($nValue == null)) return false;

          $this->aResult['tm_mon']  = ($nValue - 1);
          break;

          // ----------
        case '%Y': // Year
          sscanf($sDate, "%4d%[^\\n]", $nValue, $sDateAfter);

          if (strlen($nValue) != 4) return false;

          $this->aResult['tm_year']  = ($nValue);
          break;
          // ----------
        case '%y': // 2-digit year
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (!isset($nValue)) return false;
          if (strlen($nValue) == 1) {
            // Must be in range 0-9
            $nValue = '0' . $nValue;
          }
          // Get the century as %C not supported on Windows
          $c=substr(strftime("%Y"), 0, 2);
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
        case '%A': // Full weekday
          // sscanf isn't powerful enough for this.
          // Get locale specific day names
          $dayStr = '';
          for ($i = 0; $i < 7; $i++){
            $a = strtolower(Kohana::lang('dates.days.'.$i));
            $weekdays[$a] = $i;
            $dayStr .= ($i == 0) ? Kohana::lang('dates.days.'.$i) : "|".Kohana::lang('dates.days.'.$i);
          }
          $a = preg_match("/^(".$dayStr.")(.*)/i",$sDate,$refs);
          if ($a){
            $nValue = $weekdays[strtolower($refs[1])];
            $this->aResult['tm_wday'] = $nValue;
            $sDateAfter = $refs[2];
          } else {
            return false;
          }
          break;
        case '%a': // Abbreviated weekday according to current locale
          // sscanf isn't powerful enough for this.
          // Get locale specific day names
          $dayStr = '';
          for ($i = 0; $i < 7; $i++){
            $weekdays[strtolower(Kohana::lang('dates.abbrDays.'.$i))] = $i;
            $dayStr .= ($i == 0) ? Kohana::lang('dates.abbrDays.'.$i) : "|".Kohana::lang('dates.abbrDays.'.$i);
          }
          $a = preg_match("/^(".$dayStr.")(.*)/i",$sDate,$refs);
          if ($a){
            $nValue = $weekdays[strtolower($refs[1])];
            $this->aResult['tm_wday'] = $nValue;
            $sDateAfter = $refs[2];
          } else {
            return false;
          }
          break;
        case '%e': // Day of the month as decimal number, single digit preceeded by a space.
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if(($nValue < 1) || ($nValue > 31) || ($nValue == null)) return false;
          $this->aResult['tm_mday']  = $nValue;
          break;
        case '%B': // Full month according to current locale.
          // sscanf isn't powerful enough for this.
          // Get locale specific day names
          $dayStr = '';
          for ($i = 0; $i < 12; $i++){
            $weekdays[strtolower(Kohana::lang('dates.months.'.$i))] = $i;
            $dayStr .= ($i == 0) ? Kohana::lang('dates.months.'.$i) : "|".Kohana::lang('dates.months.'.$i);
          }

          $a = preg_match("/^(".$dayStr.")(.*)/i",$sDate,$refs);
          if ($a){
            $nValue = $weekdays[strtolower($refs[1])];
            $this->aResult['tm_mon'] = $nValue;
            $sDateAfter = $refs[2];
          } else {
            return false;
          }
          break;
        case '%b': // Abbreviated month according to current locale.
          // sscanf isn't powerful enough for this.
          // Get locale specific day names
          $dayStr = '';
          for ($i = 0; $i < 12; $i++){
            $weekdays[strtolower(Kohana::lang('dates.abbrMonths.'.$i))] = $i;
            $dayStr .= ($i == 0) ? Kohana::lang('dates.abbrMonths.'.$i) : "|".Kohana::lang('dates.abbrMonths.'.$i);
          }
          $a = preg_match("/^(".$dayStr.")(.*)/i",$sDate,$refs);
          if ($a){
            $nValue = $weekdays[strtolower($refs[1])];
            $this->aResult['tm_mon'] = $nValue;
            $sDateAfter = $refs[2];
          } else {
            return false;
          }
          break;
        case '%K': // Season
          // Get locale specific season names
          $sRegex = '';
          $first = true;
          foreach (Kohana::lang('dates.seasons') as $key => $season) {
            $seasons[strtolower($season)] = $key;
            $sRegex .= ($first) ? $season : "|".$season;
            $first = false;
          }
          $a = preg_match("/^(".$sRegex.")(.*)/i", $sDate, $refs);
          if ($a){
            $nValue = strtolower($refs[1]);
            $this->aResult['tm_season'] = $seasons[strtolower($nValue)];
            $sDateAfter = $refs[2];
          } else {
            return false;
          }
          break;
        case '%k': // Season (short form) in year
          break;
        case '%C': // Century
          //Use a regex for this
          $a = preg_match("/^(\d{1,2})c(.*)/i", $sDate, $refs);
          if ($a) {
            $nValue = $refs[1];
            $this->aResult['tm_century'] = $nValue;
            $sDateAfter = $refs[2];
          } else {
            return false;
          }
          break;
        default: return false; // Bad pattern
      }

      // ===== Next please =====
      $sFormat = $sFormatAfter;
      $sDate   = $sDateAfter;

      $this->aResult['unparsed'] = $sDate;

    } // END while($sFormat != "")
    return empty($this->aResult['unparsed']);
  }

  public function getIsoDate(){
    if ($this->aResult['tm_year'] == null)
      return null;
    return $this->formatDate($this->tm_year, $this->tm_mon + 1, $this->tm_mday);
  }

  public function getImpreciseDateStart(){
    // Copy the date array
    $aStart = $this->aResult;
    // If we're a century
    if (($a = $aStart['tm_century']) != null){
      $aStart['tm_year'] = 100*($a-1);
      $aStart['tm_mon'] = 0;
      $aStart['tm_mday'] = 1;
      return $this->formatDate($aStart['tm_year'], $aStart['tm_mon'] + 1, $aStart['tm_mday']);
    }

    // Do we have a year, else set it to this year
    if ($aStart['tm_year'] == null) $aStart['tm_year'] = date("Y");

    // Is this a season?
    if (($a = $aStart['tm_season']) != null){
      switch ($a) {
        case 'spring':
          return $this->formatDate($aStart['tm_year'], 3, 1);
          break;
        case 'summer':
          return $this->formatDate($aStart['tm_year'], 6, 1);
          break;
        case 'autumn':
          return $this->formatDate($aStart['tm_year'], 9, 1);
          break;
        case 'winter':
          // End of winter into previous year
          // E.g Winter 2010 is from 1/12/2009 to 28/2/2010
          return $this->formatDate($aStart['tm_year'] - 1, 12, 1);
          break;
      }
    }

    // If no month is given, set it to January
    if ($aStart['tm_mon'] == null) $aStart['tm_mon'] = 0;

    // If no day is given, set it to the first
    if ($aStart['tm_mday'] == null) $aStart['tm_mday'] = 1;

    return $this->formatDate($aStart['tm_year'], $aStart['tm_mon'] + 1, $aStart['tm_mday']);
  }

  public function getImpreciseDateEnd(){
    // Copy the date array
    $aStart = $this->aResult;
    // If we're a century
    if (($a = $aStart['tm_century']) !== null) {
      return $this->formatDate(100*($a)-1, 12, 31);
    }

    // Do we have a year, else set it to this year
    if ($aStart['tm_year'] === null) $aStart['tm_year'] = date("Y");

    // Is this a season?
    if (($a = $aStart['tm_season']) !== null){
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
    if ($aStart['tm_mon'] === null)
      $aStart['tm_mon'] = 11;

    // If no day is given, set day to end of the month using the 't' format which gets the days in the month.
    // Since we can't use mktime for historic dates, use the year 2000 arbitrarily and handle feb specially
    if ($aStart['tm_mday'] === null) {
      if ($aStart['tm_mon']+1===2)
        $aStart['tm_mday'] = $this->isLeapYear($aStart['tm_year']) ? 29 : 28;
      else
        $aStart['tm_mday'] = date('t', mktime(0,0,0,$aStart['tm_mon']+1, 1, 2000));
    }

    // Build our date
    return $this->formatDate($aStart['tm_year'], $aStart['tm_mon'] + 1, $aStart['tm_mday']);
  }

  /**
   * Gets the precision of this date - that is, the lowest element (from 'tm_sec' up to 'tm_year') which is
   * not reported as null.
   */
  public function getPrecision(){
    foreach ($this->aResult as $key=>$res){
      if ($res != null) return $key;
    }
    return null;
  }

  /**
   * Formats a year, month and day as a Y-m-d format date string.
   * @param integer $year
   * @param integer $month
   * @param integer $day
   * @param string $format Date format string, defaults to Y-m-d
   * @return string
   */
  private function formatDate($year, $month, $day, $format='Y-m-d') {
    if (!checkdate($month, $day, $year))
      throw new InvalidArgumentException('Invalid date');
    // avoid mktime approach because of need to support historic dates.
    $date = new DateTime();
    return $date->setDate($year, $month, $day)->format($format);
  }

  private function isLeapYear($year) {
    return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year %400) == 0)));
  }


}