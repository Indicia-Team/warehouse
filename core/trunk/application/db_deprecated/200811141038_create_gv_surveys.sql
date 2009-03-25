CREATE OR REPLACE VIEW gv_surveys AS
 SELECT s.id, s.title, s.description, w.title AS website
   FROM surveys s
   LEFT JOIN websites w ON s.website_id = w.id;