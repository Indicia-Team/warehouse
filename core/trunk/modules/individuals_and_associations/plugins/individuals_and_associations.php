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
 * @package	Groups and Individuals
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Adds a tab for viewing designations for the selected taxon.
 * @todo Complete this tab
 * @return <type>
 */
function individuals_and_associations_extend_ui() {
  return array(array(
    'view'=>'sample/sample_edit', 
    'type'=>'tab',
    'controller'=>'subject_observation/index',
    'title'=>'Subject Observations',
    'allowForNew'=>false,
  ));
}

/**
 * Create a menu item for the list of known subjects.
 */
function individuals_and_associations_alter_menu ($menu, $auth) {
  if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('editor')) {
    $menu['Entered Data']['Known Subjects'] = 'known_subject';
    $menu['Entered Data']['Identifiers'] = 'identifier';
    $menu['Entered Data']['Subject Observations'] = 'subject_observation';
    $menu['Custom Attributes']['Known Subject Attributes'] = 'known_subject_attribute';
    $menu['Custom Attributes']['Identifiers Attributes'] = 'identifier_attribute';
    $menu['Custom Attributes']['Subject Observation Attributes'] = 'subject_observation_attribute';
  }
  return $menu;
}

/**
 * Hook to ORM enable the relationship between known subjects and: 
 * 1) taxa_taxon_lists from the taxon end.
 * 2) websites from the website end.
 */
function individuals_and_associations_extend_orm() {
  return array(
    'taxa_taxon_list'=>array(
      'has_many'=>array('known_subjects_taxa_taxon_lists',),
    ),
    'occurrence'=>array(
      'has_many'=>array('occurrences_subject_observations',),
    ),
    'sample'=>array(
      'has_many'=>array('subject_observations',),
    ),
    'website'=>array(
      'has_many'=>array('known_subjects', 'identifiers', 'subject_observations',),
    ),
  );
}

/**
 * Expose the Groups and Individuals entities through the data services.
 */
function individuals_and_associations_extend_data_services() {
  return array(
    'known_subjects'=>array(),
    'identifiers'=>array(),
    'subject_observations'=>array(),
    'known_subject_attributes'=>array(),
    'identifier_attributes'=>array(),
    'subject_observation_attributes'=>array(),
    'known_subject_attribute_values'=>array(),
    'identifier_attribute_values'=>array(),
    'subject_observation_attribute_values'=>array(),
    'known_subject_comments'=>array(),
    'identifiers_subject_observations'=>array(),
    'occurrences_subject_observations'=>array(),
  );
}

?>