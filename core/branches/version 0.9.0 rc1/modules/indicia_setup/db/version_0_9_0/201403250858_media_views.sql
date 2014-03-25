
DROP VIEW IF EXISTS list_occurrence_images;

CREATE OR REPLACE VIEW list_occurrence_media AS 
 SELECT om.id, om.occurrence_id, om.path, om.caption, om.created_on, om.created_by_id, om.updated_on, om.updated_by_id, om.deleted, 
    om.external_details, o.website_id, om.media_type_id, ctt.term as media_type
   FROM occurrence_media om
   JOIN cache_termlists_terms ctt on ctt.id=om.media_type_id
   JOIN occurrences o ON o.id = om.occurrence_id AND o.deleted = false
  WHERE om.deleted = false;

DROP VIEW IF EXISTS gv_occurrence_images;

CREATE OR REPLACE VIEW gv_occurrence_media AS 
 SELECT om.id, om.path, om.caption, om.deleted, om.occurrence_id, ctt.term as media_type
   FROM occurrence_media om
   JOIN cache_termlists_terms ctt on ctt.id=om.media_type_id
  WHERE om.deleted = false;

DROP VIEW IF EXISTS list_sample_images;

CREATE OR REPLACE VIEW list_sample_media AS 
 SELECT sm.id, sm.sample_id, sm.path, sm.caption, sm.created_on, sm.created_by_id, sm.updated_on, sm.updated_by_id, sm.deleted, 
    su.website_id, sm.media_type_id, ctt.term as media_type
   FROM sample_media sm
   JOIN cache_termlists_terms ctt on ctt.id=sm.media_type_id
   JOIN samples s ON s.id = sm.sample_id AND s.deleted = false
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
  WHERE sm.deleted = false;

DROP VIEW IF EXISTS gv_sample_images;

CREATE OR REPLACE VIEW gv_sample_media AS 
 SELECT sm.id, sm.path, sm.caption, sm.deleted, sm.sample_id, ctt.term as media_type
   FROM sample_media sm
   JOIN cache_termlists_terms ctt on ctt.id=sm.media_type_id
  WHERE deleted = false;

DROP VIEW IF EXISTS list_taxon_images;

CREATE OR REPLACE VIEW list_taxon_media AS 
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id, tm.media_type_id, ctt.term as media_type
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id 
  WHERE tm.deleted = false;

DROP VIEW IF EXISTS gv_taxon_images;

CREATE OR REPLACE VIEW gv_taxon_media AS 
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id, ctt.term as media_type
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id
  WHERE tm.deleted = false;

DROP VIEW IF EXISTS detail_taxon_images;

CREATE OR REPLACE VIEW detail_taxon_media AS 
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id, 
     tm.created_by_id, c.username AS created_by, tm.updated_by_id, u.username AS updated_by,
     tm.media_type_id, ctt.term as media_type
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id
   JOIN users c ON c.id = tm.created_by_id
   JOIN users u ON u.id = tm.updated_by_id
  WHERE tm.deleted = false;

DROP VIEW IF EXISTS list_location_images;

CREATE OR REPLACE VIEW list_location_media AS 
 SELECT lm.id, lm.location_id, lm.path, lm.caption, lm.created_on, lm.created_by_id, 
     lm.updated_on, lm.updated_by_id, lm.deleted, lw.website_id,
     lm.media_type_id, ctt.term as media_type
   FROM location_media lm
   JOIN cache_termlists_terms ctt on ctt.id=lm.media_type_id
   JOIN locations l ON l.id = lm.location_id AND l.deleted = false
   LEFT JOIN locations_websites lw ON l.id = lw.location_id AND lw.deleted = false
  WHERE lm.deleted = false
  ORDER BY lm.id;

DROP VIEW IF EXISTS gv_location_images;

CREATE OR REPLACE VIEW gv_location_media AS 
 SELECT lm.id, lm.path, lm.caption, lm.deleted, lm.location_id, ctt.term as media_type
   FROM location_media lm
   JOIN cache_termlists_terms ctt on ctt.id=lm.media_type_id
  WHERE lm.deleted = false;

-- Views to provide proxy access to the media tables for legacy code that refers to the images tables.
CREATE OR REPLACE VIEW occurrence_images AS 
 SELECT id, occurrence_id, path, caption, created_on, created_by_id, updated_on, updated_by_id, deleted, external_details, media_type_id
   FROM occurrence_media;

CREATE OR REPLACE VIEW sample_images AS 
 SELECT id, sample_id, path, caption, created_on, created_by_id, updated_on, updated_by_id, deleted, media_type_id
   FROM sample_media;

CREATE OR REPLACE VIEW taxon_images AS 
 SELECT id, taxon_meaning_id, path, caption, created_on, created_by_id, updated_on, updated_by_id, deleted, media_type_id
   FROM taxon_media;

CREATE OR REPLACE VIEW location_images AS 
 SELECT id, location_id, path, caption, created_on, created_by_id, updated_on, updated_by_id, deleted, media_type_id
   FROM location_media;