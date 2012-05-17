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


-- Table: cache_taxon_searchterms

-- DROP TABLE cache_taxon_searchterms;

CREATE TABLE cache_taxon_searchterms
(
  id serial NOT NULL, -- Unique identifier and primary key for the table.
  taxa_taxon_list_id integer NOT NULL, -- Identifies the taxon list entry which this searchable term applies to.
  searchterm character varying NOT NULL, -- Searchable identifier for the taxon. Includes taxon formal and vernacular names, simplified versions of these for searching and codes, abbreviations or other shortcuts used to lookup taxa.
  original character varying NOT NULL, -- Contains the unsimplified version of the search term. Same as searchterm if not a simplified entry.
  taxon_group character varying NOT NULL, -- Name of the taxon group.
  taxon_meaning_id integer NOT NULL, -- Identifies the taxon meaning associated with this name. All names with the same ID refer to the same taxon.
  preferred_taxon character varying NOT NULL, -- Provides the preferred taxon name for a taxon that has been looked up,
  default_common_name character varying, -- Provides the preferred common name for a taxon that has been looked up,
  preferred_authority character varying,
  language_iso character varying,
  name_type character(1) NOT NULL, -- Type of taxon name string. Options are (L)atin, (S)ynonym, (V)ernacular, (O)ther vernacular name, (C)ode.
  simplified boolean DEFAULT false, -- Is this a name which has been simplified make it tolerant of some spelling and punctuation errors when searching.
  code_type_id integer, -- For names which are codes, identifies the type of code. Foreign key to the termlists_terms table.
  source_id integer, -- Depending on what type of search term this is, may be used to identify the PK of the record which supplied the term.
  CONSTRAINT pk_cache_taxon_searchterms PRIMARY KEY (id),
  CONSTRAINT fk_taxon_searchterms_code_type_id FOREIGN KEY (code_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_searchterms_taxa_taxon_list FOREIGN KEY (taxa_taxon_list_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_searchterms_taxon_meaning_id FOREIGN KEY (taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT chk_taxon_searchterms_name_type CHECK (name_type = ANY (ARRAY['L'::bpchar, 'S'::bpchar, 'V'::bpchar, 'O'::bpchar, 'C'::bpchar]))
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE cache_taxon_searchterms IS 'Provides a table with content optimised for searching for taxon name strings. There can be several searchable terms or other codes per taxon item.';
COMMENT ON COLUMN cache_taxon_searchterms.id IS 'Unique identifier and primary key for the table.';
COMMENT ON COLUMN cache_taxon_searchterms.taxa_taxon_list_id IS 'Identifies the taxon list entry which this searchable name applies to.';
COMMENT ON COLUMN cache_taxon_searchterms.searchterm IS 'Searchable identifier for the taxon. Includes taxon formal and vernacular names, simplified versions of these for searching and codes, abbreviations or other shortcuts used to lookup taxa.';
COMMENT ON COLUMN cache_taxon_searchterms.original IS 'Contains the unsimplified version of the search term. Same as searchterm if not a simplified entry.';
COMMENT ON COLUMN cache_taxon_searchterms.taxon_group IS 'Name of the taxon group.';
COMMENT ON COLUMN cache_taxon_searchterms.taxon_meaning_id IS 'Identifies the taxon meaning associated with this name. All names with the same ID refer to the same taxon.';
COMMENT ON COLUMN cache_taxon_searchterms.preferred_taxon IS 'Provides the preferred taxon name for a taxon that has been looked up,';
COMMENT ON COLUMN cache_taxon_searchterms.default_common_name IS 'Provides the preferred common name for a taxon that has been looked up,';
COMMENT ON COLUMN cache_taxon_searchterms.name_type IS 'Type of taxon name string. Options are (L)atin, (S)ynonym, (V)ernacular, (O)ther vernacular name, (C)ode.';
COMMENT ON COLUMN cache_taxon_searchterms.simplified IS 'Is this a name which has been simplified make it tolerant of some spelling and punctuation errors when searching.';
COMMENT ON COLUMN cache_taxon_searchterms.code_type_id IS 'For names which are codes, identifies the type of code. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN cache_taxon_searchterms.source_id IS 'Depending on what type of search term this is, may be used to identify the PK of the record which supplied the term';


-- Index: fki_taxon_searchterms_code_type_id

-- DROP INDEX fki_taxon_searchterms_code_type_id;

CREATE INDEX fki_taxon_searchterms_code_type_id
  ON cache_taxon_searchterms
  USING btree
  (code_type_id);

-- Index: fki_taxon_searchterms_taxa_taxon_list

-- DROP INDEX fki_taxon_searchterms_taxa_taxon_list;

CREATE INDEX fki_taxon_searchterms_taxa_taxon_list
  ON cache_taxon_searchterms
  USING btree
  (taxa_taxon_list_id);

-- Index: fki_taxon_searchterms_taxon_meaning_id

-- DROP INDEX fki_taxon_searchterms_taxon_meaning_id;

CREATE INDEX fki_taxon_searchterms_taxon_meaning_id
  ON cache_taxon_searchterms
  USING btree
  (taxon_meaning_id);

-- Index: ix_cache_taxon_searchterms_searchterm

-- DROP INDEX ix_cache_taxon_searchterms_searchterm;

CREATE INDEX ix_cache_taxon_searchterms_searchterm
  ON cache_taxon_searchterms
  USING btree
  (searchterm);

