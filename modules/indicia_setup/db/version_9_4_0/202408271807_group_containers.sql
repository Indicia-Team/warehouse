ALTER TABLE groups
  ADD COLUMN is_container BOOLEAN DEFAULT false,
  ADD COLUMN is_contained BOOLEAN DEFAULT false;

COMMENT ON COLUMN groups.is_container IS 'Is the group a container for other sub-groups? Changes the behaviour of the group as it''s purpose is as an organisational tool for the contained groups, so only has administrator members.';
COMMENT ON COLUMN groups.is_contained IS 'Is the group a sub-group of a container group? Inherits filtering and admin members from the parent group.';