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
 * @package	Client
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Extension class that supplies new controls to support species associations/interactions.
 */
class extension_associations {

  /**
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options Array of options provided as @ parameters in the configuration. The possibilities are:
   *   * from_grid_id - ID of the species checklist grid containing the species associations are being recorded from.
   *   * to_grid_id - ID of the species checklist grid containing the species associations are being recorded to.
   *   * association_type_termlist - Title of a termlist which is available for selection to set the type of
   *     association. You can alternatively provide association_type_termlist_id if you would prefer to specify
   *     the ID rather than the title, though this is less portable between staging and live warehouses.
   *   * association_type - Term from the termlist above which sets the type of association, if only
   *     one type allowed. This causes the association types select control to be ommitted from the output.
   *   * part_termlist - as above for parts
   *   * part
   *
   * @todo CHECK COMMENT ABOVE COMPLETE AND ACCURATE
   *
   *
   * @param $path
   * @return string
   */
  public static function input_associations_list($auth, $args, $tabalias, $options, $path) {
    if (empty($options['association_type_termlist']) && empty($options['association_type_termlist_id']))
      return 'The associations.input_associations_list control requires an association_type_termlist parameter.';
    if (empty($options['from_grid_id']) || empty($options['to_grid_id']))
      return 'The associations.input_associations_list control requires a from_grid_id and to_grid_id parameter.';
    $r = '<button type="button" id="associations-add">Add an association</button>';
    self::read_termlist_details($auth, $options);
    data_entry_helper::$javascript .= "indiciaData.associationCtrlOptions = " . json_encode($options) . ";\n";
    $r .= '<div id="associations-list"></div>';
    self::load_existing_data($auth);
    return $r;
  }

  /**
   * When editing an existing sample, load the associations data.
   */
  private static function load_existing_data($auth) {
    if (!empty(data_entry_helper::$entity_to_load['sample:id'])) {
      $data = data_entry_helper::get_population_data(array(
        'table' => 'occurrence_association',
        'extraParams' => $auth['read'] + array('sample_id' => data_entry_helper::$entity_to_load['sample:id'], 'view'=>'detail'),
        'caching' => false
      ));
      data_entry_helper::$javascript .= "populate_existing_associations(" . json_encode($data) . ");\n";
    }
  }

  /**
   * Populates the options array with the termlist IDs and termlist contents needed for the form controls.
   * @param $auth
   * @param $options
   */
  private static function read_termlist_details($auth, &$options) {
    $allRelevantTermlists = array('association_type', 'position', 'part', 'impact');
    // check which of all the termlists we have a title for but no ID so we know to convert them
    $termlistsToConvert = array();
    foreach ($allRelevantTermlists as $termlistToCheck) {
      if (!empty($options["{$termlistToCheck}_termlist"]) && empty($options["{$termlistToCheck}_termlist_id"])) {
        $termlistsToConvert[] = $options["{$termlistToCheck}_termlist"];
      }
    }
    if (!empty($termlistsToConvert)) {
      $termlists = data_entry_helper::get_population_data(array(
        'table' => 'termlist',
        'extraParams' => $auth['read'] + array(
            'query' => json_encode(array(
              'in' => array(
                'title',
                $termlistsToConvert
              )
            ))
          )
      ));
      foreach ($termlists as $termlist) {
        foreach ($allRelevantTermlists as $termlistToCheck) {
          if (!empty($options["{$termlistToCheck}_termlist"]) && $termlist['title']===$options["{$termlistToCheck}_termlist"])
            $options["{$termlistToCheck}_termlist_id"] = $termlist['id'];
        }
      }
    }
    // now for each termlist, get the term records
    foreach ($allRelevantTermlists as $termlistToCheck) {
      if (!empty($options["{$termlistToCheck}_termlist_id"])) {
        $terms = data_entry_helper::get_population_data(array(
          'table' => 'termlists_term',
          'extraParams' => $auth['read'] + array(
              'view' => 'cache',
              'termlist_id' => $options["{$termlistToCheck}_termlist_id"],
              'preferred' => 't',
              'columns' => 'id,term'
            )
        ));
        $termsArray = array();
        foreach ($terms as $term) {
          $termsArray[$term['id']] = $term['term'];
        }
        $options["{$termlistToCheck}_terms"] = $termsArray;
      }
    }
  }

}