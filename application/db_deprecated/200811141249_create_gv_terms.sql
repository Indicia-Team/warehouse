-- View: gv_terms

-- DROP VIEW gv_terms;

CREATE OR REPLACE VIEW gv_terms AS 
 SELECT t.id, t.term, t.parent_id, t.meaning_id, t.language_id, t.preferred, t.created_on, t.created_by_id, t.updated_on, t.updated_by_id, t.deleted, l.language, tt.termlist_id
   FROM terms t
   LEFT JOIN languages l ON t.language_id = l.id
   JOIN termlists_terms tt ON t.id = tt.term_id;

