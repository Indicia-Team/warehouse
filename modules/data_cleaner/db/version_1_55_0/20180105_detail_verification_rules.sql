
DROP VIEW IF EXISTS detail_verification_rules;
CREATE VIEW detail_verification_rules AS
  SELECT id, title, description, test_type, error_message, reverse_rule, created_on
FROM verification_rules
WHERE deleted=false;

DROP VIEW IF EXISTS detail_verification_rule_metadata;
CREATE VIEW  detail_verification_rule_metadata AS
  SELECT id, verification_rule_id, key, value
FROM verification_rule_metadata
WHERE deleted=false;