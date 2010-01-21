
DROP VIEW IF EXISTS detail_occurrences;
CREATE OR REPLACE VIEW detail_occurrences AS
 SELECT o.id, o.confidential, o.comment, o.taxa_taxon_list_id, o.determiner_id, t.taxon, s.entered_sref, s.entered_sref_system,
 	s.geom, s.location_name, s.survey_id, s.date_start, s.date_end, s.date_type,
 	s.location_id, l.name AS location, l.code AS location_code, s.recorder_names, o.sample_id,
 	(d.first_name::text || ' '::text) || d.surname::text AS determiner, o.website_id, o.created_by_id, c.username AS created_by, o.created_on, o.updated_by_id, u.username AS updated_by, o.updated_on
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id
   LEFT JOIN people d ON d.id = o.determiner_id
   LEFT JOIN locations l ON l.id = s.location_id
   JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   LEFT JOIN surveys su ON s.survey_id = su.id
   JOIN users c ON c.id = o.created_by_id
   JOIN users u ON u.id = o.updated_by_id;

DROP VIEW IF EXISTS detail_samples;
CREATE VIEW detail_samples AS
SELECT s.id, s.entered_sref, s.entered_sref_system, s.geom,
    s.location_name, s.date_start, s.date_end, s.date_type, s.location_id,
    l.name AS location, l.code AS location_code, s.created_by_id,
    c.username AS created_by, s.created_on, s.updated_by_id, u.username AS
    updated_by, s.updated_on, su.website_id, s.parent_id
FROM ((((samples s LEFT JOIN locations l ON ((l.id = s.location_id))) LEFT
    JOIN surveys su ON ((s.survey_id = su.id))) JOIN users c ON ((c.id =
    s.created_by_id))) JOIN users u ON ((u.id = s.updated_by_id))); 
   
DROP VIEW IF EXISTS list_sample_attributes;
CREATE OR REPLACE VIEW list_sample_attributes AS 
 SELECT sa.id,
 		sa.caption,
		sa.data_type,
		sa.termlist_id,
		sa.multi_value,
		saw.website_id,
		saw.restrict_to_survey_id,
		(((sa.id || '|'::text) || sa.data_type::text) || '|'::text) || COALESCE(sa.termlist_id::text, ''::text) AS signature,
		sa.deleted,
		saw.deleted AS website_deleted
   FROM sample_attributes sa
   LEFT JOIN sample_attributes_websites saw ON sa.id = saw.sample_attribute_id
  ORDER BY saw.id;

DROP VIEW IF EXISTS list_occurrence_attributes;
CREATE OR REPLACE VIEW list_occurrence_attributes AS 
 SELECT oa.id,
 		oa.caption,
		oa.data_type,
		oa.termlist_id,
		oa.multi_value,
		oaw.website_id,
		oaw.restrict_to_survey_id,
		(((oa.id || '|'::text) || oa.data_type::text) || '|'::text) || COALESCE(oa.termlist_id::text, ''::text) AS signature,
		oa.deleted,
		oaw.deleted AS website_deleted
   FROM occurrence_attributes oa
   LEFT JOIN occurrence_attributes_websites oaw ON oaw.occurrence_attribute_id = oa.id
  ORDER BY oaw.id;


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
        END AS raw_value, sa.termlist_id, saw.website_id
   FROM samples s
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
   JOIN sample_attributes_websites saw ON saw.website_id = su.website_id AND (saw.restrict_to_survey_id = su.id OR saw.id IS NULL) AND saw.deleted = false
   JOIN sample_attributes sa ON sa.id = saw.sample_attribute_id AND sa.deleted = false
   LEFT JOIN sample_attribute_values sav ON sav.sample_attribute_id = sa.id AND sav.sample_id = s.id AND sav.deleted = false
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id) ON tt.id = sav.int_value AND sa.data_type = 'L'::bpchar
  WHERE s.deleted = false
  ORDER BY sa.id;