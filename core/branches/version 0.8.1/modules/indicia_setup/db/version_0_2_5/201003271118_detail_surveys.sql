-- View: detail_surveys

CREATE OR REPLACE VIEW detail_surveys AS 
 SELECT s.id, s.title, s.owner_id, p.surname AS owner, s.description, s.website_id, w.title AS website, s.created_by_id, c.username AS created_by, s.updated_by_id, u.username AS updated_by
   FROM surveys s
   JOIN users c ON c.id = s.created_by_id
   JOIN users u ON u.id = s.updated_by_id
   LEFT JOIN people p ON p.id = s.owner_id
   JOIN websites w ON w.id = s.website_id;
