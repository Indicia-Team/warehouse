-- View: gv_survey_attributes

-- DROP VIEW gv_survey_attributes;

CREATE OR REPLACE VIEW gv_survey_attributes AS 
 SELECT a.id, aw.website_id, w.title AS website, a.caption, 
        CASE a.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE a.data_type
        END AS data_type, a.public, a.created_by_id, a.deleted
   FROM survey_attributes a
   LEFT JOIN survey_attributes_websites aw ON a.id = aw.survey_attribute_id AND aw.deleted = false
   LEFT JOIN websites w ON w.id = aw.website_id AND w.deleted = false
  WHERE a.deleted = false;

-- View: list_survey_attributes

-- DROP VIEW list_survey_attributes;

CREATE OR REPLACE VIEW list_survey_attributes AS 
 SELECT a.id, a.caption, fsb2.name AS outer_structure_block, fsb.name AS inner_structure_block, a.data_type, 
 ct.control AS control_type, a.termlist_id, a.multi_value, aw.website_id, 
 (((a.id || '|'::text) || a.data_type::text) || '|'::text) || COALESCE(a.termlist_id::text, ''::text) AS signature, 
 aw.default_text_value, aw.default_int_value, aw.default_float_value, aw.default_date_start_value, aw.default_date_end_value, 
 aw.default_date_type_value, COALESCE((aw.validation_rules::text || ''::text) || a.validation_rules::text, 
 COALESCE(aw.validation_rules, ''::character varying)::text || COALESCE(a.validation_rules, ''::character varying)::text) AS validation_rules, 
 a.deleted, aw.deleted AS website_deleted, a.public
   FROM survey_attributes a
   LEFT JOIN survey_attributes_websites aw ON a.id = aw.survey_attribute_id AND aw.deleted = false
   LEFT JOIN control_types ct ON ct.id = aw.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE a.deleted = false
  ORDER BY fsb2.weight, fsb.weight, aw.weight;

-- View: list_survey_attribute_values

-- DROP VIEW list_survey_attribute_values;

CREATE OR REPLACE VIEW list_survey_attribute_values AS 
 SELECT av.id, s.id AS survey_id, a.id AS survey_attribute_id, 
        CASE a.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE a.data_type
        END AS data_type, a.caption, 
        CASE a.data_type
            WHEN 'T'::bpchar THEN av.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN av.int_value::character varying::text
            WHEN 'B'::bpchar THEN av.int_value::character varying::text
            WHEN 'F'::bpchar THEN av.float_value::character varying::text
            WHEN 'D'::bpchar THEN av.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS value, 
        CASE a.data_type
            WHEN 'T'::bpchar THEN av.text_value
            WHEN 'L'::bpchar THEN av.int_value::character varying::text
            WHEN 'I'::bpchar THEN av.int_value::character varying::text
            WHEN 'B'::bpchar THEN av.int_value::character varying::text
            WHEN 'F'::bpchar THEN av.float_value::character varying::text
            WHEN 'D'::bpchar THEN av.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS raw_value, a.termlist_id, l.iso, s.website_id
   FROM surveys s
   JOIN survey_attribute_values av ON av.survey_id = s.id AND av.deleted = false
   JOIN survey_attributes a ON a.id = av.survey_attribute_id AND a.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id
   JOIN languages l ON l.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
  WHERE s.deleted = false
  ORDER BY a.id;
