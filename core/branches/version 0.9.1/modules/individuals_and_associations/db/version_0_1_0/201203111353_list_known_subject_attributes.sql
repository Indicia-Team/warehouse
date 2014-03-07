-- View: list_known_subject_attributes

-- DROP VIEW list_known_subject_attributes;

CREATE OR REPLACE VIEW list_known_subject_attributes AS 
 SELECT pa.id, pa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, pa.data_type, ct.control AS control_type, pa.termlist_id, pa.multi_value, paw.website_id, (((pa.id || '|'::text) || pa.data_type::text) || '|'::text) || COALESCE(pa.termlist_id::text, ''::text) AS signature, paw.default_text_value, paw.default_int_value, paw.default_float_value, paw.default_date_start_value, paw.default_date_end_value, paw.default_date_type_value, COALESCE((paw.validation_rules::text || '
'::text) || pa.validation_rules::text, COALESCE(paw.validation_rules, ''::character varying)::text || COALESCE(pa.validation_rules, ''::character varying)::text) AS validation_rules, pa.deleted, paw.deleted AS website_deleted, pa.public
   FROM known_subject_attributes pa
   LEFT JOIN known_subject_attributes_websites paw ON pa.id = paw.known_subject_attribute_id AND paw.deleted = false
   LEFT JOIN control_types ct ON ct.id = paw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = paw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE pa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, paw.weight;
