CREATE OR REPLACE VIEW lookup_taxa_taxon_lists AS 
 SELECT tt.id, tt.taxon_meaning_id, tt.taxon_list_id, t.taxon, t.authority
   FROM taxa_taxon_lists tt
   JOIN taxa t ON tt.taxon_id = t.id AND t.deleted = false
  WHERE tt.deleted = false;