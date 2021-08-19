<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit test class for verification rule module.
 */
class VerificationRuleTest extends TestCase {

  /**
   * Ensure id difficulty test file is parsed correctly.
   */
  public function test_parser_identification_difficulty() {
    $file = 'modules/data_cleaner/tests/rule_files/id_difficulty.txt';
    $filecontent = file_get_contents($file);
    $parsed = data_cleaner::parseTestFile($filecontent);
    $expected = [
      'metadata' => [
        'testtype' => 'IdentificationDifficulty',
        'organisation' => 'Identification difficulty organisation',
        'lastchanged' => '20210816',
      ],
      'ini' => [
        '0' => [
          '1' => 'Identification difficulty message 1.',
          '2' => 'Identification difficulty message 2, with comma.',
        ],
      ],
      'data' => [
        '0' => [
          'nbnsys0000007825' => '1',
          'nbnsys0000007826' => '2',
        ],
      ],
    ];
    $this->assertEquals($expected, $parsed);
  }

  /**
   * Ensure ancillary species test file is parsed correctly.
   */
  public function test_parse_ancillary_species() {
    $file = 'modules/data_cleaner/tests/rule_files/ancillary_species.txt';
    $filecontent = file_get_contents($file);
    $parsed = data_cleaner::parseTestFile($filecontent);
    $expected = [
      'metadata' => [
        'testtype' => 'AncillarySpecies',
        'group' => 'Ancillary species rule group',
        'shortname' => 'Ancillary species rule name',
        'description' => 'Ancillary species rule description',
        'errormsg' => 'Ancillary species error message',
        'reverserule' => 'True',
        'lastchanged' => '20210816',
      ],
      'ini' => [
        '0' => [
          '1' => 'Ancillary species message 1.',
          '2' => 'Ancillary species message 2, with comma.',
        ],
      ],
      'data' => [
        '0' => [
          'nbnsys0000007848' => '1',
          'nbnsys0100004993' => '2',
        ],
      ],
    ];
    $this->assertEquals($expected, $parsed);
  }

  /**
   * Ensure period test file is parsed correctly.
   */
  public function test_parse_period() {
    $file = 'modules/data_cleaner/tests/rule_files/period.txt';
    $filecontent = file_get_contents($file);
    $parsed = data_cleaner::parseTestFile($filecontent);
    $expected = [
      'metadata' => [
        'testtype' => 'Period',
        'group' => 'Period rule group',
        'shortname' => 'Period rule name',
        'description' => 'Period rule description',
        'errormsg' => 'Period rule error message',
        'tvk' => 'NBNSYS0100005964',
        'startdate' => '',
        'enddate' => '19581231',
        'lastchanged' => '20210816',
      ],
    ];
    $this->assertEquals($expected, $parsed);
  }

  /**
   * Ensure period within year test file is parsed correctly.
   */
  public function test_parse_period_within_year() {
    $file = 'modules/data_cleaner/tests/rule_files/period_within_year.txt';
    $filecontent = file_get_contents($file);
    $parsed = data_cleaner::parseTestFile($filecontent);
    $expected = [
      'metadata' => [
        'testtype' => 'PeriodWithinYear',
        'group' => 'Period within year rule group',
        'shortname' => 'Period within year rule name',
        'description' => 'Period within year rule description',
        'errormsg' => 'Period within year rule error message',
        'tvk' => 'NBNSYS0100027431',
        'startdate' => '0501',
        'enddate' => '0930',
        'lastchanged' => '20210816',
      ],
    ];
    $this->assertEquals($expected, $parsed);
  }

  /**
   * Ensure period within year test file with stage is parsed correctly.
   */
  public function test_parse_period_within_year_with_stage() {
    $file = 'modules/data_cleaner/tests/rule_files/period_within_year_with_stage.txt';
    $filecontent = file_get_contents($file);
    $parsed = data_cleaner::parseTestFile($filecontent);
    $expected = [
      'metadata' => [
        'testtype' => 'PeriodWithinYear',
        'group' => 'Period within year rule group',
        'shortname' => 'Period within year rule name',
        'description' => 'Period within year rule description',
        'errormsg' => 'Period within year rule error message',
        'tvk' => 'NHMSYS0000504013',
        'datafieldname' => 'Stage',
        'lastchanged' => '20210816',
      ],
      'data' => [
        '0' => [
          'stage' => 'Larva',
          'startdate' => '0515',
          'enddate' => '0731',
        ],
        '1' => [
          'stage' => 'Pupa',
          'startdate' => '0701',
          'enddate' => '0515',
        ],
        '2' => [
          'stage' => 'Adult',
          'startdate' => '0501',
          'enddate' => '0630',
        ],
      ],
    ];
    $this->assertEquals($expected, $parsed);
  }

  /**
   * Ensure period within year test file with stage is parsed correctly.
   */
  public function test_parse_without_polygon() {
    $file = 'modules/data_cleaner/tests/rule_files/without_polygon.txt';
    $filecontent = file_get_contents($file);
    $parsed = data_cleaner::parseTestFile($filecontent);
    $expected = [
      'metadata' => [
        'testtype' => 'WithoutPolygon',
        'group' => 'Without polygon rule group',
        'shortname' => 'Without polygon rule name',
        'description' => 'Without polygon rule description',
        'errormsg' => 'Without polygon rule error message',
        'datafieldname' => 'Species',
        'datarecordid' => 'NBNSYS0000007908',
        'lastchanged' => '20210816',
      ],
      '10km_gb' => [
        '0' => [
          'nh80' => '-',
          'nh91' => '-',
          'nh95' => '-',
        ],
      ],
    ];
    $this->assertEquals($expected, $parsed);
  }

}
