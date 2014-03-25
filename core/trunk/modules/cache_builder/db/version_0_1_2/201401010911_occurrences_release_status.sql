UPDATE cache_occurrences co
SET release_status=o.release_status
FROM occurrences o
WHERE o.id=co.id
