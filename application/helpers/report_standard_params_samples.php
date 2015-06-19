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
 * @package	Core
 * @subpackage Helpers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to provide standardised reporting parameters for samples data
 * reports.
 * Sample reports that support standard parameters MUST have the following tables & aliases in the query:
 *   1) samples as s
 *   2) surveys as su
 *   3) locations as l
 */
class report_standard_params_samples {

  /**
   * Returns a list of the parameter names which have been deprecated and should be converted
   * to newer parameter names. Maintains backwards compatibility with clients that are not
   * running the latest code. Returns an array, with each element being a sub array containing
   * the old and new parameter names. A third optional element in the sub-array can be set to
   * TRUE to enable string quoting in the output.
   * @return array
   */
  public static function getDeprecatedParameters() {
    return array(
      array('location_id', 'location_list'),
      array('survey_id', 'survey_list'),
      array('indexed_location_id', 'indexed_location_list'),
      array('input_form', 'input_form_list', TRUE)
    );
  }

  /**
   * @return array List of parameters that have an associated operation parameter. E.g. along
   * with the sample_id parameter you can supply sample_id='>=' to define the operation
   * to be applied in the filter.
   * @return array
   */
  public static function getOperationParameters() {
    return array(
      'sample_id' => array('datatype'=>'lookup', 'default'=>'', 'display'=>'ID operation',
        'description'=>'Sample ID lookup operation', 'lookup_values'=>'=:is,>=:is at least,<=:is at most'
      ),
      'website_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Website IDs mode',
        'description'=>'Include or exclude the list of websites', 'lookup_values'=>'in:Include,not in:Exclude'
      ),
      'survey_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Survey IDs mode',
        'description'=>'Include or exclude the list of surveys', 'lookup_values'=>'in:Include,not in:Exclude'
      ),
      'input_form_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Input forms mode',
        'description'=>'Include or exclude the list of input forms', 'lookup_values'=>'in:Include,not in:Exclude'
      ),
      'location_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Location IDs mode',
        'description'=>'Include or exclude the list of locations', 'lookup_values'=>'in:Include,not in:Exclude'
      ),
      'indexed_location_list' => array('datatype'=>'lookup', 'default'=>'in', 'display'=>'Indexed location IDs mode',
        'description'=>'Include or exclude the list of indexed locations', 'lookup_values'=>'in:Include,not in:Exclude'
      )
    );
  }

  /**
   * Retrieves the list of standard reporting parameters available for this report type.
   * @return array
   */
  public static function getParameters() {
    return array(
      'idlist' => array('datatype'=>'idlist', 'default'=>'', 'display'=>'List of IDs', 'emptyvalue'=>'', 'fieldname'=>'s.id', 'alias'=>'sample_id',
        'description'=>'Comma separated list of sample IDs to filter to'
      ),
      'searchArea' => array('datatype'=>'geometry', 'default'=>'', 'display'=>'Boundary',
        'description'=>'Boundary to search within',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"st_intersects(s.geom, st_makevalid(st_geomfromtext('#searchArea#',900913)))")
        )
      ),
      'sample_id' => array('datatype'=>'integer', 'default'=>'', 'display'=>'ID',
        'description'=>'Sample ID',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"s.id #sample_id_op# #sample_id#")
        )
      ),
      'location_name' => array('datatype'=>'text', 'default'=>'', 'display'=>'Location name',
        'description'=>'Name of location to filter to (contains search)',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"(s.location_name ilike '%#location_name#%' or l.name ilike '%#location_name#%')")
        )
      ),
      'location_list' => array('datatype'=>'integer[]', 'default'=>'', 'display'=>'Location IDs',
        'description'=>'Comma separated list of location IDs',
        'joins' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"JOIN locations #alias:lfilt# on #alias:lfilt#.id #location_list_op# (#location_list#) and #alias:lfilt#.deleted=false " .
            "and st_intersects(coalesce(#alias:lfilt#.boundary_geom, #alias:lfilt#.centroid_geom), s.geom) " .
            "and not st_touches(coalesce(#alias:lfilt#.boundary_geom, #alias:lfilt#.centroid_geom), s.geom)")
        )
      ),
      'indexed_location_list' => array('datatype'=>'integer[]', 'default'=>'', 'display'=>'Location IDs (indexed)',
        'description'=>'Comma separated list of location IDs, for locations that are indexed using the spatial index builder',
        'joins' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"JOIN index_locations_samples #alias:ilsfilt# on #alias:ilsfilt#.sample_id=s.id and #alias:ilsfilt#.location_id #indexed_location_list_op# (#indexed_location_list#)")
        )
      ),
      'date_from' => array('datatype'=>'date', 'default'=>'', 'display'=>'Date from',
        'description'=>'Date of first sample to include in the output',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"('#date_from#'='Click here' OR s.date_end >= CAST(COALESCE('#date_from#','1500-01-01') as date))")
        )
      ),
      'date_to' => array('datatype'=>'date', 'default'=>'', 'display'=>'Date to',
        'description'=>'Date of last sample to include in the output',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"('#date_to#'='Click here' OR s.date_start <= CAST(COALESCE('#date_to#','1500-01-01') as date))")
        )
      ),
      'date_age' => array('datatype'=>'text', 'default'=>'', 'display'=>'Date from time ago',
        'description'=>'E.g. enter "1 week" or "3 days" to define the how old samples can be before they are dropped from the report.',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"s.date_start>now()-'#date_age#'::interval")
        )
      ),
      'input_date_from' => array('datatype'=>'date', 'default'=>'', 'display'=>'Input date from',
        'description'=>'Input date of first sample to include in the output',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"('#input_date_from#'='Click here' OR s.created_on >= CAST('#input_date_from#' as date))")
        )
      ),
      'input_date_to' => array('datatype'=>'date', 'default'=>'', 'display'=>'Input date to',
        'description'=>'Input date of last sample to include in the output',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"('#input_date_to#'='Click here' OR s.created_on < CAST('#input_date_to#' as date)+'1 day'::interval)")
        )
      ),
      'input_date_age' => array('datatype'=>'text', 'default'=>'', 'display'=>'Input date from time ago',
        'description'=>'E.g. enter "1 week" or "3 days" to define the how long ago samples can be input before they are dropped from the report.',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"s.created_on>now()-'#input_date_age#'::interval")
        )
      ),
      'edited_date_from' => array('datatype'=>'date', 'default'=>'', 'display'=>'Last update date from',
        'description'=>'Last update date of first sample to include in the output',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"('#edited_date_from#'='Click here' OR s.updated_on >= CAST('#edited_date_from#' as date))")
        )
      ),
      'edited_date_to' => array('datatype'=>'date', 'default'=>'', 'display'=>'Last update date to',
        'description'=>'Last update date of last sample to include in the output',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"('#edited_date_to#'='Click here' OR s.updated_on < CAST('#edited_date_to#' as date)+'1 day'::interval)")
        )
      ),
      'edited_date_age' => array('datatype'=>'text', 'default'=>'', 'display'=>'Last update date from time ago',
        'description'=>'E.g. enter "1 week" or "3 days" to define the how long ago samples can be last updated before they are dropped from the report.',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"s.updated_on>now()-'#edited_date_age#'::interval")
        )
      ),
      'verified_date_from' => array('datatype'=>'date', 'default'=>'', 'display'=>'Verification status change date from',
        'description'=>'Verification status change date of first sample to include in the output',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"('#verified_date_from#'='Click here' OR s.verified_on >= CAST('#verified_date_from#' as date))")
        )
      ),
      'verified_date_to' => array('datatype'=>'date', 'default'=>'', 'display'=>'Verification status change date to',
        'description'=>'Verification status change date of last sample to include in the output',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"('#verified_date_to#'='Click here' OR s.verified_on < CAST('#verified_date_to#' as date)+'1 day'::interval)")
        )
      ),
      'verified_date_age' => array('datatype'=>'text', 'default'=>'', 'display'=>'Verification status change date from time ago',
        'description'=>'E.g. enter "1 week" or "3 days" to define the how long ago samples can have last had their status changed before they are dropped from the report.',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"s.verified_on>now()-'#verified_date_age#'::interval")
        )
      ),
      'quality' => array('datatype'=>'lookup', 'default'=>'', 'display'=>'Quality',
        'description'=>'Minimum quality of records to include',
        'lookup_values'=>'V:Accepted records only,P:Not reviewed,!D:Exclude queried or not accepted records,' .
          '!R:Exclude not accepted records,R:Not accepted records only,DR:Queried or not accepted records,all:All records',
        'wheres' => array(
          array('value'=>'V', 'operator'=>'equal', 'sql'=>"s.record_status='V'"),
          array('value'=>'P', 'operator'=>'equal', 'sql'=>"s.record_status = 'C'"),
          array('value'=>'!D', 'operator'=>'equal', 'sql'=>"s.record_status not in ('R','D')"),
          array('value'=>'!R', 'operator'=>'equal', 'sql'=>"s.record_status<>'R'"),
          array('value'=>'R', 'operator'=>'equal', 'sql'=>"s.record_status='R'"),
          array('value'=>'DR', 'operator'=>'equal', 'sql'=>"s.record_status in ('R','D')"),
          // The all filter does not need any SQL
        )
      ),
      'user_id' => array('datatype'=>'integer', 'default'=>'', 'display'=>"Current user's warehouse ID"),
      'my_records' => array('datatype'=>'boolean', 'default'=>'', 'display'=>"Only include my records",
        'wheres' => array(
          array('value'=>'1', 'operator'=>'equal', 'sql'=>"s.created_by_id=#user_id#")
        )
      ),
      'group_id' => array('datatype'=>'integer', 'default'=>'', 'display'=>"ID of a group to filter to the members of",
        'description'=>'Specify the ID of a recording group. This filters the report to the members of the group.',
        'joins' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"join groups_users #alias:gu# on #alias:gu#.user_id=s.created_by_id and #alias:gu#.group_id=#group_id# and #alias:gu#.deleted=false")
        ),
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"s.group_id=#group_id#")
        )
      ),
      'implicit_group_id' => array('datatype'=>'integer', 'default'=>'', 'display'=>"ID of a group to filter to the members of",
        'description'=>'Specify the ID of a recording group. This filters the report to the members of the group.',
        'joins' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"join groups_users #alias:gu# on #alias:gu#.user_id=s.created_by_id and #alias:gu#.group_id=#implicit_group_id# and #alias:gu#.deleted=false")
        )
      ),
      'website_list' => array('datatype'=>'integer[]', 'default'=>'', 'display'=>"Website IDs",
        'description'=>'Comma separated list of IDs',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"su.website_id #website_list_op# (#website_list#)")
        )
      ),
      'survey_list' => array('datatype'=>'integer[]', 'default'=>'', 'display'=>"Survey IDs",
        'description'=>'Comma separated list of IDs',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"su.id #survey_list_op# (#survey_list#)")
        )
      ),
      'input_form_list' => array('datatype'=>'text[]', 'default'=>'', 'display'=>"Input forms",
        'description'=>'Comma separated list of input form paths',
        'wheres' => array(
          array('value'=>'', 'operator'=>'', 'sql'=>"s.input_form #input_form_list_op# (#input_form_list#)")
        )
      )
    );
  }

  /**
   * Returns an array of the parameters which have defaults and their associated default values.
   * @return array
   */
  public static function getDefaultParameterValues() {
    return array(
      'sample_id_op'=>'=',
      'website_list_op'=>'in',
      'survey_list_op'=>'in',
      'input_form_list_op'=>'in',
      'location_list_op'=>'in',
      'indexed_location_list_op'=>'in',
      'website_list_op_context'=>'in',
      'survey_list_op_context'=>'in',
      'input_form_list_op_context'=>'in',
      'location_list_op_context'=>'in',
      'indexed_location_list_op_context'=>'in'
    );
  }
}