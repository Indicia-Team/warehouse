DROP VIEW list_taxa_taxon_lists;

CREATE OR REPLACE VIEW list_taxa_taxon_lists AS 
 SELECT ttl.id, ttl.taxon_id, t.taxon, t.authority, l.iso AS language, tc.taxon AS common, tpref.taxon as preferred_name, tg.title as taxon_group, ttl.taxon_list_id, ttl.preferred, tl.title AS taxon_list, tl.website_id, t.external_key, ttl.allow_data_entry
   FROM taxa_taxon_lists ttl
   JOIN taxa_taxon_lists ttlpref ON ttlpref.taxon_meaning_id=ttl.taxon_meaning_id AND ttlpref.preferred='true' AND ttlpref.deleted=false
   JOIN taxa tpref ON tpref.id=ttlpref.taxon_id AND tpref.deleted=false
   JOIN taxon_lists tl ON tl.id = ttl.taxon_list_id AND tl.deleted=false
   JOIN taxa t ON t.id = ttl.taxon_id AND t.deleted=false
   JOIN languages l ON l.id=t.language_id
   JOIN taxon_groups tg ON tg.id=t.taxon_group_id
   LEFT JOIN taxa tc ON tc.id = ttl.common_taxon_id AND tc.deleted=false
  WHERE ttl.deleted = false;