CREATE OR REPLACE FUNCTION quality_check(quality character varying, record_status character, certainty character)
  RETURNS boolean AS
$BODY$
DECLARE r boolean;
BEGIN
r=
  -- always include verified
  ((record_status='V') OR
  -- include certain data if requested, unless expert marked as dubious
  (quality='C' AND certainty='C' AND record_status <> 'D') OR
  -- include certain or likely data if requested, unless expert marked as dubious. Certainty not indicated treated as likely
  (quality='L' AND (certainty in ('C', 'L') OR (certainty IS NULL)) AND record_status <> 'D') OR
  -- include anything not dubious or worse, if requested
  (quality='!D' AND record_status NOT IN ('D'))) AND 
  -- always exclude rejected, in progress and test records
  record_status NOT IN ('R', 'I', 'T');
RETURN r;
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
COMMENT ON FUNCTION quality_check(quality character varying, record_status character, certainty character) IS 
    'Implements a standard quality check for a record, based on the record_status and certainty. Quality can be requested to be V (verified), C (at least certain), L (at least likely), !D (not dubious or rejected), !R (not rejected).';

