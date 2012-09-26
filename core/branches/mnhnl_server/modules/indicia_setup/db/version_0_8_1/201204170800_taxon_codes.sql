INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Taxon code types', 'Types of taxon code, such as BRC code, NBN Key or GBIF number.', now(), 1, now(), 1, 'indicia:taxon_code_types');

SELECT insert_term('searchable', 'eng', null, 'indicia:taxon_code_types');
SELECT insert_term('not searchable', 'eng', null, 'indicia:taxon_code_types');
SELECT insert_term('Bradley Fletcher', 'eng', null, 'indicia:taxon_code_types');

UPDATE termlists_terms tlt
SET parent_id=(SELECT id FROM list_termlists_terms WHERE term='searchable' AND termlist_external_key='indicia:taxon_code_types') 
FROM termlists tl, terms t
WHERE t.term='Bradley Fletcher'
AND t.id=tlt.term_id AND t.deleted=false
AND tl.id=tlt.termlist_id AND tl.deleted=false
AND tl.external_key='indicia:taxon_code_types';

-- Table: taxon_codes

-- DROP TABLE taxon_codes;

CREATE TABLE taxon_codes
(
  id serial NOT NULL, -- Unique identifier and primary key for the table.
  taxon_meaning_id integer NOT NULL, -- Identifies the taxon meaning which the code applies to. Foreign key to the taxon_meanings table.
  code character varying NOT NULL, -- The text for the taxon code.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  code_type_id integer NOT NULL, -- Identifies the type of code, e.g. BRC code, Bradley Fletcher Number, GBIF number. Foreign key to the termlists_terms table.
  CONSTRAINT fk_taxon_codes_code_type FOREIGN KEY (code_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_codes_taxon_meaning FOREIGN KEY (taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_code_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_code_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE taxon_codes IS 'List of codes which can be used to identify a taxon as an alternative to a taxon name.';
COMMENT ON COLUMN taxon_codes.id IS 'Unique identifier and primary key for the table.';
COMMENT ON COLUMN taxon_codes.taxon_meaning_id IS 'Identifies the taxon meaning which the code applies to. Foreign key to the taxon_meanings table.';
COMMENT ON COLUMN taxon_codes.code IS 'The text for the taxon code.';
COMMENT ON COLUMN taxon_codes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_codes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_codes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_codes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxon_codes.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN taxon_codes.code_type_id IS 'Identifies the type of code, e.g. BRC code, Bradley Fletcher Number, GBIF number. Foreign key to the termlists_terms table.';


-- Index: fki_taxon_codes_code_type

-- DROP INDEX fki_taxon_codes_code_type;

CREATE INDEX fki_taxon_codes_code_type
  ON taxon_codes
  USING btree
  (code_type_id);

-- Index: fki_taxon_codes_taxon_meaning

-- DROP INDEX fki_taxon_codes_taxon_meaning;

CREATE INDEX fki_taxon_codes_taxon_meaning
  ON taxon_codes
  USING btree
  (taxon_meaning_id);

-- Index: pk_taxon_codes

-- DROP INDEX pk_taxon_codes;

CREATE INDEX pk_taxon_codes
  ON taxon_codes
  USING btree
  (id);

