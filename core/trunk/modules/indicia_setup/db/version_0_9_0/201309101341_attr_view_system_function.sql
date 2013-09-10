CREATE OR REPLACE VIEW list_sample_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, sa.termlist_id, sa.multi_value, saw.website_id, saw.restrict_to_survey_id, (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, saw.default_text_value, saw.default_int_value, saw.default_float_value, saw.default_date_start_value, saw.default_date_end_value, saw.default_date_type_value, COALESCE((saw.validation_rules::text || '
'::text) || sa.validation_rules::text, COALESCE(saw.validation_rules, ''::character varying)::text || COALESCE(sa.validation_rules, ''::character varying)::text) AS validation_rules, 
sa.deleted, saw.deleted AS website_deleted, saw.restrict_to_sample_method_id, sa.system_function
   FROM sample_attributes sa
   LEFT JOIN sample_attributes_websites saw ON sa.id = saw.sample_attribute_id AND saw.deleted = false
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE sa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, saw.weight;

CREATE OR REPLACE VIEW list_occurrence_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, sa.termlist_id, sa.multi_value, saw.website_id, saw.restrict_to_survey_id, (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, saw.default_text_value, saw.default_int_value, saw.default_float_value, saw.default_date_start_value, saw.default_date_end_value, saw.default_date_type_value, COALESCE((saw.validation_rules::text || '
'::text) || sa.validation_rules::text, COALESCE(saw.validation_rules, ''::character varying)::text || COALESCE(sa.validation_rules, ''::character varying)::text) AS validation_rules, 
sa.deleted, saw.deleted AS website_deleted, sa.system_function
   FROM occurrence_attributes sa
   LEFT JOIN occurrence_attributes_websites saw ON sa.id = saw.occurrence_attribute_id AND saw.deleted = false
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE sa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, saw.weight;

CREATE OR REPLACE VIEW list_location_attributes AS 
 SELECT la.id, la.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, la.data_type, ct.control AS control_type, la.termlist_id, la.multi_value, law.website_id, law.restrict_to_survey_id, (((la.id || '|'::text) || la.data_type::text) || '|'::text) || COALESCE(la.termlist_id::text, ''::text) AS signature, law.default_text_value, law.default_int_value, law.default_float_value, law.default_date_start_value, law.default_date_end_value, law.default_date_type_value, COALESCE((law.validation_rules::text || '
'::text) || la.validation_rules::text, COALESCE(law.validation_rules, ''::character varying)::text || COALESCE(la.validation_rules, ''::character varying)::text) AS validation_rules, 
la.deleted, law.deleted AS website_deleted, law.restrict_to_location_type_id, la.system_function
   FROM location_attributes la
   LEFT JOIN location_attributes_websites law ON la.id = law.location_attribute_id AND law.deleted = false
   LEFT JOIN control_types ct ON ct.id = law.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = law.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE la.deleted = false
  ORDER BY fsb2.weight, fsb.weight, law.weight;