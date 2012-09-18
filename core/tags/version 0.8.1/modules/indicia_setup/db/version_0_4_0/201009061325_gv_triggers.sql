--DROP VIEW gv_triggers;

CREATE OR REPLACE VIEW gv_triggers AS 
 SELECT t.id, t.name, t.description, t.public,
	COALESCE(p.first_name::text || ' '::text, ''::text) || p.surname::text AS created_by_name, 
  CASE t.public WHEN true THEN null ELSE u.id END AS private_for_user_id, t.deleted
   FROM triggers t
   JOIN users u ON u.id=t.created_by_id and u.deleted=false
   JOIN people p ON p.id=u.person_id and p.deleted=false;