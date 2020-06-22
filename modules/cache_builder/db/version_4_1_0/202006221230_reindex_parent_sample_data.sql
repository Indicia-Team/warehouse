-- #slow script#

-- It may be necessary to break this up into chunks of 1 million by adding
-- a BETWEEN .. filter on id.
UPDATE cache_occurrences_functional
SET website_id=website_id
WHERE parent_sample_id IS NOT NULL;