-- Unique constraint doesn't work here due to the way that ORM save code works.
DROP INDEX IF EXISTS idx_groups_users_unique;