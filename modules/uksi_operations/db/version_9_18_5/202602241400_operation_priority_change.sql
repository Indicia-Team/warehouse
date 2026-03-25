

UPDATE uksi_operations
SET operation_priority=CASE lower(operation)
  WHEN 'extract name' THEN 1
  WHEN 'new taxon' THEN 2
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