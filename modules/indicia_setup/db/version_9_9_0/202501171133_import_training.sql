ALTER TABLE imports
ADD training boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN imports.training is 'Flag indicating if this import was performed in training mode.';