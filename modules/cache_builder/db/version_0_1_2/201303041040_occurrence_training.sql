ALTER TABLE cache_occurrences ADD training boolean NOT NULL default false;
COMMENT ON COLUMN cache_occurrences.training IS 'Flag indicating if this record was created for training purposes and is therefore not considered real.';
