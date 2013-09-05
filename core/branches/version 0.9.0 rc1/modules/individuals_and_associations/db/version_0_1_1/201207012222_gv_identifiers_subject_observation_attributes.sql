-- View: gv_identifiers_subject_observation_attributes

-- DROP VIEW gv_identifiers_subject_observation_attributes;

CREATE OR REPLACE VIEW gv_identifiers_subject_observation_attributes AS 
         SELECT pa.id, oaw.website_id, w.title AS website, pa.caption, oaw.restrict_to_survey_id AS survey_id, s.title AS survey,
                CASE pa.data_type
                    WHEN 'T'::bpchar THEN 'Text'::bpchar
                    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
                    WHEN 'I'::bpchar THEN 'Integer'::bpchar
                    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
                    WHEN 'F'::bpchar THEN 'Float'::bpchar
                    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
                    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
                    ELSE pa.data_type
                END AS data_type, pa.public, pa.created_by_id, pa.deleted
           FROM identifiers_subject_observation_attributes pa
      LEFT JOIN identifiers_subject_observation_attributes_websites oaw ON pa.id = oaw.identifiers_subject_observation_attribute_id AND oaw.deleted = false
   LEFT JOIN websites w ON w.id = oaw.website_id AND w.deleted = false
   LEFT JOIN surveys s ON s.id = oaw.restrict_to_survey_id AND s.deleted = false
  WHERE pa.deleted = false;
