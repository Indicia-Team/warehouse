CREATE OR REPLACE VIEW list_location_comments
 AS
 SELECT lc.id,
    lc.comment,
    lc.location_id,
    lc.email_address,
    lc.updated_on,
    COALESCE(lc.person_name, ((p.surname::text || ', '::text) || p.first_name::text)::character varying) AS person_name,
    u.username,
    lw.website_id
   FROM location_comments lc
     JOIN locations l ON l.id = lc.location_id AND l.deleted = false
     JOIN users u ON lc.created_by_id = u.id
     JOIN people p ON p.id = u.person_id AND p.deleted = false
	 LEFT JOIN locations_websites lw ON lw.location_id=l.id AND lw.deleted=false
  WHERE lc.deleted = false;

CREATE OR REPLACE VIEW detail_location_comments
 AS
 SELECT lc.id,
    lc.comment,
    lc.location_id,
    lc.email_address,
    lc.updated_on,
    COALESCE(lc.person_name, ((p.surname::text || ', '::text) || p.first_name::text)::character varying) AS person_name,
    c.username,
    lc.created_on,
    lc.created_by_id,
    c.username AS created_by,
    lc.updated_by_id,
    u.username AS updated_by,
    lw.website_id
   FROM location_comments lc
     JOIN locations l ON l.id = lc.location_id AND l.deleted = false
     JOIN users c ON lc.created_by_id = c.id
     JOIN users u ON lc.updated_by_id = u.id
     JOIN people p ON p.id = c.person_id AND p.deleted = false
	 LEFT JOIN locations_websites lw ON lw.location_id=l.id AND lw.deleted=false
  WHERE lc.deleted = false;

CREATE OR REPLACE VIEW gv_location_comments
 AS
 SELECT lc.id,
    lc.comment,
    lc.location_id,
    lc.email_address,
    lc.updated_on,
    lc.person_name,
    u.username,
    lw.website_id
   FROM location_comments lc
     JOIN locations l ON l.id = lc.location_id AND l.deleted = false
	 LEFT JOIN locations_websites lw ON lw.location_id=l.id AND lw.deleted=false
     LEFT JOIN users u ON lc.created_by_id = u.id
  WHERE lc.deleted = false;

