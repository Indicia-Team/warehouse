CREATE OR REPLACE VIEW list_termlists_term_attribute_values AS
 SELECT av.id, tlt.id AS termlists_term_id, a.id AS termlists_term_attribute_id,
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