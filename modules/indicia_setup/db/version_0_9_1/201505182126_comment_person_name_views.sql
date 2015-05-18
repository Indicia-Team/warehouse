CREATE OR REPLACE VIEW list_occurrence_comments AS 
 SELECT oc.id,
    oc.comment,
    oc.occurrence_id,
    oc.email_address,
    oc.updated_on,
    COALESCE(oc.person_name, p.surname || ', ' || p.first_name) as person_name,
    u.username,
    o.website_id,
    oc.record_status,
    oc.record_substatus,
    oc.query
   FROM occurrence_comments oc
     JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
     JOIN users u ON oc.created_by_id = u.id
     JOIN people p ON p.id=u.person_id and p.deleted=false
  WHERE oc.deleted = false;

CREATE OR REPLACE VIEW detail_occurrence_comments AS 
 SELECT oc.id,
    oc.comment,
    oc.occurrence_id,
    oc.email_address,
    oc.updated_on,
    COALESCE(oc.person_name, p.surname || ', ' || p.first_name) as person_name,
    c.username,
    o.website_id,
    oc.created_on,
    oc.created_by_id,
    c.username AS created_by,
    oc.updated_by_id,
    u.username AS updated_by,
    oc.record_status,
    oc.record_substatus,
    oc.query
   FROM occurrence_comments oc
     JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
     JOIN users c ON oc.created_by_id = c.id
     JOIN users u ON oc.updated_by_id = u.id
     JOIN people p ON p.id=c.person_id and p.deleted=false
  WHERE oc.deleted = false;


CREATE OR REPLACE VIEW list_sample_comments AS 
 SELECT sc.id,
    sc.comment,
    sc.sample_id,
    sc.email_address,
    sc.updated_on,
    COALESCE(sc.person_name, p.surname || ', ' || p.first_name) as person_name,
    u.username,
    su.website_id
   FROM sample_comments sc
     JOIN samples s ON s.id = sc.sample_id AND s.deleted = false
     JOIN surveys su ON su.id = s.survey_id
     JOIN users u ON sc.created_by_id = u.id
     JOIN people p ON p.id=u.person_id and p.deleted=false
  WHERE sc.deleted = false;

CREATE OR REPLACE VIEW detail_sample_comments AS 
 SELECT sc.id,
    sc.comment,
    sc.sample_id,
    sc.email_address,
    sc.updated_on,
    COALESCE(sc.person_name, p.surname || ', ' || p.first_name) as person_name,
    c.username,
    sc.created_on,
    sc.created_by_id,
    c.username AS created_by,
    sc.updated_by_id,
    u.username AS updated_by,
    su.website_id
   FROM sample_comments sc
     JOIN samples s ON s.id = sc.sample_id AND s.deleted = false
     JOIN users c ON sc.created_by_id = c.id
     JOIN users u ON sc.updated_by_id = u.id
     JOIN people p ON p.id=c.person_id and p.deleted=false
     JOIN surveys su ON su.id = s.survey_id
  WHERE sc.deleted = false;

  
CREATE OR REPLACE VIEW list_survey_comments AS 
 SELECT sc.id,
    sc.comment,
    sc.survey_id,
    sc.email_address,
    sc.updated_on,
    COALESCE(sc.person_name, p.surname || ', ' || p.first_name) as person_name,
    u.username,
    s.website_id
   FROM survey_comments sc
     JOIN surveys s ON s.id = sc.survey_id AND s.deleted = false
     JOIN users u ON sc.created_by_id = u.id
     JOIN people p ON p.id=u.person_id and p.deleted=false
  WHERE sc.deleted = false;

  
CREATE OR REPLACE VIEW detail_survey_comments AS 
 SELECT sc.id,
    sc.comment,
    sc.survey_id,
    sc.email_address,
    sc.updated_on,
    COALESCE(sc.person_name, p.surname || ', ' || p.first_name) as person_name,
    c.username,
    sc.created_on,
    sc.created_by_id,
    c.username AS created_by,
    sc.updated_by_id,
    u.username AS updated_by,
    s.website_id
   FROM survey_comments sc
     JOIN surveys s ON s.id = sc.survey_id AND s.deleted = false
     JOIN users c ON sc.created_by_id = c.id
     JOIN users u ON sc.updated_by_id = u.id
     JOIN people p ON p.id=c.person_id and p.deleted=false
  WHERE sc.deleted = false;
