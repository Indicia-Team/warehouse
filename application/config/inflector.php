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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

defined('SYSPATH') or die('No direct script access.');

$config['uncountable'] = [
  'access',
  'advice',
  'art',
  'baggage',
  'dances',
  'data',
  'equipment',
  'fish',
  'fuel',
  'furniture',
  'food',
  'heat',
  'honey',
  'homework',
  'impatience',
  'information',
  'knowledge',
  'luggage',
  'metadata',
  'money',
  'music',
  'news',
  'patience',
  'progress',
  'pollution',
  'research',
  'rice',
  'sand',
  'series',
  'sheep',
  'sms',
  'species',
  'toothpaste',
  'traffic',
  'understanding',
  'water',
  'weather',
  'work',
  'workflow_undo',
  'workflow_metadata',
];

$config['irregular'] = [
  'child' => 'children',
  'clothes' => 'clothing',
  'goose' => 'geese',
  'leaf' => 'leaves',
  'man' => 'men',
  'mouse' => 'mice',
  'movie' => 'movies',
  'ox' => 'oxen',
  'person' => 'people',
  'taxon' => 'taxa',
  'woman' => 'women',
  // Data tables.
  'classification_results_occurrence_medium' => 'classification_results_occurrence_media',
  'gv_taxon_lists_taxon' => 'gv_taxon_lists_taxa',
  'location_medium' => 'location_media',
  'occurrence_medium' => 'occurrence_media',
  'sample_medium' => 'sample_media',
  'survey_medium' => 'survey_media',
  'taxon_medium' => 'taxon_media',
  'verification_rule_datum' => 'verification_rule_data',
  'verification_rule_metadatum' => 'verification_rule_metadata',
];
