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
 * Helper class to provide standardised reporting parameters for occurrences data reports.
 */
class report_standard_params_occurrences {

  /**
   * Retrieve deprecated parameter details.
   *
   * Returns a list of the parameter names which have been deprecated and should be converted
   * to newer parameter names. Maintains backwards compatibility with clients that are not
   * running the latest code. Returns an array, with each element being a sub array containing
   * the old and new parameter names. A third optional element in the sub-array can be set to
   * TRUE to enable string quoting in the output.
   *
   * @return array
   *   List of deprecated parameters and their replacements.
   */
  public static function getDeprecatedParameters() {
    return [
      ['location_id', 'location_list'],
      ['survey_id', 'survey_list'],
      ['indexed_location_id', 'indexed_location_list'],
      ['input_form', 'input_form_list', TRUE],
      ['higher_taxa_taxon_list_list', 'taxa_taxon_list_list'],
    ];
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
      'occ_id' => [
        'datatype' => 'lookup',
        'display' => 'ID operation',
        'description' => 'Operator to use in conjunction with a value provided in the occ_id parameter.',
        'lookup_values' => '=:is,>=:is at least,<=:is at most',
      ],
      'smp_id' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Sample ID operation',
        'description' => 'Operator to use in conjunction with a value provided in the smp_id parameter.',
        'lookup_values' => '=:is,>=:is at least,<=:is at most',
      ],
      'website_list' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Website IDs mode',
        'description' => 'Include or exclude the list of websites provided in the website_list parameter',
        'lookup_values' => 'in:Include,not in:Exclude',
      ],
      'survey_list' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Survey IDs mode',
        'description' => 'Include or exclude the list of surveys provided in the survey_list parameter',
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
      'indexed_location_list' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Indexed location IDs mode',
        'description' => 'Include or exclude the list of indexed locations',
        'lookup_values' => 'in:Include,not in:Exclude',
      ],
      'quality' => [
        'datatype' => 'lookup',
        'default' => 'in',
        'display' => 'Quality filter mode',
        'description' => 'Include or exclude the list of quality codes',
        'lookup_values' => 'in:Include,not in:Exclude',
      ],
      'taxon_rank_sort_order' => [
        'datatype' => 'lookup',
        'default' => '',
        'display' => 'Taxon rank mode',
        'description' => 'Mode for filtering by taxon rank in the hierarchy',
        'lookup_values' => '=:include only this level in the hierarchy,>=:include this level and lower,<=:include this level and higher',
      ],
      'identification_difficulty' => [
        'datatype' => 'lookup',
        'default' => '',
        'display' => 'Identification difficulty operation',
        'description' => 'Identification difficulty lookup operation',
        'lookup_values' => '=:is,>=:is at least,<=:is at most',
      ],
      'date_year' => [
        'datatype' => 'lookup',
        'default' => '',
        'display' => 'Year filter operation',
        'description' => 'Operation for filtering on date',
        'lookup_values' => '=:is,>=:is in or after,<=:is in or before',
      ],
      'input_date_year' => [
        'datatype' => 'lookup',
        'default' => '',
        'display' => 'Year of input filter operation',
        'description' => 'Operation for filtering on input date',
        'lookup_values' => '=:is,>=:is in or after,<=:is in or before',
      ],
      'edited_date_year' => [
        'datatype' => 'lookup',
        'default' => '',
        'display' => 'Year of last editfilter operation',
        'description' => 'Operation for filtering on last edit date',
        'lookup_values' => '=:is,>=:is in or after,<=:is in or before',
      ],
      'verified_date_year' => [
        'datatype' => 'lookup',
        'default' => '',
        'display' => 'Year of last verification filter operation',
        'description' => 'Operation for filtering on last verification date',
        'lookup_values' => '=:is,>=:is in or after,<=:is in or before',
      ],
    ];
  }

  /**
   * Retrieves the list of standard reporting parameters available for this report type.
   *
   * @return array
   *   List of parameter definitions.
   */
  public static function getParameters() {
    return [
      'idlist' => [
        'datatype' => 'idlist',
        'display' => 'List of IDs',
        'emptyvalue' => '',
        'fieldname' => 'o.id',
        'alias' => 'occ_id',
        'description' => 'Comma separated list of occurrence IDs to filter to.',
      ],
      'occ_id' => [
        'datatype' => 'integer',
        'display' => 'ID',
        'description' => 'Limit by occurrence ID.',
        'wheres' => [
          [
            'sql' => "o.id #occ_id_op# #occ_id#",
          ],
        ],
      ],
      'smp_id' => [
        'datatype' => 'integer',
        'display' => 'Sample ID',
        'description' => 'Limit by sample ID.',
        'wheres' => [
          [
            'sql' => "o.sample_id #smp_id_op# #smp_id#",
          ],
        ],
      ],
      'occurrence_external_key' => [
        'datatype' => 'text',
        'display' => 'External key',
        'description' => 'Limit to a single record matching this occurrence external key.',
        'wheres' => [
          [
            'sql' => "o.external_key='#occurrence_external_key#'",
          ],
        ],
      ],
      'searchArea' => [
        'datatype' => 'geometry',
        'display' => 'Boundary',
        'description' => 'Boundary to search within, in Well Known Text format using Web Mercator projection.',
        'wheres' => [
          [
            'sql' => "st_intersects(#sample_geom_field#, st_makevalid(st_geomfromtext('#searchArea#',900913)))",
          ],
        ],
      ],
      'location_name' => [
        'datatype' => 'text',
        'display' => 'Location name',
        'description' => 'Name of location to filter to (starts with search)',
        'wheres' => [
          [
            'sql' => "o.location_name ilike replace('#location_name#', '*', '%') || '%'",
          ],
        ],
      ],
      'location_list' => [
        'datatype' => 'integer[]',
        'display' => 'Location IDs',
        'description' => 'Comma separated list of location IDs',
        'joins' => [
          [
            'sql' => "JOIN locations #alias:lfilt# ON #alias:lfilt#.id #location_list_op# (#location_list#) AND #alias:lfilt#.deleted=false " .
              "AND st_intersects(coalesce(#alias:lfilt#.boundary_geom, #alias:lfilt#.centroid_geom), #sample_geom_field#) " .
              "AND (st_geometrytype(#sample_geom_field#)='ST_Point' OR NOT st_touches(coalesce(#alias:lfilt#.boundary_geom, #alias:lfilt#.centroid_geom), #sample_geom_field#))",
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
            'sql' => "o.location_ids IS NOT NULL AND o.location_ids && ARRAY[#indexed_location_list#]",
          ],
        ],
      ],
      'indexed_location_type_list' => [
        'datatype' => 'integer[]',
        'display' => 'Location Type IDs (indexed)',
        'description' => 'Comma separated list of location type IDs. Any record indexed against any location of one ' .
          'of these types will be included.',
        'joins' => [
          [
            'sql' => 'join locations ltype on o.location_ids @> ARRAY[ltype.id] ' .
              'and ltype.location_type_id in (#indexed_location_type_list#) and ltype.deleted=false',
          ],
        ],
      ],
      'output_sref_systems' => [
        'datatype' => 'string[]',
        'display' => 'Output reference systems',
        'description' => 'Comma separated list of output spatial reference systems to filter to. Allows broad geographic limits to be applied.',
        'wheres' => [
          [
            'sql' => "onf.output_sref_system IN (#output_sref_systems#)",
          ],
        ],
      ],
      'date_year' => [
        'datatype' => 'integer',
        'display' => 'Year',
        'description' => 'Filter by year of the record',
        'wheres' => [
          [
            'sql' => "extract(year from o.date_start) #date_year_op# #date_year#",
          ],
        ],
      ],
      'date_from' => [
        'datatype' => 'date',
        'display' => 'Date from',
        'description' => 'Date of first record to include in the output',
        'wheres' => [
          [
            'sql' => "('#date_from#'='Click here' OR o.date_end >= CAST(COALESCE('#date_from#','1500-01-01') as date))",
          ],
        ],
      ],
      'date_to' => [
        'datatype' => 'date',
        'display' => 'Date to',
        'description' => 'Date of last record to include in the output',
        'wheres' => [
          [
            'sql' => "('#date_to#'='Click here' OR o.date_start <= CAST(COALESCE('#date_to#','1500-01-01') as date))",
          ],
        ],
      ],
      'date_age' => [
        'datatype' => 'text',
        'display' => 'Date from time ago',
        'description' => 'E.g. enter "1 week" or "3 days" to define the how old records can be before they are dropped from the report.',
        'wheres' => [
          [
            'sql' => "o.date_start>now()-'#date_age#'::interval",
          ],
        ],
      ],
      'input_date_year' => [
        'datatype' => 'integer',
        'display' => 'Input year',
        'description' => 'Filter by year of the input date of the record',
        'wheres' => [
          [
            'sql' => "extract(year from o.created_on) #input_date_year_op# #input_date_year#",
          ],
        ],
      ],
      'input_date_from' => [
        'datatype' => 'date',
        'display' => 'Input date from',
        'description' => 'Input date of first record to include in the output',
        'wheres' => [
          [
            // Use filter on both created_on and updated_on, as the latter is
            // indexed.
            'sql' => "o.created_on >= '#input_date_from#'::timestamp AND o.updated_on >= '#input_date_from#'::timestamp",
          ],
        ],
      ],
      'input_date_to' => [
        'datatype' => 'date',
        'display' => 'Input date to',
        'description' =>
        'Input date of last record to include in the output',
        'wheres' => [
          [
            'sql' => "('#input_date_to#'='Click here' OR (o.created_on <= '#input_date_to#'::timestamp OR (length('#input_date_to#')<=10 AND o.created_on < cast('#input_date_to#' as date) + '1 day'::interval)))",
          ],
        ],
      ],
      'input_date_age' => [
        'datatype' => 'text',
        'display' => 'Input date from time ago',
        'description' => 'E.g. enter "1 week" or "3 days" to define the how long ago records can be input before they are dropped from the report.',
        'wheres' => [
          [
            // Use filter on both created_on and updated_on, as the latter is
            // indexed.
            'sql' => "o.created_on>now()-'#input_date_age#'::interval AND o.updated_on>now()-'#input_date_age#'::interval",
          ],
        ],
      ],
      'edited_date_year' => [
        'datatype' => 'integer',
        'display' => 'Last update date year',
        'description' => 'Filter by year of the last update of the record',
        'wheres' => [
          [
            'sql' => "extract(year from o.updated_on) #edited_date_year_op# #edited_date_year#",
          ],
        ],
      ],
      'edited_date_from' => [
        'datatype' => 'date',
        'display' => 'Last update date from',
        'description' => 'Last update date of first record to include in the output',
        'wheres' => [
          [
            'sql' => "('#edited_date_from#'='Click here' OR o.updated_on >= '#edited_date_from#'::timestamp)",
          ],
        ],
      ],
      'edited_date_to' => [
        'datatype' => 'date',
        'display' => 'Last update date to',
        'description' => 'Last update date of last record to include in the output',
        'wheres' => [
          [
            'sql' => "('#edited_date_to#'='Click here' OR (o.updated_on <= '#edited_date_to#'::timestamp OR (length('#edited_date_to#')<=10 AND o.updated_on < cast('#edited_date_to#' as date) + '1 day'::interval)))",
          ],
        ],
      ],
      'edited_date_age' => [
        'datatype' => 'text',
        'display' => 'Last update date from time ago',
        'description' => 'E.g. enter "1 week" or "3 days" to define the how long ago records can be last updated before they are dropped from the report.',
        'wheres' => [
          [
            'sql' => "o.updated_on>now()-'#edited_date_age#'::interval",
          ],
        ],
      ],
      'verified_date_year' => [
        'datatype' => 'integer',
        'display' => 'Verification year',
        'description' => 'Filter by year of the last verification of the record',
        'wheres' => [
          [
            'sql' => "extract(year from o.verified_on) #verified_date_year_op# #verified_date_year#",
          ],
        ],
      ],
      'verified_date_from' => [
        'datatype' => 'date',
        'display' => 'Verification status change date from',
        'description' => 'Verification status change date of first record to include in the output',
        'wheres' => [
          [
            'sql' => "('#verified_date_from#'='Click here' OR o.verified_on >= CAST('#verified_date_from#' as date))",
          ],
        ],
      ],
      'verified_date_to' => [
        'datatype' => 'date',
        'display' => 'Verification status change date to',
        'description' => 'Verification status change date of last record to include in the output',
        'wheres' => [
          [
            'sql' => "('#verified_date_to#'='Click here' OR o.verified_on < CAST('#verified_date_to#' as date)+'1 day'::interval)",
          ],
        ],
      ],
      'verified_date_age' => [
        'datatype' => 'text',
        'display' => 'Verification status change date from time ago',
        'description' => 'E.g. enter "1 week" or "3 days" to define the how long ago records can have last had their status changed before they are dropped from the report.',
        'wheres' => [
          [
            'sql' => "o.verified_on>now()-'#verified_date_age#'::interval",
          ],
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
            'sql' => 'o.tracking >= #tracking_from#',
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
            'sql' => 'o.tracking <= #tracking_from#',
          ],
        ],
      ],
      'quality' => [
        'datatype' => 'lookup',
        'display' => 'Quality',
        'multiselect' => TRUE,
        'param_op' => 'inOrNotIn',
        'description' => 'Minimum quality of records to include',
        'lookup_values' => 'V1:Accepted as correct records only,V2:Accepted as considered correct records only,V:Accepted records only,-3:Reviewer agreed at least plausible,' .
          'C3:Plausible records only,C:Recorder was certain,L:Recorder thought the record was at least likely,' .
          'P:Not reviewed,T:Not reviewed but trusted recorder,!D:Exclude queried or not accepted records,!R:Exclude not accepted records,D:Queried records only,' .
          'A:Answered records,R:Not accepted records only,R4:Not accepted because unable to verify records only,R5:Not accepted as incorrect records only,DR:Queried or not accepted records,all:All records',
        'wheres' => [
          // Query has been answered.
          [
            'value' => 'A',
            'operator' => 'equal',
            'sql' => "o.query='A'",
          ],
          // Plausible.
          [
            'value' => 'C3',
            'operator' => 'equal',
            'sql' => "(o.record_status='C' and o.record_substatus=3)",
          ],
          // Queried (or legacy Dubious).
          [
            'value' => 'D',
            'operator' => 'equal',
            'sql' => "(o.record_status='D' or coalesce(o.query, '')='Q')",
          ],
          // Pending.
          [
            'value' => 'P',
            'operator' => 'equal',
            'sql' => "o.record_status='C' and o.record_substatus is null and (o.query<>'Q' or o.query is null)",
          ],
          // Not accepted.
          [
            'value' => 'R',
            'operator' => 'equal',
            'sql' => "o.record_status='R'",
          ],
          [
            'value' => 'R4',
            'operator' => 'equal',
            'sql' => "o.record_status='R' and o.record_substatus=4",
          ],
          [
            'value' => 'R5',
            'operator' => 'equal',
            'sql' => "o.record_status='R' and o.record_substatus=5",
          ],
          // Accepted.
          [
            'value' => 'V1',
            'operator' => 'equal',
            'sql' => "o.record_status='V' and o.record_substatus=1",
          ],
          [
            'value' => 'V2',
            'operator' => 'equal',
            'sql' => "o.record_status='V' and o.record_substatus=2",
          ],
          [
            'value' => 'V',
            'operator' => 'equal',
            'sql' => "o.record_status='V'",
          ],
          // The following parameters are legacy to support old filters.
          // Plausible or accepted.
          [
            'value' => '-3',
            'operator' => 'equal',
            'sql' => "(o.record_status='V' or o.record_substatus<=3)",
          ],
          // Not queried, dubious or rejected.
          [
            'value' => '!D',
            'operator' => 'equal',
            'sql' => "(o.record_status not in ('R','D') and (o.query<>'Q' or o.query is null))",
          ],
          // Not rejected.
          [
            'value' => '!R',
            'operator' => 'equal',
            'sql' => "o.record_status<>'R'",
          ],
          // Recorder thinks identification is certain to be correct.
          [
            'value' => 'C',
            'operator' => 'equal',
            'sql' => "o.record_status<>'R' and o.certainty='C'",
          ],
          // Queried, dubious or rejected.
          [
            'value' => 'DR',
            'operator' => 'equal',
            'sql' => "(o.record_status in ('R','D') or o.query='Q')",
          ],
          // Recorder thinks identification is likely to be correct.
          [
            'value' => 'L',
            'operator' => 'equal',
            'sql' => "o.record_status<>'R' and o.certainty in ('C','L')",
          ],
          // Trusted recorders.
          [
            'value' => 'T',
            'operator' => 'equal',
            'sql' => "o.record_status='C' and o.record_substatus is null",
          ],

          // The all filter does not need any SQL.
        ],
        'joins' => [
          [
            'value' => 'T',
            'operator' => 'equal',
            'sql' =>
            "JOIN user_trusts #alias:ut# on (#alias:ut#.survey_id=o.survey_id
      OR #alias:ut#.taxon_group_id=o.taxon_group_id
      OR (o.location_ids @> ARRAY[#alias:ut#.location_id] OR #alias:ut#.location_id IS NULL)
    )
    AND #alias:ut#.deleted=false
    AND ((o.survey_id = #alias:ut#.survey_id) or (#alias:ut#.survey_id is null and (#alias:ut#.taxon_group_id is not null or #alias:ut#.location_id is not null)))
    AND ((o.taxon_group_id = #alias:ut#.taxon_group_id) or (#alias:ut#.taxon_group_id is null and (#alias:ut#.survey_id is not null or #alias:ut#.location_id is not null)))
    AND ((o.location_ids @> ARRAY[#alias:ut#.location_id]) OR (#alias:ut#.location_id IS NULL and (#alias:ut#.survey_id is not null or #alias:ut#.taxon_group_id is not null)))
    AND o.created_by_id = #alias:ut#.user_id",
          ],
        ],
      ],
      'certainty' => [
        'datatype' => 'lookup',
        'display' => 'Certainty',
        'multiselect' => TRUE,
        'description' => "Recorder's certainty of the identification",
        'lookup_values' => 'C:Certain,L:Likely,U:Uncertain,NS:Not stated',
        'wheres' => [
          [
            'value' => 'C',
            'operator' => 'equal',
            'sql' => "o.certainty='C'",
          ],
          [
            'value' => 'L',
            'operator' => 'equal',
            'sql' => "o.certainty='L'",
          ],
          [
            'value' => 'U',
            'operator' => 'equal',
            'sql' => "o.certainty='U'",
          ],
          [
            'value' => 'NS',
            'operator' => 'equal',
            'sql' => "o.certainty IS NULL",
          ],
        ],
      ],
      'exclude_sensitive' => [
        'datatype' => 'boolean',
        'display' => 'Exclude sensitive records',
        'description' => 'Exclude sensitive records?',
        'wheres' => [
          [
            'sql' => "o.sensitive<>true",
          ],
        ],
      ],
      'confidential' => [
        'datatype' => 'boolean',
        'display' => 'Confidential records',
        'description' => 'Filtering based on confidential status of the record',
        'lookup_values' => 't:Confidential records only,f:Exclude confidential records,all:All records',
        'wheres' => [
          [
            'value' => 't',
            'operator' => 'equal',
            'sql' => "o.confidential='t'",
          ],
          [
            'value' => 'f',
            'operator' => 'equal',
            'sql' => "o.confidential='f'",
          ],
          // Nothing to do for all case.
        ],
      ],
      'release_status' => [
        'datatype' => 'lookup',
        'display' => 'Release status',
        'description' => 'Release status of the record',
        'lookup_values' =>
          'R:Released,' .
          'RM:Released by other recorders plus my own unreleased records;' .
          'U:Unreleased because records belong of a project that has not yet released the records,' .
          'RU:Released plus unreleased because records belong to a project that has not yet released the records,' .
          'P:Recorder has requested a precheck before release,' .
          'RP:Released plus records where recorder has requested a precheck before release,' .
          'A:All',
        'wheres' => [
          [
            'value' => 'R',
            'operator' => 'equal',
            'sql' => "o.release_status='R'",
          ],
          [
            'value' => 'U',
            'operator' => 'equal',
            'sql' => "o.release_status='U'",
          ],
          [
            'value' => 'RU',
            'operator' => 'equal',
            'sql' => "o.release_status in ('R','U')",
          ],
          [
            'value' => 'P',
            'operator' => 'equal',
            'sql' => "o.release_status='P'",
          ],
          [
            'value' => 'RP',
            'operator' => 'equal',
            'sql' => "o.release_status in ('R','P')",
          ],
          [
            'value' => 'RM',
            'operator' => 'equal',
            'sql' => "o.release_status='R' or o.created_by_id=#user_id#",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'marine_flag' => [
        'datatype' => 'lookup',
        'display' => 'Marine flag',
        'description' => 'Marine species filtering?',
        'lookup_values' => 'A:Include marine and non-marine species,Y:Only marine species,N:Exclude marine species',
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "o.marine_flag=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "(o.marine_flag is null or o.marine_flag=false)",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'freshwater_flag' => [
        'datatype' => 'lookup',
        'display' => 'Freshwater flag',
        'description' => 'Freshwater species filtering?',
        'lookup_values' => 'A:Include freshwater and non-freshwater species,Y:Only freshwater species,N:Exclude freshwater species',
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "o.freshwater_flag=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "(o.freshwater_flag is null or o.freshwater_flag=false)",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'terrestrial_flag' => [
        'datatype' => 'lookup',
        'display' => 'Terrestrial flag',
        'description' => 'Terrestrial species filtering?',
        'lookup_values' => 'A:Include terrestrial and non-terrestrial species,Y:Only terrestrial species,N:Exclude terrestrial species',
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "o.terrestrial_flag=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "(o.terrestrial_flag is null or o.terrestrial_flag=false)",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'non_native_flag' => [
        'datatype' => 'lookup',
        'display' => 'Non-native flag',
        'description' => 'Non-native species filtering?',
        'lookup_values' => 'A:Include non-native and native species,Y:Only non-native species,N:Exclude non-native species',
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "o.non_native_flag=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "(o.non_native_flag is null or o.non_native_flag=false)",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'autochecks' => [
        'datatype' => 'lookup',
        'display' => 'Automated checks',
        'description' => 'Filter to only include records that have passed or failed automated checks',
        'lookup_values' => 'N:Not filtered,F:Include only records that fail checks,P:Include only records which pass checks',
        'wheres' => [
          [
            'value' => 'F',
            'operator' => 'equal',
            'sql' => "o.data_cleaner_result = 'f' and o.applied_verification_rule_types<>ARRAY[]::text[]",
          ],
          [
            'value' => 'P',
            'operator' => 'equal',
            'sql' => "o.data_cleaner_result = 't' and o.applied_verification_rule_types<>ARRAY[]::text[]",
          ],
        ],
        'joins' => [
          // Note need to be tolerant of rule flags from both data_cleaner and
          // record_cleaner modules.
          [
            'value' => 'identification_difficulty',
            'operator' => 'equal',
            'sql' => "join cache_occurrences_nonfunctional onf_rulefail on onf_rulefail.id=o.id and onf_rulefail.data_cleaner_info like '%difficulty]%'",
          ],
          [
            'value' => 'period',
            'operator' => 'equal',
            'sql' => "join cache_occurrences_nonfunctional onf_rulefail on onf_rulefail.id=o.id and onf_rulefail.data_cleaner_info like '%period]%'",
          ],
          [
            'value' => 'period_within_year',
            'operator' => 'equal',
            'sql' => "join cache_occurrences_nonfunctional onf_rulefail on onf_rulefail.id=o.id and (onf_rulefail.data_cleaner_info like '%period_within_year]%' or onf_rulefail.data_cleaner_info like '%phenology]%')",
          ],
          [
            'value' => 'without_polygon',
            'operator' => 'equal',
            'sql' => "join cache_occurrences_nonfunctional onf_rulefail on onf_rulefail.id=o.id and (onf_rulefail.data_cleaner_info like '%without_polygon]%' or onf_rulefail.data_cleaner_info like '%tenkm]%')",
          ],
        ],
      ],
      // Autocheck_rule support is legacy.
      'autocheck_rule' => [
        'datatype' => 'text',
        'display' => 'Autocheck rules',
        'description' => 'Filter to only include records that have failed this rule.',
        'joins' => [
          [
            'sql' => "join cache_occurrences_nonfunctional onf_rulefail on onf_rulefail.id=o.id and onf_rulefail.data_cleaner_info like '%#autocheck_rule#]%'",
          ],
        ],
      ],
      'classifier_agreement' => [
        'datatype' => 'lookup',
        'display' => 'Classifier agreement',
        'description' => "If a classifier was used, flag indicating if the classifier's most likely suggestion matches the current determination.",
        'lookup_values' => "A:Include all records irrespective of classificaton," .
          "Y:Only records where the classifier's most likely suggestion matches the current determination," .
          "N:Only records where the classifier's most likely suggestion does not match the current determination," .
          "C:Any record where a classifier was used",
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "o.classifier_agreement=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "o.classifier_agreement=false",
          ],
          [
            'value' => 'C',
            'operator' => 'equal',
            'sql' => "o.classifier_agreement IS NOT NULL",
          ],
        ],
      ],
      'dna_derived' => [
        'datatype' => 'boolean',
        'display' => 'DNA derived occurrences filter',
        'description' => 'Include or exclude records which are DNA derived.',
        'wheres' => [
          [
            'value' => '1',
            'operator' => 'equal',
            'sql' => "o.dna_derived=true",
          ],
          [
            'value' => '0',
            'operator' => 'equal',
            'sql' => "o.dna_derived=false",
          ],
        ],
      ],
      'has_photos' => [
        'datatype' => 'boolean',
        'display' => 'Photo records filter',
        'description' => 'Include or exclude records which have photos.',
        'wheres' => [
          [
            'value' => '1',
            'operator' => 'equal',
            'sql' => "o.media_count>0",
          ],
          [
            'value' => '0',
            'operator' => 'equal',
            'sql' => "o.media_count=0",
          ],
        ],
      ],
      'zero_abundance' => [
        'datatype' => 'boolean',
        'display' => 'Zero abundance filter',
        'description' => 'Include or exclude zero abundance records.',
        'wheres' => [
          [
            'value' => '1',
            'operator' => 'equal',
            'sql' => "o.zero_abundance=true",
          ],
          [
            'value' => '0',
            'operator' => 'equal',
            'sql' => "o.zero_abundance=false",
          ],
        ],
      ],
      'user_id' => [
        'datatype' => 'integer',
        'display' => "Current user's warehouse ID",
      ],
      'my_records' => [
        'datatype' => 'boolean',
        'display' => 'Include or exclude my records',
        'wheres' => [
          [
            'value' => '0',
            'operator' => 'equal',
            'sql' => "o.created_by_id<>#user_id#",
          ],
          [
            'value' => '1',
            'operator' => 'equal',
            'sql' => "o.created_by_id=#user_id#",
          ],
        ],
      ],
      'recorder_name' => [
        'datatype' => 'text',
        'display' => 'Recorder name contains',
        'joins' => [
          [
            'standard_join' => 'sj_snf',
          ],
        ],
        'wheres' => [
          [
            'sql' => "sj_snf.recorders ~* regexp_replace('#recorder_name#', '[^a-zA-Z0-9]+', '|')",
          ],
        ],
      ],
      'created_by_id' => [
        'datatype' => 'integer',
        'display' => 'Limit to records created by this user ID',
        'wheres' => [
          [
            'sql' => "o.created_by_id=#created_by_id#",
          ],
        ],
      ],
      'group_id' => [
        'datatype' => 'integer',
        'display' => "ID of a group to filter to records in",
        'description' => 'Specify the ID of a recording group. This filters the report to the records added to this group.',
        'wheres' => [
          [
            'sql' => "o.group_id=#group_id#",
          ],
        ],
      ],
      'implicit_group_id' => [
        'datatype' => 'integer',
        'display' => "ID of a group to filter to the members of",
        'description' => 'Specify the ID of a recording group. This filters the report to the members of the group.',
        'joins' => [
          [
            'sql' => "join groups_users #alias:gu# on #alias:gu#.user_id=o.created_by_id and #alias:gu#.group_id=#implicit_group_id# and #alias:gu#.deleted=false",
          ],
        ],
      ],
      'website_list' => [
        'datatype' => 'integer[]',
        'display' => "Website IDs",
        'description' =>
          'Comma separated list of IDs of websites to limit to within the set of ' .
          'websites you have permission to access records for.',
        'wheres' => [
          [
            'sql' => "o.website_id #website_list_op# (#website_list#)",
          ],
        ],
      ],
      'survey_list' => [
        'datatype' => 'integer[]',
        'display' => "Survey IDs",
        'description' => 'Comma separated list of IDs of survey datasets to limit to.',
        'wheres' => [
          [
            'sql' => "o.survey_id #survey_list_op# (#survey_list#)",
          ],
        ],
      ],
      'input_form_list' => [
        'datatype' => 'text[]',
        'display' => "Input forms",
        'description' => 'Comma separated list of input form paths',
        'wheres' => [
          [
            'sql' => "o.input_form #input_form_list_op# (#input_form_list#)",
          ],
        ],
      ],
      'import_guid_list' => [
        'datatype' => 'string[]',
        'display' => "Import  GUIDs",
        'description' => 'Comma separated list of GUIDs of occurrence imports to limit to.',
        'wheres' => [
          [
            'sql' => "o.import_guid IN (#import_guid_list#)",
          ],
        ],
      ],
      'taxon_rank_sort_order' => [
        'datatype' => 'integer',
        'display' => 'Taxon rank',
        'description' => 'Rank of the identified taxon in the taxonomic hierarchy',
        'wheres' => [
          [
            'sql' => "o.taxon_rank_sort_order #taxon_rank_sort_order_op# #taxon_rank_sort_order#",
          ],
        ],
      ],
      'taxon_group_list' => [
        'datatype' => 'integer[]',
        'display' => "Taxon Group IDs",
        'description' => 'Comma separated list of IDs of taxon groups to limit to.',
        'wheres' => [
          [
            'sql' => "o.taxon_group_id in (#taxon_group_list#)",
          ],
        ],
      ],
      'taxa_taxon_list_list' => [
        'datatype' => 'integer[]',
        'display' => "Higher taxa taxon list IDs",
        'description' => 'Comma separated list of preferred IDs.',
        'wheres' => [
          [
            'sql' => "o.taxon_path && ARRAY[#taxon_meaning_ids_from_ids#]",
          ],
        ],
        'preprocess' => [
          'taxon_meaning_ids_from_ids' => "select string_agg(distinct m.taxon_meaning_id::text, ',')
            from cache_taxa_taxon_lists l
            join cache_taxa_taxon_lists m on (m.taxon_meaning_id=l.taxon_meaning_id or m.external_key=l.external_key)
            where l.id in (#taxa_taxon_list_list#)",
        ],
      ],
      'taxon_meaning_list' => [
        'datatype' => 'integer[]',
        'display' => "Taxon meaning IDs",
        'description' => 'Comma separated list of taxon meaning IDs',
        'wheres' => [
          [
            'sql' => "(o.taxon_path && ARRAY[#taxon_meaning_ids#] OR o.taxon_meaning_id in (#taxon_meaning_list-unprocessed#))",
          ],
        ],
        'preprocess' => [
          'taxon_meaning_ids' => "select string_agg(distinct m.taxon_meaning_id::text, ',')
            from cache_taxa_taxon_lists l
            join cache_taxa_taxon_lists m on (m.taxon_meaning_id=l.taxon_meaning_id or m.external_key=l.external_key)
            where l.taxon_meaning_id in (#taxon_meaning_list#)",
        ],
      ],
      'taxa_taxon_list_external_key_list' => [
        'datatype' => 'string[]',
        'display' => "Taxon external keys",
        'description' => 'Comma separated list of taxon external keys',
        'wheres' => [
          [
            'sql' => "o.taxon_path && ARRAY[#taxon_meaning_ids_from_keys#]",
          ],
        ],
        'preprocess' => [
          'taxon_meaning_ids_from_keys' => "select string_agg(distinct taxon_meaning_id::text, ',')
            from cache_taxa_taxon_lists
            where external_key in (#taxa_taxon_list_external_key_list#)",
        ],
        // Datatype of processed parameter differs.
        'processed_datatype' => 'integer[]',
      ],
      'taxon_designation_list' => [
        'datatype' => 'integer[]',
        'display' => 'Taxon designations',
        'description' => 'Comma separated list of taxon designation IDs',
        'joins' => [
          [
            'sql' =>
              "join taxa_taxon_lists ttlpref on ttlpref.id=o.preferred_taxa_taxon_list_id and ttlpref.deleted=false\n" .
              "join taxa_taxon_designations ttd on ttd.taxon_id=ttlpref.taxon_id and ttd.deleted=false " .
              "and ttd.taxon_designation_id in (#taxon_designation_list#)",
          ],
        ],
      ],
      'identification_difficulty' => [
        'datatype' => 'integer',
        'display' => 'Identification difficulty',
        'description' => 'Identification difficulty on a scale of 1 to 5',
        'wheres' => [
          [
            'sql' => "coalesce(o.identification_difficulty, 0) #identification_difficulty_op# #identification_difficulty#",
          ],
        ],
      ],
      'taxa_taxon_list_attribute_ids' => [
        'datatype' => 'integer[]',
        'display' => 'Taxon attribute IDs',
        'description' => 'List of taxa_taxon_list_attribute_ids that will be searched for terms when using the ' .
          'taxa_taxon_list_attribute_terms_ids parameter.',
      ],
      'taxa_taxon_list_attribute_termlist_term_ids' => [
        'datatype' => 'integer[]',
        'display' => 'Taxon attribute term IDs',
        'description' => 'List of termlist_term_ids that must be linked to the taxa returned by the report as taxa ' .
          'taxon list attributes. Use in conjunction with taxa_taxon_list_attribute_ids.',
        'joins' => [
          [
            'sql' => 'join taxa_taxon_list_attribute_values ttl_attribute_terms ' .
              'on ttl_attribute_terms.taxa_taxon_list_id=o.preferred_taxa_taxon_list_id ' .
              'and ttl_attribute_terms.taxa_taxon_list_attribute_id in (#taxa_taxon_list_attribute_ids#) ' .
              'and ttl_attribute_terms.int_value in (#taxa_taxon_list_attribute_termlist_term_ids#)',
          ],
        ],
      ],
      'taxa_scratchpad_list_id' => [
        'datatype' => 'integer',
        'display' => 'Scratchpad taxon list',
        'description' => 'Limit to taxa listed in a scratchpad list.',
        'wheres' => [
          [
            'sql' => "o.taxon_group_id IN (#taxon_group_ids#) and o.taxon_path && ARRAY[#taxon_meaning_ids_from_scratchpad#]",
          ],
        ],
        'preprocess' => [
          'taxon_meaning_ids_from_scratchpad' => "select string_agg(distinct m.taxon_meaning_id::text, ',')
            from scratchpad_list_entries sle
            join cache_taxa_taxon_lists l on l.id=sle.entry_id
            join cache_taxa_taxon_lists m on (m.taxon_meaning_id=l.taxon_meaning_id or m.external_key=l.external_key)
            where sle.scratchpad_list_id=#taxa_scratchpad_list_id#",
          // Adds a second filter on taxon group ID. This is more likely to
          // successfully use an index when the list of taxon meaning IDs is
          // long.
          'taxon_group_ids' => "select string_agg(distinct l.taxon_group_id::text, ',')
            from scratchpad_list_entries sle
            join cache_taxa_taxon_lists l on l.id=sle.entry_id
            where sle.scratchpad_list_id=#taxa_scratchpad_list_id#",
        ],
      ],
      'licences' => [
        'datatype' => 'string[]',
        'display' => 'Record licence types',
        'description' => 'Licence types to show records for. Options are none, open and restricted.',
        'wheres' => [
          [
            'sql' => "o.licence_id in (#licences_from_licence_types#) or (o.licence_id is null and (array[#licences-unprocessed#] && array['none']))",
          ],
        ],
        'preprocess' => [
          'licences_from_licence_types' => "select string_agg(id::text, ', ')
            from licences
            where deleted=false
            and case open
              when true then array['open'] && array[#licences#]
              else array['restricted'] && array[#licences#]
            end",
        ],
        // Datatype of processed parameter differs.
        'processed_datatype' => 'integer[]',
      ],
      'media_licences' => [
        'datatype' => 'string[]',
        'display' => 'Media licence types',
        'description' => 'Licence types to show records with media licences for. Options are none, open and restricted.',
        'wheres' => [
          [
            'sql' => "(o.media_count=0 OR exists(
              select * from occurrence_media m
              where m.deleted=false
              and o.id=m.occurrence_id
              and (m.licence_id in (#licences_from_licence_types#) or (m.licence_id is null and (array[#media_licences-unprocessed#] && array['none'])))
            ))",
          ],
        ],
        'preprocess' => [
          'licences_from_licence_types' => "select string_agg(id::text, ', ')
            from licences
            where deleted=false
            and case open
              when true then array['open'] && array[#media_licences#]
              else array['restricted'] && array[#media_licences#]
            end",
        ],
        // Datatype of processed parameter differs.
        'processed_datatype' => 'integer[]',
      ],
      'coordinate_precision' => [
        'datatype' => 'integer',
        'display' => 'Coordinate precision',
        'description' => 'Filter on the coordinate precision of the record',
        'wheres' => [
          [
            'sql' => "
              WHEN o.sensitive=true OR snf.privacy_precision IS NOT NULL OR snf.entered_sref_system NOT SIMILAR TO '[0-9]+' THEN
                  get_sref_precision(onf.output_sref, onf.output_sref_system, null)
              ELSE
                COALESCE(snf.attr_sref_precision, 50)
              END #coordinate_precision_op# #coordinate_precision#",
          ],
        ],
      ],
    ];
  }

  /**
   * Information about parameter difference for legacy reasons.
   *
   * When the cache tables were restructured some of the fields and logic in the SQL for parameters changed. This
   * function allows filter SQL to be mapped back to SQL compatible with the old structure and is used by reports
   * that have not been migrated to benefit from the new structure (e.g. if they use the cache_occurrences view).
   * Not implemented for samples.
   */
  public static function getLegacyStructureParameters() {
    return [
      'input_date_from' => [
        'wheres' => [
          [
            // Use filter on both created_on and updated_on, as the latter is
            // indexed.
            'sql' => "o.cache_created_on >= '#input_date_from#'::timestamp AND o.cache_updated_on >= '#input_date_from#'::timestamp",
          ],
        ],
      ],
      'input_date_to' => [
        'wheres' => [
          [
            'sql' =>
              "('#input_date_to#'='Click here' OR (o.cache_created_on <= '#input_date_to#'::timestamp " .
              "OR (length('#input_date_to#')<=10 AND o.cache_created_on < cast('#input_date_to#' as date) + '1 day'::interval)))",
          ],
        ],
      ],
      'input_date_age' => [
        'wheres' => [
          [
            // Use filter on both created_on and updated_on, as the latter is
            // indexed.
            'sql' => "o.cache_created_on>now()-'#input_date_age#'::interval AND o.cache_updated_on>now()-'#input_date_age#'::interval",
          ],
        ],
      ],
      'edited_date_from' => [
        'wheres' => [
          [
            'sql' => "('#edited_date_from#'='Click here' OR o.cache_updated_on >= '#edited_date_from#'::timestamp)",
          ],
        ],
      ],
      'edited_date_to' => [
        'wheres' => [
          [
            'sql' =>
              "('#edited_date_to#'='Click here' OR (o.cache_updated_on <= '#edited_date_to#'::timestamp " .
              "OR (length('#edited_date_to#')<=10 AND o.cache_updated_on < cast('#edited_date_to#' as date) + '1 day'::interval)))",
          ],
        ],
      ],
      'edited_date_age' => [
        'wheres' => [
          [
            'sql' => "o.cache_updated_on>now()-'#edited_date_age#'::interval",
          ],
        ],
      ],
      'exclude_sensitive' => [
        'wheres' => [
          [
            'sql' => "o.sensitivity_precision is null",
          ],
        ],
      ],
      'marine_flag' => [
        'joins' => [
          [
            'standard_join' => 'sj_prefcttl',
          ],
        ],
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "sj_prefcttl.marine_flag=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "(sj_prefcttl.marine_flag is null or sj_prefcttl.marine_flag=false)",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'freshwater_flag' => [
        'joins' => [
          [
            'standard_join' => 'sj_prefcttl',
          ],
        ],
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "sj_prefcttl.freshwater_flag=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "(sj_prefcttl.freshwater_flag is null or sj_prefcttl.freshwater_flag=false)",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'terrestrial_flag' => [
        'joins' => [
          [
            'standard_join' => 'sj_prefcttl',
          ],
        ],
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "sj_prefcttl.terrestrial_flag=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "(sj_prefcttl.terrestrial_flag is null or sj_prefcttl.terrestrial_flag=false)",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'non_native_flag' => [
        'joins' => [
          [
            'standard_join' => 'sj_prefcttl',
          ],
        ],
        'wheres' => [
          [
            'value' => 'Y',
            'operator' => 'equal',
            'sql' => "sj_prefcttl.non_native_flag=true",
          ],
          [
            'value' => 'N',
            'operator' => 'equal',
            'sql' => "(sj_prefcttl.non_native_flag is null or sj_prefcttl.non_native_flag=false)",
          ],
          // The all filter does not need any SQL.
        ],
      ],
      'autochecks' => [
        'wheres' => [
          [
            'value' => 'F',
            'operator' => 'equal',
            'sql' => "o.data_cleaner_info is not null and o.data_cleaner_info<>'pass'",
          ],
          [
            'value' => 'P',
            'operator' => 'equal',
            'sql' => "o.data_cleaner_info = 'pass'",
          ],
        ],
      ],
      'has_photos' => [
        'wheres' => [
          [
            'value' => '1',
            'operator' => 'equal',
            'sql' => "o.images is not null",
          ],
          [
            'value' => '0',
            'operator' => 'equal',
            'sql' => "o.images is null",
          ],
        ],
      ],
      'taxon_rank_sort_order' => [
        'wheres' => [
          [
            'sql' => "o.taxon_rank_sort_order #taxon_rank_sort_order_op# #taxon_rank_sort_order#",
          ],
        ],
      ],
      'confidential' => [
        // Disables the confidential filter on legacy reports.
        'wheres' => [],
      ],
    ];
  }

  /**
   * Returns an array of the parameters which have defaults and their associated default values.
   *
   * @return array
   *   Associative array of parameters with defaults.
   */
  public static function getDefaultParameterValues() {
    return [
      'date_year_op' => '=',
      'input_date_year_op' => '=',
      'edited_date_year_op' => '=',
      'verified_date_year_op' => '=',
      'occ_id_op' => '=',
      'smp_id_op' => '=',
      'taxon_rank_sort_order_op' => '=',
      'website_list_op' => 'in',
      'survey_list_op' => 'in',
      'input_form_list_op' => 'in',
      'location_list_op' => 'in',
      'indexed_location_list_op' => 'in',
      'identification_difficulty_op' => '=',
      'quality_op' => 'in',
      'coordinate_precision_op' => '<=',
      'date_year_op_context' => '=',
      'input_date_year_op_context' => '=',
      'edited_date_year_op_context' => '=',
      'verified_date_year_op_context' => '=',
      'occ_id_op_context' => '=',
      'smp_id_op_context' => '=',
      'website_list_op_context' => 'in',
      'survey_list_op_context' => 'in',
      'input_form_list_op_context' => 'in',
      'location_list_op_context' => 'in',
      'indexed_location_list_op_context' => 'in',
      'identification_difficulty_op_context' => '=',
      'quality_op_context' => 'in',
      'coordinate_precision_op_context' => '<=',
      'release_status' => 'R',
      'confidential' => 'f',
      'user_id' => 0,
    ];
  }

}
