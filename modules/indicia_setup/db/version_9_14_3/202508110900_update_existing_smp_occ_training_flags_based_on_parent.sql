-- #slow script#

-- Update any subsample training flags if the parent sample is training.
with updated as (
UPDATE samples s1
SET training = true, updated_on=now(), updated_by_id=1
FROM samples s2
WHERE s2.id = s1.parent_id AND s1.training = false AND s2.training = true
RETURNING s1.id, s1.training)
UPDATE cache_samples_functional csf
SET training = updated.training, updated_on=now()
FROM updated
WHERE csf.id = updated.id;

-- Make sure all the occurrences are also training for any training samples.
with updated as (
UPDATE occurrences o
SET training = true, updated_on=now(), updated_by_id=1
FROM samples s
WHERE o.sample_id = s.id AND o.training = false AND s.training = true
RETURNING o.id, o.training)
UPDATE cache_occurrences_functional cof
SET training = updated.training, updated_on=now()
FROM updated
WHERE cof.id = updated.id;