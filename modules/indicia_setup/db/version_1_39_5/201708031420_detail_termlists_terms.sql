CREATE OR REPLACE VIEW detail_termlists_terms AS
SELECT tlt.id,
    tlt.term_id,
    t.term,
    tlt.termlist_id,
    tl.title AS termlist,
    tlt.meaning_id,
    tlt.preferred,
    tltp.id as parent_id,
    tp.term AS parent,
    tlt.sort_order,
    tl.website_id,
    tlt.created_by_id,
    c.username AS created_by,
    tlt.updated_by_id,
    u.username AS updated_by,
    l.iso,
    tl.external_key AS termlist_external_key
   FROM termlists_terms tlt
     JOIN termlists tl ON tl.id = tlt.termlist_id AND tl.deleted = false
     JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
     JOIN languages l ON l.id = t.language_id AND l.deleted = false
     JOIN users c ON c.id = tlt.created_by_id
     JOIN users u ON u.id = tlt.updated_by_id
     JOIN termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.termlist_id=tlt.termlist_id and tltpref.preferred=true
     LEFT JOIN termlists_terms tltppref ON tltppref.id = tltpref.parent_id AND tltppref.deleted=false
     LEFT JOIN (termlists_terms tltp 
       JOIN terms tp ON tp.id = tltp.term_id AND tp.deleted=false
     ) ON tltp.meaning_id=tltppref.meaning_id AND tltp.termlist_id=tltppref.termlist_id AND tltp.deleted=false
       AND tp.language_id=t.language_id
  WHERE tlt.deleted = false
  ORDER BY tlt.sort_order, t.term;