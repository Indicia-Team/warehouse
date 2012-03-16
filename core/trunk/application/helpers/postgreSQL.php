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
  
  
  public static function setOccurrenceCreatorByCmsUser($websiteId, $userId, $cmsUserId, $db=null) {
    if (!$db)
      $db = new Database();
    $db->query("update occurrences as o ".
      "set created_by_id=$userId ".
      "from sample_attribute_values sav ".
      "join sample_attributes sa ".
      "    on sa.id=sav.sample_attribute_id ".
      "    and sa.caption='CMS User ID' ".
      "    and sa.deleted=false ".
      "where o.sample_id = sav.sample_id ".
      "and sav.deleted=false ".
      "and o.deleted=false ".
      "and o.website_id=$websiteId ".
      "and sav.int_value=$cmsUserId");
  }
  
  /** 
   * Runs a query to select the notification data to generate for verification and comment status updates since the 
   * last run date. This allows recorders to be notified of verification actions and/or comments on their records.
   */
  public static function selectVerificationAndCommentNotifications($last_run_date, $db=null) {
    if (!$db)
      $db = new Database();
    return $db->query("select 'V' as source_type, co.id, co.created_by_id, co.taxon, co.date_start, co.date_end, co.date_type, 
        co.public_entered_sref, u.username, 'Status was set to ' || o.record_status as comment, null as auto_generated, o.updated_on
from occurrences o
join cache_occurrences co on co.id=o.id
join users u on u.id=o.verified_by_id
where o.verified_on>'$last_run_date'
and o.record_status not in ('I','T','C')
union 
select 'C' as source_type, co.id, co.created_by_id, co.taxon, co.date_start, co.date_end, co.date_type, co.public_entered_sref, u.username, 
    oc.comment, oc.auto_generated, oc.updated_on
from occurrences o
join cache_occurrences co on co.id=o.id
join occurrence_comments oc on oc.occurrence_id=o.id and oc.deleted=false
join users u on u.id=oc.created_by_id
where oc.created_on>'$last_run_date'
order by id desc, updated_on asc")->result();
  }
  
}
?>
