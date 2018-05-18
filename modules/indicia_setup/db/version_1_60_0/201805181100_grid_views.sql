DROP VIEW gv_taxon_lists;

CREATE VIEW gv_taxon_lists AS
 SELECT t.id, t.title, t.website_id, w.title as website_title, t.parent_id, t.description
   FROM taxon_lists t
   LEFT JOIN websites w on w.id=t.website_id and w.deleted=false
  WHERE t.deleted = false;