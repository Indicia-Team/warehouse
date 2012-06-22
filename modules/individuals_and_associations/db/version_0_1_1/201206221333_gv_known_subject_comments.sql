CREATE OR REPLACE VIEW gv_known_subject_comments AS 
 SELECT ksc.id, ksc.comment, ksc.known_subject_id, ksc.email_address, ksc.updated_on, ksc.person_name, u.username, ks.website_id
   FROM known_subject_comments ksc
   JOIN known_subjects ks ON ks.id = ksc.known_subject_id AND ks.deleted = false
   LEFT JOIN users u ON ksc.created_by_id = u.id
  WHERE ksc.deleted = false;