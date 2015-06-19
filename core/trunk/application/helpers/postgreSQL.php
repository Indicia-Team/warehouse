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
      "and sav.int_value=$cmsUserId ".
      "and o.created_by_id<>$userId ".
      "and o.created_by_id=1");
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
    // ID difficulty notifications only passed through for level 3 and above.
    return $db->query(
"select distinct on (co.id) case when oc.auto_generated=true then 'A' when co.verified_on>'$last_run_date' and co.record_status not in ('I','T','C') then 'V' else 'C' end as source_type,
        co.id, co.created_by_id as notify_user_id, co.taxon, co.date_start, co.date_end, co.date_type, co.public_entered_sref,
        co.verified_on, oc.comment, oc.auto_generated, oc.generated_by, co.record_status, co.record_substatus, co.cache_updated_on as updated_on, oc.created_by_id as occurrence_comment_created_by_id,
        case when oc.auto_generated=true then oc.generated_by else 'oc_id:' || oc.id::varchar end as source_detail, 't' as record_owner
into temporary records_to_notify
      from cache_occurrences co
      left join occurrence_comments oc on oc.occurrence_id=co.id and oc.deleted=false and oc.created_on>'$last_run_date' and oc.created_by_id<>co.created_by_id
          and (coalesce(oc.generated_by, '')<>'data_cleaner_identification_difficulty' or coalesce(oc.generated_by_subtype, '') not in ('1','2'))
      where ((co.verified_on>'$last_run_date'
      and co.record_status not in ('I','T','C'))
      or oc.id is not null)
      and co.created_by_id<>1
    union
    select distinct 'C' as source_type, co.id, ocprev.created_by_id as notify_user_id, co.taxon, co.date_start, co.date_end, co.date_type, co.public_entered_sref,
        co.verified_on, oc.comment, oc.auto_generated, oc.generated_by, co.record_status, co.record_substatus, co.cache_updated_on as updated_on, oc.created_by_id as occurrence_comment_created_by_id,
        'oc_id:' || oc.id::varchar as source_detail, case ocprev.created_by_id when co.created_by_id then 't' else 'f' end as record_owner
      from cache_occurrences co
      join occurrence_comments ocprev on ocprev.occurrence_id=co.id and ocprev.deleted=false and ocprev.created_by_id<>co.created_by_id and ocprev.created_by_id<>1
      join occurrence_comments oc on oc.occurrence_id=co.id and oc.deleted=false and oc.created_on>'$last_run_date' and oc.created_by_id<>ocprev.created_by_id
      where co.created_by_id<>1 and oc.created_by_id<>1
      -- only notify if not the commenter or record owner
      and ocprev.created_by_id<>oc.created_by_id and ocprev.created_by_id<>co.created_by_id;

select rn.*, u.username
from records_to_notify rn
join occurrences o on o.id=rn.id
left join notifications n on n.linked_id=o.id 
          and n.source_type=rn.source_type
          and n.source_detail=rn.source_detail
join users u on u.id=coalesce(rn.occurrence_comment_created_by_id, o.verified_by_id)
where n.id is null;")->result();
  }  
  
  /** 
   * Function to be called on postSubmit of a sample, to make sure that any changed occurrences are linked to their map square entries properly.
   */
  public static function insertMapSquaresForSamples($ids, $size, $db=null) {
    self::insertMapSquares($ids, 's', $size, $db);
  }
  
  /** 
   * Function to be called on postSubmit of an occurrence or occurrences if submitted directly (i.e. not as part of a sample), 
   * to make sure that any changed occurrences are linked to their map square entries properly.
   */
  public static function insertMapSquaresForOccurrences($ids, $size, $db=null) {
    self::insertMapSquares($ids, 'o', $size, $db);
  }
  
  /** 
   * Code for the insertMapSquaresFor... methods, which takes the table alias as a parameter in order to be generic.
   */ 
  private static function insertMapSquares($ids, $alias, $size, $db=null) {
    if (count($ids)>0) {
      static $srid;
      if (!isset($srid)) {
        $srid = kohana::config('sref_notations.internal_srid');
      }
      if (!$db)
        $db = new Database();
      $idlist=implode(',', $ids);
      // Seems much faster to break this into small queries than one big left join.
      $smpInfo = $db->query(
      "SELECT DISTINCT s.id, st_astext(coalesce(s.geom, l.centroid_geom)) as geom, o.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, $size) as size, 
          coalesce(s.entered_sref_system, l.centroid_sref_system) as entered_sref_system,
          round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, $size), s.entered_sref_system)))) as x,
          round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, $size), s.entered_sref_system)))) as y
        FROM samples s
        JOIN occurrences o ON o.sample_id=s.id
        LEFT JOIN locations l on l.id=s.location_id AND l.deleted=false
        WHERE $alias.id IN ($idlist)")->result_array(TRUE);
      $km=$size/1000;
      foreach ($smpInfo as $s) {
        $existing = $db->query("SELECT id FROM map_squares WHERE x={$s->x} AND y={$s->y} AND size={$s->size}")->result_array(FALSE);
        if (count($existing)===0) {
          $qry=$db->query("INSERT INTO map_squares (geom, x, y, size)
            VALUES (reduce_precision(st_geomfromtext('{$s->geom}', $srid), '{$s->confidential}', {$s->size}, '{$s->entered_sref_system}'), {$s->x}, {$s->y}, {$s->size})");
          $msqId=$qry->insert_id();
        }
        else 
          $msqId=$existing[0]['id'];
        $db->query("UPDATE cache_occurrences co SET map_sq_{$km}km_id=$msqId WHERE sample_id={$s->id}");
      }
    }
  }
  
  /**
   * A clone of the list_fields methods provided by the Kohana database object, but with caching as it
   * involves a database hit but is called quite frequently.
   * @param string $entity Table or view name
   * @param Database $db Database object if available
   * @return array Array of field definitions for the object. 
   */
  public static function list_fields($entity, $db=null) {
    $key="list_fields$entity";
    $cache = Cache::instance();
    $result = $cache->get($key);
    if ($result===null) {
      if (!$db)
        $db = new Database();
      $result = $db->query('
        SELECT column_name, column_default, is_nullable, data_type, udt_name,
          character_maximum_length, numeric_precision, numeric_precision_radix, numeric_scale
        FROM information_schema.columns
        WHERE table_name = \''. $entity .'\'
        ORDER BY ordinal_position
      ');

      $cols=$result->result_array(TRUE);
      $result = NULL;

      foreach ($cols as $row)
      {
        // Make an associative array
        $result[$row->column_name] = self::sql_type($row->data_type);

        if (!strncmp($row->column_default, 'nextval(', 8))
        {
          $result[$row->column_name]['sequenced'] = TRUE;
        }

        if ($row->is_nullable === 'YES')
        {
          $result[$row->column_name]['null'] = TRUE;
        }
      }
      if (!isset($result))
        throw new Kohana_Database_Exception('database.table_not_found', $entity);
      else 
        $cache->set($key, $result);
    }
    return $result;
  }
  
  /**
   * A clone of the sql_type method in the PG driver, copied here to support our version of list_fields.
   * Converts a Kohana data type name to the SQL equivalent.
   * @staticvar $sql_types Used to cache the sql types config per request
   * @param string $str Type name
   * @return type SQL version of the type name
   */
  protected static function sql_type($str)
  {
    static $sql_types;

    if ($sql_types === NULL)
    {
      // Load SQL data types
      $sql_types = Kohana::config('sql_types');
    }

    $str = strtolower(trim($str));

    if (($open  = strpos($str, '(')) !== FALSE)
    {
      // Find closing bracket
      $close = strpos($str, ')', $open) - 1;

      // Find the type without the size
      $type = substr($str, 0, $open);
    }
    else
    {
      // No length
      $type = $str;
    }

    empty($sql_types[$type]) and exit
    (
      'Unknown field type: '.$type.'. '.
      'Please report this: http://trac.kohanaphp.com/newticket'
    );

    // Fetch the field definition
    $field = $sql_types[$type];

    switch ($field['type'])
    {
      case 'string':
      case 'float':
        if (isset($close))
        {
          // Add the length to the field info
          $field['length'] = substr($str, $open + 1, $close - $open);
        }
      break;
      case 'int':
        // Add unsigned value
        $field['unsigned'] = (strpos($str, 'unsigned') !== FALSE);
      break;
    }

    return $field;
  }
}
?>
