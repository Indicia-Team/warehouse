
ALTER TABLE occurrences
ADD COLUMN IF NOT EXISTS dna_derived BOOLEAN DEFAULT FALSE NOT NULL;

COMMENT ON COLUMN occurrences.dna_derived IS 'Flag indicating if the occurrence is DNA derived and has metadata in the dna_occurrences table.';

-- Trigger to ensure this stays in sync.
CREATE OR REPLACE FUNCTION sync_occurrence_dna_derived() RETURNS TRIGGER AS $$
BEGIN
  IF TG_OP = 'DELETE' THEN
    UPDATE occurrences
       SET dna_derived = FALSE
     WHERE id = OLD.occurrence_id;
  ELSE
    -- Update or insert.
    UPDATE occurrences
      SET dna_derived = NOT new.deleted
    WHERE id = NEW.occurrence_id;
  END IF;
  RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER occurrence_dna_derived_sync
AFTER INSERT OR DELETE ON dna_occurrences
FOR EACH ROW
EXECUTE FUNCTION sync_occurrence_dna_derived();

CREATE TRIGGER occurrence_dna_derived_sync_update
AFTER UPDATE ON dna_occurrences
FOR EACH ROW
WHEN (OLD.deleted IS DISTINCT FROM NEW.deleted)
EXECUTE FUNCTION sync_occurrence_dna_derived();