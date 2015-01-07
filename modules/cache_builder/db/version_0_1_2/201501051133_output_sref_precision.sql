ALTER TABLE cache_occurrences ADD COLUMN output_sref character varying;
ALTER TABLE cache_occurrences ADD COLUMN sref_precision integer;

COMMENT ON COLUMN cache_occurrences.output_sref IS
    'A display spatial reference created for all records, using the most appropriate local grid system where possible.';
COMMENT ON COLUMN cache_occurrences.sref_precision IS
    'For point based map references, stores the precision of the point if known, e.g. as supplied by a GPS.';
