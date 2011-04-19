CREATE OR REPLACE VIEW gv_taxon_images AS 
 SELECT id, path, caption,deleted, taxon_meaning_id
   FROM taxon_images;
   
CREATE OR REPLACE VIEW gv_occurrence_images AS 
 SELECT id, path, caption,deleted, occurrence_id
   FROM occurrence_images;