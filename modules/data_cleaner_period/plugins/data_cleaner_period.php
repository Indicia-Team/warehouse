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
 * @package Data Cleaner
 * @subpackage Plugins
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

/**
 * Hook into the data cleaner to declare checks for the test of record period.
 *
 * E.g. for new arrivals or extinctions.
 *
 * @return array
 *   Array of rules.
 */
function data_cleaner_period_data_cleaner_rules() {
  return [
    'testType' => 'period',
    'optional' => [
      'Metadata' => ['StartDate', 'EndDate', 'Tvk', 'Taxon', 'TaxonMeaningId'],
    ],
    'queries' => [
      // Slightly convoluted logic required in this test to get it to work with
      // ranges in middle of year as well as ranges that span the end of the
      // year.
      // Also note in these queries we use 2012 as the year for expanding dates
      // that have just a month and day, as it is a leap year so all dates are
      // covered.
      // Split into 3 on the way it joins taxa to the rule def as this is faster
      // than 1 complex query.
      [
        'joins' =>
        "JOIN cache_taxa_taxon_lists cttl ON
          cttl.id = co.taxa_taxon_list_id
        JOIN verification_rule_metadata vrm ON
          vrm.key = 'Tvk' AND
          vrm.value = co.taxa_taxon_list_external_key AND
          vrm.deleted = false
        JOIN verification_rules vr ON
          vr.id = vrm.verification_rule_id AND
          vr.test_type = 'Period' AND
          vr.deleted = false
        LEFT JOIN verification_rule_metadata vrmstart ON
          vrmstart.verification_rule_id = vr.id AND
          vrmstart.key = 'StartDate' AND
          length(vrmstart.value) = 8 AND
          vrmstart.deleted = false
        LEFT JOIN verification_rule_metadata vrmend ON
          vrmend.verification_rule_id = vr.id AND
          vrmend.key = 'EndDate' AND
          length(vrmend.value) = 8 AND
          vrmend.deleted = false",
        'where' =>
        "vr.reverse_rule <> ((vrmstart is null or vrmend.value is null or vrmstart.value <= vrmend.value)
        and ((vrmstart.value is not null and co.date_start < cast(vrmstart.value as date))
        or (vrmend.value is not null and co.date_start > cast(vrmend.value as date))))",
      ],
      [
        'joins' =>
        "JOIN cache_taxa_taxon_lists cttl ON
          cttl.id=co.taxa_taxon_list_id
        JOIN verification_rule_metadata vrm ON
          vrm.key = 'Taxon' AND
          vrm.value = cttl.preferred_taxon AND
          vrm.deleted = false
        JOIN verification_rules vr ON
          vr.id = vrm.verification_rule_id AND
          vr.test_type = 'Period' AND
          vr.deleted = false
        LEFT JOIN verification_rule_metadata vrmstart ON
          vrmstart.verification_rule_id = vr.id AND
          vrmstart.key = 'StartDate' AND
          length(vrmstart.value) = 8 AND
          vrmstart.deleted = false
        LEFT JOIN verification_rule_metadata vrmend ON
          vrmend.verification_rule_id = vr.id AND
          vrmend.key = 'EndDate' AND
          length(vrmend.value) = 8 AND
          vrmend.deleted = false",
        'where' =>
        "vr.reverse_rule <> ((vrmstart is null or vrmend.value is null or vrmstart.value <= vrmend.value)
        and ((vrmstart.value is not null and co.date_start < cast(vrmstart.value as date))
        or (vrmend.value is not null and co.date_start > cast(vrmend.value as date))))",
      ],
      [
        'joins' =>
        "JOIN cache_taxa_taxon_lists cttl ON
          cttl.id=co.taxa_taxon_list_id
        JOIN verification_rule_metadata vrm ON
          vrm.key = 'TaxonMeaningId' AND
          vrm.value = cast(co.taxon_meaning_id as character varying) AND
          vrm.deleted = false
        JOIN verification_rules vr ON
          vr.id = vrm.verification_rule_id AND
          vr.test_type = 'Period' AND
          vr.deleted = false
        LEFT JOIN verification_rule_metadata vrmstart ON
          vrmstart.verification_rule_id = vr.id AND
          vrmstart.key = 'StartDate' AND
          length(vrmstart.value) = 8 AND
          vrmstart.deleted = false
        LEFT JOIN verification_rule_metadata vrmend ON
          vrmend.verification_rule_id = vr.id AND
          vrmend.key = 'EndDate' AND
          length(vrmend.value) = 8 AND
          vrmend.deleted = false",
        'where' =>
        "vr.reverse_rule <> ((vrmstart is null or vrmend.value is null or vrmstart.value <= vrmend.value)
        and ((vrmstart.value is not null and co.date_start < cast(vrmstart.value as date))
        or (vrmend.value is not null and co.date_start > cast(vrmend.value as date))))",
      ],
    ],
  ];
}

/**
 * Taxon version keys should really be uppercase, so enforce this.
 *
 * Without this, the query would have to be case insensitive which is slow.
 */
function data_cleaner_period_data_cleaner_postprocess($id, $db) {
  $db->query(
    "update verification_rule_metadata set value=upper(value)
    where key ilike 'Tvk'
    and value<>upper(value)
    and verification_rule_id = $id"
  );
}
