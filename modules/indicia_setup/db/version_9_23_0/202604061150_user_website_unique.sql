-- #slow script#

-- Remove stale duplicate users_websites data which occurred due to lack of uniqueness check.
DELETE FROM users_websites uw
WHERE id IN (
  -- Stale duplicates will have row number > 1.
  SELECT id FROM (
    SELECT id,
      ROW_NUMBER() OVER (PARTITION BY user_id, website_id ORDER BY created_on DESC) AS rn
    FROM users_websites
  ) sub
  WHERE sub.rn > 1
);

-- Remove an incorrectly named index, which is unused anyway.
DROP INDEX IF EXISTS fki_sample_licence;

-- Another unused index.
DROP INDEX IF EXISTS fki_users_websites_media_licence;

-- This one is used, but will be replaced by a unique index on the combination of user_id and website_id, so drop it first.
DROP INDEX IF EXISTS fki_users_websites_user;

CREATE UNIQUE INDEX IF NOT EXISTS ix_unique_users_website
    ON users_websites USING btree
    (user_id ASC, website_id ASC);
