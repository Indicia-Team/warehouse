<?php

function survey_structure_export_extend_ui() {
  return array(array(
    'view'=>'survey/survey_edit',
    'type'=>'tab',
    'controller'=>'survey_structure_export',
    'title'=>'Import & Export',
    'allowForNew' => false
  ), array(
    'view'=>'termlist/termlist_edit',
    'type'=>'tab',
    'controller'=>'termlist_export',
    'title'=>'Import & Export',
    'allowForNew' => false
  ));
}

?>