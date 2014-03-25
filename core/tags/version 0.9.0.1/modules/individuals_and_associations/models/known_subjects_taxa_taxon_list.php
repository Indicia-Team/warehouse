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
 * @package	Core
 * @subpackage Models
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Model class for the Known_subjects_taxa_taxon_lists table.
 *
 * @package	Groups and individuals module
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Known_subjects_taxa_taxon_list_Model extends ORM
{

  protected $has_one = array(
    //'known_subject',
    //'taxa_taxon_list',
  );
  protected $belongs_to = array(
    'known_subject',
    'taxa_taxon_list',
    'created_by'=>'user',
  );

  public function validate(Validation $array, $save = FALSE) {
    // uses PHP trim() to remove whitespace from beginning and end of all fields before validation
    $array->pre_filter('trim');
    $array->add_rules('known_subject_id','required');
    $array->add_rules('taxa_taxon_list_id','required');

    // Explicitly add those fields for which we don't do validation
    $this->unvalidatedFields = array(
      'deleted',
    );
    return parent::validate($array, $save);
  }

}

?>
