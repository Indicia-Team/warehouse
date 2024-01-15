<?php

/**
 * @file
 * A helper class for detecting various messages related to the server status.
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
 * Helper class to provide info on server status.
 */
class serverStatus {

  /**
   * Main access function to retrieve a list of tips useful on initial setup.
   *
   * @param object $db
   *   Kohana database object.
   * @param array|null $authFilter
   *   User's website access filter, if not core admin.
   *
   * @return array
   *   List of tips.
   */
  public static function getGettingStartedTips($db, $authFilter) {
    $messages = [];
    self::checkWebsite($db, $authFilter, $messages);
    self::checkSurvey($db, $authFilter, $messages);
    self::checkTaxonList($db, $authFilter, $messages);
    self::checkMasterTaxonList($authFilter, $messages);
    // @todo Implement a check that the user has set up a species checklist and added some species.
    return $messages;
  }

  /**
   * Main access function to retrieve a list of server status warnings.
   *
   * @param object $db
   *   Kohana database object.
   * @param array|null $authFilter
   *   User's website access filter, if not core admin.
   *
   * @return array
   *   List of tips.
   */
  public static function getStatusWarnings($db, $authFilter) {
    $messages = [];
    // Limit these warnings to core admin users.
    $auth = new Auth();
    if ($auth->logged_in('CoreAdmin')) {
      self::checkScheduledTasksHasBeenSetup($db, $messages);
      self::checkScheduledTasks($db, $messages);
      self::checkWorkQueue($db, $messages);
    }
    return $messages;
  }

  /**
   * Retrieve tips relating to the operation of scheduled tasks.
   *
   * @param object $db
   *   Kohana database object.
   * @param array $messages
   *   List of tips, which will be amended if any tips identified by this function.
   */
  private static function checkScheduledTasksHasBeenSetup($db, array &$messages) {
    $query = $db
      ->select('count(*)')
      ->from('system')
      ->where('last_scheduled_task_check is not null')
      ->where("name<>'indicia'")
      ->get()->current();
    if ($query->count === '0') {
      $description = <<<DESC
The Indicia warehouse requires the scheduled tasks functionality to be configured in order for background tasks such
as indexing to be perfomed. See
<a href="http://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/scheduled-tasks.html"> the scheduled
tasks documentation</a>.
DESC;
      $messages[] = array(
        'title' => 'Scheduled tasks',
        'description' => $description,
      );
    }
  }

  /**
   * Retrieve tips relating to the operation of scheduled tasks.
   *
   * @param object $db
   *   Kohana database object.
   * @param array $messages
   *   List of tips, which will be amended if any tips identified by this function.
   */
  private static function checkScheduledTasks($db, array &$messages) {
    $query = $db
      ->select(array(
        "sum(case when last_scheduled_task_check > now()-'1 day'::interval then 1 else 0 end) as new",
        "sum(case when last_scheduled_task_check <= now()-'1 day'::interval then 1 else 0 end) as old",
      ))
      ->from('system')
      ->where('last_scheduled_task_check is not null')
      ->get()->current();
    $description = '';
    if (empty($query->old) && empty($query->new)) {
      $description = <<<DESC
The scheduled tasks process has never been called. This means that many background
processes required for the operation of Indicia are not being run, for example species and term lookup
services will return empty results.
See <a href="http://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/scheduled-tasks.html">
the scheduled tasks documentation</a>.
DESC;
    }
    elseif (empty($query->new)) {
      $description = <<<DESC
The scheduled tasks process has not been called recently. This means that many background
processes required for the operation of Indicia are not being run, for example species and term lookup
services may return empty results.
See <a href="http://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/scheduled-tasks.html">
the scheduled tasks documentation</a>.
DESC;
    }
    elseif (!empty($query->old)) {
      $description = <<<DESC
Some scheduled tasks appear to be not running correctly as their timestamp indicates the
last successful run was more than a day ago.
See <a href="http://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/scheduled-tasks.html">
the scheduled tasks documentation</a>.
DESC;
    }
    if (!empty($description)) {
      $messages[] = array(
        'title' => 'Scheduled tasks',
        'description' => $description,
        'severity' => 'warning',
      );
    }
  }

