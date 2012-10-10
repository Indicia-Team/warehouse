DROP VIEW list_taxa_taxon_lists;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, tc.taxon AS common, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, tl.website_id, t.external_key
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id
  WHERE ttl.deleted = false;

DROP VIEW detail_taxa_taxon_lists;

CREATE OR REPLACE VIEW detail_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, l.iso AS language_iso, tc.taxon AS common, ttl.taxon_list_id, tl.title AS taxon_list, ttl.taxon_meaning_id, 
     ttl.preferred, ttl.taxonomic_sort_order, ttl.description AS description_in_list, t.description AS general_description, ttl.parent_id, tp.taxon AS parent, 
     ti.path AS image_path, ti.caption AS image_caption, ttl.created_by_id, c.username AS created_by, ttl.updated_by_id, u.username AS updated_by, t.external_key
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted = false
   JOIN languages l ON l.id = t.language_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id AND tc.deleted = false
   JOIN users c ON c.id = ttl.created_by_id
   JOIN users u ON u.id = ttl.updated_by_id
   LEFT JOIN taxa_taxon_lists ttlp ON ttlp.id = ttl.parent_id AND ttlp.deleted = false
   LEFT JOIN taxa tp ON tp.id = ttlp.taxon_id AND tp.deleted = false
   LEFT JOIN taxon_images ti ON ti.taxon_meaning_id = ttl.taxon_meaning_id AND ti.deleted = false
  WHERE ttl.deleted = false;