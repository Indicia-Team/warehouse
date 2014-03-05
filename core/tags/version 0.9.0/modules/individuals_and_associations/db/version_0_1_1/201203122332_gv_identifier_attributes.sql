-- View: gv_identifier_attributes

-- DROP VIEW gv_identifier_attributes;

CREATE OR REPLACE VIEW gv_identifier_attributes AS 
         SELECT pa.id, oaw.website_id, w.title AS website, pa.caption,
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
           FROM identifier_attributes pa
      LEFT JOIN identifier_attributes_websites oaw ON pa.id = oaw.identifier_attribute_id AND oaw.deleted = false
   LEFT JOIN websites w ON w.id = oaw.website_id AND w.deleted = false
  WHERE pa.deleted = false;
