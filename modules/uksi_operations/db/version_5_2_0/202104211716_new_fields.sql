ALTER TABLE uksi_operations
  ADD COLUMN current_name TEXT;

COMMENT ON COLUMN uksi_operations.current_name IS 'Name added in this batch being referred to in a later operation, e.g. to add a synonym to.';