-- View: gv_location_images

-- DROP VIEW gv_location_images;

CREATE OR REPLACE VIEW gv_location_images AS 
 SELECT location_images.id, location_images.path, location_images.caption, location_images.deleted, location_images.location_id
   FROM location_images;

-- View: gv_sample_images

-- DROP VIEW gv_sample_images;

CREATE OR REPLACE VIEW gv_sample_images AS 
 SELECT sample_images.id, sample_images.path, sample_images.caption, sample_images.deleted, sample_images.sample_id
   FROM sample_images;