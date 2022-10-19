ALTER TABLE terms
  ADD COLUMN code character varying,
  ADD COLUMN description character varying;

COMMENT ON COLUMN terms.code
    IS 'A code or reference number associated with the term.';

COMMENT ON COLUMN terms.description
    IS 'A description of the term.';
