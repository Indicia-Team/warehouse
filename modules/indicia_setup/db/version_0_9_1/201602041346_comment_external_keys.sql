-- Add an external_key column to comments tables for external synching
CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN

success := TRUE;

BEGIN

	ALTER TABLE occurrence_comments
  ADD COLUMN external_key character varying(50);

EXCEPTION
    WHEN duplicate_column THEN
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN

	ALTER TABLE sample_comments
  ADD COLUMN external_key character varying(50);

EXCEPTION
    WHEN duplicate_column THEN
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON COLUMN occurrence_comments.external_key IS
  'For comments imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';

COMMENT ON COLUMN sample_comments.external_key IS
  'For comments imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';

CREATE OR REPLACE VIEW detail_occurrence_comments AS 
 SELECT oc.id,
    oc.comment,
    oc.occurrence_id,
    oc.email_address,
    oc.updated_on,
    COALESCE(oc.person_name, ((p.surname::text || ', '::text) || p.first_name::text)::character varying) AS person_name,
    c.username,
    o.website_id,
    oc.created_on,
    oc.created_by_id,
    c.username AS created_by,
    oc.updated_by_id,
    u.username AS updated_by,
    oc.record_status,
    oc.record_substatus,
    oc.query,
    oc.external_key
   FROM occurrence_comments oc
     JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
     JOIN users c ON oc.created_by_id = c.id
     JOIN users u ON oc.updated_by_id = u.id
     LEFT JOIN people p ON p.id = c.person_id AND p.deleted = false AND c.created_by_id <> 1
  WHERE oc.deleted = false;
  
  
CREATE OR REPLACE VIEW detail_sample_comments AS 
 SELECT sc.id,
    sc.comment,
    sc.sample_id,
    sc.email_address,
    sc.updated_on,
    COALESCE(sc.person_name, ((p.surname::text || ', '::text) || p.first_name::text)::character varying) AS person_name,
    c.username,
    sc.created_on,
    sc.created_by_id,
    c.username AS created_by,
    sc.updated_by_id,
    u.username AS updated_by,
    su.website_id,
    sc.external_key
   FROM sample_comments sc
     JOIN samples s ON s.id = sc.sample_id AND s.deleted = false
     JOIN users c ON sc.created_by_id = c.id
     JOIN users u ON sc.updated_by_id = u.id
     JOIN people p ON p.id = c.person_id AND p.deleted = false
     JOIN surveys su ON su.id = s.survey_id
  WHERE sc.deleted = false;

