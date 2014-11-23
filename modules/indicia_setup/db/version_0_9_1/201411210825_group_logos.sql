ALTER TABLE groups
   ADD COLUMN logo_path character varying;
COMMENT ON COLUMN groups.logo_path
  IS 'Path to the group logo file, within the server''s upload directory.';

CREATE OR REPLACE VIEW list_groups AS 
 SELECT g.id, g.title, g.code, g.group_type_id, g.description, g.from_date, g.to_date, g.private_records, g.website_id, 
   g.filter_id, g.joining_method, g.logo_path
   FROM groups g
  WHERE g.deleted = false;
  
CREATE OR REPLACE VIEW detail_groups AS 
 SELECT g.id, g.title, g.code, g.group_type_id, g.description, g.from_date, g.to_date, g.private_records, g.website_id, 
   g.joining_method, g.filter_id, f.definition AS filter_definition, g.created_by_id, 
   c.username AS created_by, g.updated_by_id, u.username AS updated_by, g.logo_path
   FROM groups g
   LEFT JOIN filters f ON f.id = g.filter_id AND f.deleted = false
   JOIN users c ON c.id = g.created_by_id
   JOIN users u ON u.id = g.updated_by_id
  WHERE g.deleted = false;
