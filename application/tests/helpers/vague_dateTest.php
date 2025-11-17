<?php

use PHPUnit\Framework\TestCase;

class Helper_Vague_Date_Test extends TestCase {

  /*****************************
   *  Vague date to string tests
   *****************************
   *
   * The following vague date types exist
   * D  Date
   * DD Date range
   * O  Month
   * OO Month range
   * P  Season
   * Y  Year
   * YY Year range
   * Y- Open ended year range
   * -Y Open start year range
   * M  Month only
   * S  Season only
   * U  Unknown
   * C  Century
   * CC Century range
   * C- Open ended century range
   * -C Up to century
   **/

  /**
   *
   * Each element of the returned array is a test.
   * The index of each element is the test name.
   * Each element is itself an array consisting of, in order,
   *  - the start_date of the vague date to convert
   *  - the end_date of the vague date to convert
   *  - the date_type of the vague date to convert
   *  - the expected date string.
   */
  public function provideVagueDateToString() {
    return [
      'Date 2001-06-15' => ['2001-06-15', '2001-06-15', 'D', '15/06/2001'],
      'Date 2004-02-29' => ['2004-02-29', '2004-02-29', 'D', '29/02/2004'],
      'Date 1970-01-01' => ['1970-01-01', '1970-01-01', 'D', '01/01/1970'],
      'Date 1900-12-31' => ['1900-12-31', '1900-12-31', 'D', '31/12/1900'],
      'Date 1800-01-01' => ['1800-01-01', '1800-01-01', 'D', '01/01/1800'],
      'Date range 2001-03-28 to 2001-03-29' => ['2001-03-28', '2001-03-29', 'DD', '28/03/2001 to 29/03/2001'],
      'Month 2001-03' => ['2001-03-01', '2001-03-31', 'O', '03/2001'],
      'Month 2004-02' => ['2004-02-01', '2004-02-29', 'O', '02/2004'],
      'Month 1900-01' => ['1900-01-01', '1900-01-31', 'O', '01/1900'],
      'Month 1800-01' => ['1800-01-01', '1800-01-31', 'O', '01/1800'],
      'Month range 2001-03 to 2001-07' => ['2001-03-01', '2001-07-31', 'OO', '03/2001 to 07/2001'],
      'Season Winter 2010' => ['2009-12-01', '2010-02-28', 'P', 'Winter 2010'],
      'Season Spring 2010' => ['2010-03-01', '2010-05-31', 'P', 'Spring 2010'],
      'Season Summer 2010' => ['2010-06-01', '2010-08-31', 'P', 'Summer 2010'],
      'Season Autumn 2010' => ['2010-09-01', '2010-11-30', 'P', 'Autumn 2010'],
      'Year 2001' => ['2001-01-01', '2001-12-31', 'Y', '2001'],
      'Year 1970' => ['1970-01-01', '1970-12-31', 'Y', '1970'],
      'Year 1900' => ['1900-01-01', '1900-12-31', 'Y', '1900'],
      'Year 1800' => ['1800-01-01', '1800-12-31', 'Y', '1800'],
      'Year range 2001 to 2005' => ['2001-01-01', '2005-12-31', 'YY', '2001 to 2005'],
      'Open ended year range From 2001' => ['2001-01-01', '', 'Y-', 'From 2001'],
      'Open ended year range From 1970' => ['1970-01-01', '', 'Y-', 'From 1970'],
      'Open ended year range From 1900' => ['1900-01-01', '', 'Y-', 'From 1900'],
      'Open ended year range From 1800' => ['1800-01-01', '', 'Y-', 'From 1800'],
      'Open start year range To 2001' => ['', '2001-12-31', '-Y', 'To 2001'],
      'Open start year range To 1970' => ['', '1970-12-31', '-Y', 'To 1970'],
      'Open start year range To 1900' => ['', '1900-12-31', '-Y', 'To 1900'],
      'Open start year range To 1800' => ['', '1800-12-31', '-Y', 'To 1800'],
      'Month only March' => ['2001-03-01', '2001-03-31', 'M', 'March'],
      'Season only Winter' => ['2009-12-01', '2010-02-28', 'S', 'Winter'],
      'Season only Spring' => ['2010-03-01', '2010-05-31', 'S', 'Spring'],
      'Season only Summer' => ['2010-06-01', '2010-08-31', 'S', 'Summer'],
      'Season only Autumn' => ['2010-09-01', '2010-11-30', 'S', 'Autumn'],
      'Unknown' => ['', '', 'U', 'Unknown'],
      'Century 20' => ['1901-01-01', '2000-12-31', 'C', '20c'],
      'Century range 17 to 19' => ['1601-01-01', '1900-12-31', 'CC', '17c to 19c'],
      'Open ended century range from 19' => ['1801-01-01', '', 'C-', 'From 19c'],
      'Open start century range to 20' => ['', '2000-12-31', '-C', 'To 20c'],
    ];
  }

