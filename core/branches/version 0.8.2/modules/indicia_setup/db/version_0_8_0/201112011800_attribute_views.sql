
DROP VIEW IF EXISTS list_sample_attribute_values; 
CREATE OR REPLACE VIEW list_sample_attribute_values AS 
   SELECT sav.id, s.id AS sample_id, sa.id AS sample_attribute_id, 
        CASE sa.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
	    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE sa.data_type
        END AS data_type, sa.caption, 
        CASE sa.data_type
            WHEN 'T'::bpchar THEN sav.text_value
            WHEN 'L'::bpchar THEN t.term::text
            WHEN 'I'::bpchar THEN sav.int_value::character varying::text
	    WHEN 'B'::bpchar THEN sav.int_value::character varying::text
            WHEN 'F'::bpchar THEN sav.float_value::character varying::text
            WHEN 'D'::bpchar THEN sav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (sav.date_start_value::character varying::text || ' - '::text) || sav.date_end_value::character varying::text
            ELSE NULL::text
        END AS value,
        CASE sa.data_type
            WHEN 'T'::bpchar THEN sav.text_value
            WHEN 'L'::bpchar THEN sav.int_value::character varying::text
            WHEN 'I'::bpchar THEN sav.int_value::character varying::text
	    WHEN 'B'::bpchar THEN sav.int_value::character varying::text
            WHEN 'F'::bpchar THEN sav.float_value::character varying::text
            WHEN 'D'::bpchar THEN sav.date_start_value::character varying::text
            WHEN 'V'::bpchar THEN (sav.date_start_value::character varying::text || ' - '::text) || sav.date_end_value::character varying::text
            ELSE NULL::text
        END AS raw_value, sa.termlist_id, l.iso, saw.website_id
   FROM samples s
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
   JOIN sample_attributes_websites saw ON saw.website_id = su.website_id AND (saw.restrict_to_survey_id = su.id OR saw.restrict_to_survey_id IS NULL) AND saw.deleted = false
   JOIN sample_attributes sa ON sa.id = saw.sample_attribute_id AND sa.deleted = false
   LEFT JOIN sample_attribute_values sav ON sav.sample_attribute_id = sa.id AND sav.sample_id = s.id AND sav.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id
   JOIN languages l ON l.id = t.language_id) ON tt.id = sav.int_value AND sa.data_type = 'L'::bpchar
  WHERE s.deleted = false
  ORDER BY sa.id;
  
  
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
        END AS raw_value, oa.termlist_id, l.iso, oaw.website_id
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id AND s.deleted = false
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
   JOIN occurrence_attributes_websites oaw ON oaw.website_id = su.website_id AND (oaw.restrict_to_survey_id = su.id OR oaw.restrict_to_survey_id IS NULL) AND oaw.deleted = false
   JOIN occurrence_attributes oa ON oa.id = oaw.occurrence_attribute_id AND oa.deleted = false
   LEFT JOIN occurrence_attribute_values oav ON oav.occurrence_attribute_id = oa.id AND oav.occurrence_id = o.id AND oav.deleted = false
   LEFT JOIN (termlists_terms tt
    JOIN terms t ON t.id = tt.term_id
    JOIN languages l ON l.id = t.language_id) ON tt.id = oav.int_value AND oa.data_type = 'L'::bpchar
  WHERE o.deleted = false
  ORDER BY oa.id;

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
        END AS raw_value, la.termlist_id, lg.iso, lw.website_id
   FROM locations l
   JOIN locations_websites lw ON lw.location_id = l.id AND lw.deleted = false
   JOIN location_attribute_values lav ON lav.location_id = l.id AND lav.deleted = false
   JOIN location_attributes la ON la.id = lav.location_attribute_id AND la.deleted = false   
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id
   JOIN languages lg ON lg.id = t.language_id) ON tt.id = lav.int_value AND la.data_type = 'L'::bpchar
  WHERE l.deleted = false
  ORDER BY la.id;