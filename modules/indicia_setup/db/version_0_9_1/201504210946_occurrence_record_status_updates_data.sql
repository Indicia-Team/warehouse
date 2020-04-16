-- #slow script#
UPDATE occurrence_comments SET query=FALSE;

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
