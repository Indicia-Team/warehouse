DROP VIEW detail_termlists_terms;

CREATE OR REPLACE VIEW detail_termlists_terms AS 
 SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist, tlt.meaning_id, tlt.preferred, tlt.parent_id, tp.term AS parent, tl.website_id, tlt.created_by_id, c.username AS created_by, tlt.updated_by_id, u.username AS updated_by, tlt.sort_order, l.iso
   FROM termlists_terms tlt
   JOIN termlists tl ON tl.id = tlt.termlist_id
   JOIN terms t ON t.id = tlt.term_id
   JOIN languages l ON l.id=t.language_id
   JOIN users c ON c.id = tlt.created_by_id
   JOIN users u ON u.id = tlt.updated_by_id
   LEFT JOIN termlists_terms tltp ON tltp.id = tlt.parent_id
   LEFT JOIN terms tp ON tp.id = tltp.term_id
  WHERE tlt.deleted = false;
