-- #slow script#

UPDATE cache_samples_functional u
SET external_key=s.external_key
FROM samples s
WHERE s.id=u.id
AND COALESCE(s.external_key, '') <> COALESCE(u.external_key, '');