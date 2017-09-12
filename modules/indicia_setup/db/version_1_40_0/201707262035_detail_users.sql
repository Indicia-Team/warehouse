CREATE OR REPLACE VIEW detail_users AS
 SELECT u.id,
    u.username,
    uw.website_id,
    u.person_id,
    u.created_by_id,
    cu.username AS created_by,
    u.updated_by_id,
    u.created_on,
    u.updated_on,
    uu.username AS updated_by,
    p.surname,
    p.first_name,
    p.surname::text || COALESCE(', '::text || p.first_name::text, ''::text) AS person_name,
    p.email_address,
    (((p.surname::text || COALESCE(', '::text || p.first_name::text, ''::text)) || ' ('::text) || p.email_address::text) || ')'::text AS name_and_email,
    uw.licence_id,
    p.surname::text || COALESCE(', '::text || p.first_name::text, ''::text) || ' [' || u.id::text || ']' AS person_name_unique
   FROM users u
     JOIN users cu ON cu.id = u.created_by_id
     JOIN users uu ON uu.id = u.updated_by_id
     JOIN users_websites uw ON u.id = uw.user_id AND uw.site_role_id IS NOT NULL
     JOIN people p ON p.id = u.person_id AND p.deleted = false
  WHERE u.deleted = false;

CREATE OR REPLACE VIEW detail_groups_users AS
 SELECT gu.id,
    gu.group_id,
    g.title AS group_title,
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
    gu.pending,
    gu.access_level,
    g.from_date as group_from_date,
    g.to_date as group_to_date,
    g.to_date < now() as group_expired,
    p.surname::text || COALESCE(', '::text || p.first_name::text, ''::text) || ' [' || u.id::text || ']' AS person_name_unique
   FROM groups_users gu
     JOIN groups g ON g.id = gu.group_id AND g.deleted = false
     JOIN users u ON u.id = gu.user_id AND u.deleted = false
     JOIN people p ON p.id = u.person_id AND p.deleted = false
     JOIN users c ON c.id = gu.created_by_id
     JOIN users up ON up.id = gu.updated_by_id
  WHERE gu.deleted = false;