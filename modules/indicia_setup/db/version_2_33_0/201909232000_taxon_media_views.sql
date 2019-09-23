CREATE OR REPLACE VIEW list_taxon_media AS
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id, tm.media_type_id, ctt.term as media_type,
     l.id as licence_id, l.code as licence_code, l.title as licence_title, tm.external_details as external_details
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id
   LEFT JOIN licences l on l.id=tm.licence_id AND l.deleted=false
  WHERE tm.deleted = false;

CREATE OR REPLACE VIEW detail_taxon_media AS
 SELECT tm.id, tm.path, tm.caption, tm.deleted, tm.taxon_meaning_id,
     tm.created_by_id, c.username AS created_by, tm.updated_by_id, u.username AS updated_by,
     tm.media_type_id, ctt.term as media_type,
     l.id as licence_id, l.code as licence_code, l.title as licence_title, tm.external_details as external_details
   FROM taxon_media tm
   JOIN cache_termlists_terms ctt on ctt.id=tm.media_type_id
   JOIN users c ON c.id = tm.created_by_id
   JOIN users u ON u.id = tm.updated_by_id
   LEFT JOIN licences l on l.id=tm.licence_id AND l.deleted=false
  WHERE tm.deleted = false;