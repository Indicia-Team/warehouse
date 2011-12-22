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
 * @package	Verification Check
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the verification checker to declare checks for the test of species rarity.
 * Any designated species is picked up by this test. 
 * @return type array of rules.
 */
function verification_check_species_designation_verification_rules() {
  foreach (Kohana::config('config.modules') as $path) {
    $plugin = basename($path);
    if ($plugin==='taxon_designations') {
      return array(
        array(
          'message'=>'This species is designated \' || td.title || \'.',
          'query' => array(
            'joins' =>
                "join taxa_taxon_designations ttd on ttd.taxon_id=occlist.taxon_id and ttd.deleted=false ".
                "join taxon_designations td on td.id=ttd.taxon_designation_id and td.deleted=false "
          )
        )
      );
    }
  }
  echo 'taxon_designations module not installed so cannot run taxon designation rule checks.';
  kohana::log('alert', 'taxon_designations module not installed so cannot run taxon designation rule checks.');
  return array();
}

?>