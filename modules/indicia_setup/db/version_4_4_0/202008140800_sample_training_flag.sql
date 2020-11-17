ALTER TABLE samples
ADD COLUMN training boolean NOT NULL DEFAULT false;
COMMENT ON COLUMN samples.training IS 'Flag indicating if this sample was created for training purposes and is therefore not considered real.';
