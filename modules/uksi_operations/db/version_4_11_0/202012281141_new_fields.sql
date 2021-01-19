ALTER TABLE uksi_operations
  ADD COLUMN current_organism_key CHAR(16),
  ADD COLUMN notes TEXT,
  ADD COLUMN testing_comment TEXT;

COMMENT ON COLUMN uksi_operations.current_organism_key IS 'Organism key for operations which affect an existing taxon concept.';
COMMENT ON COLUMN uksi_operations.notes IS 'Notes from the spreadsheet';
COMMENT ON COLUMN uksi_operations.testing_comment IS 'Testing comments from the spreadsheet';