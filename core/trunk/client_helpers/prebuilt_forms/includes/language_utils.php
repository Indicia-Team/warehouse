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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * List of methods that assist with Indicia and Drupal language interactions.
 * @package Client
 * @subpackage PrebuiltForms.
 */

/**
 * Get the iso 639 code for the user's logged in language.
 * @return array 3 character language code
 * @todo Complete the list
 */
function iform_lang_iso_639_2($lang=null) {
  if ($lang==null) {
    global $language;
    $lang = $language->language;
  }
  // If there is a sub-language, ignore it (e.g. en-GB becomes just en). 
  // @todo may want to handle sub-languages
  $lang = explode('-', $lang);
  $lang = $lang[0];
  switch ($lang) {
    case 'en' : return 'eng';
    case 'de' : return 'deu';
    case 'lb' : return 'ltz';
    case 'fr' : return 'fra';
  }
}