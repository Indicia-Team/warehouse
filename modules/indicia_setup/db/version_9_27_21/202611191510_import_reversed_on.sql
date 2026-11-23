ALTER TABLE imports
  ADD COLUMN reversed_on timestamp without time zone,
  ADD COLUMN reversed_by_id integer,
  ADD CONSTRAINT fk_import_reverser FOREIGN KEY (reversed_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;;

COMMENT ON COLUMN imports.reversed_on IS 'Timestamp when the import was reversed';
COMMENT ON COLUMN imports.reversed_by_id IS 'User ID of the person who reversed the import';
