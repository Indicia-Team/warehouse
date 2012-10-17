DROP VIEW list_taxa_taxon_designations;

DROP VIEW list_taxa_taxon_lists;

DROP VIEW gv_taxa_taxon_lists;

DROP VIEW detail_taxa_taxon_lists;

ALTER TABLE taxa_taxon_lists
ALTER COLUMN taxonomic_sort_order TYPE bigint;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, l.iso AS language, tc.taxon AS common, tpref.taxon AS preferred_name, tg.title AS taxon_group, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, tl.website_id, t.external_key, ttl.allow_data_entry, ttl.taxon_meaning_id, t.taxon_group_id
   FROM taxa_taxon_lists ttl
   JOIN taxa_taxon_lists ttlpref ON ttlpref.taxon_meaning_id = ttl.taxon_meaning_id AND ttlpref.preferred = true AND ttlpref.deleted = false AND ttlpref.taxon_list_id = ttl.taxon_list_id
   JOIN taxa tpref ON tpref.id = ttlpref.taxon_id AND tpref.deleted = false
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id AND tl.deleted = false
   JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted = false
   JOIN languages l ON l.id = t.language_id
   JOIN taxon_groups tg ON tg.id = t.taxon_group_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id AND tc.deleted = false
  WHERE ttl.deleted = false
  ORDER BY ttl.taxonomic_sort_order, t.taxon;

CREATE OR REPLACE VIEW list_taxa_taxon_designations AS 
 SELECT ttd.id, td.title, td.code, td.abbreviation, t.taxon, cttl.default_common_name as common, cttl.preferred_taxon as preferred_name, cttl.language, cttl.taxon_group
   FROM taxon_designations td
   JOIN taxa_taxon_designations ttd ON ttd.taxon_designation_id = td.id AND ttd.deleted = false
   JOIN taxa t on t.id=ttd.taxon_id and t.deleted=false
   JOIN taxa_taxon_lists ttl on ttl.taxon_id=t.id and ttl.deleted=false
   JOIN cache_taxa_taxon_lists cttl on cttl.id=ttl.id
  WHERE td.deleted = false;

CREATE OR REPLACE VIEW gv_taxa_taxon_lists AS 
 SELECT tt.id, tt.taxon_list_id, tt.taxon_id, tt.created_on, tt.created_by_id, tt.parent_id, tt.taxon_meaning_id, tt.taxonomic_sort_order, tt.preferred, tt.deleted, t.taxon, t.taxon_group_id, t.language_id, t.authority, t.search_code, t.scientific, l.language, tg.title AS taxon_group
   FROM taxa_taxon_lists tt
   JOIN taxa t ON tt.taxon_id = t.id AND t.deleted = false
   JOIN languages l ON t.language_id = l.id AND l.deleted = false
   JOIN taxon_groups tg ON t.taxon_group_id = tg.id AND tg.deleted = false
  WHERE tt.deleted = false;

CREATE OR REPLACE VIEW detail_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, l.iso AS language_iso, tc.taxon AS common, ttl.taxon_list_id, tl.title AS taxon_list, ttl.taxon_meaning_id, ttl.preferred, ttl.taxonomic_sort_order, ttl.description AS description_in_list, t.description AS general_description, ttl.parent_id, tp.taxon AS parent, ti.path AS image_path, ti.caption AS image_caption, ttl.created_by_id, c.username AS created_by, ttl.updated_by_id, u.username AS updated_by, t.external_key, ttl.allow_data_entry
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