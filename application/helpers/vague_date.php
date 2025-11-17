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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Exception class for aborting.
 */
class InvalidVagueDateException extends Exception {}

class vague_date {

  /**
   * List of regex strings used to try to capture date ranges.
   *
   * The regex key should, naturally, point to the regular expression. Start
   * should point to the backreference for the string to be parsed for the
   * 'start' date, 'end' to the backreference of the string to be parsed for
   * the 'end' date. -1 means grab the text before the match, 1 means after, 0
   * means set the value to empty. Types are not determined here. Should either
   * 'start' or 'end' contain the string '...', this will be interpreted as
   * one-ended range.
   */
  private static function dateRangeStrings() {
    return [
      [
        // Date to date or date - date.
        'regex' => '/(?P<sep> to | - )/i',
        'start' => -1,
        'end' => 1,
      ],
      [
        // dd/mm/yy(yy)-dd/mm/yy(yy) or dd.mm.yy(yy)-dd.mm.yy(yy).
        'regex' => '/^\d{2}[\/\.]\d{2}[\/\.]\d{2}(\d{2})?(?P<sep>-)\d{2}[\/\.]\d{2}[\/\.]\d{2}(\d{2})?$/',
        'start' => -1,
        'end' => 1,
      ],
      [
        // mm/yy(yy)-mm/yy(yy) or mm.yy(yy)-mm.yy(yy).
        'regex' => '/^\d{2}[\/\.]\d{2}(\d{2})?(?P<sep>-)\d{2}[\/\.]\d{2}(\d{2})?$/',
        'start' => -1,
        'end' => 1,
      ],
      [
        // yyyy-yyyy.
        'regex' => '/^\d{4}(?P<sep>-)\d{4}$/',
        'start' => -1,
        'end' => 1,
      ],
      [
        // Century to century.
        'regex' => '/^\d{2}c-\d{2}c?$/',
        'start' => -1,
        'end' => 1,
      ],
      [
        'regex' => '/^(?P<sep>to|pre|before[\.]?)/i',
        'start' => 0,
        'end' => 1,
      ],
      [
        'regex' => '/(?P<sep>from|after)/i',
        'start' => 1,
        'end' => 0,
      ],
      [
        'regex' => '/(?P<sep>-)$/',
        'start' => -1,
        'end' => 0,
      ],
      [
        'regex' => '/^(?P<sep>-)/',
        'start' => 0,
        'end' => 1,
      ],
    ];
  }

  /**
   * Array of formats used to parse a string looking for a single day with the strptime()
   * function - see http://uk2.php.net/manual/en/function.strptime.php
   */
  private static function singleDayFormats() {
    return [
      // ISO 8601 date format 1997-10-12.
      '%Y-%m-%d',
      // 12/10/1997.
      '%d/%m/%Y',
      // 12/10/97.
      '%d/%m/%y',
      // 12.10.1997.
      '%d.%m.%Y',
      // 12.10.97.
      '%d.%m.%y',
      // Monday 12 October 1997.
      '%A %e %B %Y',
      // Mon 12 October 1997.
      '%a %e %B %Y',
      // Monday 12 Oct 1997.
      '%A %e %b %Y',
      // Mon 12 Oct 1997.
      '%a %e %b %Y',
      // Monday 12 October 97.
      '%A %e %B %y',
      // Mon 12 October 97.
      '%a %e %B %y',
      // Monday 12 Oct 97.
      '%A %e %b %y',
      // Mon 12 Oct 97.
      '%a %e %b %y',
      // Monday 12 October.
      '%A %e %B',
      // Mon 12 October.
      '%a %e %B',
      // Monday 12 Oct.
      '%A %e %b',
      // Mon 12 Oct.
      '%a %e %b',
      // 12 October 1997.
      '%e %B %Y',
      // 12 Oct 1997.
      '%e %b %Y',
      // 12 October 97.
      '%e %B %y',
      // 12 Oct 97.
      '%e %b %y',
      // American date format.
      '%m/%d/%y',
    ];
  }

  /**
   * Array of formats used to parse a string looking for a single month in a year
   * with the strptime() function - see http://uk2.php.net/manual/en/function.strptime.php
   */
  private static function singleMonthInYearFormats() {
    return [
      // ISO 8601 format - truncated to month 1998-06
      '%Y-%m',
      // 06/1998
      '%m/%Y',
      // 06/96
      '%m/%y',
      // June 1998
      '%B %Y',
      // Jun 1998
      '%b %Y',
      // June 98
      '%B %y',
      // Jun 98
      '%b %y',
    ];
  }

