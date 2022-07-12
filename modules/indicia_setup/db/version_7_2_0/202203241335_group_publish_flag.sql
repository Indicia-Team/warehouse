ALTER TABLE groups
  ADD COLUMN published bool DEFAULT false;

COMMENT ON COLUMN groups.published IS 'Set to true if the data for this group should be published via any automated publishing tool. For example the data can be sent to the NBN Atlas or GBIF.';