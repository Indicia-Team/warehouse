-- SLOW SCRIPT
ALTER TABLE occurrences
   ADD COLUMN record_substatus smallint CONSTRAINT occurrences_record_substatus_check CHECK (record_substatus BETWEEN 1 AND 5),  
   ADD COLUMN record_decision_source character CONSTRAINT record_decision_source_check CHECK (record_decision_source IN ('H', 'M'));

COMMENT ON COLUMN occurrences.record_substatus IS 'Provides additional detail on the record status. Values are: 1=accepted as correct, 2=accepted as considered correct, 3=plausible, 4=not accepted as unable to verify, 5=not accepted, incorrect. Null for unchecked records.';
COMMENT ON COLUMN occurrences.record_decision_source IS 'Defines if the record status decision was by a human (H) or machine (M).';

ALTER TABLE occurrence_comments
   ADD COLUMN query boolean,
   -- Add columns to log occurrence status changes with the comments
   ADD COLUMN record_status character(1) CONSTRAINT occurrence_comments_record_status_check CHECK (record_status = ANY (ARRAY['I'::bpchar, 'C'::bpchar, 'V'::bpchar, 'R'::bpchar, 'T'::bpchar, 'D'::bpchar])),
   ADD COLUMN record_substatus smallint CONSTRAINT occurrence_comments_record_substatus_check CHECK (record_substatus BETWEEN 1 AND 5);
UPDATE occurrence_comments SET query=FALSE;
ALTER TABLE occurrence_comments ALTER query SET default false;

COMMENT ON COLUMN occurrence_comments.query IS 'Set to true if this comment asks a question that needs a response.';
COMMENT ON COLUMN occurrence_comments.record_status IS 'If this comment relates to the changing of the status of a record, then determines the status it was changed to. Provides and audit trail of verification changes.';
COMMENT ON COLUMN occurrence_comments.record_substatus IS 'As record_status but provides an audit trail of the occurrences.record_substatus field';

UPDATE occurrence_comments oc 
SET query=true 
FROM occurrences o 
WHERE (oc.comment LIKE 'I emailed this record%' OR oc.comment LIKE 'Query%')
AND oc.occurrence_id=o.id
AND o.record_status IN ('S', 'C', 'D');

-- Sent status is no longer valid. We track queries via comments instead.
UPDATE occurrences SET record_status='C' where record_status='S';

ALTER TABLE occurrences DROP CONSTRAINT occurrences_record_status_check;

ALTER TABLE occurrences
  ADD CONSTRAINT occurrences_record_status_check CHECK (record_status = ANY (ARRAY['I'::bpchar, 'C'::bpchar, 'V'::bpchar, 'R'::bpchar, 'T'::bpchar, 'D'::bpchar]));

COMMENT ON COLUMN occurrences.record_status IS 'Status of this record. I - in progress, C - completed, V - verified, R - rejected, D - dubious/queried (deprecated), T - test.';
