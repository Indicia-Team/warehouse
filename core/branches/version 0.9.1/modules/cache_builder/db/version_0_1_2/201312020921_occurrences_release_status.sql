ALTER TABLE cache_occurrences ADD COLUMN release_status character(1);
ALTER TABLE cache_occurrences ALTER COLUMN release_status SET DEFAULT 'R'::bpchar;
COMMENT ON COLUMN cache_occurrences.release_status IS 'Release states of this record. R - released, P - recorder has requested a precheck before release, U - unreleased as part of a project whcih is witholding records until completion.';
  
UPDATE cache_occurrences co
SET release_status=o.release_status
FROM occurrences o
WHERE o.id=co.id
