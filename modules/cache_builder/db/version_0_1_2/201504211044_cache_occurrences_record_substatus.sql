-- move into a cache_builder script
ALTER TABLE cache_occurrences
   ADD COLUMN record_substatus smallint,
   ADD COLUMN query character(1);

COMMENT ON COLUMN cache_occurrences.record_substatus is 'Detail on record status, copied from occurrences table.';
COMMENT ON COLUMN cache_occurrences.query is 'Derived query status of the record. Q if there is a query comment outstanding A if the query comment has an answer.';