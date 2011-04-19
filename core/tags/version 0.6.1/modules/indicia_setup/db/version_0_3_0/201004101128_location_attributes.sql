-- View: list_location_attribute_values

DROP VIEW IF EXISTS list_location_attribute_values;

CREATE OR REPLACE VIEW list_location_attribute_values AS 
 SELECT lav.id, l.id AS location_id, la.id AS location_attribute_id, 
        CASE la.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE la.data_type
        END AS data_type, la.caption, 
        CASE la.data_type
            WHEN 'T'::bpchar THEN lav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN lav.int_value::character varying::text
            WHEN 'B'::bpchar THEN lav.int_value::character varying::text
            WHEN 'F'::bpchar THEN lav.float_value::character varying::text
            WHEN 'D'::bpchar THEN lav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (lav.date_start_value::character varying::text || ' - '::text) || lav.date_end_value::character varying::text
            ELSE NULL::text
        END AS value, 
        CASE la.data_type
            WHEN 'T'::bpchar THEN lav.text_value
            WHEN 'L'::bpchar THEN lav.int_value::character varying::text
            WHEN 'I'::bpchar THEN lav.int_value::character varying::text
            WHEN 'B'::bpchar THEN lav.int_value::character varying::text
            WHEN 'F'::bpchar THEN lav.float_value::character varying::text
            WHEN 'D'::bpchar THEN lav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (lav.date_start_value::character varying::text || ' - '::text) || lav.date_end_value::character varying::text
            ELSE NULL::text
        END AS raw_value, la.termlist_id
   FROM locations l
   JOIN location_attribute_values lav ON lav.location_id = l.id AND lav.deleted = false
   JOIN location_attributes la ON la.id = lav.location_attribute_id AND la.deleted = false   
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id) ON tt.id = lav.int_value AND la.data_type = 'L'::bpchar
  WHERE l.deleted = false
  ORDER BY la.id;

