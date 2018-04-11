<fieldset><legend><?php echo $other_data['name']; ?> attribute
species list allocation</legend>
  <ol>
<?php
// TODO this query must filter out the taxon_lists_taxa_taxon_list_attributes.deleted flag (in (false or null))

if (is_null($this->auth_filter) || $this->auth_filter['field'] !== 'website_id')
  $lists = ORM::factory('taxon_list')->where(array('deleted'=>'f'))->orderby('title','asc')->find_all();
else
  $lists = ORM::factory('taxon_list')->where(array('deleted'=>'f'))->in('website_id',$this->auth_filter['values'])->orderby('title','asc')->find_all();
foreach ($lists as $list) {
  $rec = $this->db->select('id')
      ->from('taxon_lists_taxa_taxon_list_attributes')
      ->where(array(
        'taxon_list_id'=>$list->id,
        'taxa_taxon_list_attribute_id'=>html::initial_value($values,$model->object_name.':id'),
        'deleted'=>'f'
      ))
      ->get()->result_array();
  echo '<li><label for="taxon_list_'.$list->id.'" class="wide">'.$list->title.'</label>';
  echo form::checkbox('taxon_list_'.$list->id, TRUE, count($rec)>0, 'class="vnarrow"');
  echo '</li>';
}
?>
  </ol>
</fieldset>
