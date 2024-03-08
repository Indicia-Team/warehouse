DELETE FROM groups_locations gl1
USING groups_locations gl2
WHERE gl2.deleted=false
AND gl1.deleted=false
AND gl2.group_id=gl1.group_id
AND gl2.location_id=gl1.location_id
AND gl2.id>gl1.id

CREATE UNIQUE INDEX IF NOT EXISTS ix_groups_location_unique ON groups_locations(group_id, location_id)
WHERE deleted=false;