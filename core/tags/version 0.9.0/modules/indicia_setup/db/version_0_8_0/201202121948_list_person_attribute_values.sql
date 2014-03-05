-- View: list_person_attribute_values

-- DROP VIEW list_person_attribute_values;

CREATE OR REPLACE VIEW list_person_attribute_values AS 
 SELECT pav.id, p.id AS person_id, pa.id AS person_attribute_id, 
        CASE pa.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE pa.data_type
        END AS data_type, pa.caption, 
        CASE pa.data_type
            WHEN 'T'::bpchar THEN pav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN pav.int_value::character varying::text
            WHEN 'B'::bpchar THEN pav.int_value::character varying::text
            WHEN 'F'::bpchar THEN pav.float_value::character varying::text
            WHEN 'D'::bpchar THEN pav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (pav.date_start_value::character varying::text || ' - '::text) || pav.date_end_value::character varying::text
            ELSE NULL::text
        END AS value, 
        CASE pa.data_type
            WHEN 'T'::bpchar THEN pav.text_value
            WHEN 'L'::bpchar THEN pav.int_value::character varying::text
            WHEN 'I'::bpchar THEN pav.int_value::character varying::text
            WHEN 'B'::bpchar THEN pav.int_value::character varying::text
            WHEN 'F'::bpchar THEN pav.float_value::character varying::text
            WHEN 'D'::bpchar THEN pav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (pav.date_start_value::character varying::text || ' - '::text) || pav.date_end_value::character varying::text
            ELSE NULL::text
        END AS raw_value, pa.termlist_id, l.iso, paw.website_id
   FROM people p
   LEFT JOIN person_attribute_values pav ON pav.person_id=p.id AND pav.deleted=false
   LEFT JOIN (users u
     JOIN users_websites uw ON uw.user_id = u.id AND uw.site_role_id is not null 
     JOIN person_attributes_websites paw ON paw.website_id = uw.website_id AND paw.deleted = false
   ) ON u.person_id=p.id
   JOIN person_attributes pa ON (pa.id=COALESCE(pav.person_attribute_id, paw.person_attribute_id) 
       OR pa.public=true) AND (pa.id = pav.person_attribute_id OR pav.id IS NULL) AND pa.deleted=false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id
   JOIN languages l ON l.id = t.language_id) ON tt.meaning_id = pav.int_value AND pa.data_type = 'L'::bpchar
  WHERE p.deleted = false
  ORDER BY pa.id;
