DROP VIEW IF EXISTS list_taxa_taxon_lists;
DROP VIEW IF EXISTS detail_taxa_taxon_lists;
DROP VIEW IF EXISTS gv_taxon_lists_taxa;


ALTER TABLE taxa ALTER authority TYPE character varying(100);

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, l.iso AS language_iso, t.image_path AS taxon_image_path, ttl.image_path
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   JOIN languages l ON l.id = t.language_id
  WHERE ttl.deleted = false;

CREATE OR REPLACE VIEW detail_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, ttl.taxon_list_id, tl.title AS taxon_list, ttl.taxon_meaning_id, ttl.preferred, ttl.parent_id, tp.taxon AS parent, l.iso AS language_iso, t.image_path AS taxon_image_path, ttl.image_path, t.description AS taxon_description, ttl.description, ttl.created_by_id, c.username AS created_by, ttl.updated_by_id, u.username AS updated_by
   FROM taxa_taxon_lists ttl
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id
   JOIN taxa t ON t.id = ttl.taxon_id
   JOIN users c ON c.id = ttl.created_by_id
   JOIN users u ON u.id = ttl.updated_by_id
   JOIN languages l ON l.id = t.language_id
   LEFT JOIN taxa_taxon_lists ttlp ON ttlp.id = ttl.parent_id
   LEFT JOIN taxa tp ON tp.id = ttlp.taxon_id
  WHERE ttl.deleted = false; 

CREATE OR REPLACE VIEW gv_taxon_lists_taxa AS 
 SELECT tt.id, tt.taxon_list_id, tt.taxon_id, tt.created_on, tt.created_by_id, tt.parent_id, tt.taxon_meaning_id, tt.taxonomic_sort_order, tt.preferred, tt.deleted, t.taxon, t.taxon_group_id, t.language_id, t.authority, t.search_code, t.scientific, l.language
   FROM taxa_taxon_lists tt
   JOIN taxa t ON tt.taxon_id = t.id
   JOIN languages l ON t.language_id = l.id;