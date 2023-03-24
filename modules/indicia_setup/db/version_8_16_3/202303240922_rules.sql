ALTER TABLE custom_verification_rules
DROP CONSTRAINT chk_custom_verification_rules_rule_type;

UPDATE custom_verification_rules SET rule_type='species recorded' where rule_type='species_recorded';

ALTER TABLE custom_verification_rules
ADD CONSTRAINT chk_custom_verification_rules_rule_type CHECK (rule_type=ANY(ARRAY['abundance','geography','phenology','period','species recorded']));

COMMENT ON COLUMN custom_verification_rules.rule_type IS 'Type of rule, either abundance, geography, phenology, period or species recorded.';