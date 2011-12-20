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
 * @package	Verification Check
 * @subpackage Plugins
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Hook into the task scheduler. Loop through any plugins that declare verification
 * rules and run them against the list of occurrences that need verification checks.
 * A check is required when the website registration has auto-checking enabled and
 * the occurrence is new, or the occurrence has had its identification changed, or
 * the species' verification metadata has changed (which changes the species' verification
 * version), the occurrence has been edited, or the sample has been edited.
 * 
 */
function verification_check_scheduled_task() {
  $db = new Database();
  $rules = verification_check_get_rules();
  verification_check_get_occurrence_list($db);
  verification_check_cleanout_old_messages($rules, $db);
  verification_check_run_rules($rules, $db);
  verification_check_update_occurrence_metadata($db);
  $db->query('drop table occlist');
}

/**
 * Build a list of all the rules that are exposed by enabled verification check modules.
 * @return array List of rules
 */
function verification_check_get_rules() {
  $cacheId = 'verification-check-rules';
  $cache = Cache::instance();
  // use cached rules if available
  if ($cache) {//(!($rules = $cache->get($cacheId))) {
    // need to build the set of rules from plugin modules
    $rules = array();
    foreach (Kohana::config('config.modules') as $path) {
      $plugin = basename($path);
      if (file_exists("$path/plugins/$plugin.php")) {
        require_once("$path/plugins/$plugin.php");
        if (function_exists($plugin.'_verification_rules')) {
          $pluginRules = call_user_func($plugin.'_verification_rules');
          // mark each rule with the plugin name that generated it.
          foreach ($pluginRules as &$pluginRule)
            $pluginRule['plugin'] = $plugin;
          $rules = array_merge($rules, $pluginRules);
        }
      }
    }
    $cache->set($cacheId, $rules);
  }
  return $rules;
}

/**
 * Build a temporary table with the list of occurrences we will process, so that we have
 * consistency if changes are happening concurrently.
 * @param type $db 
 */
function verification_check_get_occurrence_list($db) {
  $query = 'create temporary table occlist as 
  select o.id as occurrence_id, s.id as sample_id, w.id as website_id, 
  ttl.id as taxa_taxon_list_id, ttl.verification_check_version, now() as timepoint
  from occurrences o
  inner join samples s on s.id=o.sample_id and s.deleted=false
  inner join websites w on w.id=o.website_id and w.deleted=false and w.verification_checks_enabled=true
  inner join taxa_taxon_lists ttl on ttl.id = o.taxa_taxon_list_id and ttl.deleted=false
  where o.deleted=false and o.record_status not in (\'V\',\'R\',\'D\')
  and (ttl.id <> o.last_verification_check_taxa_taxon_list_id
  or ttl.verification_check_version>last_verification_check_version
  or o.updated_on>o.last_verification_check_date
  or s.updated_on>o.last_verification_check_date)';;
  $db->query($query);
}

/**
 * Loop through the rules. For each distinct module, clean up any old messages for
 * occurrences we are about to rescan
 * @param type $rules List of rule definitions
 */
function verification_check_cleanout_old_messages($rules, $db) {
  $modulesDone=array();
  foreach ($rules as $rule) {
    if (!in_array($rule['plugin'], $modulesDone)) {
      // mark delete any previous occurrence comments for this plugin for taxa we are rechecking
      $query = 'update occurrence_comments oc
  set deleted=true
  from occlist
  where oc.occurrence_id=occlist.occurrence_id
  and oc.generated_by=\''.$rule['plugin'].'\'';
      $db->query($query);
      $modulesDone[]=$rule['plugin'];
    }
  }
}

/**
 * Run through the list of verification check rules, and run the queries to generate
 * comments in the occurrences.
 */
function verification_check_run_rules($rules, $db) {
  foreach ($rules as $rule) {
    $query = 'insert into occurrence_comments (comment, created_by_id, created_on,  
    updated_by_id, updated_on, occurrence_id, auto_generated, generated_by) 
select distinct \''.$rule['message'].'\', 1, now(), 1, now(), occlist.occurrence_id, true, \''.$rule['plugin'].'\'
from occlist';
    if (isset($rule['query']['joins']))
      $query .= "\n" . $rule['query']['joins'];
    if (isset($rule['query']['where']))
      $query .= "\n" . $rule['query']['where'];
    // we now have the query ready to run which will return a list of the occurrence ids that fail the check.
    $db->query($query);
  }
}

/**
 * Update the metadata associated with each occurrence so we know the rules have been run.
 * @param type $db Kohana database instance.
 */
function verification_check_update_occurrence_metadata($db) { 
  // Note we use the information from the point when we started the process, in case
  // any changes have happened in the meanwhile which might otherwise be missed.
  $query = 'update occurrences o
set last_verification_check_date=occlist.timepoint, 
    last_verification_check_taxa_taxon_list_id=occlist.taxa_taxon_list_id, 
    last_verification_check_version=occlist.verification_check_version
from occlist
where occlist.occurrence_id=o.id';
  $db->query($query);
}