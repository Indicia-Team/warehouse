-- View: lookup_taxa_taxon_lists

-- DROP VIEW lookup_taxa_taxon_lists;

CREATE OR REPLACE VIEW lookup_taxa_taxon_lists AS 
 SELECT tt.id,
    tt.taxon_meaning_id,
    tt.taxon_list_id,
    t.taxon,
    t.authority,
    t.external_key, 
    t.search_code
   FROM taxa_taxon_lists tt
     JOIN taxa t ON tt.taxon_id = t.id AND t.deleted = false
  WHERE tt.deleted = false
  ORDER BY tt.preferred DESC;