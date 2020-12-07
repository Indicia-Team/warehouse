ALTER TABLE taxa
  ADD COLUMN organism_key varchar;

COMMENT ON COLUMN taxa.organism_key
    IS 'Identifier for the organism concept, e.g. when linking to UKSI.';