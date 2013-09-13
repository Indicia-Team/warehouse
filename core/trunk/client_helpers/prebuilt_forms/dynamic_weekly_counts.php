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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */
 
require_once('dynamic_sample_occurrence.php');

/**
 * A input form with a grid for entering species counts against a grid of weeks, species names. 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_dynamic_weekly_counts extends iform_dynamic_sample_occurrence {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_dynamic_weekly_counts_definition() {
    return array(
      'title'=>'Weekly counts',
      'category' => 'General Purpose Data Entry Forms',
      'description' => 'A dynamic form which supports a grid of species counts per week.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {   
    $r = array_merge(
      parent::get_parameters(),
      array(
        array(
            'fieldname'=>'season_start',
            'label'=>'Season Start',
            'helpText'=>'Date which always falls in the first week of the recording season, in ddmm format.',
            'type'=>'text_input',
            'default'=>'2803',
            'group'=>'Weekly Counts'
        ),
        array(
            'fieldname'=>'weekday',
            'label'=>'Start week on ',
            'helpText'=>'Day of week that the recording week starts on.',
            'type'=>'select',
            'default'=>'Monday',
            'options' => array(
              'Sunday'=>lang::get('Sunday'),
              'Monday'=>lang::get('Monday'),
              'Tuesday'=>lang::get('Tuesday'),
              'Wednesday'=>lang::get('Wednesday'),
              'Thursday'=>lang::get('Thursday'),
              'Friday'=>lang::get('Friday'),
              'Saturday'=>lang::get('Saturday')
            ),
            'group'=>'Weekly Counts'
        ),
        array(
            'fieldname'=>'weeks',
            'label'=>'Weeks',
            'helpText'=>'Number of weeks in the recording season.',
            'type'=>'text_input',
            'default'=>'26',
            'group'=>'Weekly Counts'
        ),
        array(
            'fieldname'=>'headings',
            'label'=>'Grid heading row formats',
            'helpText'=>'Formats for each grid heading row. Specify a comma separated list of format specifiers, one per heading row required. ' .
                'A format specifier can contain "week" to output the week number, "start" followed by a PHP date format character to output the date or part of the '.
                'date at the start of the week, or "end" followed by a date format character to output the date or part of the date at the end of the week. Values are '.
                'automatically only output when there is a change from the previous column.',
            'type'=>'text_input',
            'default'=>'week,startY,startM,startd-endd',
            'group'=>'Weekly Counts'
        ),
      )
    );
    foreach ($r as $idx => $param) {
      if ($param['fieldname']==='extra_list_id')
        unset($r[$idx]);
    }
    return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values. 
   *
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    
  }
  
  protected static function get_control_weeklycountsgrid($auth, $args, $tabAlias, $options) {
    $startDate=self::getStartDate($args);
    $r = '<table id="weekly-counts-grid">';
    $r .= '<thead>';
    $currentDate=new DateTime();
    $currentDate->setTimestamp($startDate);
    $headingFormats=explode(',', $args['headings']);
    // array to capture multiple header rows of th elements.
    $ths=array_fill(0, count($headingFormats), array(''));
    $lastHeadings=array_fill(0, count($headingFormats), '');
    for ($i=1; $i<=$args['weeks']; $i++) {
      $weekEnd = clone $currentDate;
      date_add($weekEnd, date_interval_create_from_date_string('6 days'));
      foreach ($headingFormats as $idx => $format) {
        preg_match_all('/[a-zA-Z]+/', $format, $matches);
        if (!empty($matches)) {
          foreach($matches[0] as $token) {
            if ($token==='week') 
              $format = str_replace($token, $i, $format);
            else {
              if (substr($token, 0, 3)==='end') {
                $dateFormat=preg_replace('/^end/', '', $token);
                $useDate=$weekEnd;
              } else {
                $dateFormat=preg_replace('/^start/', '', $token);
                $useDate=$currentDate;
              }
              $format = str_replace($token, $useDate->format($dateFormat), $format);
              if ($idx===3 && $i===1) drupal_set_message($token);
            }
          }          
        }
        if (count($ths[$idx])===0 || $format!==$lastHeadings[$idx]) {
          $ths[$idx][]=$format;
          $lastHeadings[$idx]=$format;
        } else
          $ths[$idx][]='';        
      }
      date_add($currentDate, date_interval_create_from_date_string('1 week'));
    }
    foreach ($headingFormats as $idx=>$format) 
      $r .= '<tr><th>' . implode('</th><th>', $ths[$idx]) . '</th></tr>';
    $r .= '</thead>';
    $r .= '<tbody>';
    $r .= self::speciesRows($args, $auth);
    $r .= '</tbody></table>';
    return $r;
  }
  
  /**
   * Retrieves the start date for the series of weeks. Uses this year's season, unless before the 
   * start in which case it returns last year's season.
   */
  private static function getStartDate($args) {
    $now = getdate();
    $startDate = self::getStartDateForYear($args, $now['year']);
    // if before start of season, may as well show last year's form.
    if (time()<$startDate)
      $startDate = self::getStartDateForYear($args, $now['year']-1);
    return $startDate;
  }
      
  /**
   * Retrieves the start date for the series of weeks, given a year of the season start.
   * @param array $args Form arguments array, containing start setting (ddmm format) and optional weekday (full name day of week).
   * @return timestamp Start date
   */
  private static function getStartDateForYear($args, $yr) {
    $day = substr($args['start'], 0, 2);
    $month = substr($args['start'], 2, 2);
    $proposedStart=mkTime(0, 0, 0, $month, $day, $yr);
    if ($args['weekday']) {
      $dateArr = getdate($proposedStart);
      if (strtolower($dateArr['weekday'])!==strtolower($args['weekday']))
        $proposedStart=strtotime('Last '.$args['weekday'], $proposedStart);
    }
    return $proposedStart;
  }
  
  private static function speciesRows($args, $auth) {
    $speciesList = data_entry_helper::get_population_data(array(
      'table'=>'cache_taxa_taxon_list',
      'extraParams' => array('taxon_list_id'=>$args['list_id'], 'preferred'=>'t') + $auth['read']
    ));
    $r = '';
    foreach ($speciesList as $species) {
      $r .= '<tr><td>' . $species['default_common_name'] . '</td>';
      for ($i=0; $i<$args['weeks']; $i++) {
        $r .= "<td><input type=\"text\" name=\"count-{$species[id]}-{$i}\"/></td>";
      }
      $r .= '</tr>';
    }
    return $r;
  }

}
