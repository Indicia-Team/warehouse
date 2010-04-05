-- Deleted column not used in users_websites as we nullify the site role id if they lose access.
UPDATE users_websites
SET site_role_id=null 
WHERE deleted='true';

ALTER TABLE users_websites
DROP COLUMN deleted CASCADE;