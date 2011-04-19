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
        case '%y': // 2digit year
          sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

          if (strlen($nValue) != 2) return false;

          if ($nValue <= strftime("%y")) {
            // This century.
            $nValue = strftime("%C").$nValue;
          } else {
            // Last century.
            $nValue = (strftime("%C") - 1).$nValue;
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
          $a = preg_match("/(".$dayStr.")(.*)/i",$sDate,$refs);
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
          $a = preg_match("/(".$dayStr.")(.*)/i",$sDate,$refs);
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
          $a = preg_match("/(".$dayStr.")(.*)/i",$sDate,$refs);
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
          $a = preg_match("/(".$dayStr.")(.*)/i",$sDate,$refs);
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
          $a = preg_match("/(".$sRegex.")(.*)/i", $sDate, $refs);
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
          $a = preg_match("/(\d{1,2})c(.*)/i", $sDate, $refs);
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
    return true;
  }

  public function getIsoDate(){
    if ($this->aResult['tm_year'] == null) return null;
    return date("Y-m-d", mktime(0,0,0,$this->tm_mon + 1,$this->tm_mday,$this->tm_year));
  }

  public function getImpreciseDateStart(){
    // Copy the date array
    $aStart = $this->aResult;
    // If we're a century
    if (($a = $aStart['tm_century']) != null){
      $aStart['tm_year'] = 100*($a-1);
      $aStart['tm_mon'] = 0;
      $aStart['tm_mday'] = 1;
      return date("Y-m-d", mktime(0,0,0,$aStart['tm_mon'] + 1, $aStart['tm_mday'], $aStart['tm_year']));
    }

    // Do we have a year, else set it to this year
    if ($aStart['tm_year'] == null) $aStart['tm_year'] = date("Y");

    // Is this a season?
    if (($a = $aStart['tm_season']) != null){
      switch ($a) {
        case 'spring':
          return date("Y-m-d", mktime(0,0,0,3,1,$aStart['tm_year']));
          break;
        case 'summer':
          return date("Y-m-d", mktime(0,0,0,6,1,$aStart['tm_year']));
          break;
        case 'autumn':
          return date("Y-m-d", mktime(0,0,0,9,1,$aStart['tm_year']));
          break;
        case 'winter':
          return date("Y-m-d", mktime(0,0,0,12,1,$aStart['tm_year']-1));
          break;
      }
    }

    // If no month is given, set it to January
    if ($aStart['tm_mon'] == null) $aStart['tm_mon'] = 0;

    // If no day is given, set it to the first
    if ($aStart['tm_mday'] == null) $aStart['tm_mday'] = 1;

    return date("Y-m-d", mktime(0,0,0,$aStart['tm_mon'] + 1, $aStart['tm_mday'], $aStart['tm_year']));


  }

  public function getImpreciseDateEnd(){
    // Copy the date array
    $aStart = $this->aResult;
    // If we're a century
    if (($a = $aStart['tm_century']) != null){
      $aStart['tm_year'] = 100*($a)-1;
      $aStart['tm_mon'] = 11;
      $aStart['tm_mday'] = 31;
      return date("Y-m-d", mktime(0,0,0,$aStart['tm_mon'] + 1, $aStart['tm_mday'], $aStart['tm_year']));
    }

    // Do we have a year, else set it to this year
    if ($aStart['tm_year'] == null) $aStart['tm_year'] = date("Y");

    // Is this a season?
    if (($a = $aStart['tm_season']) != null){
      switch ($a) {
        case 'spring':
          return date("Y-m-d", mktime(0,0,0,6,0, $aStart['tm_year']));
          break;
        case 'summer':
          return date("Y-m-d", mktime(0,0,0,9,0, $aStart['tm_year']));
          break;
        case 'autumn':
          return date("Y-m-d", mktime(0,0,0,12,0, $aStart['tm_year']));
          break;
        case 'winter':
          return date("Y-m-d", mktime(0,0,0,3,0, $aStart['tm_year']));
          break;
      }
    }

    // If no month is given, set it to December
    if ($aStart['tm_mon'] == null) $aStart['tm_mon'] = 11;

    // If no day is given, set month to month +1 and day to 0
    if ($aStart['tm_mday'] == null) {
      $aStart['tm_mday'] = 0;
      $aStart['tm_mon'] += 1;
    }

    return date("Y-m-d", mktime(0,0,0,$aStart['tm_mon'] + 1, $aStart['tm_mday'], $aStart['tm_year']));


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


}
?>
