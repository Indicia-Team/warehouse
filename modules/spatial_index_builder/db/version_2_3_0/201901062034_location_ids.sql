-- #slow script#

UPDATE cache_samples_functional
SET location_ids=ARRAY[]::integer[]
WHERE location_ids IS NULL
AND id<(SELECT max(id) FROM cache_samples_functional WHERE location_ids IS NOT NULL);

UPDATE cache_occurrences_functional
SET location_ids=ARRAY[]::integer[]
WHERE location_ids IS NULL
AND id<(SELECT max(id) FROM cache_occurrences_functional WHERE location_ids IS NOT NULL);