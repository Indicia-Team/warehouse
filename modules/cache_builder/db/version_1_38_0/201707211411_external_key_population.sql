-- #slow script#

UPDATE cache_occurrences_functional cof
SET external_key=o.external_key
FROM occurrences o
WHERE o.id=cof.id 
AND o.external_key IS NULL;