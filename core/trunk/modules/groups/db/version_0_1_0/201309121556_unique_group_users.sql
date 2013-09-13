ALTER TABLE groups_users DROP CONSTRAINT uc_groups_users;

CREATE UNIQUE INDEX idx_groups_users_unique
ON groups_users(group_id , user_id)
WHERE deleted=false;