  /**
   * @dataProvider provideVagueDateToString
   */
  public function testVagueDateToString($from, $to, $type, $expected) {
    $fromDate = $from ? new DateTime($from) : NULL;
    $toDate = $to ? new DateTime($to) : NULL;
    $vd = array($fromDate, $toDate, $type);
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals($expected, $s, 'Failed converting vague date (dates) to ' . $expected);
    // Test using strings rather than date objects.
    $fromStr = $from ? $from : '';
    $toStr = $to ? $to : '';
    $vd = array($fromStr, $toStr, $type);
    $s = vague_date::vague_date_to_string($vd);
    $this->assertEquals($expected, $s, 'Failed converting vague date (strings) to ' . $expected);
  }

  /*****************************
   *  String to vague date tests
   *****************************
   *
   * The following date formats are valid
   *
   * 1997-08-02               '%Y-%m-%d'
   * 02/08/1997               '%d/%m/%Y'
   * 02/08/97                 '%d/%m/%y'
   * 02.08.1997               '%d.%m.%Y'
   * 02.08.97                 '%d.%m.%y'
   * Monday  2 August 1997   '%A %e %B %Y'
   * Mon  2 August 1997      '%a %e %B %Y'
   * Monday  2 Aug 1997       '%A %e %b %Y'
   * Mon  2 Aug 1997          '%a %e %b %Y'
   * Monday  2 August 97     '%A %e %B %y'
   * Mon  2 August 97        '%a %e %B %y'
   * Monday  2 Aug 97         '%A %e %b %y'
   * Mon  2 Aug 97            '%a %e %b %y'
   * Monday  2 August        '%A %e %B'
   * Mon  2 August           '%a %e %B'
   * Monday  2 Aug            '%A %e %b'
   * Mon  2 Aug               '%a %e %b'
   *  2 August 1997          '%e %B %Y'
   *  2 Aug 1997              '%e %b %Y'
   *  2 August 97            '%e %B %y'
   *  2 Aug 97                '%e %b %y'
   * 08/02/97                 '%m/%d/%y'
   *
   * The following month formats are valid
   *
   * 1998-06    '%Y-%m'
   * 06/1998    '%m/%Y'
   * 06/98      '%m/%y'
   * June 1998  '%B %Y'
   * Jun 1998   '%b %Y'
   * June 98    '%B %y'
   * Jun 98     '%b %y'
   *
   * The following year formats are valid
   *
   * 1998       '%Y'
   * 98         '%y'
   *
   * The following season formats are valid
   *
   * Autumn 2008  '%K %Y'
   * Autumn 08    '%K %y'
   *
   * The following month only formats are valid
   *
   * October    '%B'
   * Oct        '%b'
   *
   * The following season only formats are valid
   *
   * Autumn     '%K'
   *
   *
   * The following century formats are valid
   * 18c        '%C'
   *
   * The following date range strings are valid
   *
   * date to date
   * date - date
   * date-date
   * month to month
   * month - month
   * month-month
   * year to year
   * year - year
   * (year-year not permitted as conflicts with mm-yy format)
   * to|pre|before year|century
   * - year|century
   * from|after year|century
   * year|century -


   **/

