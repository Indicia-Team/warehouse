ALTER TABLE cache_samples_functional
ADD COLUMN training boolean;
COMMENT ON COLUMN cache_samples_functional.training IS 'Flag indicating if this sample was created for training purposes and is therefore not considered real.';
