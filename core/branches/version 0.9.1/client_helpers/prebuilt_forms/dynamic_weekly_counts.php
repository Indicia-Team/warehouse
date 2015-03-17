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
  
  private static $existingSamples=array();
  private static $sampleIdsByDate=array();
  
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
   * Output the weekly counts grid. This integrates with the dynamic form user interface definition - use [weekly_counts_grid] 
   * to output the form.
   * @param array $auth Authorisation tokens
   * @param array $args Form configuration arguments
   * @param string $tabAlias ID of the tab this is loaded onto. Not used.
   * @param array $options Control specific options. Not used.
   */
  protected static function get_control_weeklycountsgrid($auth, $args, $tabAlias, $options) {
    $r = '<table id="weekly-counts-grid">';
    $r .= '<thead>';
    $currentDate=self::getStartDate($args);
    $headingFormats=explode(',', $args['headings']);
    // array to capture multiple header rows of th elements.
    $ths=array_fill(0, count($headingFormats), array(''));
    $lastHeadings=array_fill(0, count($headingFormats), '');
    for ($i=1; $i<=$args['weeks']; $i++) {
      $weekEnd = strtotime('+6 days', $currentDate);
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
              $format = str_replace($token, date($dateFormat, $useDate), $format);
            }
          }          
        }
        if (count($ths[$idx])===0 || $format!==$lastHeadings[$idx]) {
          $ths[$idx][]=$format;
          $lastHeadings[$idx]=$format;
        } else
          $ths[$idx][]='';        
      }
      $currentDate = strtotime('+1 week', $currentDate);
    }
    foreach ($headingFormats as $idx=>$format) {
      $r .= '<tr><th>' . implode('</th><th>', $ths[$idx]) . '</th>';
      if ($idx===count($headingFormats)-1)
        $r .= '<th>'.lang::get('Max').'</th><th>'.lang::get('Weeks').'</th>';
      $r .= '</tr>';
    }
    $r .= '</thead>';
    $r .= '<tbody>';
    if (!empty($_GET['sample_id'])) {
      // load existing subsample data
      self::$existingSamples=data_entry_helper::get_population_data(array(
        'table'=>'sample',
        'extraParams'=>$auth['read'] + array('parent_id'=>$_GET['sample_id'], 'view'=>'detail'),
        'nocache'=>true
      ));
      // We want a list of sample IDs, keyed by date for lookup later.      
      foreach (self::$existingSamples as $sample) 
        self::$sampleIdsByDate[strtotime($sample['date_start'])]=$sample['id'];
    }
    $r .= self::sampleAttrRows($args, $auth);
    $r .= self::speciesRows($args, $auth, $tableData);
    $r .= '</tbody></table>';
    if (!empty(self::$sampleIdsByDate)) 
      // store existing sample IDs in form so we can post edits against them
      $r .= '<input type="hidden" name="samples-dates" value="'.htmlspecialchars(json_encode(self::$sampleIdsByDate)).'"/>';
    // JavaScript will populate this for us to post the count data.
    $r .= '<input id="table-data" name="table-data" type="hidden" value="'.htmlspecialchars(json_encode($tableData)).'"/>';
    return $r;
  }
  
  /**
   * Retrieves the start date for the series of weeks. Uses this year's season, unless before the 
   * start in which case it returns last year's season.
   * @param array $args Form configuration arguments
   */
  private static function getStartDate($args) {
    $now = getdate();
    if (!empty($_GET['sample_id']) && preg_match('/^[0-9]+$/', $_GET['sample_id'])) {
      $date=data_entry_helper::$entity_to_load['sample:date_start'];
      preg_match('/(?P<year>[0-9]{4})/', $date, $matches);
      return self::getStartDateForYear($args, $matches['year']);
    }
    if (!empty($_GET['year']) && preg_match('/^[0-9]{4}$/', $_GET['year']))
      return self::getStartDateForYear($args, $_GET['year']);
    $startDate = self::getStartDateForYear($args, $now['year']);
    // if before start of season, may as well show last year's form.
    if (time()<$startDate)
      $startDate = self::getStartDateForYear($args, $now['year']-1);
    return $startDate;
  }
      
  /**
   * Retrieves the start date for the series of weeks, given a year of the season start.
   * @param array $args Form arguments array, containing start setting (ddmm format) and optional weekday (full name day of week).
   * @param int $yr Year
   * @return timestamp Start date
   */
  private static function getStartDateForYear($args, $yr) {
    $day = substr($args['season_start'], 0, 2);
    $month = substr($args['season_start'], 2, 2);
    $proposedStart=mkTime(0, 0, 0, $month, $day, $yr);
    if ($args['weekday']) {
      $dateArr = getdate($proposedStart);
      if (strtolower($dateArr['weekday'])!==strtolower($args['weekday']))
        $proposedStart=strtotime('Last '.$args['weekday'], $proposedStart);
    }
    return $proposedStart;
  }
  
  private static function sampleAttrRows($args, $auth) {
    $sampleAttrs = self::getAttributes($args, $auth);
    $controls=array();
    $controlsExisting=array();
    $captions=array();
    data_entry_helper::species_checklist_prepare_attributes($args, $sampleAttrs, $controls, $controlsExisting, $captions);
    if (!empty($_GET['sample_id'])) {
      // loading existing, so let's retrieve all the subsample data
      $ids = array();
      foreach (self::$existingSamples as $sample) {
        $ids[] = $sample['id'];
      }
      $sampleData=data_entry_helper::get_population_data(array(
        'table'=>'sample_attribute_value',
        'extraParams'=>$auth['read'] + array('sample_id'=>$ids, 'view'=>'list'),
        'nocache'=>true
      ));
    }
    $r = '';
    foreach ($controls as $idx => $control) {
      $r .= "<tr><td><strong>{$captions[$idx]}</strong></td>";
      $weekstart=self::getStartDate($args);
      for ($i=0; $i<$args['weeks']; $i++) {
        if ($weekstart>time())
          $r .= "<td class=\"col-$i\"><span class=\"disabled\"></span></td>";
        else {
          $valId = '';
          $val = null;
          if (!empty(self::$sampleIdsByDate[$weekstart])) {
            $sampleId=self::$sampleIdsByDate[$weekstart];
            foreach ($sampleData as $value) {
              if ($value['sample_id']===$sampleId && $value['sample_attribute_id']===$sampleAttrs[$idx]['attributeId']) {
                $valId = ":$value[id]";
                $val = $value['raw_value'];
              }
            }
          }
          $fieldname = str_replace('smpAttr', "smpAttr$i", $sampleAttrs[$idx]['fieldname']) . $valId;
          $thisCtrl = str_replace('{fieldname}', $fieldname, $control);
          if ($val!==null) {
            // Inject value into existing attribute control.  Approach taken depends on the data type
            if ($sampleAttrs[$idx]['data_type']==='B' && $val==='1')
              // Currently only supporting checkboxes here
              $thisCtrl = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $thisCtrl);
            
          }
          $r .= "<td class=\"col-$i\">$thisCtrl</td>";
        }
        $weekstart=strtotime('+7 days', $weekstart);
      }
      $r .= "</tr>\n";
      
    }
    return $r;
  }
  
  /** 
   * Returns the rows for the species grid.
   * @param array $args Form configuration arguments
   * @param array $auth Authorisation tokens
   * @param array $tableData For any existing data loaded into the grid, returns an array of values keyed by fieldname. 
   * @return string HTML for the list of tr elements to insert.
   */
  private static function speciesRows($args, $auth, &$tableData) {
    $tableData=array();
    if (!empty($_GET['sample_id'])) {      
      $existingValues=data_entry_helper::get_population_data(array(
        'report'=>'library/occurrence_attribute_values/occurrence_attribute_values_list',
        'extraParams'=>$auth['read'] + array('parent_sample_id'=>$_GET['sample_id']),
        'nocache'=>true
      ));
      // want a list of attribute value IDs and values, keyed by sample ID and ttl ID for lookup later.
      $valuesBySampleTtl=array();
      foreach ($existingValues as $value) {
        $valuesBySampleTtl["{$value[sample_id]}|{$value[taxa_taxon_list_id]}"]="{$value[id]}|{$value[occurrence_id]}|{$value[value]}";
      }
    }
    $attrOptions = array(
        'valuetable'=>'occurrence_attribute_value',
        'attrtable'=>'occurrence_attribute',
        'key'=>'occurrence_id',
        'fieldprefix'=>'sc#wk#:#ttlId#:#occId#:occAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id']
    );
    $attributes = data_entry_helper::getAttributes($attrOptions);
    if (count($attributes)!==1)
      throw new exception('There must be a single integer occurrence attribute set up for the survey associated with this form.');
    $attr=array_pop($attributes);
    if (!preg_match('/^[TI]$/', $attr['data_type']))
      throw new exception('The occurrence attribute configured for the survey associated with this form must be an integer.');
    $speciesList = data_entry_helper::get_population_data(array(
      'table'=>'cache_taxa_taxon_list',
      'extraParams' => array('taxon_list_id'=>$args['list_id'], 'preferred'=>'t', 'orderby'=>'taxonomic_sort_order') + $auth['read']
    ));
    foreach ($speciesList as $species) {
      $name=empty($species['default_common_name']) ? '<em>' . $species['preferred_taxon'] . '</em>' : $species['default_common_name'];
      $r .= "<tr><td>$name</td>";
      $weekstart=self::getStartDate($args);
      for ($i=0; $i<$args['weeks']; $i++) {
        $valId='';
        $occId='';
        $val='';
        if (isset(self::$sampleIdsByDate[$weekstart])) {
          $sampleId=self::$sampleIdsByDate[$weekstart];
          if (!empty($valuesBySampleTtl["$sampleId|{$species[id]}"])) {
            $tokens=explode('|', $valuesBySampleTtl["$sampleId|{$species[id]}"]);
            $valId=$tokens[0];
            $occId=$tokens[1];
            $val=$tokens[2];
          }
        }
        $fieldname = str_replace(array('#wk#', '#ttlId#', '#occId#'), array($i, $species['id'], $occId), $attr['fieldname']);
        if ($valId) {
          $fieldname .= ':'.$valId;
          $tableData[$fieldname]=$val;
        }
        if ($weekstart>time())
          $r .= "<td class=\"col-$i\"><span class=\"disabled\"></span></td>";
        else 
          // we don't use name for the inputs, as there are too many to post! JS will store all info in a single JSON hidden input.
          $r .= "<td class=\"col-$i\"><input type=\"text\" class=\"count-input\" id=\"$fieldname\" value=\"$val\"/></td>";
        $weekstart=strtotime('+7 days', $weekstart);
      }
      $r .= '<td class="max"></td><td class="weeks"></td></tr>';
    }
    $r .= '<tr class="species-totals"><td>Number of species</td>';
    for ($i=0; $i<$args['weeks']; $i++) {
      $r .= "<td class=\"col-$i\">0</td>";
    }
    $r .= '</tr>';
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
    $fromDate=self::getStartDate($args);
    $values['sample:date_start']=date('Y-m-d', $fromDate);
    $dateEnd=strtotime('+' . ($args['weeks']*7-1) . ' days', $fromDate);
    // force max date to today to pass validation.
    if ($dateEnd>time()) $dateEnd=time();
    $values['sample:date_end']=date('Y-m-d', $dateEnd);
    $values['sample:date_type']='DD';
    $weekData=array();
    $countValues = json_decode($values['table-data']);
    // existing samples being posted?
    $samplesDates=array();
    if (!empty($values['samples-dates']))
      $samplesDates=json_decode($values['samples-dates'], true);
    unset($values['table-data']);
    unset($values['samples-dates']);
    $parentSample = submission_builder::wrap_with_images($values, 'sample');
    foreach ($countValues as $key=>$value) {
      $tokens=explode(':', $key);
      // consider existing values, or filled in values only
      if (($value!=='' || count($tokens)===6) && preg_match('/^sc([0-9]+):/', $key, $matches)) {
        $weekIdx=$matches[1];
        if (!isset($weekData["week$weekIdx"]))
          $weekData["week$weekIdx"]=array();        
        $datelessKey=preg_replace('/^sc([0-9]+):/', 'sc:', $key);
        $weekData["week$weekIdx"][$datelessKey]=$value;
        $presenceKey=preg_replace('/occAttr:[0-9]+(:[0-9]+)?$/', 'present', $datelessKey);
        $weekData["week$weekIdx"][$presenceKey]=$tokens[1];
      }
    }
    $parentSample['subModels']=array();
    // retrieve any sample data for each week
    $weekSampleData = array();
    foreach ($values as $key => $value) {
      if (preg_match('/^smpAttr(\d+):(.+)/', $key, $matches)) {
        if (!isset($weekSampleData["week$matches[1]"]))
          $weekSampleData["week$matches[1]"]=array();
        $weekSampleData["week$matches[1]"]["smpAttr:$matches[2]"] = $value;
      }
    }
    foreach ($weekData as $week => $data) {
      $weekno=substr($week, 4);
      $weekstart = strtotime('+' . ($weekno) . ' weeks', $fromDate);
      if (isset($samplesDates[$weekstart]))
        $data['sample:id']=$samplesDates[$weekstart];
      $data['sample:date_start']=date('Y-m-d', $weekstart);
      $data['sample:date_end']=date('Y-m-d', strtotime('+6 days', $weekstart));
      $data['sample:date_type']='DD';
      $data['website_id']=$values['website_id'];
      $data['survey_id']=$values['survey_id'];
      $data['entered_sref']=$values['sample:entered_sref'];
      $data['entered_sref_system']=$values['sample:entered_sref_system'];
      $data['geom']=$values['sample:geom'];
      if (isset($weekSampleData["week$weekno"]))
        $data = array_merge($data, $weekSampleData["week$weekno"]);
      $subSampleAndOccs = data_entry_helper::build_sample_occurrences_list_submission($data);
      $parentSample['subModels'][] = array('fkId'=>'parent_id', 'model'=>$subSampleAndOccs);
    }
    return $parentSample;
  }
  

}
