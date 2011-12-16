-- View: gv_location_attributes

-- DROP VIEW gv_location_attributes;

CREATE OR REPLACE VIEW gv_taxa_taxon_list_attributes AS 
  SELECT ta.id, tl.id as taxon_list_id, tl.title AS taxon_list, ta.caption, 
  CASE ta.data_type
    WHEN 'T'::bpchar THEN 'Text'::bpchar
    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
    WHEN 'I'::bpchar THEN 'Integer'::bpchar
    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
    WHEN 'F'::bpchar THEN 'Float'::bpchar
    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
    ELSE ta.data_type
  END AS data_type, ta.public, ta.created_by_id, ta.deleted, tl.website_id
  FROM taxa_taxon_list_attributes ta
  JOIN taxon_lists_taxa_taxon_list_attributes tta ON tta.taxa_taxon_list_attribute_id=ta.id AND tta.deleted=false
  JOIN taxon_lists tl ON tl.id=tta.taxon_list_id AND tl.deleted=false
  WHERE ta.deleted=false
UNION 
  SELECT ta.id, null as taxon_list_id, '' as taxon_list, ta.caption, 
  CASE ta.data_type
    WHEN 'T'::bpchar THEN 'Text'::bpchar
    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
    WHEN 'I'::bpchar THEN 'Integer'::bpchar
    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
    WHEN 'F'::bpchar THEN 'Float'::bpchar
    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
    ELSE ta.data_type
  END AS data_type, ta.public, ta.created_by_id, ta.deleted, null as website_id
  FROM taxa_taxon_list_attributes ta
  WHERE ta.deleted=false;

