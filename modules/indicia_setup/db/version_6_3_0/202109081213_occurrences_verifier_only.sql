ALTER TABLE occurrences
    ADD COLUMN verifier_only_data json;

COMMENT ON COLUMN occurrences.verifier_only_data
    IS 'Data provided by the recorder about a record where they have only given permission for the data to be used for verification purposes, not for public reporting or onward data tranmission.';