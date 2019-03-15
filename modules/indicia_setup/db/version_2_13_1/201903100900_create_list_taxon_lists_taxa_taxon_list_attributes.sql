DROP VIEW IF EXISTS list_taxon_lists_taxa_taxon_list_attributes;

CREATE OR REPLACE VIEW list_taxon_lists_taxa_taxon_list_attributes AS
SELECT tlttla.id as id, a.caption as attribute_caption, tl.id as taxon_list_id, tl.title as taxon_list_title, tlttla.control_type_id as control_type_id, ct.control as control_type
FROM taxon_lists_taxa_taxon_list_attributes tlttla
JOIN taxa_taxon_list_attributes a ON a.id = tlttla.taxa_taxon_list_attribute_id AND a.deleted = false
LEFT JOIN taxon_lists tl on tl.id = tlttla.taxon_list_id AND tl.deleted=false
LEFT JOIN control_types ct on ct.id = tlttla.control_type_id;