  private static function checkWorkQueue($db, array &$messages) {
    $maxP1 = 2000;
    $maxP1Late = 0;
    $maxP2 = 10000;
    $maxP3 = 20000;
    $maxErrors = 0;
    try {
      $sql = <<<SQL
SELECT 'p1' as stat, count(*) FROM (
  SELECT id FROM work_queue WHERE priority=1 AND error_detail IS NULL LIMIT $maxP1+1
  ) as sub
UNION
SELECT 'p1late', count(*) FROM (
  SELECT id FROM work_queue WHERE priority=1 AND error_detail IS NULL AND created_on<now() - '30 minutes'::interval LIMIT $maxP1Late+1
  ) as sub
UNION
SELECT 'p2', count(*) FROM (
  SELECT id FROM work_queue WHERE priority=2 AND error_detail IS NULL LIMIT $maxP2+1
  ) AS sub
UNION
SELECT 'p3', count(*) FROM (
  SELECT id FROM work_queue WHERE priority=3 AND error_detail IS NULL LIMIT $maxP3+1
  ) AS sub
UNION
(SELECT 'errors', CASE WHEN error_detail IS NOT NULL THEN 1 ELSE 0 END
  FROM work_queue
  ORDER BY error_detail LIMIT $maxErrors+1);
SQL;
      $stats = $db->query($sql)->result();
      foreach ($stats as $statRow) {
        switch ($statRow->stat) {
          case 'p1':
            if ($statRow->count > $maxP1) {
              $messages[] = array(
                'title' => kohana::lang('general_errors.workQueueTooFull', 1),
                'description' =>
                  kohana::lang('general_errors.workQueueTooFullDescription', $maxP1, 1) . ' ' .
                  kohana::lang('general_errors.workQueueTooFullExplain'),
              );
            }
            break;

          case 'p1late':
            if ($statRow->count > $maxP1Late) {
              $messages[] = array(
                'title' => kohana::lang('general_errors.workQueueSlow', 1),
                'description' => kohana::lang('general_errors.workQueueTooFullExplain'),
              );
            }
            break;

          case 'p2':
            if ($statRow->count > $maxP2) {
              $messages[] = array(
                'title' => kohana::lang('general_errors.workQueueTooFull', 2),
                'description' =>
                  kohana::lang('general_errors.workQueueTooFullDescription', $maxP2, 2) . ' ' .
                  kohana::lang('general_errors.workQueueTooFullExplain'),
              );
            }
            break;

          case 'p3':
            if ($statRow->count > $maxP3) {
              $messages[] = array(
                'title' => kohana::lang('general_errors.workQueueTooFull', 3),
                'description' =>
                  kohana::lang('general_errors.workQueueTooFullDescription', $maxP3, 3) . ' ' .
                  kohana::lang('general_errors.workQueueTooFullExplain'),
              );
            }
            break;

          case 'errors':
            if ($statRow->count > $maxErrors) {
              $messages[] = array(
                'title' => kohana::lang('general_errors.workQueueErrors'),
                'description' => kohana::lang('general_errors.workQueueErrorsDescription'),
                'severity' => 'danger',
              );
            }
            break;
        }
      }
    }
    catch (Kohana_Database_Exception $e) {
      $messages[] = [
        'title' => 'Work queue not installed.',
        'description' => 'Please ensure that the warehouse database upgrade has been performed correctly.',
        'severity' => 'danger',
      ];
    }
  }

  /**
   * Retrieve tips relating to the registration of websites.
   *
   * @param object $db
   *   Kohana database object.
   * @param array|null $authFilter
   *   User's website access filter, if not core admin.
   * @param array $messages
   *   List of tips, which will be amended if any tips identified by this function.
   */
  private static function checkWebsite($db, $authFilter, array &$messages) {
    if (!empty($authFilter) && $authFilter['field'] === 'website_id') {
      // User is already allocated to some websites, so no need to prompt them to set them up.
      return;
    }
    $query = $db
      ->select('count(id) as count')
      ->from('websites')
      ->where('id<>1')
      ->get()->current();
    if ($query->count == 0) {
      $messages[] = array(
        'title' => 'Website registration',
        'description' => 'Before submitting records to this warehouse you need to register a website or app that ' .
          'the records will come from. See ' .
          '<a href="http://indicia-docs.readthedocs.io/en/latest/site-building/warehouse/websites.html">the website ' .
          'registration documentation</a>.'
      );
    }
  }

