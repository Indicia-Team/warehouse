-- View: list_taxa_taxon_lists

DROP VIEW list_taxa_taxon_lists;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
  WHERE ttl.deleted = false;