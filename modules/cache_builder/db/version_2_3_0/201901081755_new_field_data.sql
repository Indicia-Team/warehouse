-- #slow script#
UPDATE cache_occurrences_functional o
SET verification_checks_enabled=true
FROM websites w
WHERE w.id=o.website_id
AND w.verification_checks_enabled=true;

UPDATE cache_occurrences_functional o
SET parent_sample_id=s.parent_id
FROM samples s
WHERE s.id=o.sample_id
AND s.parent_id IS NOT NULL;

UPDATE cache_samples_functional sf
SET parent_sample_id=s.parent_id
FROM samples s
WHERE s.id=sf.id
AND s.parent_id IS NOT NULL;