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

// following assumes that any update to locations/samples/occurrences leads to a change in the updated_on fields
// this is not necessarily the case for direct DB access. Direct DB access may lead to
// a situation where a full rebuild of the cache is required.

$config['summary_truncate'] = "
  TRUNCATE summary_occurrences";

$config['select_definitions'] = "
  SELECT d.*, s.website_id FROM summariser_definitions d
	JOIN surveys s ON s.id = d.survey_id AND s.deleted = 'f'
	WHERE d.deleted = 'f'";

$config['clear_survey'] = "
  DELETE FROM summary_occurrences
	WHERE survey_id = #survey_id#";

$config['clear_survey_location'] = "
  DELETE FROM summary_occurrences
	WHERE survey_id = #survey_id#
		AND location_id = #location_id#";

$config['clear_survey_taxon'] = "
  DELETE FROM summary_occurrences
	WHERE survey_id = #survey_id#
		AND taxa_taxon_list_id = #taxa_taxon_list_id#";

$config['first_sample_creation_date'] = "
  SELECT min(created_on)::date  - INTEGER '1' as first_date
	FROM samples
	WHERE survey_id = #survey_id#";

$config['rebuild_survey'] = "
  UPDATE summary_occurrences
	SET summary_created_on = '#date#'
	WHERE survey_id = #survey_id#";

// Pick up if a deleted location has any entries in the summary_occurrences (sweeps up).
// in sweep up mode still works OK, it returns stuff in the cache table.
// TODO consider use of restriction on location_type_id

