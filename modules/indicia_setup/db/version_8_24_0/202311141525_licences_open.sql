ALTER TABLE licences
  ADD COLUMN open boolean default false;

COMMENT ON COLUMN licences.open IS 'True if the licence is considered open, so the records are available for wider use with few or no restrictions.';

UPDATE licences
SET open=true
WHERE code IN ('CC0', 'CC BY', 'OGL');

