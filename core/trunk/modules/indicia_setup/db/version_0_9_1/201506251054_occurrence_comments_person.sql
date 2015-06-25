CREATE OR REPLACE VIEW list_occurrence_comments AS 
 SELECT oc.id, oc.comment, oc.occurrence_id, oc.email_address, oc.updated_on, COALESCE(oc.person_name, ((p.surname::text || ', '::text) || p.first_name::text)::character varying) AS person_name, u.username, o.website_id, oc.record_status, oc.record_substatus, oc.query
   FROM occurrence_comments oc
   JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
   JOIN users u ON oc.created_by_id = u.id and u.id<>1
   JOIN people p ON p.id = u.person_id AND p.deleted = false
  WHERE oc.deleted = false;

CREATE OR REPLACE VIEW detail_occurrence_comments AS 
 SELECT oc.id, oc.comment, oc.occurrence_id, oc.email_address, oc.updated_on, COALESCE(oc.person_name, ((p.surname::text || ', '::text) || p.first_name::text)::character varying) AS person_name, c.username, o.website_id, oc.created_on, oc.created_by_id, c.username AS created_by, oc.updated_by_id, u.username AS updated_by, oc.record_status, oc.record_substatus, oc.query
   FROM occurrence_comments oc
   JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
   JOIN users c ON oc.created_by_id = c.id
   JOIN users u ON oc.updated_by_id = u.id
   JOIN people p ON p.id = c.person_id AND p.deleted = false and c.created_by_id<>1
  WHERE oc.deleted = false;