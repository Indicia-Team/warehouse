-- Add a sortable permissiveness order for licences.
-- Lower values are more restrictive, higher values are more permissive.

ALTER TABLE licences
  ADD COLUMN IF NOT EXISTS permissiveness_sort_order integer;

COMMENT ON COLUMN licences.permissiveness_sort_order IS
  'Numeric sort order from restrictive to permissive licences.';

-- Populate known default licence codes added by standard install scripts.
-- Unknown/custom licences are left NULL for site administrators to define.
UPDATE licences
SET permissiveness_sort_order = CASE code
  WHEN 'CC BY-NC-ND' THEN 10
  WHEN 'CC BY-NC-SA' THEN 20
  WHEN 'CC BY-NC' THEN 30
  WHEN 'CC BY-ND' THEN 40
  WHEN 'CC BY-SA' THEN 50
  WHEN 'CC BY' THEN 60
  WHEN 'OGL' THEN 70
  WHEN 'CC0' THEN 80
  ELSE permissiveness_sort_order
END
WHERE deleted = false
AND code IN (
  'CC BY-NC-ND',
  'CC BY-NC-SA',
  'CC BY-NC',
  'CC BY-ND',
  'CC BY-SA',
  'CC BY',
  'OGL',
  'CC0'
)
AND permissiveness_sort_order IS NULL;
