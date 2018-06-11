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
    self::checkScheduledTasksHasBeenSetup($db, $messages);
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
    self::checkScheduledTasks($db, $messages);
    self::checkWorkQueue($db, $messages);
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
      ->get()->current();
    if ($query->count === '12') {
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
See <a href=@http://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/scheduled-tasks.html">
the scheduled tasks documentation</a>.
DESC;
    }
    elseif (!empty($query->old)) {
      $description = <<<DESC
Some scheduled tasks appear to be not running correctly as their timestamp indicates the
last successful run was more than a day ago.
See <a href=@http://indicia-docs.readthedocs.io/en/latest/administrating/warehouse/scheduled-tasks.html">
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
    $qState = $db
      ->select([
        'sum(case when priority=1 then 1 else 0 end) as p1',
        "sum(case when priority=1 and created_on<now() - '30 minutes'::interval then 1 else 0 end) as p1late",
        'sum(case when priority=2 then 1 else 0 end) as p2',
        'sum(case when priority=3 then 1 else 0 end) as p3',
        'sum(case when error_detail is null then 0 else 1 end) as errors',
      ])
      ->from('work_queue')
      ->get()->current();
    if ($qState->p1 > 2000) {
      $messages[] = array(
        'title' => 'Priority 1 entries in work queue',
        'description' => 'More than 2000 priority 1 entries in the work_queue table. This may indicate a problem, poor performance, or the server catching up after a significant data upload.',
      );
    }
    if ($qState->p1late > 0) {
      $messages[] = array(
        'title' => 'Priority 1 entries in work queue processing slowly',
        'description' => 'Priority 1 entries in the work_queue table are not being processed within half an hour. This may indicate a problem, poor performance, or the server catching up after a significant data upload.',
      );
    }
    if ($qState->p2 > 10000) {
      $messages[] = array(
        'title' => 'Priority 2 entries in work queue',
        'description' => 'More than 2000 priority 2 entries in the work_queue table. This may indicate a problem, poor performance, or the server catching up after a significant data upload.',
      );
    }
    if ($qState->p3 > 20000) {
      $messages[] = array(
        'title' => 'Priority 3 entries in work queue',
        'description' => 'More than 20000 priority 3 entries in the work_queue table. This may indicate a problem, poor performance, or the server catching up after a significant data upload.',
      );
    }
    if ($qState->errors > 0) {
      $messages[] = array(
        'title' => 'Errors in work queue',
        'description' => 'There are errors in the work_queue table which need to be checked and fixed.',
        'severity' => 'danger',
      );
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
    $masterTaxonListId = kohana::config('cache_builder_variables.master_list_id', FALSE, FALSE);
    if (!$masterTaxonListId && empty($authFilter)) {
      $url = url::base();
      $description = <<<TXT
Although not essential, if you have a single species checklist which contains a full taxonomic hierarchy, then you
should add this list's ID to the cache builder module's warehouse configuration. To do this:
<ul>
  <li>Go to <a href="{$url}index.php/taxon_list">the species lists page</a> and find the ID of your full list.</li>
  <li>In the warehouse file system, copy the file modules/cache_builder/config/cache_builder_variables.php.example to
  modules/cache_builder/config/cache_builder_variables.php then edit the file with a text editor. Change the value
  given for \$config['master_list_id'] to your list's ID and save the file.</li>
</ul>
TXT;
      $messages[] = array(
        'title' => 'Master species checklist',
        'description' => $description,
      );
    }
  }

}
