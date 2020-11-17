-- #slow script#
UPDATE samples s
SET training = TRUE
FROM occurrences o 
LEFT JOIN occurrences o2 on o2.sample_id = o.sample_id and o2.id != o.id and o2.training = false and o2.deleted = false
WHERE o.sample_id = s.id and o.training = true and o2.id is null and s.deleted = false and o.deleted = false;