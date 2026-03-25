ALTER TABLE surveys ADD COLUMN hide_emails_from_verifiers BOOLEAN NOT NULL DEFAULT FALSE;

COMMENT ON COLUMN surveys.hide_emails_from_verifiers IS 'Whether to hide the email addresses of recorders from verifiers. This is used for surveys where the recorders are unlikely to be in a position to reply to verification queries.';