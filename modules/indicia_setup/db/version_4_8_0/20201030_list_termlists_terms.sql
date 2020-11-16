CREATE OR REPLACE VIEW list_termlists_terms AS
  SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist, tl.website_id, tl.external_key AS termlist_external_key, l.iso, tlt.sort_order, tlt.allow_data_entry
   FROM termlists_terms tlt
   JOIN termlists tl ON tl.id = tlt.termlist_id AND tl.deleted = false
   JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
   JOIN languages l ON l.id = t.language_id AND l.deleted = false
  WHERE tlt.deleted = false
  ORDER BY tlt.sort_order, t.term;