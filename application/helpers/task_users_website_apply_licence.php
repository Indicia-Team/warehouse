<?php

/**
 * @file
 * Queue worker to apply updated users_websites default licences to existing data.
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
 * Queue worker to apply updated users_websites default licences to existing data.
 */
class task_users_website_apply_licence {

  public const BATCH_SIZE = 10;

  /**
   * Work_queue class will automatically expire the completed tasks.
   *
   * @const bool
   */
  public const SELF_CLEANUP = FALSE;

  /**
   * Process a claimed batch of licence application queue entries.
   *
   * Modes:
   * - empty: only apply where the target licence field is currently null.
   * - all: apply to all matching records.
   *
   * @param object $db
   *   Database connection object.
   * @param object $taskType
   *   Object read from the database for the task batch.
   * @param string $procId
   *   Unique identifier of this work queue processing run.
   */
  public static function process($db, $taskType, $procId) {
    $procIdEsc = pg_escape_literal($db->getLink(), $procId);
    $sql = <<<SQL

UPDATE samples s
SET licence_id=(q.params->>'licence_id')::integer
FROM surveys su
JOIN work_queue q ON q.task='task_users_website_apply_licence'
WHERE q.claimed_by=$procIdEsc
AND (q.params->>'licence_id') IS NOT NULL
AND su.website_id=(q.params->>'website_id')::integer
AND su.id=s.survey_id
AND s.created_by_id=(q.params->>'user_id')::integer
AND (
  (q.params->>'licence_mode')='all'
  OR ((q.params->>'licence_mode')='empty' AND s.licence_id IS NULL)
);

UPDATE cache_samples_nonfunctional snf
SET licence_code=l.code
FROM samples s
JOIN surveys su ON su.id=s.survey_id
JOIN licences l ON l.id=s.licence_id
JOIN work_queue q ON q.task='task_users_website_apply_licence'
WHERE q.claimed_by=$procIdEsc
AND (q.params->>'licence_id') IS NOT NULL
AND su.website_id=(q.params->>'website_id')::integer
AND s.created_by_id=(q.params->>'user_id')::integer
AND snf.id=s.id
AND COALESCE(snf.licence_code, '')<>l.code;

UPDATE cache_occurrences_functional o
SET licence_id=(q.params->>'licence_id')::integer
FROM work_queue q
WHERE q.task='task_users_website_apply_licence'
AND q.claimed_by=$procIdEsc
AND (q.params->>'licence_id') IS NOT NULL
AND o.website_id=(q.params->>'website_id')::integer
AND o.created_by_id=(q.params->>'user_id')::integer
AND (
  (q.params->>'licence_mode')='all'
  OR ((q.params->>'licence_mode')='empty' AND o.licence_id IS NULL)
);

UPDATE cache_occurrences_nonfunctional onf
SET licence_code=l.code
FROM cache_occurrences_functional o
JOIN licences l ON l.id=o.licence_id
JOIN work_queue q ON q.task='task_users_website_apply_licence'
WHERE q.claimed_by=$procIdEsc
AND (q.params->>'licence_id') IS NOT NULL
AND o.website_id=(q.params->>'website_id')::integer
AND o.created_by_id=(q.params->>'user_id')::integer
AND onf.id=o.id
AND COALESCE(onf.licence_code, '')<>l.code;

UPDATE sample_media sm
SET licence_id=(q.params->>'media_licence_id')::integer
FROM samples s
JOIN surveys su ON su.id=s.survey_id
JOIN work_queue q ON q.task='task_users_website_apply_licence'
WHERE q.claimed_by=$procIdEsc
AND (q.params->>'media_licence_id') IS NOT NULL
AND su.website_id=(q.params->>'website_id')::integer
AND sm.sample_id=s.id
AND sm.created_by_id=(q.params->>'user_id')::integer
AND (
  (q.params->>'media_licence_mode')='all'
  OR ((q.params->>'media_licence_mode')='empty' AND sm.licence_id IS NULL)
);

UPDATE occurrence_media om
SET licence_id=(q.params->>'media_licence_id')::integer
FROM occurrences o
JOIN work_queue q ON q.task='task_users_website_apply_licence'
WHERE q.claimed_by=$procIdEsc
AND (q.params->>'media_licence_id') IS NOT NULL
AND o.website_id=(q.params->>'website_id')::integer
AND om.occurrence_id=o.id
AND om.created_by_id=(q.params->>'user_id')::integer
AND (
  (q.params->>'media_licence_mode')='all'
  OR ((q.params->>'media_licence_mode')='empty' AND om.licence_id IS NULL)
);

UPDATE location_media lm
SET licence_id=(q.params->>'media_licence_id')::integer
FROM locations l
JOIN locations_websites lw ON lw.location_id=l.id AND lw.deleted=false
JOIN work_queue q ON q.task='task_users_website_apply_licence'
WHERE q.claimed_by=$procIdEsc
AND (q.params->>'media_licence_id') IS NOT NULL
AND lw.website_id=(q.params->>'website_id')::integer
AND lm.location_id=l.id
AND lm.created_by_id=(q.params->>'user_id')::integer
AND (
  (q.params->>'media_licence_mode')='all'
  OR ((q.params->>'media_licence_mode')='empty' AND lm.licence_id IS NULL)
);

SQL;
    $db->query($sql);
  }

}
