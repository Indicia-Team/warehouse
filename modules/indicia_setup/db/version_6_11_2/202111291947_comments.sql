CREATE OR REPLACE VIEW list_occurrence_comments AS
 SELECT oc.id, oc.comment, oc.occurrence_id, oc.email_address, oc.updated_on, COALESCE(p.surname || ', ' || p.first_name, oc.person_name)::varchar AS person_name,
    u.username, o.website_id, oc.record_status, oc.record_substatus, oc.query, oc.confidential, oc.correspondence_data
   FROM occurrence_comments oc
   JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
   LEFT JOIN users u ON oc.created_by_id = u.id and u.id<>1
   LEFT JOIN people p ON p.id = u.person_id AND p.deleted = false
  WHERE oc.deleted = false;