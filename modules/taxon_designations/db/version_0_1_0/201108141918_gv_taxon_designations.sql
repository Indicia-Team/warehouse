CREATE OR REPLACE VIEW gv_taxon_designations AS 
 SELECT td.id, td.title, t.term AS category, td.deleted
   FROM taxon_designations td
   LEFT JOIN (termlists_terms tlt
   JOIN terms t ON t.id = tlt.term_id AND t.deleted = false) ON tlt.id = td.category_id AND tlt.deleted = false
  WHERE td.deleted = false;
