ALTER TABLE groups
  ADD COLUMN container BOOLEAN DEFAULT false,
  ADD COLUMN contained_by_group_id INTEGER,
  ADD CONSTRAINT fk_groups_contained_by_group_id FOREIGN KEY (contained_by_group_id)
    REFERENCES groups (id);

COMMENT ON COLUMN groups.container IS 'Is the group a container for other sub-groups? Changes the behaviour of the group as it''s purpose is as an organisational tool for the contained groups, so only has administrator members.';
COMMENT ON COLUMN groups.contained_by_group_id IS 'If the group is contained by another group, points to the container. Inherits filtering and admin members from the parent group.';

CREATE INDEX fki_groups_contained_by_group_id ON groups(contained_by_group_id);