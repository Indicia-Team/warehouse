ALTER TABLE form_structure_blocks DROP CONSTRAINT fk_form_structure_blocks_parent;

ALTER TABLE form_structure_blocks
  ADD CONSTRAINT fk_form_structure_blocks_parent FOREIGN KEY (parent_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL;

ALTER TABLE occurrence_attributes_websites DROP CONSTRAINT fk_occurrence_attributes_website_form_structure_block;

ALTER TABLE occurrence_attributes_websites
  ADD CONSTRAINT fk_occurrence_attributes_website_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL;

ALTER TABLE sample_attributes_websites DROP CONSTRAINT fk_sample_attributes_website_form_structure_block;

ALTER TABLE sample_attributes_websites
  ADD CONSTRAINT fk_sample_attributes_website_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL;

ALTER TABLE location_attributes_websites DROP CONSTRAINT fk_location_attributes_website_form_structure_block;

ALTER TABLE location_attributes_websites
  ADD CONSTRAINT fk_location_attributes_website_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL;