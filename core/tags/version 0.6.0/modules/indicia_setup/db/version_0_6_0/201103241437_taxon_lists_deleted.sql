CREATE OR REPLACE VIEW list_taxon_lists AS 
 SELECT t.id, t.title, t.website_id
   FROM taxon_lists t
   WHERE deleted=false;

CREATE OR REPLACE VIEW detail_taxon_lists AS 
 SELECT t.id, t.title, t.description, t.website_id, w.title AS website, t.parent_id, p.title AS parent, t.created_by_id, c.username AS created_by, t.updated_by_id, u.username AS updated_by
   FROM taxon_lists t
   LEFT JOIN websites w ON w.id = t.website_id AND w.deleted=false
   LEFT JOIN taxon_lists p ON p.id = t.parent_id AND p.deleted=false
   JOIN users c ON c.id = t.created_by_id
   JOIN users u ON u.id = t.updated_by_id
   WHERE t.deleted=false;