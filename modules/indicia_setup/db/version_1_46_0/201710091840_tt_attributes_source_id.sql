ALTER TABLE termlists_term_attributes 
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN termlists_term_attributes.source_id IS
  'Points to a termlists_term which describes where the attribute originated.';

ALTER TABLE termlists_term_attribute_values
ADD COLUMN source_id integer,
ADD CONSTRAINT fk_source_id FOREIGN KEY (source_id) 
REFERENCES termlists_terms (id) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
COMMENT ON COLUMN taxa_taxon_list_attribute_values.source_id IS
  'Points to a termlists_term which describes where the attribute value originated.';