-- View: gv_termlists

-- DROP VIEW gv_termlists;

CREATE OR REPLACE VIEW gv_termlists AS 
 SELECT t.id, t.title, t.description, t.website_id, t.parent_id, t.deleted, t.created_on, t.created_by_id, t.updated_on, t.updated_by_id, w.title AS website, p.surname AS creator
   FROM termlists t
   LEFT JOIN websites w ON t.website_id = w.id
   INNER JOIN users u ON t.created_by_id = u.id
   INNER JOIN people p ON u.person_id = p.id;