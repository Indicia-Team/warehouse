DROP VIEW detail_groups_users;

CREATE OR REPLACE VIEW detail_groups_users AS 
 SELECT gu.id,
    gu.group_id,
    g.title as group_title, 
    g.group_type_id,
    gu.user_id,
    gu.administrator,
    g.website_id,
    u.username,
    p.first_name,
    p.surname,
    p.surname::text || COALESCE(', '::text || p.first_name::text, ''::text) AS person_name,
    gu.created_by_id,
    c.username AS created_by,
    gu.updated_by_id,
    up.username AS updated_by,
    gu.pending
   FROM groups_users gu
     JOIN groups g ON g.id = gu.group_id AND g.deleted = false
     JOIN users u ON u.id = gu.user_id AND u.deleted = false
     JOIN people p ON p.id = u.person_id AND p.deleted = false
     JOIN users c ON c.id = gu.created_by_id
     JOIN users up ON up.id = gu.updated_by_id
  WHERE gu.deleted = false;