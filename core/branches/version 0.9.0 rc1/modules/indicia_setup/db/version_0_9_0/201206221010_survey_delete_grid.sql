CREATE OR REPLACE VIEW gv_surveys AS 
 SELECT s.id, s.title, s.description, w.title AS website, s.deleted, s.website_id
   FROM surveys s
   LEFT JOIN websites w ON s.website_id = w.id AND w.deleted = false
  WHERE s.deleted = false;
