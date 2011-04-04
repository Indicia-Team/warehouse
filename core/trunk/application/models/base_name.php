<?php defined('SYSPATH') or die('No direct script access.');

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
 * Base class for the models which represent a name (term or taxon) on a list, i.e. Taxa_Taxon_Lists
 * and termlists_terms.
 *
 * @package	Core
 * @subpackage Models
 * @link	http://code.google.com/p/indicia/wiki/DataModel
 */
class Base_Name_Model extends ORM_Tree {

  protected function parseRelatedNames($value, $parser) {
    $arrLine = explode("\n", trim($value));
    $arrNames = array();

    foreach ($arrLine as $line)
    {
      if (trim($line) == '')
        break;
      $c = array();
      $b = preg_split("/(?<!\\\\ )\|/",$line);
      foreach($b as $d) $c[] = trim($d);
      call_user_func_array(array($this, $parser), array($c, &$arrNames));
    }
    return $arrNames;
  }

  /**
   * Retrieve the list of synonyms using a meaning id.
   */
  public function getSynonomy($meaning_field, $meaning_id)
  {
    return ORM::factory(inflector::singular($this->table_name))->where(
      array(
        'preferred' => 'f',
        'deleted' => 'f',
        $meaning_field => $meaning_id
      ))->find_all();
  }

}

?>