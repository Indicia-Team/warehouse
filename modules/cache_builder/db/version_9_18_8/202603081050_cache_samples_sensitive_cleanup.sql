-- #slow script#

-- Cleanup unnecessary records from cache_samples_sensitive, which should only
-- contain records where sensitive=true or private=true.
DELETE FROM cache_samples_sensitive ss
USING cache_samples_functional s
WHERE ss.id = s.id
AND (s.sensitive = false AND s.private = false);

SELECT s.id
INTO TEMPORARY TABLE tmp_samples_to_update
FROM cache_samples_sensitive ss
JOIN cache_samples_functional s ON s.id = ss.id
WHERE s.sensitive = true OR s.private = true
AND ss.location_ids IS NULL;

-- Re-generate the spatial links.
INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT 'task_spatial_index_builder_sample', 'sample', id, 2, 30, now()
FROM tmp_samples_to_update;

-- Use this query to test if the work queue has completed:
SELECT count(*) FROM work_queue q
WHERE q.task='task_spatial_index_builder_sample'
AND q.entity='sample'
AND q.record_id IN (SELECT id FROM tmp_samples_to_update);

-- Once the above returns zero, we can force re-indexing.
UPDATE cache_samples_functional s
SET website_id=s.website_id
WHERE s.id IN (SELECT id FROM tmp_samples_to_update);

UPDATE cache_occurrences_functional o
SET website_id=o.website_id
WHERE o.sample_id IN (SELECT id FROM tmp_samples_to_update);