  /**
   *
   * Each element of the returned array is a test.
   * The index of each element is the test name.
   * Each element is itself an array consisting of, in order,
   *  - the date string to convert
   *  - the expected start_date of the vague date representation
   *  - the expected end_date of the vague date representation
   *  - the expected date_type of the vague date representation
   *
   */
  public function provideStringToVagueDate() {
    $year = date('Y');
    $lastYear = $year - 1;
    $lastDayInFeb = date('L') === '1' ? '29' : '28';
    return [
      'Date 1997-08-02' => ['1997-08-02', '1997-08-02', '1997-08-02', 'D'],
      'Date 02/08/1997' => ['02/08/1997', '1997-08-02', '1997-08-02', 'D'],
      'Date 02/08/97'   => ['02/08/97', '1997-08-02', '1997-08-02', 'D'],
      'Date 02.08.1997' => ['02.08.1997', '1997-08-02', '1997-08-02', 'D'],
      'Date 02.08.97  ' => ['02.08.97', '1997-08-02', '1997-08-02', 'D'],
      'Date Monday  2 August 1997' => ['Monday  2 August 1997', '1997-08-02', '1997-08-02', 'D'],
      'Date Mon  2 August 1997' => ['Mon  2 August 1997', '1997-08-02', '1997-08-02', 'D'],
      'Date Monday  2 Aug 1997' => ['Monday  2 Aug 1997', '1997-08-02', '1997-08-02', 'D'],
      'Date Mon  2 Aug 1997' => ['Mon  2 Aug 1997', '1997-08-02', '1997-08-02', 'D'],
      'Date Monday  2 August 97' => ['Monday  2 August 97', '1997-08-02', '1997-08-02', 'D'],
      'Date Mon  2 August 97' => ['Mon  2 August 97', '1997-08-02', '1997-08-02', 'D'],
      'Date Monday  2 Aug 97' => ['Monday  2 Aug 97', '1997-08-02', '1997-08-02', 'D'],
      'Date Mon  2 Aug 97' => ['Mon  2 Aug 97', '1997-08-02', '1997-08-02', 'D'],
      'Date 2 August 1997' => ['2 August 1997', '1997-08-02', '1997-08-02', 'D'],
      'Date 2 Aug 1997' => ['2 Aug 1997', '1997-08-02', '1997-08-02', 'D'],
      'Date 2 August 97' => ['2 August 97', '1997-08-02', '1997-08-02', 'D'],
      'Date 2 Aug 97' => ['2 Aug 97', '1997-08-02', '1997-08-02', 'D'],
      'Date range 28/03/2001 - 28/03/2001' => ['28/03/2001 - 28/03/2001', '2001-03-28', '2001-03-28', 'DD'],
      'Date range 26/03/2008 - 26/03/2008' => ['26/03/2008 - 26/03/2008', '2008-03-26', '2008-03-26', 'DD'],
      'Date range 28/03/1999 - 14/11/2007' => ['28/03/1999 - 14/11/2007', '1999-03-28', '2007-11-14', 'DD'],
      'Date range 28/03/1999 to 14/11/2007' => ['28/03/1999 to 14/11/2007', '1999-03-28', '2007-11-14', 'DD'],
      'Date range 28/03/1999-14/11/2007' => ['28/03/1999-14/11/2007', '1999-03-28', '2007-11-14', 'DD'],
      'Date range 28.03.1999-14.11.2007' => ['28.03.1999-14.11.2007', '1999-03-28', '2007-11-14', 'DD'],
      'Date range 1999-03-28 - 2007-11-14' => ['1999-03-28 - 2007-11-14', '1999-03-28', '2007-11-14', 'DD'],
      'Month 1998-06' => ['1998-06', '1998-06-01', '1998-06-30', 'O'],
      'Month 06/98' => ['06/98', '1998-06-01', '1998-06-30', 'O'],
      'Month June 1998' => ['June 1998', '1998-06-01', '1998-06-30', 'O'],
      'Month Jun 1998' => ['Jun 1998', '1998-06-01', '1998-06-30', 'O'],
      'Month June 98' => ['June 98', '1998-06-01', '1998-06-30', 'O'],
      'Month Jun 98' => ['Jun 98', '1998-06-01', '1998-06-30', 'O'],
      'Month Feb 97' => ['Feb 97', '1997-02-01', '1997-02-28', 'O'],
      'Month January 2013' => ['January 2013', '2013-01-01', '2013-01-31', 'O'],
      'Month February 2013' => ['February 2013', '2013-02-01', '2013-02-28', 'O'],
      'Month March 2013' => ['March 2013', '2013-03-01', '2013-03-31', 'O'],
      'Month April 2013' => ['April 2013', '2013-04-01', '2013-04-30', 'O'],
      'Month May 2013' => ['May 2013', '2013-05-01', '2013-05-31', 'O'],
      'Month June 2013' => ['June 2013', '2013-06-01', '2013-06-30', 'O'],
      'Month July 2013' => ['July 2013', '2013-07-01', '2013-07-31', 'O'],
      'Month August 2013' => ['August 2013', '2013-08-01', '2013-08-31', 'O'],
      'Month September 2013' => ['September 2013', '2013-09-01', '2013-09-30', 'O'],
      'Month October 2013' => ['October 2013', '2013-10-01', '2013-10-31', 'O'],
      'Month November 2013' => ['November 2013', '2013-11-01', '2013-11-30', 'O'],
      'Month December 2013' => ['December 2013', '2013-12-01', '2013-12-31', 'O'],
      'Month 01/2013' => ['01/2013', '2013-01-01', '2013-01-31', 'O'],
      'Month Oct 92' => ['Oct 92', '1992-10-01', '1992-10-31', 'O'],
      'Month Oct 02' => ['Oct 02', '2002-10-01', '2002-10-31', 'O'],
      'Month Oct 12' => ['Oct 12', '2012-10-01', '2012-10-31', 'O'],
      'Month range 1998-06 - 1998-08' => ['1998-06 - 1998-08', '1998-06-01', '1998-08-31', 'OO'],
      'Month range 06/1998 - 08/1998' => ['06/1998 - 08/1998', '1998-06-01', '1998-08-31', 'OO'],
      'Month range 06/1998 to 08/1998' => ['06/1998 to 08/1998', '1998-06-01', '1998-08-31', 'OO'],
      'Month range 06/1998-08/1998' => ['06/1998-08/1998', '1998-06-01', '1998-08-31', 'OO'],
      'Month range 06.1998-08.1998' => ['06/1998 to 08/1998', '1998-06-01', '1998-08-31', 'OO'],
      'Year 2001' => ['2001', '2001-01-01', '2001-12-31', 'Y'],
      'Year 1964' => ['1964', '1964-01-01', '1964-12-31', 'Y'],
      'Year 1900' => ['1900', '1900-01-01', '1900-12-31', 'Y'],
      'Year 1700' => ['1700', '1700-01-01', '1700-12-31', 'Y'],
      'Year 01' => ['01', '2001-01-01', '2001-12-31', 'Y'],
      'Year 12' => ['12', '2012-01-01', '2012-12-31', 'Y'],
      'Year 64' => ['64', '1964-01-01', '1964-12-31', 'Y'],
      'Year range 2001 - 2005' => ['2001 - 2005', '2001-01-01', '2005-12-31', 'YY'],
      'Year range 2001 to 2005' => ['2001 - 2005', '2001-01-01', '2005-12-31', 'YY'],
      'Year range 2001-2005' => ['2001-2005', '2001-01-01', '2005-12-31', 'YY'],
      'Open ended year range From 2001' => ['From 2001', '2001-01-01', NULL, 'Y-'],
      'Open ended year range After 2001' => ['After 2001', '2001-01-01', NULL, 'Y-'],
      'Open ended year range 2001-' => ['2001-', '2001-01-01', NULL, 'Y-'],
      'Open start year range To 2001' => ['To 2001', NULL, '2001-12-31', '-Y'],
      'Open start year range to 2001' => ['to 2001', NULL, '2001-12-31', '-Y'],
      'Open start year range Pre 2001' => ['Pre 2001', NULL, '2001-12-31', '-Y'],
      'Open start year range Before 2001' => ['Before 2001', NULL, '2001-12-31', '-Y'],
      'Open start year range -2001' => ['-2001', NULL, '2001-12-31', '-Y'],
      'Open start year range -1899' => ['-1899', NULL, '1899-12-31', '-Y'],
      'Season Spring 2012' => ['Spring 2012', '2012-03-01', '2012-05-31', 'P'],
      'Season Summer 2012' => ['Summer 2012', '2012-06-01', '2012-08-31', 'P'],
      'Season Autumn 2012' => ['Autumn 2012', '2012-09-01', '2012-11-30', 'P'],
      'Season Winter 2012' => ['Winter 2012', '2011-12-01', '2012-02-29', 'P'],
      'Season Winter 2013' => ['Winter 2013', '2012-12-01', '2013-02-28', 'P'],
      'Season Autumn 12' => ['Autumn 12', '2012-09-01', '2012-11-30', 'P'],
      'Season Autumn 02' => ['Autumn 02', '2002-09-01', '2002-11-30', 'P'],
      'Season Autumn 92' => ['Autumn 92', '1992-09-01', '1992-11-30', 'P'],
      // Month only and season only years are always for the current year.
      'Month only March' => ['March', "$year-03-01", "$year-03-31", 'M'],
      'Season only Winter' => ['Winter', "$lastYear-12-01", "$year-02-$lastDayInFeb", 'S'],
      'Season only Spring' => ['Spring', "$year-03-01", "$year-05-31", 'S'],
      'Season only Summer' => ['Summer', "$year-06-01", "$year-08-31", 'S'],
      'Season only Autumn' => ['Autumn', "$year-09-01", "$year-11-30", 'S'],
      'Unknown' => ['Unknown', NULL, NULL, 'U'],
      'Unknown U' => ['U', NULL, NULL, 'U'],
      'Century 20' => ['20c', '1901-01-01', '2000-12-31', 'C'],
      'Century range 17 to 19' => ['17c to 19c', '1601-01-01', '1900-12-31', 'CC'],
      'Century range 17 - 19' => ['17c - 19c', '1601-01-01', '1900-12-31', 'CC'],
      'Open ended century range 19-' => ['19c-', '1801-01-01', '', 'C-'],
      'Open start century range -20' => ['-20c', '', '2000-12-31', '-C'],
    ];
  }

  /**
   * @dataProvider provideStringToVagueDate
   */
  public function testStringToVagueDate($string, $from, $to, $type) {
    $vd = vague_date::string_to_vague_date($string);
    $this->assertEquals($from, $vd[0]);
    $this->assertEquals($to, $vd[1]);
    $this->assertEquals($type, $vd[2]);
  }

  public function provideBadStringToVagueDate() {
    return [
      'Date 29/02/2001' => ['29/02/2001'],
      'Date 34/3/73' => ['34/3/73'],
      'Date 34 march 73' => ['34 march 73'],
      'Date 100/13/2001' => ['100/13/2001'],
      'Date 06/1992-1996' => ['06/1992-1996'],
      'Date Apr 2019 to Dec 2017' => ['Apr 2019 to Dec 2017'],
      'Date 04/2019 to 12/2017' => ['04/2019 to 12/2017'],
      'Date 07/0200 to 06/2001' => ['07/0200 to 06/2001'],
    ];
  }

  /**
   * @dataProvider provideBadStringToVagueDate
   */
  public function testBadStringToVagueDate($string) {
    $vd = vague_date::string_to_vague_date($string);
    $this->assertFalse($vd);
  }

}
