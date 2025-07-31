-- #slow script#

-- This first part of the code is the same as it was previously, but we need to do run again
-- as there may be new subsamples with an incorrect training flag since last time it was run.

UPDATE samples s
SET training = TRUE
FROM occurrences o
LEFT JOIN occurrences o2 on o2.sample_id = o.sample_id and o2.training = false and o2.deleted = false
WHERE o.sample_id = s.id and o.training = true and o2.id is null and s.deleted = false and o.deleted = false;

UPDATE cache_samples_functional csf
SET training = TRUE
FROM occurrences o
LEFT JOIN occurrences o2 on o2.sample_id = o.sample_id and o2.training = false and o2.deleted = false
WHERE o.sample_id = csf.id and o.training = true and o2.id is null and o.deleted = false;

UPDATE samples s_parent
SET training = TRUE
FROM samples s_child
LEFT JOIN samples s_child2 on s_child2.parent_id = s_child.parent_id and s_child2.training = false and s_child2.deleted = false
WHERE s_child.parent_id = s_parent.id and s_child.training = true and s_child2.id is null and s_parent.deleted = false and s_child.deleted = false;

UPDATE cache_samples_functional csf_parent
SET training = TRUE
FROM samples s_child
LEFT JOIN samples s_child2 on s_child2.parent_id = s_child.parent_id and s_child2.training = false and s_child2.deleted = false
WHERE s_child.parent_id = csf_parent.id and s_child.training = true and s_child2.id is null and s_child.deleted = false;