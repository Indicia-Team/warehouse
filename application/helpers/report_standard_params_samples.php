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
   * Gets parameter details related to operations on other parameter values.
   *
   * List of parameters that have an associated operation parameter. E.g. along
   * with the occ_id parameter you can supply occ_id_op='>=' to define the operation
   * to be applied in the filter.
   *
   * @return array
   *   List of operation parameters with configuration.
   */
  public static function getOperationParameters() {
    return [
      'indexed_location_list' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Indexed location IDs mode',
        'description' => 'Include or exclude the list of indexed locations',
        'lookup_values' => 'in:Include,not in:Exclude',
      ],
      'input_form_list' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Input forms mode',
        'description' => 'Include or exclude the list of input forms',
        'lookup_values' => 'in:Include,not in:Exclude',
      ],
      'location_list' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Location IDs mode',
        'description' => 'Include or exclude the list of locations',
        'lookup_values' => 'in:Include,not in:Exclude',
      ],
      'quality' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Quality filter mode',
        'description' => 'Include or exclude the list of quality codes',
        'lookup_values' => 'in:Include,not in:Exclude',
      ],
      'smp_id' => [
        'datatype' => 'lookup',
        'default' => '',
        'display' => 'ID operation',
        'description' => 'Sample ID lookup operation',
        'lookup_values' => '=:is,>=:is at least,<=:is at most',
      ],
      'survey_list' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Survey IDs mode',
        'description' => 'Include or exclude the list of surveys',
        'lookup_values' => 'in:Include,not in:Exclude'
      ],
      'website_list' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Website IDs mode',
        'description' => 'Include or exclude the list of websites',
        'lookup_values' => 'in:Include,not in:Exclude'
      ],
    ];
  }

  /**
   * Retrieves the list of standard reporting parameters available for this report type.
   *
   * @return array
   *   Parameters list.
   */
  public static function getParameters() {
    return [
      'idlist' => [
        'datatype' => 'idlist',
        'display' => 'List of IDs',
        'emptyvalue' => '',
        'fieldname' => 's.id',
        'alias' => 'smp_id',
        'description' => 'Comma separated list of sample IDs to filter to',
      ],
      'searchArea' => [
        'datatype' => 'geometry',
        'display' => 'Boundary',
        'description' => 'Boundary to search within, in Well Known Text format using Web Mercator projection.',
        'wheres' => [
          [
            'sql' => "st_intersects(s.geom, st_makevalid(st_geomfromtext('#searchArea#',900913)))",
          ],
        ],
      ],
      'smp_id' => [
        'datatype' => 'integer',
        'display' => 'ID',
        'description' => 'Sample ID',
        'wheres' => [
          [
            'sql' => "s.id #smp_id_op# #smp_id#",
          ]
        ],
      ],
      'sample_method_id' => [
        'datatype' => 'integer',
        'display' => 'Sample Method ID',
        'description' => 'Termlists_terms ID for the Sample Method',
        'wheres' => [
          ['sql' => "s.location_name ilike replace('#location_name#', '*', '%') || '%'"],
        ],
      ],
      'location_name' => [
        'datatype' => 'text',
        'display' => 'Location name',
        'description' => 'Name of location to filter to (starts with search)',
        'wheres' => [
          ['sql' => "s.location_name ilike '%#location_name#%'"],
        ],
      ],
      'location_list' => [
        'datatype' => 'integer[]',
        'display' => 'Location IDs',
        'description' => 'Comma separated list of location IDs',
        'joins' => [
          [
            'sql' => "JOIN locations #alias:lfilt# on #alias:lfilt#.id #location_list_op# (#location_list#) and #alias:lfilt#.deleted=false " .
              "and st_intersects(coalesce(#alias:lfilt#.boundary_geom, #alias:lfilt#.centroid_geom), s.geom) " .
              "and (st_geometrytype(s.geom)='ST_Point' or not st_touches(coalesce(#alias:lfilt#.boundary_geom, #alias:lfilt#.centroid_geom), s.geom))",
          ],
        ],
      ],
      'indexed_location_list' => [
        'datatype' => 'integer[]',
        'display' => 'Location IDs (indexed)',
        'description' => 'Comma separated list of location IDs, for locations that are indexed using the spatial index builder',
        'param_op' => 'inOrNotIn',
        'wheres' => [
          [
            'sql' => "s.location_ids IS NOT NULL AND s.location_ids && ARRAY[#indexed_location_list#]",
          ],
        ],
      ],
      'indexed_location_type_list' => [
        'datatype' => 'integer[]',
        'display' => 'Location Type IDs (indexed)',
        'description' => 'Comma separated list of location type IDs. Any record indexed against any location of one of these types will be included.',
        'joins' => [
          [
            'sql' => 'join locations ltype on s.location_ids @> ARRAY[ltype.id] and ltype.location_type_id in (#indexed_location_type_list#) and ltype.deleted=false',
          ],
        ],
      ],
      'date_from' => [
        'datatype' => 'date',
        'display' => 'Date from',
        'description' => 'Date of first sample to include in the output',
        'wheres' => [
          ['sql' => "('#date_from#'='Click here' OR s.date_end >= CAST(COALESCE('#date_from#','1500-01-01') as date))"],
        ],
      ],
      'date_to' => [
        'datatype' => 'date',
        'display' => 'Date to',
        'description' => 'Date of last sample to include in the output',
        'wheres' => [
          ['sql' => "('#date_to#'='Click here' OR s.date_start <= CAST(COALESCE('#date_to#','1500-01-01') as date))"],
        ],
      ],
      'date_age' => [
        'datatype' => 'text',
        'display' => 'Date from time ago',
        'description' => 'E.g. enter "1 week" or "3 days" to define the how old samples can be before they are dropped from the report.',
        'wheres' => [
          ['sql' => "s.date_start>now()-'#date_age#'::interval"],
        ],
      ],
      'input_date_from' => [
        'datatype' => 'date',
        'display' => 'Input date from',
        'description' => 'Input date of first sample to include in the output',
        'wheres' => [
          ['sql' => "('#input_date_from#'='Click here' OR s.created_on >= '#input_date_from#'::timestamp)"],
        ],
      ],
      'input_date_to' => [
        'datatype' => 'date',
        'display' => 'Input date to',
        'description' => 'Input date of last sample to include in the output',
        'wheres' => [
          ['sql' => "('#input_date_to#'='Click here' OR (s.created_on <= '#input_date_to#'::timestamp OR (length('#input_date_to#')<=10 AND s.created_on < cast('#input_date_to#' as date) + '1 day'::interval)))"],
        ],
      ],
      'input_date_age' => [
        'datatype' => 'text',
        'display' => 'Input date from time ago',
        'description' => 'E.g. enter "1 week" or "3 days" to define the how long ago samples can be input before they are dropped from the report.',
        'wheres' => [
          ['sql' => "s.created_on>now()-'#input_date_age#'::interval"],
        ],
      ],
      'edited_date_from' => [
        'datatype' => 'date',
        'display' => 'Last update date from',
        'description' => 'Last update date of first sample to include in the output',
        'wheres' => [
          ['sql' => "('#edited_date_from#'='Click here' OR s.updated_on >= '#edited_date_from#'::timestamp)"],
        ],
      ],
      'edited_date_to' => [
        'datatype' => 'date',
        'display' => 'Last update date to',
        'description' => 'Last update date of last sample to include in the output',
        'wheres' => [
          ['sql' => "('#edited_date_to#'='Click here' OR (s.updated_on <= '#edited_date_to#'::timestamp OR (length('#edited_date_to#')<=10 AND s.updated_on < cast('#edited_date_to#' as date) + '1 day'::interval)))"],
        ],
      ],
      'edited_date_age' => [
        'datatype' => 'text',
        'display' => 'Last update date from time ago',
        'description' => 'E.g. enter "1 week" or "3 days" to define the how long ago samples can be last updated before they are dropped from the report.',
        'wheres' => [
          ['sql' => "s.updated_on>now()-'#edited_date_age#'::interval"],
        ],
      ],
      'verified_date_from' => [
        'datatype' => 'date',
        'display' => 'Verification status change date from',
        'description' => 'Verification status change date of first sample to include in the output',
        'wheres' => [
          ['sql' => "('#verified_date_from#'='Click here' OR s.verified_on >= CAST('#verified_date_from#' as date))"],
        ],
      ],
      'verified_date_to' => [
        'datatype' => 'date',
        'display' => 'Verification status change date to',
        'description' => 'Verification status change date of last sample to include in the output',
        'wheres' => [
          ['sql' => "('#verified_date_to#'='Click here' OR s.verified_on < CAST('#verified_date_to#' as date)+'1 day'::interval)"],
        ],
      ],
      'verified_date_age' => [
        'datatype' => 'text',
        'display' => 'Verification status change date from time ago',
        'description' => 'E.g. enter "1 week" or "3 days" to define the how long ago samples can have last had their status changed before they are dropped from the report.',
        'wheres' => [
          ['sql' => "s.verified_on>now()-'#verified_date_age#'::interval"],
        ],
      ],
      'tracking_from' => [
        'datatype' => 'integer',
        'display' => 'First squential update ID to include',
        'description' => 'All record inserts and updates are given a sequential tracking ID. Filter by this to limit ' .
          'the range of records returned to a contiguous batch of updates. Tracking is updated when the record is ' .
          'affected in any way, not just when it is edited. E.g. an update to spatial indexing will update the tracking.',
        'wheres' => [
          [
            'sql' => 's.tracking >= #tracking_from#',
          ],
        ],
      ],
      'tracking_to' => [
        'datatype' => 'integer',
        'display' => 'Last squential update ID to include',
        'description' => 'All record inserts and updates are given a sequential tracking ID. Filter by this to limit ' .
          'the range of records returned to a contiguous batch of updates. Tracking is updated when the record is ' .
          'affected in any way, not just when it is edited. E.g. an update to spatial indexing will update the tracking.',
        'wheres' => [
          [
            'sql' =>
            "s.tracking <= #tracking_from#",
          ],
        ],
      ],
      'quality' => [
        'datatype' => 'lookup',
        'display' => 'Quality',
        'multiselect' => TRUE,
        'param_op' => 'inOrNotIn',
        'description' => 'Sample quality filter',
        'lookup_values' => 'V:Accepted records only,P:Not reviewed,!D:Exclude queried or not accepted records,' .
          '!R:Exclude not accepted records,R:Not accepted records only,DR:Queried or not accepted records,all:All records',
        'wheres' => [
          // Query has been answered.
          [
            'value' => 'A',
            'operator' => 'equal',
            'sql' => "s.query='A'",
          ],
          // Queried (or legacy Dubious).
          [
            'value' => 'D',
            'operator' => 'equal',
            'sql' => "(s.record_status='D' or s.query='Q')",
          ],
          // Pending.
          [
            'value' => 'P',
            'operator' => 'equal',
            'sql' => "s.record_status='C' and (s.query<>'Q' or s.query is null)",
          ],
          // Not accepted.
          [
            'value' => 'R',
            'operator' => 'equal',
            'sql' => "s.record_status='R'",
          ],
          // Accepted.
          [
            'value' => 'V',
            'operator' => 'equal',
            'sql' => "s.record_status='V'",
          ],
          // The following parameters are legacy to support old filters.
          // Plausible or accepted.
          [
            'value' => '-3',
            'operator' => 'equal',
            'sql' => "(s.record_status='V' or s.record_substatus<=3)",
          ],
          // Not queried or dubious.
          [
            'value' => '!D',
            'operator' => 'equal',
            'sql' => "s.record_status<>'D' and (s.query<>'Q' or s.query is null)",
          ],
          // Not rejected.
          [
            'value' => '!R',
            'operator' => 'equal',
            'sql' => "s.record_status<>'R'",
          ],
          // The all filter does not need any SQL
        ],
      ],
      'has_photos' => [
        'datatype' => 'boolean',
        'display' => 'Photos',
        'description' => 'Only include samples which have photos?',
        'wheres' => [
          [
            'value' => '1',
            'operator' => 'equal',
            'sql' => "s.media_count>0",
          ],
          [
            'value' => '0',
            'operator' => 'equal',
            'sql' => "s.media_count=0",
          ],
        ],
      ],
      'user_id' => array('datatype' => 'integer', 'display' => "Current user's warehouse ID"),
      'my_records' => [
        'datatype' => 'boolean',
        'display' => 'Include or exclude my records',
        'wheres' => [
          [
            'value' => '0',
            'operator' => 'equal',
            'sql' => "s.created_by_id<>#user_id#",
          ],
          [
            'value' => '1',
            'operator' => 'equal',
            'sql' => "s.created_by_id=#user_id#",
          ],
        ],
      ],
      'recorder_name' => [
        'datatype' => 'text',
        'display' => 'Recorder name contains',
        'wheres' => [
          [
            'sql' => "sj_smp_snf.recorders ~* regexp_replace('#recorder_name#', '[^a-zA-Z0-9]+', '|')",
          ],
        ],
        'standard_join' => 'sj_smp_snf',
      ],
      'created_by_id' => [
        'datatype' => 'integer',
        'display' => 'Limit to samples created by this user ID',
        'wheres' => [
          ['sql' => 's.created_by_id=#created_by_id#'],
        ],
      ],
      'group_id' => [
        'datatype' => 'integer',
        'display' => 'ID of a group to filter to records in',
        'description' => 'Specify the ID of a recording group. This filters the report to the records added to this group.',
        'wheres' => [
          ['sql' => "s.group_id=#group_id#"],
        ],
      ],
      'implicit_group_id' => [
        'datatype' => 'integer',
        'display' => 'ID of a group to filter to the members of',
        'description' => 'Specify the ID of a recording group. This filters the report to the members of the group.',
        'joins' => [
          ['sql' => 'join groups_users #alias:gu# on #alias:gu#.user_id=s.created_by_id and #alias:gu#.group_id=#implicit_group_id# and #alias:gu#.deleted=false'],
        ],
      ],
      'website_list' => [
        'datatype' => 'integer[]', 'display' => "Website IDs",
        'description' => 'Comma separated list of IDs',
        'wheres' => [
          ['sql' => 's.website_id #website_list_op# (#website_list#)'],
        ]
      ],
      'survey_list' => [
        'datatype' => 'integer[]',
        'display' => "Survey IDs",
        'description' => 'Comma separated list of IDs',
        'wheres' => [
          ['sql' => "s.survey_id #survey_list_op# (#survey_list#)"],
        ],
      ],
      'input_form_list' => [
        'datatype' => 'text[]',
        'display' => "Input forms",
        'description' => 'Comma separated list of input form paths',
        'wheres' => [
          ['sql' => "s.input_form #input_form_list_op# (#input_form_list#)"],
        ],
      ],
    ];
  }

  /**
   * Returns an array of the parameters which have defaults and their associated default values.
   * @return array
   */
  public static function getDefaultParameterValues() {
    return [
      'quality_op' => 'in',
      'smp_id_op' => '=',
      'website_list_op' => 'in',
      'survey_list_op' => 'in',
      'input_form_list_op' => 'in',
      'location_list_op' => 'in',
      'indexed_location_list_op' => 'in',
      'quality_op_context' => 'in',
      'website_list_op_context' => 'in',
      'survey_list_op_context' => 'in',
      'input_form_list_op_context' => 'in',
      'location_list_op_context' => 'in',
      'indexed_location_list_op_context' => 'in',
    ];
  }
}