ALTER TABLE users ADD COLUMN allow_share_for_editing boolean DEFAULT true;
UPDATE users SET allow_share_for_editing = allow_share_for_reporting;
ALTER TABLE users ALTER COLUMN allow_share_for_editing SET NOT NULL;
COMMENT ON COLUMN users.allow_share_for_editing IS 
  'Flag set to true if the user allows their records to be edited by users on other websites that have a sharing agreement with the site they have contributed to.';
