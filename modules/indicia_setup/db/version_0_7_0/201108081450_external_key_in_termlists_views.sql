DROP VIEW list_termlists;

DROP VIEW detail_termlists;

CREATE VIEW list_termlists AS 
 SELECT t.id, t.title, t.website_id, t.external_key
   FROM termlists t
  WHERE t.deleted = false;

CREATE VIEW detail_termlists AS 
 SELECT t.id, t.title, t.description, t.website_id, w.title AS website, t.parent_id, p.title AS parent, t.created_by_id, c.username AS created_by, t.updated_by_id, u.username AS updated_by, t.external_key
   FROM termlists t
   LEFT JOIN websites w ON w.id = t.website_id
   LEFT JOIN termlists p ON p.id = t.parent_id
   JOIN users c ON c.id = t.created_by_id
   JOIN users u ON u.id = t.updated_by_id
  WHERE t.deleted = false;
