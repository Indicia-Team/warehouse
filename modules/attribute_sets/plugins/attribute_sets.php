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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse/
 */

 /**
  * Plugin methot to add attribute sets to data services.
  *
  * @return array
  *   List of exposed tables.
  */
function attribute_sets_extend_data_services() {
  return [
    'attribute_sets' => [],
    'attribute_sets_taxa_taxon_list_attributes' => [],
    'attribute_sets_surveys' => [],
    'attribute_sets_taxon_restrictions' => [],
    'occurrence_attributes_taxa_taxon_list_attributes' => [],
    'sample_attributes_taxa_taxon_list_attributes' => [],
  ];
}
