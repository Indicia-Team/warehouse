-- #slow script#
UPDATE cache_samples_functional csf
SET training = TRUE
FROM samples s 
WHERE s.id = csf.id and s.training = true;