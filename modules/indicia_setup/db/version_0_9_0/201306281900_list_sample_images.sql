CREATE OR REPLACE VIEW list_sample_images AS 
 SELECT si.id, si.sample_id, si.path, si.caption, si.created_on, 
    si.created_by_id, si.updated_on, si.updated_by_id, si.deleted, 
    su.website_id
   FROM sample_images si
   JOIN samples s ON s.id = si.sample_id AND s.deleted = false
   JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
  WHERE si.deleted = false;
