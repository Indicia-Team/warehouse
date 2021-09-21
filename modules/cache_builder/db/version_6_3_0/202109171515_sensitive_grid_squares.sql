-- #slow script#

-- Trigger re-queuing of sensitive records for Elasticsearch due to previously
-- incorrect map grid square field names.
UPDATE cache_occurrences_functional
SET website_id=website_id
WHERE sensitive=true;