ALTER TABLE scratchpad_lists
ADD COLUMN metadata JSON;

COMMENT ON COLUMN scratchpad_lists.metadata IS 'Allows custom additional information to be stored alongside a scratchpad list entry in a JSON object, e.g. likelihood of a species in a list.';