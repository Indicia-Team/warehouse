-- #slow script#

UPDATE cache_samples_functional s
SET external_key=smp.external_key
FROM samples smp
WHERE smp.id=s.id;