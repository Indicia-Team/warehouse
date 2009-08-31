-- View: gv_termlist_terms

-- DROP VIEW gv_termlists_terms;

CREATE OR REPLACE VIEW gv_termlists_terms AS 
 SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id, tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id, tt.preferred, tt.sort_order, t.term, l.language
   FROM termlists_terms tt
   JOIN terms t ON tt.term_id = t.id
   JOIN languages l ON t.language_id = l.id;

-- View: gv_term_termlists

-- DROP VIEW gv_term_termlists;

CREATE OR REPLACE VIEW gv_term_termlists AS 
 SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id, tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id, tt.preferred, tt.sort_order, t.title, t.description
   FROM termlists_terms tt
   JOIN termlists t ON tt.termlist_id = t.id;

COMMENT ON VIEW gv_term_termlists IS 'View for the terms page - shows the list of termlists that a term belongs to.';
