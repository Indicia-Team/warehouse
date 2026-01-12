ALTER TABLE survey_attributes
  ADD COLUMN reporting_category_id integer,
  ADD CONSTRAINT fk_survey_attributes_attributes_termlists_terms_rep_cat FOREIGN KEY (reporting_category_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE sample_attributes
  ADD COLUMN reporting_category_id integer,
  ADD CONSTRAINT fk_sample_attributes_attributes_termlists_terms_rep_cat FOREIGN KEY (reporting_category_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE occurrence_attributes
  ADD COLUMN reporting_category_id integer,
  ADD CONSTRAINT fk_occurrence_attributes_attributes_termlists_terms_rep_cat FOREIGN KEY (reporting_category_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE location_attributes
  ADD COLUMN reporting_category_id integer,
  ADD CONSTRAINT fk_location_attributes_attributes_termlists_terms_rep_cat FOREIGN KEY (reporting_category_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE taxa_taxon_list_attributes
  ADD COLUMN reporting_category_id integer,
  ADD CONSTRAINT fk_taxa_taxon_list_attributes_attributes_termlists_terms_rep_cat FOREIGN KEY (reporting_category_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE termlists_term_attributes
  ADD COLUMN reporting_category_id integer,
  ADD CONSTRAINT fk_termlists_term_attributes_attributes_termlists_terms_rep_cat FOREIGN KEY (reporting_category_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
ALTER TABLE person_attributes
  ADD COLUMN reporting_category_id integer,
  ADD CONSTRAINT fk_person_attributes_attributes_termlists_terms_rep_cat FOREIGN KEY (reporting_category_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN survey_attributes.reporting_category_id IS
  'Foreign key to the termlists_terms table. Identifies an optional reporting category for this attribute which can be '
  'used to organise the display of multiple attribute values in report outputs.';
COMMENT ON COLUMN sample_attributes.reporting_category_id IS
  'Foreign key to the termlists_terms table. Identifies an optional reporting category for this attribute which can be '
  'used to organise the display of multiple attribute values in report outputs.';
COMMENT ON COLUMN occurrence_attributes.reporting_category_id IS
  'Foreign key to the termlists_terms table. Identifies an optional reporting category for this attribute which can be '
  'used to organise the display of multiple attribute values in report outputs.';
COMMENT ON COLUMN location_attributes.reporting_category_id IS
  'Foreign key to the termlists_terms table. Identifies an optional reporting category for this attribute which can be used to organise the display of multiple attribute values in report outputs.';
COMMENT ON COLUMN taxa_taxon_list_attributes.reporting_category_id IS
  'Foreign key to the termlists_terms table. Identifies an optional reporting category for this attribute which can be used to organise the display of multiple attribute values in report outputs.';
COMMENT ON COLUMN termlists_term_attributes.reporting_category_id IS
  'Foreign key to the termlists_terms table. Identifies an optional reporting category for this attribute which can be used to organise the display of multiple attribute values in report outputs.';
COMMENT ON COLUMN person_attributes.reporting_category_id IS
  'Foreign key to the termlists_terms table. Identifies an optional reporting category for this attribute which can be used to organise the display of multiple attribute values in report outputs.';

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Survey attribute reporting categories', 'List of categories which survey attributes can be organised into for reporting purposes.',
    now(), 1, now(), 1, 'indicia:attr_reporting_category_survey');
INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Sample attribute reporting categories', 'List of categories which sample attributes can be organised into for reporting purposes.',
    now(), 1, now(), 1, 'indicia:attr_reporting_category_sample');
INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Occurrence attribute reporting categories', 'List of categories which occurrence attributes can be organised into for reporting purposes.',
    now(), 1, now(), 1, 'indicia:attr_reporting_category_occurrence');
INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Location attribute reporting categories', 'List of categories which location attributes can be organised into for reporting purposes.',
    now(), 1, now(), 1, 'indicia:attr_reporting_category_location');
INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Taxon attribute reporting categories', 'List of categories which taxon attributes can be organised into for reporting purposes.',
    now(), 1, now(), 1, 'indicia:attr_reporting_category_taxa_taxon_list');
INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Term attribute reporting categories', 'List of categories which term attributes can be organised into for reporting purposes.',
    now(), 1, now(), 1, 'indicia:attr_reporting_category_termlists_term');
INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Person attribute reporting categories', 'List of categories which person attributes can be organised into for reporting purposes.',
    now(), 1, now(), 1, 'indicia:attr_reporting_category_person');