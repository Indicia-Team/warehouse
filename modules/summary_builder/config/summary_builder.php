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

// need to process if anything is flagged as deleted: no deleted flags on this query.
$config['get_changed_items_query'] = "
  select o.id, o.taxa_taxon_list_id, s.id as sample_id, p.id as parent_sample_id, p.date_start, p.created_by_id, p.location_id  
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id#
	WHERE o.updated_on>='#date#'
	LIMIT #limit#";

$config['get_missed_items_query'] = "
  select o.id, o.taxa_taxon_list_id, s.id as sample_id, p.id as parent_sample_id, p.date_start, p.created_by_id, p.location_id  
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.deleted = 'f'
	LEFT JOIN summary_occurrences so ON so.survey_id = #survey_id# AND so.taxa_taxon_list_id = o.taxa_taxon_list_id AND so.location_id = p.location_id AND so.user_id = p.created_by_id AND p.date_start <= so.date_end AND p.date_start >= so.date_start
    WHERE so.survey_id IS NULL AND o.deleted = 'f'
	ORDER BY o.id ASC
	LIMIT #limit#";

$config['get_YearTaxonLocationUser_query'] = "
  select 1 as count, p.id as sample_id, p.date_start
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.created_by_id = #user_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  select 0 as count, p.id as sample_id, p.date_start
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.created_by_id = #user_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonLocationUser_Attr_query'] = "
  select oav.int_value as count, p.id as sample_id, p.date_start
	FROM occurrences o
	JOIN occurrence_attribute_values oav ON oav.occurrence_id = o.id AND oav.deleted = 'f' AND oav.occurrence_attribute_id = #attr_id# 
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.created_by_id = #user_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  select 0 as count, p.id as sample_id, p.date_start
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.created_by_id = #user_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonLocation_query'] = "
  select 1 as count, p.id as sample_id, p.date_start
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  select 0 as count, p.id as sample_id, p.date_start
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonLocation_Attr_query'] = "
  select oav.int_value as count, p.id as sample_id, p.date_start
	FROM occurrences o
	JOIN occurrence_attribute_values oav ON oav.occurrence_id = o.id AND oav.deleted = 'f' AND oav.occurrence_attribute_id = #attr_id# 
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  select 0 as count, p.id as sample_id, p.date_start
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonUser_query'] = "
  select count, estimate, date_start
	FROM summary_occurrences
	WHERE survey_id = #survey_id# AND user_id = #user_id# AND date_end>='#year#-01-01' AND date_start<='#year#-12-31' AND taxa_taxon_list_id = #taxon_id# AND location_id IS NOT NULL
  ";
$config['get_YearTaxon_query'] = "
  select count, estimate, date_start
	FROM summary_occurrences
	WHERE survey_id = #survey_id# AND user_id IS NULL AND date_end>='#year#-01-01' AND date_start<='#year#-12-31' AND taxa_taxon_list_id = #taxon_id# AND location_id IS NOT NULL 
  ";
?>
