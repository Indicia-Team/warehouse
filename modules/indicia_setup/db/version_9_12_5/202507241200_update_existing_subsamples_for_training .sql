-- #slow script#

-- Switch training flag on sample if all of its occurrences are in the opposite training state

UPDATE samples s
SET training = NOT s.training
FROM occurrences o
LEFT JOIN occurrences o2 on o2.sample_id = o.sample_id and o2.training = s.training and o2.deleted = false
WHERE o.sample_id = s.id and o.training = NOT s.training and o2.id is null and s.deleted = false and o.deleted = false;
-- Do same for cache table
UPDATE cache_samples_functional csf
SET training = NOT csf.training
FROM occurrences o
LEFT JOIN occurrences o2 on o2.sample_id = o.sample_id and o2.training = csf.training and o2.deleted = false
WHERE o.sample_id = csf.id and o.training = NOT csf.training and o2.id is null and o.deleted = false;

-- Do similar for samples/subsamples, switch training flag on parent sample if all its child subsamples
-- are in the opposite state.

UPDATE samples s_parent
SET training = NOT s_parent.training
FROM samples s_child
LEFT JOIN samples s_child2 on s_child2.parent_id = s_child.parent_id and s_child2.training = s_parent.training and s_child2.deleted = false
WHERE s_child.parent_id = s_parent.id and s_child.training = NOT s_parent.training and s_child2.id is null and s_parent.deleted = false and s_child.deleted = false;

UPDATE cache_samples_functional csf_parent
SET training = NOT csf_parent.training
FROM samples s_child
LEFT JOIN samples s_child2 on s_child2.parent_id = s_child.parent_id and s_child2.training = csf_parent.training and s_child2.deleted = false
WHERE s_child.parent_id = csf_parent.id and s_child.training = NOT csf_parent.training and s_child2.id is null and s_child.deleted = false;