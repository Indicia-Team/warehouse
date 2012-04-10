DROP VIEW list_termlists_terms;

CREATE OR REPLACE VIEW list_termlists_terms AS 
 SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist, tl.website_id, tl.external_key as termlist_external_key
   FROM termlists_terms tlt
   JOIN termlists tl ON tl.id = tlt.termlist_id
   JOIN terms t ON t.id = tlt.term_id
  WHERE tlt.deleted = false;