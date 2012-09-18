CREATE OR REPLACE VIEW gv_termlists_terms AS 
 SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id, tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id, tt.preferred, tt.sort_order, tt.deleted, t.term, l.language
   FROM termlists_terms tt
   JOIN terms t ON tt.term_id = t.id AND t.deleted=false
   JOIN languages l ON t.language_id = l.id AND l.deleted=false
   WHERE tt.deleted=false;