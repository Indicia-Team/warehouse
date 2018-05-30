CREATE OR REPLACE VIEW list_occurrence_media AS
 SELECT om.id, om.occurrence_id, om.path, om.caption, om.created_on, om.created_by_id, om.updated_on, om.updated_by_id, om.deleted,
    om.external_details, o.website_id, om.media_type_id, ctt.term as media_type,
    l.id as licence_id, l.code as licence_code, l.title as licence_title
   FROM occurrence_media om
   JOIN cache_termlists_terms ctt on ctt.id=om.media_type_id
   JOIN occurrences o ON o.id = om.occurrence_id AND o.deleted = false
   LEFT JOIN licences l on l.id=om.licence_id AND l.deleted=false
  WHERE om.deleted = false;

CREATE OR REPLACE VIEW list_sample_media AS
 SELECT sm.id, sm.sample_id, sm.path, sm.caption, sm.created_on, sm.created_by_id, sm.updated_on, sm.updated_by_id, sm.deleted,
    su.website_id, sm.media_type_id, ctt.term as media_type,
    l.id as licence_id, l.code as licence_code, l.title as licence_title
   FROM sample_media sm
   JOIN cache_termlists_terms ctt on ctt.id=sm.media_type_id
   JOIN samples s ON s.id = sm.sample_id AND s.deleted = false
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
   LEFT JOIN licences l on l.id=sm.licence_id AND l.deleted=false
  WHERE sm.deleted = false;

CREATE OR REPLACE VIEW list_location_media AS
 SELECT lm.id, lm.location_id, lm.path, lm.caption, lm.created_on, lm.created_by_id,
     lm.updated_on, lm.updated_by_id, lm.deleted, lw.website_id,
     lm.media_type_id, ctt.term as media_type,
     lc.id as licence_id, lc.code as licence_code, lc.title as licence_title
   FROM location_media lm
   JOIN cache_termlists_terms ctt on ctt.id=lm.media_type_id
   JOIN locations l ON l.id = lm.location_id AND l.deleted = false
   LEFT JOIN locations_websites lw ON l.id = lw.location_id AND lw.deleted = false
   LEFT JOIN licences lc on lc.id=lm.licence_id AND lc.deleted=false
  WHERE lm.deleted = false
  ORDER BY lm.id;

CREATE OR REPLACE VIEW list_taxon_media AS
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id, tm.media_type_id, ctt.term as media_type,
     l.id as licence_id, l.code as licence_code, l.title as licence_title
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id
   LEFT JOIN licences l on l.id=tm.licence_id AND l.deleted=false
  WHERE tm.deleted = false;

CREATE OR REPLACE VIEW detail_taxon_media AS
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id,
     tm.created_by_id, c.username AS created_by, tm.updated_by_id, u.username AS updated_by,
     tm.media_type_id, ctt.term as media_type,
     l.id as licence_id, l.code as licence_code, l.title as licence_title
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id
   JOIN users c ON c.id = tm.created_by_id
   JOIN users u ON u.id = tm.updated_by_id
   LEFT JOIN licences l on l.id=tm.licence_id AND l.deleted=false
  WHERE tm.deleted = false;

CREATE OR REPLACE VIEW list_survey_media AS
 SELECT sm.id, sm.survey_id, sm.path, sm.caption, sm.created_on, sm.created_by_id, sm.updated_on, sm.updated_by_id,
   sm.deleted, s.website_id, sm.media_type_id, ctt.term AS media_type,
   l.id as licence_id, l.code as licence_code, l.title as licence_title
   FROM survey_media sm
   JOIN cache_termlists_terms ctt ON ctt.id = sm.media_type_id
   JOIN surveys s ON s.id = sm.survey_id AND s.deleted = false
   LEFT JOIN licences l on l.id=sm.licence_id AND l.deleted=false
  WHERE sm.deleted = false;