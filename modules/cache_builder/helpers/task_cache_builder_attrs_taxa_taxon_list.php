<?php

/**
 * @file
 * Queue worker to update cache_taxa_taxon_lists.attrs_json.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Queue worker to update cache_taxa_taxon_lists.attrs_json.
 *
 * Class called when a task_cache_builder_attrs_taxa_taxon_list task encountered
 * in the work queue. Updates cache_taxa_taxon_lists.attrs_json with a json
 * attribute for easy reporting on attribute values.
 */
class task_cache_builder_attrs_taxa_taxon_list {

  /**
   * Fairly fast, so processing large batches is OK.
   */
  public const BATCH_SIZE = 50000;

  /**
   * This class will expire the completed tasks itself.
   *
   * @const bool
   */
  public const SELF_CLEANUP = TRUE;

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
    // Work out the SQL required to get the i18n for lookup term values.
    $langs = kohana::config('cache_builder_variables.attrs_cache_languages', FALSE, FALSE);
    $langTermSql = '';
    $procIdEsc = pg_escape_literal($db->getLink(), $procId);
    if ($langs !== NULL) {
      foreach ($langs as $lang) {
        if (!preg_match('/^[a-z]{3}$/', $lang)) {
          // Skip incorrectly formatted languages.
          continue;
        }
        $langTermSql .= <<<SQL

          UNION

          SELECT q.record_id as taxa_taxon_list_id, a.multi_value,
            av.taxa_taxon_list_attribute_id::text || ':$lang' as f,
            array_agg(COALESCE(ti18n.term, t.term) ORDER BY COALESCE(tlti18n.sort_order, tlt.sort_order)) as v
          FROM work_queue q
          JOIN taxa_taxon_list_attribute_values av ON av.taxa_taxon_list_id=q.record_id AND av.deleted=false
            AND COALESCE(av.int_value::text, av.text_value::text, av.float_value::text, av.date_start_value::text) IS NOT NULL
          JOIN taxa_taxon_list_attributes a ON a.id=av.taxa_taxon_list_attribute_id AND a.deleted=false
          LEFT JOIN termlists_terms tlt ON tlt.id=av.int_value AND tlt.deleted=false
          LEFT JOIN terms t ON t.id=tlt.term_id AND t.deleted=false
          LEFT JOIN (termlists_terms tlti18n
            JOIN terms ti18n ON ti18n.id=tlti18n.term_id AND ti18n.deleted=false
            JOIN languages l on l.id=ti18n.language_id AND l.deleted=false AND l.iso='$lang'
          ) ON tlti18n.meaning_id=tlt.meaning_id AND tlti18n.termlist_id=tlt.termlist_id and tlti18n.deleted=false
          WHERE q.entity='taxa_taxon_list' AND q.task='task_cache_builder_attrs_taxa_taxon_list' AND q.claimed_by=$procIdEsc
          AND a.data_type='L'
          GROUP BY q.record_id, av.taxa_taxon_list_attribute_id, a.multi_value

        SQL;
      }
    }
    $sql = <<<SQL

      SELECT taxa_taxon_list_id, ('{' || string_agg(
        to_json(f)::text || ':' ||
        CASE multi_value WHEN true THEN to_json(v)::text ELSE to_json(v[1])::text END
      , ',') || '}')::json AS attrs
      INTO temporary attrs
      FROM (
        SELECT q.record_id as taxa_taxon_list_id, a.multi_value,
          av.taxa_taxon_list_attribute_id::text as f,
          array_agg(
            CASE a.data_type
              WHEN 'T' THEN av.text_value
              WHEN 'L' THEN t.term
              WHEN 'I' THEN av.int_value::text ||
                CASE
                  WHEN a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                  ELSE ''::text
                END
              WHEN 'F' THEN av.float_value::text ||
                CASE
                  WHEN a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                  ELSE ''::text
                END
              WHEN 'B'::bpchar THEN av.int_value::text
              WHEN 'D'::bpchar THEN av.date_start_value::text
              WHEN 'V'::bpchar THEN
                av.date_start_value::text ||
                CASE WHEN av.date_end_value > av.date_start_value THEN ' - '::text || av.date_end_value::text ELSE '' END
              ELSE NULL::text
            END ORDER BY tlt.sort_order, t.term
          ) as v
        FROM work_queue q
        LEFT JOIN taxa_taxon_list_attribute_values av ON av.taxa_taxon_list_id=q.record_id AND av.deleted=false
          AND COALESCE(av.int_value::text, av.text_value::text, av.float_value::text, av.date_start_value::text) IS NOT NULL
        LEFT JOIN taxa_taxon_list_attributes a ON a.id=av.taxa_taxon_list_attribute_id AND a.deleted=false
        LEFT JOIN termlists_terms tlt ON tlt.id=av.int_value AND a.data_type='L' AND tlt.deleted=false
        LEFT JOIN terms t ON t.id=tlt.term_id AND t.deleted=false
        WHERE q.entity='taxa_taxon_list' AND q.task='task_cache_builder_attrs_taxa_taxon_list' AND claimed_by=$procIdEsc
        GROUP BY q.record_id, av.taxa_taxon_list_attribute_id, a.multi_value
        $langTermSql
      ) AS subquery
      GROUP BY taxa_taxon_list_id;
    SQL;
    $db->query($sql);
    $sql = <<<SQL
      UPDATE cache_taxa_taxon_lists u
      SET attrs_json=a.attrs
      FROM attrs a
      WHERE a.taxa_taxon_list_id=u.id;
    SQL;
    $db->query($sql);
    $sql = <<<SQL
      DELETE FROM work_queue q
      USING attrs a
      WHERE a.taxa_taxon_list_id=q.record_id
      AND q.entity='taxa_taxon_list'
      AND q.task='task_cache_builder_attrs_taxa_taxon_list';

      DROP TABLE attrs;
    SQL;
    $db->query($sql);
  }

}
