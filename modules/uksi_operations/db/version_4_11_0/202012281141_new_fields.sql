ALTER TABLE uksi_operations
  ADD COLUMN current_organism_key CHAR(16);

COMMENT ON COLUMN uksi_operations.current_organism_key IS 'Organism key for operations which affect an existing taxon concept.';