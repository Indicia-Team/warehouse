CREATE OR REPLACE VIEW list_group_pages AS 
 SELECT gp.id, gp."path", gp.caption, gp.administrator, g.website_id, gp.group_id
   FROM group_pages gp
   JOIN groups g on g.id=gp.group_id AND g.deleted=false
  WHERE gp.deleted = false;