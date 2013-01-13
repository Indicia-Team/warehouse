ALTER TABLE users ADD COLUMN allow_share_for_reporting boolean;
ALTER TABLE users ALTER COLUMN allow_share_for_reporting SET DEFAULT true;
UPDATE users SET allow_share_for_reporting=true;
ALTER TABLE users ALTER COLUMN allow_share_for_reporting SET NOT NULL;
COMMENT ON COLUMN users.allow_share_for_reporting IS 'Flag set to true if the user allows their records to be reported by users on other websites that have a sharing agreement with the site they have contributed to.';

ALTER TABLE users ADD COLUMN allow_share_for_peer_review boolean;
ALTER TABLE users ALTER COLUMN allow_share_for_peer_review SET DEFAULT true;
UPDATE users SET allow_share_for_peer_review=true;
ALTER TABLE users ALTER COLUMN allow_share_for_peer_review SET NOT NULL;
COMMENT ON COLUMN users.allow_share_for_peer_review IS 'Flag set to true if the user allows their records to be reviewed by users on other websites that have a sharing agreement with the site they have contributed to.';

ALTER TABLE users ADD COLUMN allow_share_for_verification boolean;
ALTER TABLE users ALTER COLUMN allow_share_for_verification SET DEFAULT true;
UPDATE users SET allow_share_for_verification=true;
ALTER TABLE users ALTER COLUMN allow_share_for_verification SET NOT NULL;
COMMENT ON COLUMN users.allow_share_for_verification IS 'Flag set to true if the user allows their records to be verified by users on other websites that have a sharing agreement with the site they have contributed to.';

ALTER TABLE users ADD COLUMN allow_share_for_data_flow boolean;
ALTER TABLE users ALTER COLUMN allow_share_for_data_flow SET DEFAULT true;
UPDATE users SET allow_share_for_data_flow=true;
ALTER TABLE users ALTER COLUMN allow_share_for_data_flow SET NOT NULL;
COMMENT ON COLUMN users.allow_share_for_data_flow IS 'Flag set to true if the user allows their records to be passed on by other websites that have a sharing agreement with the site they have contributed to.';

ALTER TABLE users ADD COLUMN allow_share_for_moderation boolean;
ALTER TABLE users ALTER COLUMN allow_share_for_moderation SET DEFAULT true;
UPDATE users SET allow_share_for_moderation=true;
ALTER TABLE users ALTER COLUMN allow_share_for_moderation SET NOT NULL;
COMMENT ON COLUMN users.allow_share_for_moderation IS 'Flag set to true if the user allows their records to be moderated by users on other websites that have a sharing agreement with the site they have contributed to.';