  /**
   * Retrieve tips relating to the registration of survey datasets.
   *
   * @param object $db
   *   Kohana database object.
   * @param array|null $authFilter
   *   User's website access filter, if not core admin.
   * @param array $messages
   *   List of tips, which will be amended if any tips identified by this function.
   */
  private static function checkSurvey($db, $authFilter, array &$messages) {
    $db
      ->select('count(id) as count')
      ->from('surveys')
      ->where('website_id<>1');
    if (!empty($authFilter) && $authFilter['field'] === 'website_id') {
      $db->in('website_id', $authFilter['values']);
    }
    $query = $db->get()->current();
    if ($query->count == 0) {
      $messages[] = array(
        'title' => 'Survey dataset registration',
        'description' => 'Before submitting records to this warehouse you need to register a survey dataset to add ' .
          'the records to. See ' .
          '<a href="http://indicia-docs.readthedocs.io/en/latest/site-building/warehouse/surveys.html">the survey ' .
          'dataset registration documentation</a>.'
      );
    }
  }

  /**
   * Retrieve tips relating to the creation of taxon lists.
   *
   * @param object $db
   *   Kohana database object.
   * @param array|null $authFilter
   *   User's website access filter, if not core admin.
   * @param array $messages
   *   List of tips, which will be amended if any tips identified by this function.
   */
  private static function checkTaxonList($db, $authFilter, array &$messages) {
    $websites = [NULL];
    if (!empty($authFilter) && $authFilter['field'] === 'website_id') {
      $websites = array_merge($websites, $authFilter['values']);
    }
    $db
      ->select('count(id) as count')
      ->from('taxon_lists')
      ->in('website_id', $websites);
    $query = $db->get()->current();
    if ($query->count === 0) {
      $description = <<<TXT
Before submitting records to this warehouse you need to create a species list to record against. See
<a href="http://indicia-docs.readthedocs.io/en/latest/site-building/warehouse/taxon-lists.html">the documentation for
setting up a species list.</a>.
TXT;
      $messages[] = array(
        'title' => 'Species list creation',
        'description' => $description,
      );
    }
  }

  /**
   * Retrieve tips relating to the configuration of a master taxon list.
   *
   * @param array|null $authFilter
   *   User's website access filter, if not core admin.
   * @param array $messages
   *   List of tips, which will be amended if any tips identified by this function.
   */
  private static function checkMasterTaxonList($authFilter, array &$messages) {
    $masterTaxonListId = warehouse::getMasterTaxonListId();
    if (!$masterTaxonListId && empty($authFilter)) {
      $url = url::base();
      $description = <<<TXT
Although not essential, if you have a single species checklist which contains a full taxonomic hierarchy, then you
should add this list's ID to Indicia's warehouse configuration. Ensure that the list has the accepted name unique
identifier (external_key) field populated for all the species names and that this identifier is used to map to names in
other lists. To update the configuration:
<ul>
  <li>Go to <a href="{$url}index.php/taxon_list">the species lists page</a> and find the ID of your full list.</li>
  <li>In the warehouse file system, edit the file application/config/indicia.php with a text editor. Append the
  following to the text, replacing &lt;id&gt; with your list's ID:<br/>
  \$config['master_list_id'] = &lt;id&gt;</li>
  <li>Save the file.</li>
  <li>Run the following SQL statement using pgAdmin, connected to your Indicia database with the search_path set to your
  indicia schema:<br/>
  INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)<br/>
  SELECT DISTINCT 'task_cache_builder_path_occurrence', 'occurrence', id, 2, 100, now()<br/>
  FROM occurrences<br/>
  WHERE deleted=false;<br/>
  </li>
</ul>
The scheduled tasks running on the warehouse will gradually populate the cache data for occurrence taxon paths in
batches.
TXT;
      $messages[] = array(
        'title' => 'Master species checklist',
        'description' => $description,
      );
    }
  }

}