  private static function singleMonthFormats() {
    return [
      // October
      '%B',
      // Oct
      '%b',
    ];
  }

  private static function singleYearFormats() {
    return [
      // 1998
      '%Y',
      // 98
      '%y',
    ];
  }

  private static function seasonInYearFormats() {
    return [
      // Autumn 2008
      '%K %Y',
      // Autumn 08
      '%K %y',
    ];
  }

  private static function seasonFormats() {
    return [
      // August
      '%K',
    ];
  }

  private static function centuryFormats() {
    return [
      // 20C
      '%C',
    ];
  }

  /**
   * Convert a vague date in the form of array(start, end, type) to a string.
   *
   * @param array $date
   *   Vague date in the form array(start_date, end_date, date_type), where
   *   start_date and end_date are DateTime objects or strings.
   *
   * @return string
   *   Vague date expressed as a string.
   */
  public static function vague_date_to_string(array $date) {
    $start = empty($date[0]) ? NULL : $date[0];
    $end = empty($date[1]) ? NULL : $date[1];
    $type = $date[2];
    if (empty($type)) {
      return '';
    }
    if (is_string($start)) {
      $start = DateTime::createFromFormat(Kohana::lang('dates.format'), $date[0]);
      if (!$start) {
        // If not in warehouse default date format, allow PHP standard processing.
        $start = new DateTime($date[0]);
      }
    }
    if (is_string($end)) {
      $end = DateTime::createFromFormat(Kohana::lang('dates.format'), $date[1]);
      if (!$end) {
        // If not in warehouse default date format, allow PHP standard processing.
        $end = new DateTime($date[1]);
      }
    }
    // self::validate(array($start, $end, $type);
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
   * Convert a string into a vague date.
   *
   * @param string $string
   *   The date as a string.
   *
   * @return array|false
   *   An array with 3 entries, the start date, end date and date type, or
   *   FALSE if the format can't be matched.
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
    // Our approach shall be to gradually pare down from the most complex
    // possible dates to the simplest, and match as fast as possible to try to
    // grab the most information. First we consider the potential ways that a
    // range may be represented.
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
        else {
          return FALSE;
        }
      }
      if ($end) {
        $a = self::parseSingleDate($end, $parseFormats);
        if ($a !== NULL) {
          $endDate = $a;
          $matched = TRUE;
        }
        else {
          return FALSE;
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
      if (trim($string) === 'U' || strcasecmp(trim($string), Kohana::lang('dates.unknown')) === 0) {
        return [NULL, NULL, 'U'];
      }
      else {
        return FALSE;
      }
    }
    // Okay, now we try to determine the type - we look mostly at $endDate because
    // this is more likely to contain more info e.g. 15 - 18 August 2008
    // Seasons are parsed specially - i.e. we'll have seen the word 'Summer'
    // or the like.
    try {

      if ($endDate->tm_season !== NULL) {
        // We're a season. That means we could be P (if we have a year) or
        // S (if we don't).
        if ($endDate->tm_year !== NULL) {
          // We're a P.
          $vagueDate = [
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'P',
          ];
          return self::validate($vagueDate);
        }
        else {
          // No year, so we're an S.
          $vagueDate = [
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'S',
          ];
          return self::validate($vagueDate);
        }
      }
      // Do we have day precision?
      if ($endDate->tm_mday !== NULL) {
        if (!$range) {
          // We're a D.
          $vagueDate = [
            $endDate->getIsoDate(),
            $endDate->getIsoDate(),
            'D',
          ];
          return self::validate($vagueDate);
        }
        else {
          // Type is DD. We copy across any data not set in the
          // start date.
          if ($startDate->getPrecision() == $endDate->getPrecision()) {
            $vagueDate = [
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'DD',
            ];
          }
          else {
            // Less precision in the start date -
            // try and massage them together.
            return FALSE;
          }
          return self::validate($vagueDate);

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
            $vagueDate = [
              $endDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'O',
            ];
            return self::validate($vagueDate);
          }
          else {
            // Month without a year - type M.
            $vagueDate = [
              $endDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'M',
            ];
            return self::validate($vagueDate);
          }
        }
        else {
          // We do have a range, OO.
          if ($endDate->tm_year !== NULL) {
            // We have a year - so this is OO.
            $vagueDate = [
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'OO',
            ];
            return self::validate($vagueDate);
          }
          else {
            // MM is not an allowed type.
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
          $vagueDate = [
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'C',
          ];
          return self::validate($vagueDate);
        }
        else {
          if ($start && $end) {
            // We're CC.
            $vagueDate = [
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'CC',
            ];
            return self::validate($vagueDate);
          }
          elseif ($start && !$end) {
            // We're C-.
            $vagueDate = [
              $endDate->getImpreciseDateStart(),
              NULL,
              'C-',
            ];
            return self::validate($vagueDate);
          }
          elseif ($end && !$start) {
            // We're -C.
            $vagueDate = [
              NULL,
              $endDate->getImpreciseDateEnd(),
              '-C',
            ];
            return self::validate($vagueDate);
          }
        }
      }

      // Okay, we're one of the year representations.
      if ($endDate->tm_year !== NULL) {
        if (!$range) {
          // We're Y.
          $vagueDate = [
            $endDate->getImpreciseDateStart(),
            $endDate->getImpreciseDateEnd(),
            'Y',
          ];
          return self::validate($vagueDate);
        }
        else {
          if ($start && $end) {
            // We're YY.
            $vagueDate = [
              $startDate->getImpreciseDateStart(),
              $endDate->getImpreciseDateEnd(),
              'YY',
            ];
            return self::validate($vagueDate);
          }
          elseif ($start && !$end) {
            // We're Y-.
            $vagueDate = [
              $startDate->getImpreciseDateStart(),
              NULL,
              'Y-',
            ];
            return self::validate($vagueDate);
          }
          elseif ($end && !$start) {
            // We're -Y.
            $vagueDate = [
              NULL,
              $endDate->getImpreciseDateEnd(),
              '-Y',
            ];
            return self::validate($vagueDate);
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
  protected static function vague_date_to_day($start, $end) {
    self::check(
      self::are_dates_equal($start, $end),
      'Day vague dates should have the same date for the start and end of the date range'
    );
    return $start->format(Kohana::lang('dates.format'));
  }

  /**
   * Convert a vague date to a string representing a range of days.
   */
  protected static function vague_date_to_days($start, $end) {
    self::check(
      self::is_first_date_first_or_equal($start, $end),
      'Day ranges should be presented in vague dates in the correct sequence. Start was %s, end was %s.', $start, $end);
    return $start->format(Kohana::lang('dates.format')) .
      Kohana::lang('dates.range_separator') .
      $end->format(Kohana::lang('dates.format'));
  }

  /**
   * Convert a vague date to a string representing a fixed month.
   */
  protected static function vague_date_to_month_in_year($start, $end) {
    self::check(
      self::is_month_start($start) &&
      self::is_month_end($end) &&
      self::is_same_month($start, $end),
      'Month dates should be represented by the first day and last day of the same month. Start was %s, end was %s.', $start, $end
    );
    return $start->format(Kohana::lang('dates.format_m_y'));
  }

  /**
   * Convert a vague date to a string representing a range of months.
   */
  protected static function vague_date_to_months_in_year($start, $end) {
    self::check(
      (
        self::is_month_start($start) &&
        self::is_month_end($end) &&
        self::is_first_date_first($start, $end)
      ),
      'Month ranges should be represented by the first day of the first month and last day of the last month. Start was %s, end was %s.', $start, $end
    );
    return $start->format(Kohana::lang('dates.format_m_y')) .
      Kohana::lang('dates.range_separator') .
      $end->format(Kohana::lang('dates.format_m_y'));
  }

  /**
   * Convert a vague date to a string representing a season in a given year.
   */
  protected static function vague_date_to_season_in_year($start, $end) {
    return self::convert_to_season_string($start, $end) . ' ' . $end->format('Y');
  }

  /**
   * Convert a vague date to a string representing a year.
   */
  protected static function vague_date_to_year($start, $end) {
    self::check(
      (
        self::is_year_start($start) &&
        self::is_year_end($end) &&
        self::is_same_year($start, $end)
      ),
      'Years should be represented by the first day and last day of the same year. Start was %s, end was %s.', $start, $end
    );
    return $start->format('Y');
  }

  /**
   * Convert a vague date to a string representing a range of years.
   */
  protected static function vague_date_to_years($start, $end) {
    self::check(
      (
        self::is_year_start($start) &&
        self::is_year_end($end) &&
        self::is_first_date_first($start, $end)
      ),
      'Year ranges should be represented by the first day of the first year to the last day of the last year. Start was %s, end was %s.', $start, $end
    );
    return $start->format('Y') . Kohana::lang('dates.range_separator') . $end->format('Y');
  }

  /**
   * Convert a vague date to a string representing any date after a given year.
   */
  protected static function vague_date_to_year_from($start, $end) {
    self::check(self::is_year_start($start) && $end === NULL,
      'From year date should be represented by just the first day of the first year.');
    return sprintf(Kohana::lang('dates.from_date'), $start->format('Y'));
  }

  /**
   * Convert a vague date to a string representing any date up to and including a given year.
   */
  protected static function vague_date_to_year_to($start, $end) {
    self::check($start === NULL && self::is_year_end($end),
      "To year date should be represented by just the last day of the last year. Start was %s and end was %s.", $start, $end);
    return sprintf(Kohana::lang('dates.to_date'), $end->format('Y'));
  }

  /**
   * Convert a vague date to a string representing a month in an unknown year.
   */
  protected static function vague_date_to_month($start, $end) {
    self::check(
      (
        self::is_month_start($start) &&
        self::is_month_end($end) &&
        self::is_same_month($start, $end)
      ),
      'Month dates should be represented by the start and end of the month.');
    return $start->format('F');
  }

  /**
   * Convert a vague date to a string representing a season in an unknown year.
   */
  protected static function vague_date_to_season($start, $end) {
    return self::convert_to_season_string($start, $end);
  }

  /**
   * Convert a vague date to a string representing an unknown date.
   */
  protected static function vague_date_to_unknown($start, $end) {
    self::check($start === NULL && $end === NULL,
      'Unknown dates should not have a start or end specified');
    return Kohana::lang('dates.unknown');
  }

  /**
   * Convert a vague date to a string representing a century.
   */
  protected static function vague_date_to_century($start, $end) {
    self::check(
      (
        self::is_century_start($start) &&
        self::is_century_end($end) &&
        self::is_same_century($start, $end)
      ),
      'Century dates should be represented by the first day (e.g. 1/1/1901) and
      the last day (e.g. 31/12/2000) of the century');
    return sprintf(Kohana::lang('dates.century', ($start->format('Y') - 1) / 100 + 1));
  }

  /**
   * Convert a vague date to a string representing a range of centuries.
   */
  protected static function vague_date_to_centuries($start, $end) {
    self::check(
      (
        self::is_century_start($start) &&
        self::is_century_end($end) &&
        self::is_first_date_first($start, $end)
      ),
      'Century ranges should be represented by the first day (e.g. 1/1/1701) of
      the first century and the last day (e.g. 31/12/1900) of the last century');
    return sprintf(
      Kohana::lang('dates.century', ($start->format('Y') - 1) / 100 + 1)) .
      Kohana::lang('dates.range_separator') .
      sprintf(Kohana::lang('dates.century', ($end->format('Y') - 1) / 100 + 1)
    );
  }

  /**
   * Convert a vague date to a string representing a date during or after a specified century.
   */
  protected static function vague_date_to_century_from($start, $end) {
    self::check(
      self::is_century_start($start) && $end === NULL,
      'From Century dates should be represented by the first day (e.g. 1/1/1901)
      of the century only'
    );
    return sprintf(
      Kohana::lang('dates.from_date'),
      sprintf(Kohana::lang('dates.century', ($start->format('Y') - 1) / 100 + 1))
    );
  }

  /**
   * Convert a vague date to a string representing a date before or during a specified century.
   */
  protected static function vague_date_to_century_to($start, $end) {
    self::check(
      $start === NULL && self::is_century_end($end),
      'To Century dates should be represented by the last day (e.g. 31/12/2000)
      of the century only'
    );
    return sprintf(
      Kohana::lang('dates.to_date'),
      sprintf(Kohana::lang('dates.century', ($end->format('Y') - 1) / 100 + 1))
    );
  }

  /**
   * Returns true if the supplied date is the first day of the month.
   */
  protected static function is_month_start($date) {
    return ($date->format('j') == 1);
  }

  /**
   * Returns true if the supplied date is the last day of the month.
   */
  protected static function is_month_end($date) {
    // Format t gives us the last day of the given date's month.
    return ($date->format('j') == $date->format('t'));
  }

  /**
   * Returns true if the supplied dates are the same.
   *
   * Early versions of PHP5.2 do not have valid binary comparison functions.
   */
  protected static function are_dates_equal($date1, $date2) {
    return (!strcmp($date1->format('Ymd'), $date2->format('Ymd')));
  }

  /**
   * Returns true if the first supplied date is before second.
   *
   * Early versions of PHP5.2 do not have valid binary comparison functions.
   */
  protected static function is_first_date_first($date1, $date2) {
    return (strcmp($date1->format('Ymd'), $date2->format('Ymd')) < 0);
  }

  /**
   * Returns true if the first supplied date is before second or they are the same.
   *
   * Early versions of PHP5.2 do not have valid binary comparison functions.
   */
  protected static function is_first_date_first_or_equal($date1, $date2) {
    return $date1 == $date2 || (strcmp($date1->format('Ymd'), $date2->format('Ymd')) < 0);
  }

  /**
   * Returns true if the supplied dates are in the same month.
   */
  protected static function is_same_month($date1, $date2) {
    return ($date1->format('m') == $date2->format('m'));
  }

  /**
   * Returns true if the supplied date is the first day of the year.
   */
  protected static function is_year_start($date) {
    return ($date->format('j') == 1 && $date->format('m') == 1);
  }

  /**
   * Returns true if the supplied date is the last day of the year.
   */
  protected static function is_year_end($date) {
    return ($date->format('j') == 31 && $date->format('m') == 12);
  }

  /**
   * Returns true if the supplied dates are in the same year.
   */
  protected static function is_same_year($date1, $date2) {
    return ($date1->format('Y') == $date2->format('Y'));
  }

  /**
   * Returns true if the supplied date is the first day of the century.
   *
   * Century starts on 1/1/nn01 (compatible with postgresql).
   */
  protected static function is_century_start($date) {
    return (
      $date->format('j') == 1 &&
      $date->format('m') == 1 &&
      $date->format('y') == 1
    );
  }

  /**
   * Returns true if the supplied date is the last day of the century.
   *
   * Century ends on 31/12/nn00 (compatible with postgresql).
   */
  protected static function is_century_end($date) {
    return (
      $date->format('j') == 31 &&
      $date->format('m') == 12 &&
      $date->format('y') == 0
    );
  }

  /**
   * Returns true if the supplied dates are in the same century.
   *
   * A century runs from e.g. 1/1/1901 to 31/12/2000.
   */
  protected static function is_same_century($start, $end) {
    return floor(($start->format('Y') - 1) / 100) == floor(($end->format('Y') - 1) / 100);
  }

  /**
   * Retrieve the string that describes a season (spring, summer, autumn, winter)
   * for a start and end date.
   */
  protected static function convert_to_season_string($start, $end) {
    self::check(self::is_month_start($start) && self::is_month_end($end),
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
  protected static function validate($vagueDate) {
    $start = $vagueDate[0];
    $end = $vagueDate[1];
    $type = $vagueDate[2];

    if ($end < $start && !is_null($end)) {
      // End date must be after start date.
      return FALSE;
    }
    else {
      return $vagueDate;
    }
  }

  /**
   * Tests that a check passed, and if not throws an exception containing the
   * message. Replacements in the message can be supplied as additional string
   * parameters, with %s used in the message. The replacements can also be null
   * or datetime objects which are then converted to strings.
   */
  protected static function check($pass, $message) {
    if (!$pass) {
      $args = func_get_args();
      // Any args after the message are string format inputs for the message.
      unset($args[0]);
      unset($args[1]);
      $inputs = [];
      foreach ($args as $arg) {
        kohana::log('debug', 'arg ' . gettype($arg));
        if (gettype($arg) == 'object') {
          $inputs[] = $arg->format(Kohana::lang('dates.format'));
        }
        elseif (gettype($arg) === 'NULL') {
          $inputs[] = 'null';
        }
        else {
          $inputs[] = $arg;
        }
      }
      throw new InvalidVagueDateException(vsprintf($message, $inputs));
    }
  }

}
