<?php

function taxon_designations_extend_ui() {
  return array(array(
    'view'=>'taxa_taxon_list/taxa_taxon_list_edit', 
    'type'=>'tab',
    'controller'=>'taxon_designation', 
    'title'=>'Designations'
  ));
}

?>