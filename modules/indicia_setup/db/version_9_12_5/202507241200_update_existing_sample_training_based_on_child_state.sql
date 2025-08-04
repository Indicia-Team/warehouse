-- #slow script#

-- Switch training flag on sample if all of its occurrences are in the opposite training state

UPDATE samples s1
SET training = NOT s1.training
FROM samples s2
JOIN occurrences o1 on o1.sample_id = s2.id and o1.training = NOT s2.training and o1.deleted = false
LEFT JOIN occurrences o2 on o2.sample_id = s2.id and o2.training = s2.training and o2.deleted = false
WHERE s2.id = s1.id AND o2.id IS NULL AND s2.deleted=false;

-- Do same for cache table
UPDATE cache_samples_functional s1
SET training = NOT s1.training
FROM samples s2
JOIN occurrences o1 on o1.sample_id = s2.id and o1.training = NOT s2.training and o1.deleted = false
LEFT JOIN occurrences o2 on o2.sample_id = s2.id and o2.training = s2.training and o2.deleted = false
WHERE s2.id = s1.id AND o2.id IS NULL AND s2.deleted=false;

-- Do similar for samples/subsamples, switch training flag on parent sample if all its child subsamples
-- are in the opposite state.

UPDATE samples s_parent1
SET training = NOT s_parent1.training
FROM samples s_parent2
JOIN samples s_child1 on s_child1.parent_id = s_parent2.id and s_child1.training = NOT s_parent2.training and s_child1.deleted = false
LEFT JOIN samples s_child2 on s_child2.parent_id = s_parent2.id and s_child2.training = s_parent2.training and s_child2.deleted = false
WHERE s_parent2.id = s_parent1.id AND s_child2.id IS NULL AND s_parent2.deleted=false;

UPDATE cache_samples_functional s_parent1
SET training = NOT s_parent1.training
FROM samples s_parent2
JOIN samples s_child1 on s_child1.parent_id = s_parent2.id and s_child1.training = NOT s_parent2.training and s_child1.deleted = false
LEFT JOIN samples s_child2 on s_child2.parent_id = s_parent2.id and s_child2.training = s_parent2.training and s_child2.deleted = false
WHERE s_parent2.id = s_parent1.id AND s_child2.id IS NULL AND s_parent2.deleted=false;