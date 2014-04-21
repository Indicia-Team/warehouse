CREATE OR REPLACE VIEW list_groups AS 
 SELECT g.id, g.title, g.code, g.group_type_id, g.description, g.from_date, g.to_date, g.private_records, g.website_id, g.filter_id, g.joining_method
   FROM groups g
  WHERE g.deleted = false;

CREATE OR REPLACE VIEW detail_groups AS 
 SELECT g.id, g.title, g.code, g.group_type_id, g.description, g.from_date, g.to_date, g.private_records, 
     g.website_id, g.joining_method, g.filter_id, f.definition as filter_definition, 
     g.created_by_id, c.username AS created_by, g.updated_by_id, u.username AS updated_by
   FROM groups g
   LEFT JOIN filters f ON f.id=g.filter_id AND f.deleted=false
   JOIN indicia.users c ON c.id = g.created_by_id
   JOIN indicia.users u ON u.id = g.updated_by_id
  WHERE g.deleted = false;

CREATE OR REPLACE VIEW list_groups_users AS 
 SELECT gu.id, gu.group_id, gu.user_id, gu.administrator, g.website_id, u.username, gu.pending
   FROM groups_users gu
   JOIN groups g on g.id=gu.group_id AND g.deleted=false
   JOIN users u on u.id=gu.user_id
  WHERE gu.deleted = false;

CREATE OR REPLACE VIEW detail_groups_users AS 
 SELECT gu.id, gu.group_id, gu.user_id, gu.administrator, g.website_id,
    u.username, p.first_name, p.surname, p.surname || COALESCE(', ' || p.first_name, '') as person_name,
    gu.created_by_id, c.username AS created_by, gu.updated_by_id, up.username AS updated_by, gu.pending
   FROM groups_users gu
   JOIN groups g on g.id=gu.group_id AND g.deleted=false
   JOIN users u ON u.id=gu.user_id AND u.deleted=false
   JOIN people p ON p.id=u.person_id AND p.deleted=false
   JOIN users c ON c.id = gu.created_by_id
   JOIN users up ON up.id = gu.updated_by_id
  WHERE gu.deleted = false;
  
CREATE OR REPLACE VIEW list_group_pages AS 
 SELECT gp.id, gp."path", gp.caption, gp.administrator, g.website_id
   FROM group_pages gp
   JOIN groups g on g.id=gp.group_id AND g.deleted=false
  WHERE gp.deleted = false;