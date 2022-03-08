CREATE TABLE imports (
  id serial NOT NULL,
  entity text NOT NULL,
  inserted integer NOT NULL,
  updated integer NOT NULL,
  errors integer NOT NULL,
  description text,
  import_guid text NOT NULL,
  mappings json NOT NULL,
  global_values json NOT NULL,
  created_on timestamp without time zone,
  created_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_imports PRIMARY KEY (id),
  CONSTRAINT fk_imports_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE INDEX fki_import_creator
  ON imports
  USING btree
  (created_by_id);

COMMENT ON TABLE imports
  IS 'List of additional attributes that are defined for survey dataset records.';
COMMENT ON COLUMN imports.entity IS 'Primary entity being imported (e.g. occurrence, location).';
COMMENT ON COLUMN imports.inserted IS 'Number of records inserted by the import.';
COMMENT ON COLUMN imports.updated IS 'Number of records updated by the import.';
COMMENT ON COLUMN imports.errors IS 'Number of rows with errors left in the import.';
COMMENT ON COLUMN imports.description IS 'Description metadata field provided by the user.';
COMMENT ON COLUMN imports.import_guid IS 'Unique identifier for this import.';
COMMENT ON COLUMN imports.mappings IS 'JSON containing the mapping data used in the import.';
COMMENT ON COLUMN imports.global_values IS 'JSON containing the global values applied to all rows in the import.';
COMMENT ON COLUMN imports.created_on IS 'Date this record was created.';
COMMENT ON COLUMN imports.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN imports.deleted IS 'Has this record been deleted?';

