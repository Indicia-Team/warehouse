CREATE OR REPLACE VIEW detail_taxon_groups AS 
 SELECT t.id, t.title, t.created_by_id, c.username AS created_by, t.updated_by_id, u.username AS updated_by, NULL::integer AS website_id
   FROM taxon_groups t
   JOIN users c ON c.id = t.created_by_id
   JOIN users u ON u.id = t.updated_by_id
   WHERE t.deleted=false;

CREATE OR REPLACE VIEW list_taxon_groups AS 
 SELECT t.id, t.title, NULL::integer AS website_id
   FROM taxon_groups t
   WHERE t.deleted=false;