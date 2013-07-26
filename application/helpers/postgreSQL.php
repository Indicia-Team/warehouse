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
      "set created_by_id=$userId, updated_by_id=$userId, updated_on=now() ".
      "from sample_attribute_values sav ".
      "join sample_attributes sa ".
      "    on sa.id=sav.sample_attribute_id ".
      "    and sa.caption='CMS User ID' ".
      "    and sa.deleted=false ".
      "where o.sample_id = sav.sample_id ".
      "and sav.deleted=false ".
      "and o.deleted=false ".
      "and o.website_id=$websiteId ".
      "and sav.int_value=$cmsUserId".
      "and o.created_by_id<>$userId");
  }
  
  /** 
   * Runs a query to select the notification data to generate for verification and comment status updates since the 
   * last run date. This allows recorders to be notified of verification actions and/or comments on their records.
   */
  public static function selectVerificationAndCommentNotifications($last_run_date, $db=null) {
    if (!$db)
      $db = new Database();
    // note this query excludes user 1 from the notifications (admin user) as they are records which don't
    // have a warehouse user ID. Also excludes any previous notifications of this exact source for this user.
    return $db->query("select case when oc.auto_generated=true then 'A' when o.verified_on>'$last_run_date' and o.record_status not in ('I','T','C') then 'V' else 'C' end as source_type,
        co.id, co.created_by_id as notify_user_id, co.taxon, co.date_start, co.date_end, co.date_type, co.public_entered_sref, u.username, 
        o.verified_on, co.public_entered_sref, oc.comment, oc.auto_generated, oc.generated_by, o.record_status, o.updated_on, oc.created_by_id as occurrence_comment_created_by_id,
        case when oc.auto_generated=true then oc.generated_by else 'oc_id:' || oc.id::varchar end as source_detail, 't' as record_owner           
      from occurrences o
      join cache_occurrences co on co.id=o.id
      left join occurrence_comments oc on oc.occurrence_id=o.id and oc.deleted=false and oc.created_on>'$last_run_date' and oc.created_by_id<>o.created_by_id
      join users u on u.id=coalesce(oc.created_by_id, o.verified_by_id)
      left join notifications n on n.linked_id=o.id 
          and n.source_type=case when oc.auto_generated=true then 'A' when o.verified_on>'$last_run_date' and o.record_status not in ('I','T','C') then 'V' else 'C' end
          and n.source_detail=case when oc.auto_generated=true then oc.generated_by else 'oc_id:' || oc.id::varchar end
      where ((o.verified_on>'$last_run_date'
      and o.record_status not in ('I','T','C'))
      or oc.id is not null)
      and o.created_by_id<>1
      and n.id is null
    union
    select distinct 'C' as source_type, co.id, ocprev.created_by_id as notify_user_id, co.taxon, co.date_start, co.date_end, co.date_type, co.public_entered_sref, u.username, 
        o.verified_on, co.public_entered_sref, oc.comment, oc.auto_generated, oc.generated_by, o.record_status, o.updated_on, oc.created_by_id as occurrence_comment_created_by_id,
        'oc_id:' || oc.id::varchar as source_detail, case ocprev.created_by_id when o.created_by_id then 't' else 'f' end as record_owner
      from occurrences o
      join cache_occurrences co on co.id=o.id
      join occurrence_comments ocprev on ocprev.occurrence_id=o.id and ocprev.deleted=false and ocprev.created_by_id<>o.created_by_id and ocprev.created_by_id<>1
      join occurrence_comments oc on oc.occurrence_id=o.id and oc.deleted=false and oc.created_on>'$last_run_date' and oc.created_by_id<>ocprev.created_by_id
      join users u on u.id=coalesce(oc.created_by_id, o.verified_by_id)
      left join notifications n on n.linked_id=o.id 
          and n.source_type='C' 
          and n.source_detail='oc_id:' || oc.id::varchar
      where o.created_by_id<>1 and oc.created_by_id<>1
      and n.id is null
      -- only notify if not the commenter or record owner
      and ocprev.created_by_id<>oc.created_by_id and ocprev.created_by_id<>o.created_by_id
      ")->result();
  }  
}
?>
