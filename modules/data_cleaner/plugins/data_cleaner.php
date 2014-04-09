<?php

/**
 * Create a menu item for the list of taxon designations.
 */
function data_cleaner_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin')) 
    $menu['Admin']['Verification Rules']='verification_rule';
  return $menu;
}

/** 
 * Adds the verification_rule entity to the list available via data services.
 * @return array List of additional entities to expose via the data services.
 */
function data_cleaner_extend_data_services() {
  return array(
    'verification_rules'=>array('readOnly')
  );
}

function data_cleaner_metadata() {
  return array(
    'requires_occurrences_delta'=>TRUE
  );
}

/**
 * Hook into the task scheduler to run the rules against new records on the system.
 */
function data_cleaner_scheduled_task($timestamp, $db, $endtime) {
  $rules = data_cleaner::get_rules();
  data_cleaner_cleanout_old_messages($rules, $db);
  data_cleaner_run_rules($rules, $db);
  data_cleaner_update_occurrence_metadata($db, $endtime);
  data_cleaner_set_cache_fields($db);
}


/**
 * Loop through the rules. For each distinct module, clean up any old messages for
 * occurrences we are about to rescan
 * @param array $rules List of rule definitions
 * @param object db Database object.
 */
function data_cleaner_cleanout_old_messages($rules, $db) {
  $modulesDone=array();
  foreach ($rules as $rule) {
    if (!in_array($rule['plugin'], $modulesDone)) {
      // mark delete any previous occurrence comments for this plugin for taxa we are rechecking
      $query = 'update occurrence_comments oc
        set deleted=true
        from occdelta o
        where oc.occurrence_id=o.id
        and oc.generated_by=\''.$rule['plugin'].'\'';
      $db->query($query);
      // and cleanup the notifications generated previously
      $query = "delete 
        from notifications
        using occdelta o 
        where source='Verifications and comments'
        and linked_id = o.id";
      $db->query($query);
      $modulesDone[]=$rule['plugin'];
    }
  }
}

/**
 * Run through the list of data cleaner rules, and run the queries to generate
 * comments in the occurrences.
 */
function data_cleaner_run_rules($rules, $db) {
  $count=0;
  foreach ($rules as $rule) {
    $tm = microtime(true);
    if (isset($rule['errorMsgField'])) 
      // rules are able to specify a different field (e.g. from the verification rule data) to provide the error message.
      $errorField = $rule['errorMsgField'];
    else
      $errorField = 'error_message';
    foreach ($rule['queries'] as $query) {
      // queries can override the error message field.
      $ruleErrorField = isset($query['errorMsgField']) ? $query['errorMsgField'] : $errorField;
      $implies_manual_check_required = isset($query['implies_manual_check_required']) && !$query['implies_manual_check_required'] ? 'false' : 'true';
      $errorMsgSuffix = isset($query['errorMsgSuffix']) ? $query['errorMsgSuffix'] : (isset($rule['errorMsgSuffix']) ? $rule['errorMsgSuffix'] : '');
      $subtypeField = empty($query['subtypeField']) ? '' : ", generated_by_subtype";
      $subtypeValue = empty($query['subtypeField']) ? '' : ", $query[subtypeField]";
      $sql = "insert into occurrence_comments (comment, created_by_id, created_on,
      updated_by_id, updated_on, occurrence_id, auto_generated, generated_by, implies_manual_check_required$subtypeField) 
  select distinct $ruleErrorField$errorMsgSuffix, 1, now(), 1, now(), co.id, true, '$rule[plugin]', $implies_manual_check_required$subtypeValue
  from occdelta co";
      if (isset($query['joins']))
        $sql .= "\n" . $query['joins'];
      if (isset($query['where']))
        $sql .= "\nwhere " . $query['where'];
      // we now have the query ready to run which will return a list of the occurrence ids that fail the check.
      try {
        $count += $db->query($sql)->count();
      } catch (Exception $e) {
        echo "Query failed<br/>";
        echo $e->getMessage().'<br/>';
        echo $db->last_query().'<br/>';  
      }
    }
    $tm = microtime(true) - $tm;  
    if ($tm>3) 
      kohana::log('alert', "Data cleaner rule ".$rule['testType']." took $tm seconds");
  }
  
  echo "Data cleaner generated $count messages.<br/>";
}

/**
 * Update the metadata associated with each occurrence so we know the rules have been run.
 * @param type $db Kohana database instance.
 */
function data_cleaner_update_occurrence_metadata($db, $endtime) { 
  // Note we use the information from the point when we started the process, in case
  // any changes have happened in the meanwhile which might otherwise be missed.
  $query = "update occurrences o
set last_verification_check_date='$endtime'
from occdelta
where occdelta.id=o.id";
  $db->query($query);
}

/**
 * Update the cache_occurrences.data_cleaner_info field.
 */ 
function data_cleaner_set_cache_fields($db) {
  if (in_array(MODPATH.'cache_builder', Kohana::config('config.modules'))) {
    $query = "update cache_occurrences co
set data_cleaner_info=case when o.last_verification_check_date is null then null else case sub.info when '' then 'pass' else sub.info end end
from occdelta
join occurrences o on o.id=occdelta.id
join (
      select o.id, o.last_verification_check_date, 
        array_to_string(array_agg(distinct '[' || oc.generated_by || ']{' || oc.comment || '}'),' ') as info
      from occurrences o
      join occdelta on occdelta.id=o.id
            left join occurrence_comments oc 
            on oc.occurrence_id=o.id 
            and oc.implies_manual_check_required=true 
            and oc.deleted=false
      group by o.id, o.last_verification_check_date
    ) sub on sub.id=o.id
where occdelta.id=co.id";
  $db->query($query);
  }
}

?>
