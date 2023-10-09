ALTER TABLE uksi_operations
ADD COLUMN operation_priority integer;

COMMENT ON COLUMN uksi_operations.operation_priority IS
  'Processing order within each batch, as determined by the operation type. Automatically set by the model.';

UPDATE uksi_operations
SET operation_priority=CASE lower(operation)
  WHEN 'new taxon' THEN 1
  WHEN 'extract name' THEN 2
  WHEN 'amend taxon' THEN 3
  WHEN 'promote name' THEN 4
  WHEN 'rename taxon' THEN 5
  WHEN 'merge taxa' THEN 6
  WHEN 'add synonym' THEN 7
  WHEN 'amend name' THEN 8
  WHEN 'move name' THEN 9
  WHEN 'deprecate name' THEN 10
  WHEN 'remove deprecation' THEN 11
  ELSE 999
END