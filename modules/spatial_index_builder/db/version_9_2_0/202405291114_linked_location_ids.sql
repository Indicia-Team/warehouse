-- #slow script#

-- Requeue samples where the user specified an indexed location to link to.
insert into work_queue(task, entity, record_id, cost_estimate, priority, created_on)
	select distinct 'task_spatial_index_builder_sample', 'sample', s.id, 70, 2, now()
  from samples s
  join sample_attribute_values v on v.sample_id=s.id and v.deleted=false
  join sample_attributes a on a.id=v.sample_attribute_id and a.deleted=false and a.system_function='linked_location_id'