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
        END AS raw_value, la.termlist_id, lw.website_id
   FROM locations l
   JOIN locations_websites lw ON lw.location_id = l.id AND lw.deleted = false
   JOIN location_attribute_values lav ON lav.location_id = l.id AND lav.deleted = false
   JOIN location_attributes la ON la.id = lav.location_attribute_id AND la.deleted = false   
   LEFT JOIN (termlists_terms tt
   JOIN terms t ON t.id = tt.term_id) ON tt.id = lav.int_value AND la.data_type = 'L'::bpchar
  WHERE l.deleted = false
  ORDER BY la.id;
  
DROP VIEW detail_occurrences;
--- with taxons possibly held on determinations records cant use straight joins to taxa* tables
CREATE OR REPLACE VIEW detail_occurrences AS
 SELECT o.id, o.confidential, o.comment, o.taxa_taxon_list_id, o.determiner_id, t.taxon, s.entered_sref, s.entered_sref_system,
 	s.geom, s.location_name, s.survey_id, s.date_start, s.date_end, s.date_type,
 	s.location_id, l.name AS location, l.code AS location_code, s.recorder_names,
 	(d.first_name::text || ' '::text) || d.surname::text AS determiner, o.website_id, o.created_by_id,
	c.username AS created_by, o.created_on, o.updated_by_id, u.username AS updated_by, o.updated_on,
	o.record_status, o.downloaded_flag, o.sample_id, o.deleted
   FROM occurrences o
   JOIN samples s ON s.id = o.sample_id
   LEFT JOIN people d ON d.id = o.determiner_id
   LEFT JOIN locations l ON l.id = s.location_id
   LEFT JOIN taxa_taxon_lists ttl ON ttl.id = o.taxa_taxon_list_id
   LEFT JOIN taxa t ON t.id = ttl.taxon_id
   LEFT JOIN surveys su ON s.survey_id = su.id
   JOIN users c ON c.id = o.created_by_id
   JOIN users u ON u.id = o.updated_by_id;
   
   