// All entries in summary_occurrences which are for deleted locations
$config['get_deleted_locations_query'] = "
SELECT distinct location_id, user_id, taxa_taxon_list_id, year
	FROM summary_occurrences so
	WHERE survey_id = #survey_id# AND location_id IS NOT NULL AND user_id IS NOT NULL
		AND location_id NOT IN (
			SELECT l.id
				FROM locations l
				LEFT JOIN locations_websites lw ON l.id = lw.location_id AND lw.deleted = false
				WHERE l.deleted = false AND (l.public = true OR lw.website_id = #website_id#))";

// No need to check for new locations as only appears in the data when a sample is added.

// Can't detect a change of location_id or date proerly on a sample: can detect new value OK, but not old - required
// to rebuild old data.
// Would have to have proper trigger: can do full rebuild instead.
// Curently not possible to change location or date in UKBMS front end. so not worrying about it.

// first part returns samples that have been deleted since last run date
// don't need to worry about deletion of subsamples, as these are bubbled
// down to the occurrences, also it is only really the supersamples that impact
// the calculations.
// Other non-delete updates to the samples are not actually important: 
$config['get_deleted_samples_query'] = "
  SELECT p.date_start, p.created_by_id as user_id, p.location_id
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.parent_id IS NULL
		AND p.location_id IS NOT NULL
		AND p.updated_on >= '#date#'
		AND p.deleted = 't'
LIMIT #limit#";
// second part returns samples that have been created since last run date
$config['get_created_samples_query'] = "
  SELECT p.date_start, p.created_by_id as user_id, p.location_id
	FROM samples p
	WHERE p.location_id IS NOT NULL
		AND p.parent_id IS NULL
		AND p.deleted = 'f'
		AND p.survey_id = #survey_id#
		AND p.created_on >= '#date#'
LIMIT #limit#";

// first part returns samples that have been deleted since the equivalent summary_created_on was created
//   Only need to pick up deleted parent samples, as deleting the subsample automatically deletes the 
//   occurrences, which are picked up in the items check. Presence of the (potentially empty) parent sample
//   is what impacts calculations.
$config['get_missed_deleted_samples_query'] = "
  SELECT DISTINCT p.date_start as date_start, p.created_by_id as user_id, p.location_id
	FROM samples p
	JOIN summary_occurrences so ON so.survey_id = #survey_id#
		AND so.location_id = p.location_id
		AND so.user_id = p.created_by_id
		AND p.date_start <= so.date_end
		AND p.date_start >= so.date_start
		AND p.updated_on > so.summary_created_on
	WHERE p.parent_id IS NULL
		AND p.survey_id = #survey_id#
		AND p.deleted = 't'
LIMIT #limit#";
// Other non-delete updates to the samples are not actually important: 
// second part returns samples that have been created since the equivalent summary_created_on was created,
//   or which have no entries at all. In this case at least one sample for the year must have occurrences
//   in order for data to be present, otherwise no point in doing it - this sample would always show up.
// During a rebuild, it is this query that does the vast majority of the work. The order by allows us to tell
// how far through the rebuild it has got.
$config['get_missed_created_samples_query'] = "
  SELECT p.date_start as date_start, p.created_by_id as user_id, p.location_id
	FROM samples p
	LEFT JOIN summary_occurrences so ON so.survey_id = #survey_id#
		AND so.location_id = p.location_id
		AND p.date_start BETWEEN so.date_start AND so.date_end
		AND p.created_on < so.summary_created_on
	WHERE p.parent_id IS NULL
		AND p.survey_id = #survey_id#
		AND p.location_id IS NOT NULL
		AND p.deleted = 'f'
        AND so.survey_id IS NULL
		AND EXISTS (SELECT 1
			FROM samples p2
			JOIN samples s2 ON p2.id = s2.parent_id
			JOIN occurrences o2 ON s2.id = o2.sample_id AND o2.deleted = 'f'
			WHERE EXTRACT(YEAR FROM p.date_start) = EXTRACT(YEAR FROM p2.date_start)
				AND p2.parent_id IS NULL
				AND p2.survey_id = #survey_id#
				AND p2.location_id = p.location_id
				AND p2.deleted = 'f')
  ORDER BY date_start
LIMIT #limit#";

// return all the taxa ever recorded for this location on this year - includes deleted deliberately
$config['get_taxa_query'] = "
  SELECT distinct o.taxa_taxon_list_id
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id
	JOIN samples p ON s.parent_id = p.id
		AND p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.date_end>='#year#-01-01'
		AND p.date_start<='#year#-12-31'
";

// Pick up if an occurrence has been deleted, modified or created -> user_id, location_id, taxon_id.
// Need to rebuild all the entries which have a location_id set to the location (user and non user specific) for that taxa.
// Need to rebuild all the entries which have the user_id, for that taxa.
// Need to rebuild the top level global entries for that taxa.

// pick up if the sample/parent has been deleted since last run.

// need to process if any occurrence is flagged as deleted: no deleted flags on this query.
// changes to samples handled elsewhere.
// also pick up if the sample/parent has been deleted, occurrence left alone.
// not checking if dates or locations have changed on samples, or change of taxon on occurrence:
//   cant do at moment - but also not possible through UKBMS front end. 
$config['get_changed_occurrences_query'] = "
  SELECT o.taxa_taxon_list_id, p.date_start, p.created_by_id, p.location_id  
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id IS NOT NULL
	WHERE o.updated_on>='#date#'
	LIMIT #limit#";

$config['get_missed_changed_occurrences_query'] = "
  SELECT distinct o.taxa_taxon_list_id, p.date_start, p.created_by_id, p.location_id  
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.deleted = 'f' AND p.location_id IS NOT NULL
	LEFT JOIN summary_occurrences so ON so.survey_id = #survey_id# AND so.taxa_taxon_list_id = o.taxa_taxon_list_id
		AND so.location_id = p.location_id AND so.user_id = p.created_by_id AND p.date_start <= so.date_end AND p.date_start >= so.date_start
		AND o.updated_on < so.summary_created_on
    WHERE so.survey_id IS NULL AND o.deleted = 'f'
	LIMIT #limit#";

$config['get_missed_deleted_occurrences_query'] = "
  SELECT o.taxa_taxon_list_id, p.date_start, p.created_by_id, p.location_id  
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id IS NOT NULL
	JOIN summary_occurrences so ON so.survey_id = #survey_id# AND so.taxa_taxon_list_id = o.taxa_taxon_list_id
		AND so.location_id = p.location_id AND so.user_id = p.created_by_id AND p.date_start <= so.date_end AND p.date_start >= so.date_start
		AND o.updated_on>so.summary_created_on
	WHERE o.deleted = 't'
	LIMIT #limit#";

// Rebuild all the data on rotation: this will catch any oddities, like change of taxon
// Order by picks up the oldest entries
// would like to put a distinct on this, but there is a significant performance hit.

$config['get_rebuild_occurrences_query'] = "
  SELECT so.taxa_taxon_list_id, so.location_id, so.user_id, so.year
  FROM summary_occurrences so
  WHERE so.survey_id = #survey_id#
    AND so.user_id IS NOT NULL
    AND so.location_id IS NOT NULL
  ORDER BY so.summary_created_on
  LIMIT #limit#";

// Pick up changes to the taxon list
// Need to flag if there are any occurrences attached to deleted taxa.
// If a taxa has been changed, then need to rebuild all the entries for that taxa - if preferred has changed, so will the meaning id.
// But, these this are done en-masse, so there is a real possibility that the limit will be exceed on a single run.
// So pick up if an taxa update date is after an summary occurrence entry -> user_id, location_id, taxon_id.
// This method means no need for a separate missing check.
// would like to do a distinct but there is a significant performance hit.
$config['get_changed_taxa_query'] = "
  SELECT so.taxa_taxon_list_id, so.year, so.user_id, so.location_id, so.summary_created_on, cttl.cache_updated_on
  FROM summary_occurrences so
  JOIN cache_taxa_taxon_lists cttl ON so.taxa_taxon_list_id = cttl.id
    AND so.summary_created_on < cttl.cache_updated_on
  WHERE so.survey_id = #survey_id#
  LIMIT #limit#";



$config['get_YearTaxonLocationUser_query'] = "
  SELECT 1 AS count, p.id AS sample_id, p.date_start, 't' as present
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.created_by_id = #user_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
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
  ";

$config['get_YearTaxonLocationUser_Attr_query'] = "
  SELECT oav.int_value AS count, p.id AS sample_id, p.date_start, 't' as present
	FROM occurrences o
	JOIN occurrence_attribute_values oav ON oav.occurrence_id = o.id AND oav.deleted = 'f' AND oav.occurrence_attribute_id = #attr_id# 
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.created_by_id = #user_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
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
  ";

$config['get_YearTaxonLocation_query'] = "
  SELECT 1 AS count, p.id AS sample_id, p.date_start, 't' as present
	FROM occurrences o
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start, 'f' as present
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonLocation_Attr_query'] = "
  SELECT oav.int_value AS count, p.id AS sample_id, p.date_start, 't' as present
	FROM occurrences o
	JOIN occurrence_attribute_values oav ON oav.occurrence_id = o.id AND oav.deleted = 'f' AND oav.occurrence_attribute_id = #attr_id# 
	JOIN samples s ON s.id = o.sample_id AND s.deleted = 'f'
	JOIN samples p ON s.parent_id = p.id AND p.survey_id = #survey_id# AND p.location_id = #location_id# AND p.deleted = 'f' AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
	WHERE o.taxa_taxon_list_id = #taxon_id#
		AND o.deleted = 'f' AND o.zero_abundance = 'f'
  UNION ALL
  SELECT 0 AS count, p.id AS sample_id, p.date_start, 'f' as present
	FROM samples p
	WHERE p.survey_id = #survey_id#
		AND p.location_id = #location_id#
		AND p.deleted = 'f'
		AND p.date_end>='#year#-01-01' AND p.date_start<='#year#-12-31'
  ";

$config['get_YearTaxonUser_query'] = "
  SELECT count, estimate, date_start
	FROM summary_occurrences
	WHERE survey_id = #survey_id#
		AND user_id = #user_id#
		AND date_end>='#year#-01-01'
		AND date_start<='#year#-12-31'
		AND taxa_taxon_list_id = #taxon_id#
		AND location_id IS NOT NULL
  ";
$config['get_YearTaxon_query'] = "
  SELECT sum(count) as count, sum(estimate) as estimate, date_start
	FROM summary_occurrences
	WHERE survey_id = #survey_id#
		AND user_id IS NULL
		AND date_end>='#year#-01-01'
		AND date_start<='#year#-12-31'
		AND taxa_taxon_list_id = #taxon_id#
		AND location_id IS NOT NULL 
  GROUP BY date_start
  ";
