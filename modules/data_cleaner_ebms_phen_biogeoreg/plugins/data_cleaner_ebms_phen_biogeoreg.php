<?php

/**
 * @file
 * Plugin functions for the data_cleaner_period_within_year_and_location rule.
 *
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
 * @link http://code.google.com/p/indicia/
 */

/**
 * Hook into the data cleaner to declare checks for the test of eBMS phenology within biogeographical region. 
 * @return type array of rules.
 */
function data_cleaner_ebms_phen_biogeoreg_data_cleaner_rules() {

  return [
    'testType' => 'ebmsPhenBiogeoreg',
    'optional' => [
      'Metadata' => [
        'Tvk',
        'Bgr',
      ],
      'Data' => [
        'StartDate',
        'EndDate',
      ],
    ],
    'queries' => [
      [
        'joins' =>
        "JOIN verification_rule_metadata vrm ON
          vrm.key = 'Tvk' AND
          vrm.value = co.taxa_taxon_list_external_key AND
          vrm.deleted = false
        JOIN verification_rules vr ON
          vr.id = vrm.verification_rule_id AND
          vr.test_type = 'ebmsPhenBiogeoreg' AND
          vr.deleted = false	  
        JOIN verification_rule_metadata vrmdBgr ON 
          vrmdBgr.verification_rule_id = vr.id AND 
          vrmdBgr.key = 'Bgr' AND
          vrmdBgr.deleted = false
        JOIN verification_rule_data vrdStartDate ON 
          vrdStartDate.verification_rule_id = vr.id AND 
          vrdStartDate.key = 'StartDate' AND
          vrdStartDate.deleted = false
        JOIN verification_rule_data vrdEndDate ON 
          vrdEndDate.verification_rule_id = vr.id AND 
          vrdEndDate.key = 'EndDate' AND 
          vrdEndDate.data_group = vrdStartDate.data_group AND
          vrdEndDate.deleted = false
        JOIN locations l ON
          l.code = vrmdBgr.value AND
          l.location_type_id = " . kohana::config('data_cleaner_ebms_phen_biogeoreg.location_type_id') . " AND
          l.deleted = false",
        'where' =>
        "ST_INTERSECTS(l.boundary_geom, co.public_geom)",
        'groupBy' => 
        "GROUP BY co.id, vr.error_message, co.taxa_taxon_list_external_key, co.verification_checks_enabled, co.record_status
          HAVING SUM((EXTRACT(doy from co.date_start) >= EXTRACT(doy from cast('2012' || vrdStartDate.value as date)) AND
          EXTRACT(doy from co.date_end) <= EXTRACT(doy from cast('2012' || vrdEndDate.value as date)))::integer) = 0",
      ],
    ],
  ];
}