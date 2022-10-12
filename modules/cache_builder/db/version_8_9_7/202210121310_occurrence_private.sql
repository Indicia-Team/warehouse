-- #slow script#

UPDATE cache_occurrences_functional u
SET private=s.privacy_precision IS NOT NULL
FROM samples s
WHERE s.id=u.sample_id
AND u.private IS NULL;