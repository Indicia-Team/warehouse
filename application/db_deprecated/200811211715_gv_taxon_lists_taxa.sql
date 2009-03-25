-- View: gv_taxon_lists_taxa

-- DROP VIEW gv_taxon_lists_taxa;

CREATE OR REPLACE VIEW gv_taxon_lists_taxa AS 
 SELECT tt.id, tt.taxon_list_id, tt.taxon_id, tt.created_on, tt.created_by_id, tt.parent_id, tt.taxon_meaning_id, tt.taxonomic_sort_order, tt.preferred, t.taxon, t.taxon_group_id, t.language_id, t.authority, t.search_code, t.scientific
   FROM taxa_taxon_lists tt
   JOIN taxa t ON tt.taxon_id = t.id;
