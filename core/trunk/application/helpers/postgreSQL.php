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
 * @subpackage Helpers
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to provide postgreSQL specific SQL functions, so that they can all be
 * kept in one place.
 */
class postgreSQL {
  
  public static function transformWkt($wkt, $fromSrid, $toSrid, $db=null) {
    if ($fromSrid!=$toSrid) {
      if (!$db)
        $db = new Database();
      $result = $db->query("SELECT ST_asText(ST_Transform(ST_GeomFromText('$wkt',$fromSrid),$toSrid)) AS wkt;")->current();
      return $result->wkt;
    } else
      return $wkt;
  }
  
  
  public static function setOccurrenceCreatorByCmsUser($db, $websiteId, $userId, $cmsUserId) {
    $db->query("update occurrences as o ".
      "set created_by_id=$userId ".
      "from occurrence_attribute_values oav ".
      "join occurrence_attributes oa ".
      "    on oa.id=oav.occurrence_attribute_id ".
      "    and oa.caption='CMS User ID' ".
      "    and oa.deleted=false ".
      "where oav.deleted=false ".
      "and o.deleted=false ".
      "and o.website_id=$websiteId ".
      "and oav.int_value=$cmsUserId");
  }
  
}
?>
