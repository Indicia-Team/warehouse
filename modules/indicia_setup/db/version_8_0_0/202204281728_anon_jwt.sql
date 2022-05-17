--ALTER TABLE websites
--  ADD COLUMN allow_anon_jwt_post boolean not null default false;

COMMENT ON COLUMN websites.allow_anon_jwt_post IS 'Set to TRUE if anonymous JWT tokens which don''t claim a user ID allow new records to be POSTed.';