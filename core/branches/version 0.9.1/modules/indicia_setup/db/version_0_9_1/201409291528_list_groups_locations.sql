CREATE OR REPLACE VIEW list_groups_locations AS 
 SELECT gl.id, gl.group_id, gl.location_id, g.website_id, l.name
   FROM groups_locations gl
   JOIN groups g ON g.id = gl.group_id AND g.deleted = false
   JOIN locations l ON l.id = gl.location_id and l.deleted=false
  WHERE g.deleted = false;
