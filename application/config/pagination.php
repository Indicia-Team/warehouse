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
 * @subpackage Config
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */


/**
 * Pagination configuration is defined in groups which allows you to easily switch
 * between different pagination settings for different website sections.
 * Note: all groups inherit and overwrite the default group.
 *
 * Group Options:
 *  directory      - Views folder in which your pagination style templates reside
 *  style          - Pagination style template (matches view filename)
 *  uri_segment    - URI segment (int or 'label') in which the current page number can be found
 *  query_string   - Alternative to uri_segment: query string key that contains the page number
 *  items_per_page - Number of items to display per page
 *  auto_hide      - Automatically hides pagination for single pages
 */
$config['default'] = array
(
  'directory'      => 'pagination',
  'style'          => 'classic',
  'uri_segment'    => 3,
  'query_string'   => '',
  'items_per_page' => 20,
  'auto_hide'      => FALSE,
);
