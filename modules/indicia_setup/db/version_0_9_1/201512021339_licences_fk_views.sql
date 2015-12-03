CREATE OR REPLACE VIEW list_groups AS 
 SELECT g.id,
    g.title,
    g.code,
    g.group_type_id,
    g.description,
    g.from_date,
    g.to_date,
    g.private_records,
    g.website_id,
    g.filter_id,
    g.joining_method,
    g.logo_path,
    g.implicit_record_inclusion,
    g.licence_id
   FROM groups g
  WHERE g.deleted = false;


CREATE OR REPLACE VIEW detail_groups AS 
 SELECT g.id,
    g.title,
    g.code,
    g.group_type_id,
    g.description,
    g.from_date,
    g.to_date,
    g.private_records,
    g.website_id,
    g.joining_method,
    g.filter_id,
    f.definition AS filter_definition,
    g.created_by_id,
    c.username AS created_by,
    g.updated_by_id,
    u.username AS updated_by,
    g.logo_path,
        CASE g.joining_method
            WHEN 'P'::bpchar THEN btrim(regexp_replace(regexp_replace(lower(g.title::text), '[ ]'::text, '-'::text, 'g'::text), '[^a-z0-9\-]'::text, ''::text, 'g'::text), '-'::text)
            ELSE NULL::text
        END AS url_safe_title,
    g.implicit_record_inclusion,
    g.licence_id,
    l.code as licence_code
   FROM groups g
     LEFT JOIN filters f ON f.id = g.filter_id AND f.deleted = false
     LEFT JOIN licences l on l.id=g.licence_id and l.deleted=false
     JOIN users c ON c.id = g.created_by_id
     JOIN users u ON u.id = g.updated_by_id
  WHERE g.deleted = false;
  


CREATE OR REPLACE VIEW list_samples AS 
 SELECT s.id,
    su.title AS survey,
    l.name AS location,
    s.date_start,
    s.date_end,
    s.date_type,
    s.entered_sref,
    s.entered_sref_system,
    su.website_id,
    s.survey_id,
    s.licence_id
   FROM samples s
     LEFT JOIN locations l ON s.location_id = l.id AND l.deleted = false
     JOIN surveys su ON s.survey_id = su.id AND su.deleted = false
  WHERE s.deleted = false;
  
CREATE OR REPLACE VIEW detail_samples AS
 SELECT s.id,
    s.entered_sref,
    s.entered_sref_system,
    s.geom,
    st_astext(s.geom) AS wkt,
    s.location_name,
    s.date_start,
    s.date_end,
    s.date_type,
    s.location_id,
    l.name AS location,
    l.code AS location_code,
    s.created_by_id,
    c.username AS created_by,
    s.created_on,
    s.updated_by_id,
    u.username AS updated_by,
    s.updated_on,
    su.website_id,
    s.parent_id,
    s.comment,
    s.recorder_names,
    su.id AS survey_id,
    su.title AS survey_title,
    s.sample_method_id,
    s.external_key,
    s.group_id,
    g.title AS group_title,
    s.record_status,
    s.verified_on,
    s.verified_by_id,
    s.licence_id,
    li.code as licence_code
   FROM samples s
     JOIN surveys su ON s.survey_id = su.id
     LEFT JOIN locations l ON l.id = s.location_id AND l.deleted = false
     LEFT JOIN groups g ON g.id = s.group_id AND g.deleted = false
     LEFT JOIN licences li on li.id=s.licence_id and li.deleted=false
     JOIN users c ON c.id = s.created_by_id
     JOIN users u ON u.id = s.updated_by_id
  WHERE s.deleted = false;
  

CREATE OR REPLACE VIEW list_users AS
 SELECT u.id,
    u.username,
    uw.website_id,
    u.person_id,
    uw.licence_id
   FROM users u
     JOIN users_websites uw ON u.id = uw.user_id AND uw.site_role_id IS NOT NULL
  WHERE u.deleted = false;


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
    uw.licence_id
   FROM users u
     JOIN users cu ON cu.id = u.created_by_id
     JOIN users uu ON uu.id = u.updated_by_id
     JOIN users_websites uw ON u.id = uw.user_id AND uw.site_role_id IS NOT NULL
     JOIN people p ON p.id = u.person_id AND p.deleted = false
  WHERE u.deleted = false;