-- Table: recording_schemes

CREATE TABLE recording_schemes
(
  id serial NOT NULL,
  external_key varchar NOT NULL, -- Unique external identifier for a recording scheme.
  title varchar NOT NULL, -- Title of recording scheme.
  description text, -- Description of recording scheme.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_recording_schemes PRIMARY KEY (id),
  CONSTRAINT fk_recording_schemes_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_recording_schemes_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE recording_schemes IS 'Recording schemes and societies.';
COMMENT ON COLUMN recording_schemes.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN recording_schemes.external_key IS 'A unique external key for a recording scheme.';
COMMENT ON COLUMN recording_schemes.title IS 'The name of a recording scheme.';
COMMENT ON COLUMN recording_schemes.description IS 'A short description of a recording scheme.';

-- Table: recording_scheme_taxa

CREATE TABLE recording_scheme_taxa
(
  id serial NOT NULL,
  recording_scheme_id integer NOT NULL, -- Recording scheme ID.
  organism_key varchar, -- Organism concept key.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_recording_scheme_taxa PRIMARY KEY (id),
  CONSTRAINT fk_recording_scheme_taxa_recording_scheme_id FOREIGN KEY (recording_scheme_id)
      REFERENCES recording_schemes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_recording_scheme_taxa_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_recording_scheme_taxa_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE recording_scheme_taxa IS 'Relates recording schemes to the taxa they look after.';
COMMENT ON COLUMN recording_scheme_taxa.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN recording_scheme_taxa.recording_scheme_id IS 'Foreign key identifying the recording scheme';
COMMENT ON COLUMN recording_scheme_taxa.organism_key IS 'Identifier for the organism concept.';