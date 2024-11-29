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
 * @package    Modules
 * @subpackage Summary builder
 * @author     Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL
 * @link       https://github.com/indicia-team/warehouse/
 */

// need to process deleted samples
$config['get_samples_to_process'] =
"SELECT record_id as sample_id, sd.id as definition_id
  FROM work_queue wq
  JOIN samples s ON s.id = wq.record_id AND s.parent_id IS NULL AND s.location_id IS NOT NULL
  JOIN summariser_definitions sd ON sd.survey_id = s.survey_id AND sd.deleted = false
  WHERE wq.claimed_by='#procId#'
    AND wq.entity='sample'
    AND wq.task='#task#';";

$config['get_definition'] =
"SELECT sd.*, s.website_id
  FROM summariser_definitions sd
  JOIN surveys s ON s.id = sd.survey_id AND s.deleted = 'f'
  WHERE sd.id = #definition_id# ;";

// assumes a two level sample/subsample arrangement
// Needs to return the sample details, even if no taxa: allows us to fetch any exsting data.
$config['sample_detail_lookup'] =
"SELECT parent.survey_id, EXTRACT(YEAR FROM parent.date_start) as year, parent.created_by_id as user_id,
    parent.location_id, s.website_id
  FROM samples parent
  JOIN surveys s ON parent.survey_id = s.id
  WHERE parent.id = #sample_id# ;";

$config['sample_occurrence_lookup'] =
"SELECT distinct occ.taxa_taxon_list_id
  FROM samples parent
  JOIN samples child ON child.parent_id = parent.id AND child.deleted = FALSE
  JOIN occurrences occ ON occ.sample_id = child.id AND occ.deleted = FALSE
  WHERE parent.id = #sample_id# ;";

$config['sample_existing_taxa'] =
"SELECT so.taxa_taxon_list_id
  FROM summary_occurrences so
  WHERE so.website_id = #website_id#
    AND so.survey_id = #survey_id#
    AND so.user_id = 0
    AND so.location_id = #location_id#
    AND so.year = #year# ;";

// need to process deleted occurrences: can't use cache
$config['get_occurrences_to_process'] =
"SELECT record_id as occurrence_id, sd.id as definition_id
  FROM work_queue wq
  JOIN occurrences o ON o.id = wq.record_id
  JOIN samples child ON o.sample_id = child.id
  JOIN samples parent ON child.parent_id = parent.id AND parent.location_id IS NOT NULL
  JOIN summariser_definitions sd ON sd.survey_id = parent.survey_id AND sd.deleted = false
  WHERE wq.claimed_by='#procId#'
    AND wq.entity='occurrence'
    AND wq.task='#task#';";

$config['occurrence_detail_lookup'] =
"SELECT EXTRACT(YEAR FROM parent.date_start) as year, parent.created_by_id as user_id,
    parent.location_id, occ.taxa_taxon_list_id
  FROM samples parent
  JOIN samples child ON child.parent_id = parent.id
  JOIN occurrences occ ON occ.sample_id = child.id AND occ.id = #occurrence_id# 
  WHERE parent.location_id IS NOT NULL AND parent.deleted = false ;";

$config['sample_detail_lookup_occurrence'] =
"SELECT parent.id as sample_id, parent.survey_id, EXTRACT(YEAR FROM parent.date_start) as year,
    parent.created_by_id as user_id, parent.location_id, s.website_id
  FROM samples parent
  JOIN samples child ON child.parent_id = parent.id
  JOIN occurrences occ ON occ.sample_id = child.id AND occ.id = #occurrence_id#
  JOIN surveys s ON parent.survey_id = s.id
  WHERE parent.deleted = false ;";

$config['get_locations_to_process'] =
"SELECT record_id as location_id
  FROM work_queue wq
  WHERE wq.claimed_by='#procId#'
    AND wq.entity='location'
    AND wq.task='task_summary_builder_location_delete';";

$config['location_existing_data'] =
"SELECT distinct so.survey_id, so.year, so.user_id, so.taxa_taxon_list_id
  FROM summary_occurrences so
  WHERE so.user_id > 0
  AND so.location_id = #location_id# ;";

$config['get_all_definitions'] =
"SELECT sd.*, s.website_id
  FROM summariser_definitions sd
  JOIN surveys s ON s.id = sd.survey_id AND s.deleted = 'f' ;";

$config['delete_location_data'] =
"DELETE FROM summary_occurrences so
    WHERE so.location_id = #location_id# ;";



$config['get_YearTaxonLocationUser_query'] = "
  SELECT 1 AS count, p.id AS sample_id, p.date_start, 't' as present
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.created_by_id = #user_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31' AND p.training = 'f'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start, 'f' as present
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.created_by_id = #user_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
        AND p.training = 'f'
  ";

$config['get_YearTaxonLocationUser_Attr_query'] = "
  SELECT oav.int_value AS count, p.id AS sample_id, p.date_start, 't' as present
	FROM occurrences o
	JOIN occurrence_attribute_values oav ON oav.occurrence_id = o.id AND oav.deleted = 'f' AND oav.occurrence_attribute_id = #attr_id#
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.created_by_id = #user_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31' AND p.training = 'f'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start, 'f' as present
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.created_by_id = #user_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
        AND p.training = 'f'
  ";

$config['get_YearTaxonLocation_query'] = "
  SELECT 1 AS count, p.id AS sample_id, p.date_start, 't' as present
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31' AND p.training = 'f'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start, 'f' as present
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
        AND p.training = 'f'
  ";

$config['get_YearTaxonLocation_Attr_query'] = "
  SELECT oav.int_value AS count, p.id AS sample_id, p.date_start, 't' as present
	FROM occurrences o
	JOIN occurrence_attribute_values oav ON oav.occurrence_id = o.id AND oav.deleted = 'f' AND oav.occurrence_attribute_id = #attr_id#
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31' AND p.training = 'f'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start, 'f' as present
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
        AND p.training = 'f'
  ";

$config['get_YearTaxonUser_query'] = "
  SELECT summarised_data
	FROM summary_occurrences
	WHERE survey_id = #survey_id#
		AND user_id = #user_id#
		AND year = #year#
		AND taxa_taxon_list_id = #taxon_id#
		AND location_id > 0
  ";
$config['get_YearTaxon_query'] = "
  SELECT summarised_data
	FROM summary_occurrences
	WHERE survey_id = #survey_id#
		AND user_id = 0
		AND year = #year#
		AND taxa_taxon_list_id = #taxon_id#
		AND location_id > 0
  ";
