<?php

/**
 * @file
 * Queue worker to update cache_occurrences_nonfunctiona.attrs_json.
 *
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

/**
  * Queue worker to update cache_occurrences_nonfunctional.attrs_json.

  * Class called when a task_cache_builder_attrs_occurrences task encountered
  * in the work queue. Updates task_cache_builder_attrs_occurrences.attrs_json
  * with a json attribute for easy reporting on attribute values.
  */
 class task_cache_builder_attrs_occurrences {

  /**
   * Fairly fast, so processing large batches is OK.
   */
  public const BATCH_SIZE = 50000;

  /**
   * Perform the processing for a task batch found in the queue.
   *
   * @param object $db
   *   Database connection object.
   * @param object $taskType
   *   Object read from the database for the task batch. Contains the task
   *   name, entity, priority, created_on of the first record in the batch
   *   count (total number of queued tasks of this type).
   * @param string $procId
   *   Unique identifier of this work queue processing run. Allows filtering
   *   against the work_queue table's claimed_by field to determine which
   *   tasks to perform.
   */
  public static function process($db, $taskType, $procId) {
    $sql = <<<SQL

SELECT occurrence_id, ('{' || string_agg(
  to_json(f)::text || ':' ||
  CASE multi_value WHEN true THEN to_json(v)::text ELSE to_json(v[1])::text END
, ',') || '}')::json AS attrs
INTO temporary occattrs
FROM (
  SELECT occurrence_id, a.multi_value,
    'occ:' || occurrence_attribute_id::text as f,
    array_agg(
      CASE a.data_type
        WHEN 'T' THEN av.text_value
        WHEN 'L' THEN t.term
        WHEN 'I' THEN CASE WHEN a.allow_ranges = true AND av.upper_value IS NOT NULL THEN '"' ELSE '' END || av.int_value::text ||
          CASE
            WHEN a.data_type IN ('I', 'F') AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
            ELSE ''::text
          END || CASE WHEN a.allow_ranges = true AND av.upper_value IS NOT NULL THEN '"' ELSE '' END
        WHEN 'F' THEN CASE WHEN a.allow_ranges = true AND av.upper_value IS NOT NULL THEN '"' ELSE '' END || av.float_value::text ||
          CASE
            WHEN a.data_type IN ('I', 'F') AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
            ELSE ''::text
          END || CASE WHEN a.allow_ranges = true AND av.upper_value IS NOT NULL THEN '"' ELSE '' END
        WHEN 'B'::bpchar THEN av.int_value::text
        WHEN 'I' THEN CASE WHEN a.allow_ranges = true AND av.upper_value IS NOT NULL THEN '"' ELSE '' END || av.float_value::text ||
          CASE
            WHEN a.data_type IN ('I', 'F') AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
            ELSE ''::text
          END || CASE WHEN a.data_type IN ('I', 'F') AND a.allow_ranges = true AND av.upper_value IS NOT NULL THEN '"' ELSE '' END
        WHEN 'D'::bpchar THEN '"' || av.date_start_value::text || '"'
        WHEN 'V'::bpchar THEN '"' || (av.date_start_value::text || ' - '::text) || av.date_end_value::text || '"'
        ELSE NULL::text
      END ORDER BY t.sort_order, t.term
    ) as v
  FROM work_queue q
  LEFT JOIN occurrence_attribute_values av ON av.occurrence_id=q.record_id AND av.deleted=false
    AND COALESCE(av.int_value::text, av.text_value::text, av.float_value::text, av.date_start_value::text) IS NOT NULL
  LEFT JOIN occurrence_attributes a ON a.id=av.occurrence_attribute_id AND a.deleted=false
  LEFT JOIN cache_termlists_terms t on t.id=av.int_value AND a.data_type='L'
  WHERE q.entity='occurrences' AND q.task='task_cache_builder_attrs_occurrences' AND claimed_by='$procId'
  GROUP BY occurrence_id, occurrence_attribute_id, a.multi_value

  UNION

  SELECT occurrence_id, a.multi_value,
    'occ:' || occurrence_attribute_id::text || ':fra' as f,
    array_agg(COALESCE(ti18n.term, t.term) ORDER BY COALESCE(ti18n.sort_order, t.sort_order), COALESCE(ti18n.term, t.term)) as v
  FROM work_queue q
  JOIN occurrence_attribute_values av ON av.occurrence_id=q.record_id AND av.deleted=false
    AND COALESCE(av.int_value::text, av.text_value::text, av.float_value::text, av.date_start_value::text) IS NOT NULL
  JOIN occurrence_attributes a ON a.id=av.occurrence_attribute_id AND a.deleted=false
  JOIN cache_termlists_terms t on t.id=av.int_value AND a.data_type='L'
  LEFT JOIN cache_termlists_terms ti18n on ti18n.meaning_id=t.meaning_id AND ti18n.termlist_id=t.termlist_id AND ti18n.language_iso='fra'
  WHERE q.entity='occurrences' AND q.task='task_cache_builder_attrs_occurrences' AND claimed_by='$procId'
  AND a.data_type='L'
  GROUP BY occurrence_id, occurrence_attribute_id, a.multi_value
) AS subquery
GROUP BY occurrence_id;

UPDATE cache_occurrences_nonfunctional u
SET attrs_json=oa.attrs
FROM occattrs oa
WHERE oa.occurrence_id=u.id;

SQL;
    $db->query($sql);
  }

}
