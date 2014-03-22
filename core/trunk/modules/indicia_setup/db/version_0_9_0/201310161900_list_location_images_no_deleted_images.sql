CREATE OR REPLACE VIEW list_location_images AS 
 SELECT li.id, li.location_id, li.path, li.caption, li.created_on, 
    li.created_by_id, li.updated_on, li.updated_by_id, li.deleted, 
    lw.website_id
   FROM location_images li
   JOIN locations l ON l.id = li.location_id
   JOIN locations_websites lw ON l.id = lw.location_id
  WHERE li.deleted = false
  ORDER BY li.id;