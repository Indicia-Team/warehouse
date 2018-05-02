ALTER TABLE occurrence_attributes_websites
  ADD COLUMN restrict_to_taxon_meaning_id integer,
  ADD COLUMN restrict_to_stage_term_meaning_id integer,
  ADD CONSTRAINT fk_occurrence_attributes_websites_taxon_meaning FOREIGN KEY (restrict_to_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  ADD CONSTRAINT fk_occurrence_attributes_websites_stage_term_meaning FOREIGN KEY (restrict_to_stage_term_meaning_id)
      REFERENCES meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN occurrence_attributes_websites.restrict_to_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. For attributes that are only applicable to a given taxon, identifies the taxon.';
COMMENT ON COLUMN occurrence_attributes_websites.restrict_to_stage_term_meaning_id IS
  'Foreign key to the term_meanings table. For attributes that are only applicable to a given life stage, identifies the stage.';

ALTER TABLE sample_attributes_websites
  ADD COLUMN restrict_to_taxon_meaning_id integer,
  ADD COLUMN restrict_to_stage_term_meaning_id integer,
  ADD CONSTRAINT fk_sample_attributes_websites_taxon_meaning FOREIGN KEY (restrict_to_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  ADD CONSTRAINT fk_sample_attributes_websites_stage_term_meaning FOREIGN KEY (restrict_to_stage_term_meaning_id)
      REFERENCES meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN sample_attributes_websites.restrict_to_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. For attributes that are only applicable to a given taxon, identifies the taxon.';
COMMENT ON COLUMN sample_attributes_websites.restrict_to_stage_term_meaning_id IS
  'Foreign key to the term_meanings table. For attributes that are only applicable to a given life stage, identifies the stage.';