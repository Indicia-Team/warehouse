CREATE INDEX fki_occattr_taxon_restrictions_occattr_websites
  ON occurrence_attribute_taxon_restrictions
  USING btree
  (occurrence_attributes_website_id);

CREATE INDEX fki_occattr_taxon_restrictions_taxon_meanings
  ON occurrence_attribute_taxon_restrictions
  USING btree
  (restrict_to_taxon_meaning_id);

CREATE INDEX fki_occattr_taxon_restrictions_meanings
  ON occurrence_attribute_taxon_restrictions
  USING btree
  (restrict_to_stage_term_meaning_id);

CREATE INDEX fki_smpattr_taxon_restrictions_smpttr_websites
  ON sample_attribute_taxon_restrictions
  USING btree
  (sample_attributes_website_id);

CREATE INDEX fki_smpattr_taxon_restrictions_taxon_meanings
  ON sample_attribute_taxon_restrictions
  USING btree
  (restrict_to_taxon_meaning_id);

CREATE INDEX fki_smpattr_taxon_restrictions_meanings
  ON sample_attribute_taxon_restrictions
  USING btree
  (restrict_to_stage_term_meaning_id);

CREATE INDEX fki_taxattr_taxon_restrictions_taxon_lists_taxa_taxon_list_attributes
  ON taxa_taxon_list_attribute_taxon_restrictions
  USING btree
  (taxon_lists_taxa_taxon_list_attribute_id);

CREATE INDEX fki_taxattr_taxon_restrictions_taxon_meanings
  ON taxa_taxon_list_attribute_taxon_restrictions
  USING btree
  (restrict_to_taxon_meaning_id);

CREATE INDEX fki_taxattr_taxon_restrictions_meanings
  ON taxa_taxon_list_attribute_taxon_restrictions
  USING btree
  (restrict_to_stage_term_meaning_id);