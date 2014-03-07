ALTER TABLE cache_occurrences
   ADD COLUMN certainty character(1);

COMMENT ON COLUMN cache_occurrences.certainty IS 'Certainty of the record as indicated by the recorder. Options are C (certain), L (likely) and U (uncertain).';

