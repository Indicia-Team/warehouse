ALTER TABLE location_attributes_websites
   ADD COLUMN restrict_to_location_type_id integer;

ALTER TABLE location_attributes_websites
  ADD CONSTRAINT fk_location_attributes_websites_location_type FOREIGN KEY (restrict_to_location_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN location_attributes_websites.restrict_to_location_type_id IS 'If this attribute is only used for a specific location type within the context of the website & survey combination, then specifies the termlist entry for the appropriate location type.';

ALTER TABLE sample_attributes_websites
   ADD COLUMN restrict_to_sample_method_id integer;

ALTER TABLE sample_attributes_websites
  ADD CONSTRAINT fk_sample_attributes_websites_sample_method FOREIGN KEY (restrict_to_sample_method_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN sample_attributes_websites.restrict_to_sample_method_id IS 'If this attribute is only used for a specific sample method within the context of the website & survey combination, then specifies the termlist entry for the appropriate sample method.';
