<?php

function groups_extend_orm() {
  return array('user'=>array(
    'has_and_belongs_to_many'=>array('groups')
  ));  
}

function groups_extend_data_services() {
  return array(
    'groups'=>array(),
    'groups_users'=>array(),
    'group_invitations'=>array()
  );
}

?>
