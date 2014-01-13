ALTER TABLE cache_occurrences
   ADD COLUMN verified_on timestamp without time zone;
COMMENT ON COLUMN cache_occurrences.verified_on IS 'Date this record had it''s verification status changed.';

UPDATE cache_occurrences co
SET verified_on=o.verified_on
FROM occurrences o
WHERE o.id=co.id;