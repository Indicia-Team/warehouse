CREATE TABLE dna_occurrences
(
  id serial NOT NULL,
  occurrence_id integer NOT NULL,
  associated_sequences text[],
  dna_sequence text NOT NULL,
  target_gene text NOT NULL,
  pcr_primer_reference text NOT NULL,
  env_medium text,
  env_broad_scale text,
  otu_db text,
  otu_seq_comp_appr text,
  otu_class_appr text,
  env_local_scale text,
  target_subfragment text,
  pcr_primer_name_forward text,
  pcr_primer_forward text,
  pcr_primer_name_reverse text,
  pcr_primer_reverse text,
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_dna_occurrences PRIMARY KEY (id),
  CONSTRAINT fk_dna_occurrences_occurrence FOREIGN KEY (occurrence_id)
      REFERENCES occurrences (id) MATCH SIMPLE
      ON UPDATE NO ACTION
      ON DELETE NO ACTION,
  CONSTRAINT fk_dna_occurrences_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_dna_occurrences_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

CREATE UNIQUE INDEX IF NOT EXISTS ix_unique_dna_occurrences_occurrence_id
    ON dna_occurrences USING btree
    (occurrence_id ASC NULLS LAST)
    WHERE deleted=false;

COMMENT ON TABLE dna_occurrences IS 'Additional metadata stored for DNA derived occurrences.';
COMMENT ON COLUMN dna_occurrences.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN dna_occurrences.occurrence_id IS 'ID of the occurrence this DNA metadata is associated with.';
COMMENT ON COLUMN dna_occurrences.associated_sequences IS 'A list (concatenated and separated) of identifiers (publication, global unique identifier, URI) of genetic sequence information associated with the record.';
COMMENT ON COLUMN dna_occurrences.dna_sequence IS 'The DNA sequence.';
COMMENT ON COLUMN dna_occurrences.target_gene IS 'Targeted gene or marker name for marker-based studies.';
COMMENT ON COLUMN dna_occurrences.pcr_primer_reference IS 'Reference for the primers.';
COMMENT ON COLUMN dna_occurrences.env_medium IS 'The environmental medium which surrounded your sample or specimen prior to sampling. Should be a subclass of an ENVO material.';
COMMENT ON COLUMN dna_occurrences.env_broad_scale IS 'The broad-scale environment the sample or specimen came from. Subclass of ENVO''s biome class.';
COMMENT ON COLUMN dna_occurrences.otu_db IS 'The OTU database (i.e. sequences not generated as part of the current study) used to assigning taxonomy to OTUs or ASVs.';
COMMENT ON COLUMN dna_occurrences.otu_seq_comp_appr IS 'The OTU sequence comparison approach, such as tools and thresholds used to assign “species-level” names to OTUs or ASVs.';
COMMENT ON COLUMN dna_occurrences.otu_class_appr IS 'The OTU classification approach / algorithm and clustering level (if relevant) when defining OTUs or ASVs.';
COMMENT ON COLUMN dna_occurrences.env_local_scale IS 'The local environmental context the sample or specimen came from. Please use terms that are present in ENVO and which are of smaller spatial grain than your entry for env_broad_scale.';
COMMENT ON COLUMN dna_occurrences.target_subfragment IS 'Name of subfragment of a gene or marker.';
COMMENT ON COLUMN dna_occurrences.pcr_primer_name_forward IS 'Name of the forward PCR primer that were used to amplify the sequence of the targeted gene, locus or subfragment.';
COMMENT ON COLUMN dna_occurrences.pcr_primer_forward IS 'Forward PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.';
COMMENT ON COLUMN dna_occurrences.pcr_primer_name_reverse IS 'Name of the reverse PCR primer that were used to amplify the sequence of the targeted gene, locus or subfragment.';
COMMENT ON COLUMN dna_occurrences.pcr_primer_reverse IS 'Reverse PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.';
COMMENT ON COLUMN dna_occurrences.created_on IS 'Date this record was created.';
COMMENT ON COLUMN dna_occurrences.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN dna_occurrences.updated_on IS 'Date this record was updated.';
COMMENT ON COLUMN dna_occurrences.updated_by_id IS 'Foreign key to the users table (updater).';
COMMENT ON COLUMN dna_occurrences.deleted IS 'Has this record been deleted?';


