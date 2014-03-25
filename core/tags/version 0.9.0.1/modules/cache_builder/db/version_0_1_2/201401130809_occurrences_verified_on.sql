UPDATE cache_occurrences co
SET verified_on=o.verified_on
FROM occurrences o
WHERE o.id=co.id
AND o.verified_on is not null;