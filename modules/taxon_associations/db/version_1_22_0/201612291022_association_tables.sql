CREATE TABLE taxon_associations
(
  id serial NOT NULL, -- Primary key for the table
  from_taxon_meaning_id integer NOT NULL, -- Taxon_meaning the association is from. Foreign key to the taxon_meanings table.
  to_taxon_meaning_id integer NULL, -- Taxon_meaning the association is to. Foreign key to the taxon_meanings table. Nullable to allow for the way that ORM posts the data.
  association_type_id integer NOT NULL, -- Identifies the phrase used when describing the association in a sentence <from_taxon_meaning> <association_type> <to_taxon_meaning>. For example preys upon, parasitises. Foreign key to the termlists_terms table.
  part_id integer, -- Part of the taxon_meaning identified by to_taxon_meaning_id that the interaction is with. Foreign key to the termlists_terms table.
  position_id integer, -- Position of the from taxon_meaning in relation to the to taxon_meaning. E.g. on, under. Foreign key to the termlists_terms table.
  impact_id integer, -- Identifies the impact this association has on the taxon_meaning identified by to_taxon_meaning_id. Foreign key to the termlists_terms table.
  comment text, -- Comments on the association.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_taxon_associations PRIMARY KEY (id),
  CONSTRAINT fk_taxon_meaning_association_from_taxon_meaning FOREIGN KEY (from_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_meaning_association_to_taxon_meaning FOREIGN KEY (to_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_meaning_association_type FOREIGN KEY (association_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_meaning_association_part FOREIGN KEY (part_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_meaning_association_position FOREIGN KEY (position_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_meaning_association_impact FOREIGN KEY (impact_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON COLUMN taxon_associations.id IS 'Primary key for the table.';
COMMENT ON COLUMN taxon_associations.from_taxon_meaning_id IS 'Taxon_meaning the association is from. Foreign key to the taxon_meanings table.';
COMMENT ON COLUMN taxon_associations.to_taxon_meaning_id IS 'Taxon_meaning the association is to. Foreign key to the taxon_meanings table. Nullable to allow for the way that ORM posts the data.';
COMMENT ON COLUMN taxon_associations.association_type_id IS 'Identifies the phrase used when describing the association in a sentence <from_taxon_meaning> <association_type> <to_taxon_meaning>. For example preys upon, parasitises. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN taxon_associations.part_id IS 'Part of the taxon_meaning identified by to_taxon_meaning_id that the interaction is with. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN taxon_associations.position_id IS 'Position of the from taxon_meaning in relation to the to taxon_meaning. E.g. on, under. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN taxon_associations.impact_id IS 'Identifies the impact this association is having on the taxon_meaning identified by to_taxon_meaning_id. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN taxon_associations.comment IS 'Comments pn the association.';
COMMENT ON COLUMN taxon_associations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_associations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_associations.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_associations.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxon_associations.deleted IS 'Has this record been deleted?';