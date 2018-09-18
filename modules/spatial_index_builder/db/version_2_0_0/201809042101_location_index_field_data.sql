-- #slow script#

-- Build a temp table with sample IDs mapped to their array of indexed locations.
SELECT sample_id, array_agg(distinct location_id) as location_ids
INTO temporary ils_temp
FROM index_locations_samples
GROUP BY sample_id;

CREATE INDEX ix_ils_temp ON ils_temp(sample_id);

-- Temporarily disable indexes for faster updates.
UPDATE pg_index
SET indisready=false
WHERE indrelid in (
    SELECT oid
    FROM pg_class
    WHERE relname in ('cache_occurrences_functional', 'cache_samples_functional')
);

UPDATE cache_occurrences_functional o
SET location_ids=t.location_ids
FROM ils_temp t
WHERE t.sample_id=o.sample_id;

UPDATE cache_samples_functional s
SET location_ids=t.location_ids
FROM ils_temp t
WHERE t.sample_id=s.id;

-- Re-enable the indexes then rebuild them.
UPDATE pg_index
SET indisready=true
WHERE indrelid in (
    SELECT oid
    FROM pg_class
    WHERE relname in ('cache_occurrences_functional', 'cache_samples_functional')
);

REINDEX TABLE cache_occurrences_functional;
REINDEX TABLE cache_samples_functional;

-- Table no longer required.
DROP TABLE index_locations_samples;