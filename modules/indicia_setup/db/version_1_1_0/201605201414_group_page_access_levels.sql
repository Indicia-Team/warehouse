ALTER TABLE group_pages ADD COLUMN access_level integer;
COMMENT ON COLUMN group_pages.access_level IS 'Optional minimum access level value required by the user in order to be able to use this page (in addition to the rules defined in group_pages.administrator). Null is treated as a value of zero (i.e. open access).';

ALTER TABLE groups_users ADD COLUMN access_level integer;
COMMENT ON COLUMN groups_users.access_level IS 'Access level value that this user has within this group. Unlocks access to pages with the same or lower access level. Null is treated as a value of zero';

