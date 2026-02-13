-- Need db metadata in scratchpad_list_entries now that we are allowing metadata editing.
ALTER TABLE scratchpad_list_entries
  ADD COLUMN created_on timestamp without time zone,
  ADD COLUMN created_by_id integer,
  ADD COLUMN updated_on timestamp without time zone,
  ADD COLUMN updated_by_id integer,
  ADD COLUMN deleted boolean NOT NULL DEFAULT false;

-- Fill in best guess values for created_on, created_by_id, updated_on,
-- updated_by_id based on the scratchpad_lists table.
UPDATE scratchpad_list_entries e
SET
  created_on = s.created_on,
  created_by_id = s.created_by_id,
  updated_on = s.updated_on,
  updated_by_id = s.updated_by_id
FROM scratchpad_lists s
WHERE e.scratchpad_list_id = s.id;

ALTER TABLE scratchpad_list_entries
  ALTER COLUMN created_on SET NOT NULL,
  ALTER COLUMN created_by_id SET NOT NULL,
  ALTER COLUMN updated_on SET NOT NULL,
  ALTER COLUMN updated_by_id SET NOT NULL;