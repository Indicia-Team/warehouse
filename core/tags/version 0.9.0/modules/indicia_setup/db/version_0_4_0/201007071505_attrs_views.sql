DROP VIEW list_sample_attributes;

CREATE OR REPLACE VIEW list_sample_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, 
    sa.termlist_id, sa.multi_value, saw.website_id, saw.restrict_to_survey_id,
    (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, 
    sa.deleted, saw.deleted AS website_deleted
   FROM sample_attributes sa
   LEFT JOIN sample_attributes_websites saw ON sa.id = saw.sample_attribute_id
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  ORDER BY fsb2.weight, fsb.weight, saw.weight;
  
DROP VIEW list_occurrence_attributes;

CREATE OR REPLACE VIEW list_occurrence_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, 
    sa.termlist_id, sa.multi_value, saw.website_id, saw.restrict_to_survey_id,
    (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, 
    sa.deleted, saw.deleted AS website_deleted
   FROM occurrence_attributes sa
   LEFT JOIN occurrence_attributes_websites saw ON sa.id = saw.occurrence_attribute_id
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  ORDER BY fsb2.weight, fsb.weight, saw.weight;

  
DROP VIEW list_location_attributes;

CREATE OR REPLACE VIEW list_location_attributes AS 
 SELECT sa.id, sa.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, sa.data_type, ct.control AS control_type, 
    sa.termlist_id, sa.multi_value, saw.website_id, saw.restrict_to_survey_id,
    (((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature, 
    sa.deleted, saw.deleted AS website_deleted
   FROM location_attributes sa
   LEFT JOIN location_attributes_websites saw ON sa.id = saw.location_attribute_id
   LEFT JOIN control_types ct ON ct.id = saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  ORDER BY fsb2.weight, fsb.weight, saw.weight;

