  -- Optional: run inside a transaction
BEGIN;

-- Create a new view that normalizes Processed / Has errors to Yes/No
CREATE OR REPLACE VIEW gv2_uksi_operations AS
SELECT
  id,
  sequence,
  operation,
  taxon_name,
  CASE
      WHEN operation_processed = true THEN 'Yes'::text
      ELSE 'No' :: text
  END AS operation_processed,
  CASE
      WHEN error_detail IS NOT NULL THEN 'Yes'::text
      ELSE 'No' :: text
  END AS has_errors,
  batch_processed_on::date AS batch_processed_on
FROM uksi_operations
WHERE deleted = false;

-- Optional: match grants from the original view
-- GRANT SELECT ON gv2_uksi_operations TO your_app_role;
COMMIT;

