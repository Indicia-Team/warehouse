ALTER TABLE verification_rules ADD COLUMN deleted boolean;
ALTER TABLE verification_rules ALTER COLUMN deleted SET DEFAULT false;
UPDATE verification_rules SET deleted=false;
ALTER TABLE verification_rules ALTER COLUMN deleted SET NOT NULL;
COMMENT ON COLUMN verification_rules.deleted IS 'Has this record been deleted?';

ALTER TABLE verification_rule_data ADD COLUMN deleted boolean;
ALTER TABLE verification_rule_data ALTER COLUMN deleted SET DEFAULT false;
UPDATE verification_rule_data SET deleted=false;
ALTER TABLE verification_rule_data ALTER COLUMN deleted SET NOT NULL;
COMMENT ON COLUMN verification_rule_data.deleted IS 'Has this record been deleted?';

ALTER TABLE verification_rule_metadata ADD COLUMN deleted boolean;
ALTER TABLE verification_rule_metadata ALTER COLUMN deleted SET DEFAULT false;
UPDATE verification_rule_metadata SET deleted=false;
ALTER TABLE verification_rule_metadata ALTER COLUMN deleted SET NOT NULL;
COMMENT ON COLUMN verification_rule_metadata.deleted IS 'Has this record been deleted?';