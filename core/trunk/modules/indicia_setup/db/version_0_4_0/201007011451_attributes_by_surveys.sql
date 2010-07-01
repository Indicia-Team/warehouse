DROP VIEW gv_sample_attribute_by_surveys;

CREATE OR REPLACE VIEW gv_sample_attribute_by_surveys	
 AS 
 SELECT fsb2.weight as weight1, fsb.weight as weight2, saw.weight as weight3, sa.id, fsb2.name as outer_structure_block, fsb.name as inner_structure_block, saw.website_id, saw.restrict_to_survey_id AS survey_id, w.title AS website, s.title AS survey, sa.caption, 
        CASE sa.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE sa.data_type
        END AS data_type, ct.control, sa.public, sa.created_by_id, sa.deleted
   FROM sample_attributes sa
   JOIN sample_attributes_websites saw ON sa.id = saw.sample_attribute_id AND saw.deleted = false
   JOIN websites w ON w.id = saw.website_id
   JOIN surveys s ON s.id = saw.restrict_to_survey_id
   LEFT JOIN control_types ct ON ct.id=saw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id=saw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id=fsb.parent_id   
   ORDER BY fsb2.weight, fsb.weight, saw.weight, sa."caption";