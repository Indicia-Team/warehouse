-- Script to create views for webservice access to locations_scratchpad_lists and groups_scratchpad_lists
-- View for locations_scratchpad_lists

CREATE OR REPLACE VIEW list_locations_scratchpad_lists AS
SELECT
  lsl.id,
  lsl.location_id,
  lsl.scratchpad_list_id,
  lsl.created_on,
  lsl.created_by_id,
  lsl.updated_on,
  lsl.updated_by_id,
  sl.title AS scratchpad_list_title,
  sl.website_id,
  loc.name AS location_name
FROM locations_scratchpad_lists lsl
JOIN scratchpad_lists sl ON lsl.scratchpad_list_id = sl.id AND NOT sl.deleted
JOIN locations loc ON lsl.location_id = loc.id AND NOT loc.deleted
WHERE NOT lsl.deleted;

-- View for groups_scratchpad_lists

CREATE OR REPLACE VIEW list_groups_scratchpad_lists AS
SELECT
  gsl.id,
  gsl.group_id,
  gsl.scratchpad_list_id,
  gsl.created_on,
  gsl.created_by_id,
  gsl.updated_on,
  gsl.updated_by_id,
  gsl.deleted AS gsl_deleted,
  sl.title AS scratchpad_list_title,
  sl.website_id,
  grp.title AS group_title
FROM groups_scratchpad_lists gsl
JOIN scratchpad_lists sl ON gsl.scratchpad_list_id = sl.id AND NOT sl.deleted
JOIN groups grp ON gsl.group_id = grp.id AND NOT grp.deleted
WHERE NOT gsl.deleted;

-- Add metadata to view for scratchpad_list_entries.
CREATE OR REPLACE VIEW list_scratchpad_list_entries AS
 SELECT e.id,
    s.entity,
    e.scratchpad_list_id,
    e.entry_id,
    s.website_id,
    e.metadata,
    e.created_on,
    e.created_by_id,
    e.updated_on,
    e.updated_by_id
   FROM scratchpad_list_entries e
   JOIN scratchpad_lists s on s.id=e.scratchpad_list_id and s.deleted=false
   WHERE e.deleted=false;

