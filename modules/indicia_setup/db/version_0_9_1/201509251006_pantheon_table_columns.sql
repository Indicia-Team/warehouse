ALTER TABLE taxa_taxon_list_attributes 
ADD COLUMN description text,
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN taxa_taxon_list_attributes.description IS
  'Holds a description for the attribute.';
COMMENT ON COLUMN taxa_taxon_list_attributes.source_id IS
  'Points to a termlists_term which describes where the attribute originated.';

ALTER TABLE survey_attributes
ADD COLUMN description text,
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN survey_attributes.description IS
  'Holds a description for the attribute.';
COMMENT ON COLUMN survey_attributes.source_id IS
  'Points to a termlists_term which describes where the attribute originated.';

ALTER TABLE sample_attributes
ADD COLUMN description text,
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN sample_attributes.description IS
  'Holds a description for the attribute.';
COMMENT ON COLUMN sample_attributes.source_id IS
  'Points to a termlists_term which describes where the attribute originated.';

ALTER TABLE person_attributes
ADD COLUMN description text,
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN person_attributes.description IS
  'Holds a description for the attribute.';
COMMENT ON COLUMN person_attributes.source_id IS
  'Points to a termlists_term which describes where the attribute originated.';

ALTER TABLE occurrence_attributes
ADD COLUMN description text,
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN occurrence_attributes.description IS
  'Holds a description for the attribute.';
COMMENT ON COLUMN occurrence_attributes.source_id IS
  'Points to a termlists_term which describes where the attribute originated.';

ALTER TABLE location_attributes
ADD COLUMN description text,
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN location_attributes.description IS
  'Holds a description for the attribute.';
COMMENT ON COLUMN location_attributes.source_id IS
  'Points to a termlists_term which describes where the attribute originated.';

ALTER TABLE termlists_terms 
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id)
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN termlists_terms.source_id IS
  'Points to a termlists_term which describes where the term originated.';


ALTER TABLE taxa_taxon_list_attribute_values
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN taxa_taxon_list_attribute_values.source_id IS
  'Points to a termlists_term which describes where the attribute value originated.';

ALTER TABLE survey_attribute_values
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN survey_attribute_values.source_id IS
  'Points to a termlists_term which describes where the attribute value originated.';

ALTER TABLE sample_attribute_values
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN sample_attribute_values.source_id IS
  'Points to a termlists_term which describes where the attribute value originated.';

ALTER TABLE person_attribute_values
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN person_attribute_values.source_id IS
  'Points to a termlists_term which describes where the attribute value originated.';

ALTER TABLE occurrence_attribute_values
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN occurrence_attribute_values.source_id IS
  'Points to a termlists_term which describes where the attribute value originated.';

ALTER TABLE location_attribute_values
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN location_attribute_values.source_id IS
  'Points to a termlists_term which describes where the attribute value originated.';