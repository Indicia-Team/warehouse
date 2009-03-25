DROP VIEW gv_termlists_terms;

CREATE OR REPLACE VIEW gv_termlists_terms AS 
 SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id, tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id, tt.preferred, tt.sort_order, tt.deleted, t.term, l.language
   FROM termlists_terms tt
   JOIN terms t ON tt.term_id = t.id
   JOIN languages l ON t.language_id = l.id;

DROP VIEW gv_taxon_lists_taxa;

CREATE OR REPLACE VIEW gv_taxon_lists_taxa AS 
 SELECT tt.id, tt.taxon_list_id, tt.taxon_id, tt.created_on, tt.created_by_id, tt.parent_id, tt.taxon_meaning_id, tt.taxonomic_sort_order, tt.preferred, tt.deleted, t.taxon, t.taxon_group_id, t.language_id, t.authority, t.search_code, t.scientific, l.language
   FROM taxa_taxon_lists tt
   JOIN taxa t ON tt.taxon_id = t.id
   JOIN languages l ON t.language_id = l.id;