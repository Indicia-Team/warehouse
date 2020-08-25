-- #slow script#

UPDATE cache_occurrences_functional
SET location_id=NULL
WHERE location_id IS NOT NULL
AND sensitive=TRUE;