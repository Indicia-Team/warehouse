CREATE OR REPLACE VIEW list_scratchpad_lists AS
 SELECT s.id,
    s.title,
    s.description,
    s.entity,
    s.website_id,
    s.expires_on
   FROM scratchpad_lists s
  WHERE s.deleted = false;

CREATE OR REPLACE VIEW list_scratchpad_list_entries AS
 SELECT e.id,
    s.entity,
    e.scratchpad_list_id,
    e.entry_id,
    s.website_id
   FROM scratchpad_list_entries e
   JOIN scratchpad_lists s on s.id=e.scratchpad_list_id and s.deleted=false;