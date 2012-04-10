
DROP VIEW IF EXISTS list_occurrence_attribute_values; 
CREATE OR REPLACE VIEW list_occurrence_attribute_values AS 
 SELECT oav.id, o.id AS occurrence_id, oa.id AS occurrence_attribute_id, 
        CASE oa.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
			WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE oa.data_type
        END AS data_type, oa.caption, 
        CASE oa.data_type
            WHEN 'T'::bpchar THEN oav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN oav.int_value::character varying::text
			WHEN 'B'::bpchar THEN oav.int_value::character varying::text
            WHEN 'F'::bpchar THEN oav.float_value::character varying::text
            WHEN 'D'::bpchar THEN oav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (oav.date_start_value::character varying::text || ' - '::text) || oav.date_end_value::character varying::text
            ELSE NULL::text
        END AS value,
        CASE oa.data_type
            WHEN 'T'::bpchar THEN oav.text_value
            WHEN 'L'::bpchar THEN oav.int_value::character varying::text
            WHEN 'I'::bpchar THEN oav.int_value::character varying::text
			WHEN 'B'::bpchar THEN oav.int_value::character varying::text
            WHEN 'F'::bpchar THEN oav.float_value::character varying::text
            WHEN 'D'::bpchar THEN oav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (oav.date_start_value::character varying::text || ' - '::text) || oav.date_end_value::character varying::text
            ELSE NULL::text
        END AS raw_value, oa.termlist_id, oaw.website_id
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id AND s.deleted = false
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
   JOIN occurrence_attributes_websites oaw ON oaw.website_id = su.website_id AND (oaw.restrict_to_survey_id = su.id OR oaw.id IS NULL) AND oaw.deleted = false
   JOIN occurrence_attributes oa ON oa.id = oaw.occurrence_attribute_id AND oa.deleted = false
   LEFT JOIN occurrence_attribute_values oav ON oav.occurrence_attribute_id = oa.id AND oav.occurrence_id = o.id AND oav.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id) ON tt.id = oav.int_value AND oa.data_type = 'L'::bpchar
  WHERE o.deleted = false
  ORDER BY oa.id;