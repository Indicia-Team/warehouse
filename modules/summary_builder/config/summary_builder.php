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
 * @package	Modules
 * @subpackage Summary builder
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

$config['select_definitions'] = "SELECT d.*, s.website_id FROM summariser_definitions d
	JOIN surveys s ON s.id = d.survey_id AND s.deleted = 'f'
	WHERE d.deleted = 'f'";

// need to process if any occurrence is flagged as deleted: no deleted flags on this query.
// also pick up if the sample/parent has been deleted, occurrence left alone.
// not checking if dates or locations have changed on samples, or change of taxon on occurrence:
//   cant do at moment - but also not possible through UKBMS front end. 
$config['get_changed_items_query'] = "
  SELECT o.id, o.taxa_taxon_list_id, s.id AS sample_id, p.id AS parent_sample_id, p.date_start, p.created_by_id, p.location_id  
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id IS NOT NULL
	WHERE o.updated_on>='#date#' OR (s.updated_on>='#date#' AND s.deleted = 't') OR (p.updated_on>='#date#' AND p.deleted = 't')
	LIMIT #limit#";

$config['get_missed_items_query'] = "
  SELECT o.id, o.taxa_taxon_list_id, s.id AS sample_id, p.id AS parent_sample_id, p.date_start, p.created_by_id, p.location_id  
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.deleted = 'f' AND p.location_id IS NOT NULL
	LEFT JOIN summary_occurrences so ON so.survey_id = #survey_id# AND so.taxa_taxon_list_id = o.taxa_taxon_list_id
		AND so.location_id = p.location_id AND so.user_id = p.created_by_id AND p.date_start <= so.date_end AND p.date_start >= so.date_start
		AND o.updated_on<so.summary_created_on
    WHERE so.survey_id IS NULL AND o.deleted = 'f'
  UNION ALL
  SELECT o.id, o.taxa_taxon_list_id, s.id AS sample_id, p.id AS parent_sample_id, p.date_start, p.created_by_id, p.location_id  
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id IS NOT NULL
	JOIN summary_occurrences so ON so.survey_id = #survey_id# AND so.taxa_taxon_list_id = o.taxa_taxon_list_id
		AND so.location_id = p.location_id AND so.user_id = p.created_by_id AND p.date_start <= so.date_end AND p.date_start >= so.date_start
		AND (o.updated_on>so.summary_created_on OR s.updated_on>so.summary_created_on OR p.updated_on>so.summary_created_on)
	WHERE (o.deleted = 't' OR s.deleted = 't' OR p.deleted = 't')
	LIMIT #limit#";

$config['get_YearTaxonLocationUser_query'] = "
  SELECT 1 AS count, p.id AS sample_id, p.date_start
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.created_by_id = #user_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.created_by_id = #user_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonLocationUser_Attr_query'] = "
  SELECT oav.int_value AS count, p.id AS sample_id, p.date_start
	FROM occurrences o
	JOIN occurrence_attribute_values oav ON oav.occurrence_id = o.id AND oav.deleted = 'f' AND oav.occurrence_attribute_id = #attr_id# 
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.created_by_id = #user_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.created_by_id = #user_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonLocation_query'] = "
  SELECT 1 AS count, p.id AS sample_id, p.date_start
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonLocation_Attr_query'] = "
  SELECT oav.int_value AS count, p.id AS sample_id, p.date_start
	FROM occurrences o
	JOIN occurrence_attribute_values oav ON oav.occurrence_id = o.id AND oav.deleted = 'f' AND oav.occurrence_attribute_id = #attr_id# 
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonUser_query'] = "
  SELECT count, estimate, date_start
	FROM summary_occurrences
	WHERE survey_id = #survey_id# AND user_id = #user_id# AND date_end>='#year#-01-01' AND date_start<='#year#-12-31' AND taxa_taxon_list_id = #taxon_id# AND location_id IS NOT NULL
  ";
$config['get_YearTaxon_query'] = "
  SELECT count, estimate, date_start
	FROM summary_occurrences
	WHERE survey_id = #survey_id# AND user_id IS NULL AND date_end>='#year#-01-01' AND date_start<='#year#-12-31' AND taxa_taxon_list_id = #taxon_id# AND location_id IS NOT NULL 
  ";
?>
