CREATE TABLE occurrence_associations
(
  id serial NOT NULL, -- Primary key for the table
  from_occurrence_id integer NOT NULL, -- Occurrence the association is from. Foreign key to the occurrences table.
  to_occurrence_id integer NULL, -- Occurrence the association is to. Foreign key to the occurrences table. Nullable to allow for the way that ORM posts the data.
  association_type_id integer NOT NULL, -- Identifies the phrase used when describing the association in a sentence <from_occurrence> <association_type> <to_occurrence>. For example preys upon, parasitises. Foreign key to the termlists_terms table.
  part_id integer, -- Part of the occurrence identified by to_occurrence_id that the interaction is with. Foreign key to the termlists_terms table.
  condition_id integer, -- Describes the condition of the occurrence identified by to_occurrence_id as caused by this interaction. Foreign key to the termlists_terms table.
  position_id integer, -- Position of the from occurrence in relation to the to occurrence. E.g. on, under. Foreign key to the termlists_terms table.
  impact_id integer, -- Identifies the impact this association is having on the occurrence identified by to_occurrence_id. Foreign key to the termlists_terms table.
  comment text, -- Comments provided by the recorder of the association.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_association_types PRIMARY KEY (id),
  CONSTRAINT fk_occurrence_association_from_occurrence FOREIGN KEY (from_occurrence_id)
      REFERENCES occurrences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_association_to_occurrence FOREIGN KEY (to_occurrence_id)
      REFERENCES occurrences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_association_type FOREIGN KEY (association_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_association_part FOREIGN KEY (part_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_association_condition FOREIGN KEY (condition_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_association_position FOREIGN KEY (position_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_association_impact FOREIGN KEY (impact_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON COLUMN occurrence_associations.id IS 'Primary key for the table.';
COMMENT ON COLUMN occurrence_associations.from_occurrence_id IS 'Occurrence the association is from. Foreign key to the occurrences table.';
COMMENT ON COLUMN occurrence_associations.to_occurrence_id IS 'Occurrence the association is to. Foreign key to the occurrences table. Nullable to allow for the way that ORM posts the data.';
COMMENT ON COLUMN occurrence_associations.association_type_id IS 'Identifies the phrase used when describing the association in a sentence <from_occurrence> <association_type> <to_occurrence>. For example preys upon, parasitises. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN occurrence_associations.part_id IS 'Part of the occurrence identified by to_occurrence_id that the interaction is with. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN occurrence_associations.condition_id IS 'Describes the condition of the occurrence identified by to_occurrence_id as caused by this interaction. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN occurrence_associations.position_id IS 'Position of the from occurrence in relation to the to occurrence. E.g. on, under. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN occurrence_associations.impact_id IS 'Identifies the impact this association is having on the occurrence identified by to_occurrence_id. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN occurrence_associations.comment IS 'Comments provided by the recorder of the association.';
COMMENT ON COLUMN occurrence_associations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_associations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_associations.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_associations.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN occurrence_associations.deleted IS 'Has this record been deleted?';