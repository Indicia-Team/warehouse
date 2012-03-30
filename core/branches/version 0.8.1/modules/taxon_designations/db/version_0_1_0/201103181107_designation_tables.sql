-- Table: taxon_designations

-- DROP TABLE taxon_designations;

CREATE TABLE taxon_designations
(
  id serial NOT NULL,
  title character varying(200) NOT NULL, -- Full name given for the taxon designation.
  code character varying(50), -- Identifier of the taxon designation.
  abbreviation character varying(50), -- Abbreviated name of the taxon designation.
  description character varying, -- Description of the taxon designation.
  category_id integer, -- Identifies the category of the designation. Foreign key to the termlists_terms table.
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL default false,
  CONSTRAINT pk_taxon_designations PRIMARY KEY (id),
  CONSTRAINT fk_taxon_designation_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_designation_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE taxon_designations IS 'A list of all known taxon designations.';
COMMENT ON COLUMN taxon_designations.title IS 'Full name given for the taxon designation.';
COMMENT ON COLUMN taxon_designations.code IS 'Identifier of the taxon designation.';
COMMENT ON COLUMN taxon_designations.abbreviation IS 'Abbreviated name of the taxon designation.';
COMMENT ON COLUMN taxon_designations.description IS 'Description of the taxon designation.';
COMMENT ON COLUMN taxon_designations.category_id IS 'Identifies the category of the designation. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN taxon_designations.deleted IS 'Identifies if the termlist has been marked as deleted.';




-- Table: taxa_taxon_designations

-- DROP TABLE taxa_taxon_designations;

CREATE TABLE taxa_taxon_designations
(
  id serial NOT NULL,
  taxon_id integer NOT NULL, -- Foreign key to the taxa table, identifies the taxon begin linked to the designation.
  taxon_designation_id integer NOT NULL, -- Foreign key to the taxon_designations table, identifies the designation begin linked to the taxon.
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  start_date date, -- Date the designation became applicable to the taxon.
  source character varying, -- Description of the source of this designation.
  geographical_constraint character varying, -- Description of the geographical constraints regarding this designation for the taxon.
  deleted boolean NOT NULL default false,
  CONSTRAINT pk_taxa_taxon_designations PRIMARY KEY (id),
  CONSTRAINT fk_taxa_taxon_designation_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxa_taxon_designation_taxa FOREIGN KEY (taxon_id)
      REFERENCES taxa (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxa_taxon_designation_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxa_taxon_designations_designations FOREIGN KEY (taxon_designation_id)
      REFERENCES taxon_designations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE taxa_taxon_designations IS 'Joins taxa to their designations.';
COMMENT ON COLUMN taxa_taxon_designations.taxon_id IS 'Foreign key to the taxa table, identifies the taxon begin linked to the designation.';
COMMENT ON COLUMN taxa_taxon_designations.taxon_designation_id IS 'Foreign key to the taxon_designations table, identifies the designation begin linked to the taxon.';
COMMENT ON COLUMN taxa_taxon_designations.start_date IS 'Date the designation became applicable to the taxon.';
COMMENT ON COLUMN taxa_taxon_designations.source IS 'Description of the source of this designation.';
COMMENT ON COLUMN taxa_taxon_designations.geographical_constraint IS 'Description of the geographical constraints regarding this designation for the taxon.';
COMMENT ON COLUMN taxa_taxon_designations.deleted IS 'Identifies if the termlist has been marked as deleted.';


-- Index: fki_taxa_taxon_designation_taxa

-- DROP INDEX fki_taxa_taxon_designation_taxa;

CREATE INDEX fki_taxa_taxon_designation_taxa
  ON taxa_taxon_designations
  USING btree
  (taxon_id);

-- Index: fki_taxa_taxon_designations_designations

-- DROP INDEX fki_taxa_taxon_designations_designations;

CREATE INDEX fki_taxa_taxon_designations_designations
  ON taxa_taxon_designations
  USING btree
  (taxon_designation_id);