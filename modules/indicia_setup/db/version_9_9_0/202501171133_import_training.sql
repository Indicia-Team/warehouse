ALTER TABLE imports
ADD training boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN imports.training is 'Flag indicating if this import was performed in training mode.';

-- Set existing values.
UPDATE imports i
SET training=true
WHERE (SELECT min(s.training::integer) FROM samples s WHERE s.import_guid=i.import_guid)=1;