-- #slow script#

-- Temporary index to speed up the query.
CREATE INDEX ix_temp_samples_import_guid ON samples (import_guid);

-- Set existing values.
UPDATE imports i
SET training=true
WHERE (SELECT min(s.training::integer) FROM samples s WHERE s.import_guid=i.import_guid)=1;

DROP INDEX ix_temp_samples_import_guid;