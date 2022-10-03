CREATE OR REPLACE VIEW list_location_comments
 AS
 SELECT lc.id,
    lc.comment,
    lc.location_id,
    lc.email_address,
    lc.updated_on,
    COALESCE(lc.person_name, ((p.surname::text || ', '::text) || p.first_name::text)::character varying) AS person_name,
    u.username,
    lw.website_id,
    lc.reply_to_id
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
    lw.website_id,
    lc.reply_to_id
   FROM location_comments lc
     JOIN locations l ON l.id = lc.location_id AND l.deleted = false
     JOIN users c ON lc.created_by_id = c.id
     JOIN users u ON lc.updated_by_id = u.id
     JOIN people p ON p.id = c.person_id AND p.deleted = false
	 LEFT JOIN locations_websites lw ON lw.location_id=l.id AND lw.deleted=false
  WHERE lc.deleted = false;

CREATE OR REPLACE VIEW list_occurrence_comments AS
 SELECT oc.id,
     oc.comment,
     oc.occurrence_id,
     oc.email_address,
     oc.updated_on,
     COALESCE(p.surname || ', ' || p.first_name, oc.person_name)::varchar AS person_name,
     u.username,
     o.website_id,
     oc.record_status,
     oc.record_substatus,
     oc.query,
     oc.confidential,
     oc.correspondence_data,
     oc.reply_to_id
   FROM occurrence_comments oc
   JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
   LEFT JOIN users u ON oc.created_by_id = u.id and u.id<>1
   LEFT JOIN people p ON p.id = u.person_id AND p.deleted = false
  WHERE oc.deleted = false;

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
    oc.external_key,
    oc.confidential,
    oc.correspondence_data,
    oc.reply_to_id
   FROM occurrence_comments oc
     JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
     JOIN users c ON oc.created_by_id = c.id
     JOIN users u ON oc.updated_by_id = u.id
     LEFT JOIN people p ON p.id = c.person_id AND p.deleted = false AND c.created_by_id <> 1
  WHERE oc.deleted = false;

CREATE OR REPLACE VIEW list_sample_comments AS
 SELECT sc.id,
    sc.comment,
    sc.sample_id,
    sc.email_address,
    sc.updated_on,
    COALESCE(sc.person_name, p.surname || ', ' || p.first_name) as person_name,
    u.username,
    su.website_id,
    sc.reply_to_id
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
    COALESCE(sc.person_name, ((p.surname::text || ', '::text) || p.first_name::text)::character varying) AS person_name,
    c.username,
    sc.created_on,
    sc.created_by_id,
    c.username AS created_by,
    sc.updated_by_id,
    u.username AS updated_by,
    su.website_id,
    sc.external_key,
    sc.reply_to_id
   FROM sample_comments sc
     JOIN samples s ON s.id = sc.sample_id AND s.deleted = false
     JOIN users c ON sc.created_by_id = c.id
     JOIN users u ON sc.updated_by_id = u.id
     JOIN people p ON p.id = c.person_id AND p.deleted = false
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
    s.website_id,
    sc.reply_to_id
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
    s.website_id,
    sc.reply_to_id
   FROM survey_comments sc
     JOIN surveys s ON s.id = sc.survey_id AND s.deleted = false
     JOIN users c ON sc.created_by_id = c.id
     JOIN users u ON sc.updated_by_id = u.id
     JOIN people p ON p.id=c.person_id and p.deleted=false
  WHERE sc.deleted = false;