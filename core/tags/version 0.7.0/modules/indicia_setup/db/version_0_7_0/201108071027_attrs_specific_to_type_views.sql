DROP VIEW list_location_attributes;

CREATE OR REPLACE VIEW list_location_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, sa.termlist_id, sa.multi_value, 
     saw.website_id, saw.restrict_to_survey_id, (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, 
     sa.deleted, saw.deleted AS website_deleted, saw.restrict_to_location_type_id
   FROM location_attributes sa
   LEFT JOIN location_attributes_websites saw ON sa.id = saw.location_attribute_id
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  ORDER BY fsb2.weight, fsb.weight, saw.weight;

DROP VIEW list_sample_attributes;

CREATE OR REPLACE VIEW list_sample_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, sa.termlist_id, sa.multi_value, 
     saw.website_id, saw.restrict_to_survey_id, (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, 
     saw.default_text_value, saw.default_int_value, saw.default_float_value, saw.default_date_start_value, saw.default_date_end_value, saw.default_date_type_value, 
     COALESCE((saw.validation_rules::text || '
'::text) || sa.validation_rules::text, COALESCE(saw.validation_rules, ''::character varying)::text || COALESCE(sa.validation_rules, ''::character varying)::text) AS validation_rules, 
    sa.deleted, saw.deleted AS website_deleted, saw.restrict_to_sample_method_id
   FROM sample_attributes sa
   LEFT JOIN sample_attributes_websites saw ON sa.id = saw.sample_attribute_id AND saw.deleted = false
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE sa.deleted = false
  ORDER BY fsb2.weight, fsb.weight, saw.weight;

