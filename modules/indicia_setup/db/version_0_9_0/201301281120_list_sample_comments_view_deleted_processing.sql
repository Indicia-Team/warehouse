--- Add a check on the sample and sample_comment deleted fields.
--- Returned fields are unchanges so do not need to drop the view first

CREATE OR REPLACE VIEW list_sample_comments AS
 SELECT sc.id, sc.comment, sc.sample_id, sc.email_address, sc.updated_on, sc.person_name, u.username, su.website_id
   FROM sample_comments sc
   JOIN samples s on (s.id = sc.sample_id AND s.deleted=false)
   JOIN surveys su on (su.id = s.survey_id)
   LEFT JOIN users u ON sc.created_by_id = u.id
   WHERE sc.deleted=false;
   
--- There are no gv_sample_comments or detail_sample_comments views
--- The list_occurrence_comments and gv_occurrence_comments views were updated in version_0_7_0 201104261629_grid_views.sql to not return deleted rows.
--- There is no detail_occurrence_comments
--- There is no separate location_comments table, so there are no list_location_comments, gv_location_comments or detail_location_comments views.