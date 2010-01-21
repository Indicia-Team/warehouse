ALTER TABLE occurrence_comments
ADD COLUMN person_name varchar;

COMMENT ON COLUMN occurrence_comments.person_name IS 'Identifier for anonymous commenter.';

-- View: list_occurrence_comments


DROP VIEW list_occurrence_comments;

CREATE OR REPLACE VIEW list_occurrence_comments AS 
 SELECT oc.id, oc.comment, oc.occurrence_id, oc.email_address, oc.updated_on, oc.person_name, u.username
   FROM occurrence_comments oc
   LEFT JOIN users u ON oc.created_by_id = u.id;