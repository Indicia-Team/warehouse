-- View: gv_termlists_term_attributes

-- DROP VIEW gv_termlists_term_attributes;

CREATE OR REPLACE VIEW gv_termlists_term_attributes AS
 SELECT a.id, tta.termlist_id, tl.title AS termlist, a.caption,
        CASE a.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE a.data_type
        END AS data_type, a.public, a.created_by_id, a.deleted, tl.website_id
   FROM termlists_term_attributes a
   LEFT JOIN termlists_termlists_term_attributes tta ON tta.termlists_term_attribute_id = a.id AND tta.deleted = false
   LEFT JOIN termlists tl ON tl.id = tta.termlist_id AND tl.deleted = false
  WHERE a.deleted = false;

-- View: list_termlists_term_attributes

-- DROP VIEW list_termlists_term_attributes;

CREATE OR REPLACE VIEW list_termlists_term_attributes AS
 SELECT a.id, a.caption,
  fsb2.name AS outer_structure_block,
  fsb.name AS inner_structure_block,
  a.data_type,
  ct.control AS control_type,
  a.termlist_id as lookup_termlist_id,
  a.multi_value,
  tta.termlist_id,
  (((a.id || '|'::text) || a.data_type::text) || '|'::text) || COALESCE(a.termlist_id::text, ''::text) AS signature,
  tta.default_text_value,
  tta.default_int_value,
  tta.default_float_value,
  tta.default_date_start_value,
  tta.default_date_end_value,
  tta.default_date_type_value,
  COALESCE((tta.validation_rules::text || ''::text) || a.validation_rules::text,
  COALESCE(tta.validation_rules, ''::character varying)::text || COALESCE(a.validation_rules,
  ''::character varying)::text) AS validation_rules,
  a.deleted,
  tta.deleted AS termlist_deleted,
  a.public,
  tl.website_id
   FROM termlists_term_attributes a
   LEFT JOIN termlists_termlists_term_attributes tta ON tta.termlists_term_attribute_id = a.id AND tta.deleted = false
   LEFT JOIN termlists tl ON tl.id = tta.termlist_id AND tl.deleted = false
   LEFT JOIN control_types ct ON ct.id = tta.control_type_id
   LEFT JOIN form_structure_blocks fsb ON fsb.id = tta.form_structure_block_id
   LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  WHERE a.deleted = false
  ORDER BY fsb2.weight, fsb.weight, tta.weight;

-- View: list_termlists_term_attribute_values

-- DROP VIEW list_termlists_term_attribute_values;

CREATE OR REPLACE VIEW list_termlists_term_attribute_values AS
 SELECT av.id, tlt.id AS termlists_term_id, a.id AS survey_attribute_id,
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
        END AS raw_value, a.termlist_id as lookup_termlist_id, l.iso, tl.website_id
   FROM termlists_terms tlt
   JOIN termlists_term_attribute_values av ON av.termlists_term_id = tlt.id AND av.deleted = false
   JOIN termlists_term_attributes a ON a.id = av.termlists_term_attribute_id AND a.deleted = false
   JOIN termlists tl on tl.id=tlt.termlist_id AND tl.deleted=false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id
   JOIN languages l ON l.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
  WHERE tlt.deleted = false
  ORDER BY a.id;
