-- Table: recording_scheme

-- DROP TABLE recording_scheme cascade;

CREATE TABLE recording_scheme
(
  id serial NOT NULL,
  scheme_key varchar PRIMARY KEY, -- Unique key for a recording scheme.
  title character varying NOT NULL, -- Title of recording scheme.
  description text, -- Description of recording scheme.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT fk_recording_scheme_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_recording_scheme_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE recording_scheme IS 'Recording schemes and societies.';
COMMENT ON COLUMN recording_scheme.scheme_key IS 'The unique key for a recording scheme.';
COMMENT ON COLUMN recording_scheme.title IS 'The name of a recording scheme.';

-- Table: recording_scheme_taxa

-- DROP TABLE recording_scheme_taxa;

CREATE TABLE recording_scheme_taxa
(
  id serial NOT NULL,
  scheme_key varchar, -- Recording scheme key.
  organism_key varchar, -- Organism concept key.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT fk_recording_scheme_taxa_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_recording_scheme_taxa_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_recording_scheme_taxa_scheme_key FOREIGN KEY (scheme_key)
      REFERENCES recording_scheme (scheme_key) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE recording_scheme_taxa IS 'Relates recording schemes to the taxa they look after.';
COMMENT ON COLUMN recording_scheme_taxa.id IS 'The unique key for a recording scheme.';
COMMENT ON COLUMN recording_scheme_taxa.organism_key IS 'Identifier for the organism concept.';