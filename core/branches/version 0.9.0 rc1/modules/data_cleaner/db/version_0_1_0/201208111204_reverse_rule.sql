ALTER TABLE verification_rules ADD COLUMN reverse_rule boolean;
ALTER TABLE verification_rules ALTER COLUMN reverse_rule SET DEFAULT false;
UPDATE verification_rules SET reverse_rule=false;
ALTER TABLE verification_rules ALTER COLUMN reverse_rule SET NOT NULL;

COMMENT ON COLUMN verification_rules.reverse_rule IS 'Set to true to cause a rule violation if this rule definition passes the test rather than fails.';
