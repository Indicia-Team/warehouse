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
 * @package	Data Cleaner
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the data cleaner to declare checks species being in a provided list of expected species. Can use TVK (external_key)
 * to identify the species (so rules can be compatible with Record Cleaner), or preferred species name if names are put into a [Taxa] data section.
 * Set SurveyId to the ID of a survey in metadata if this only applies to one survey. Set SpeciesFieldName to 'preferredName'
 * if looking up species names rather than by Taxon_Version_Key.
 * @return type array of rules.
 */
function data_cleaner_ancillary_species_data_cleaner_rules() {
  return array(
    'testType' => 'AncillarySpecies',
    'optional' => array(
      'Metadata'=>array('SurveyId'),
      'Data'=>array('*'), 'Taxa'=>array('*'), 'INI'=>array('*')
    ),
    'queries' => array(
      array(
        'joins' => 
            "join verification_rules vr on vr.test_type='AncillarySpecies' and vr.deleted=false ".
            "left join verification_rule_data vrd on vrd.verification_rule_id=vr.id and vrd.deleted=false ".
            "  and ((upper(vrd.key)=upper(co.taxa_taxon_list_external_key) and upper(vrd.header_name)='DATA') ".
            "or (upper(vrd.key)=upper(co.preferred_taxon) and upper(vrd.header_name)='TAXA')) ".
            "left join verification_rule_metadata vrms on vrms.verification_rule_id=vr.id and vrms.key='SurveyId' and vrms.deleted=false ".
            "left join verification_rule_data vrdini on vrdini.verification_rule_id=vr.id and vrdini.header_name='INI' and vrdini.key=vrd.value and vrdini.deleted=false",
        'where' =>
            "((vr.reverse_rule and vrd.id is not null) or (not vr.reverse_rule and vrd.id is null)) and ".
            "(vrms.value=cast(co.survey_id as character varying) or vrms.id is null)"
      )
    )
  );
}

?>