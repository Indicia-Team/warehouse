
COMMENT ON COLUMN milestones.entity IS 'Indication of what is being counted: T, S, O or M for taxa, samples, occurrences or media files.';

ALTER TABLE milestones
   ADD COLUMN send_to_user BOOLEAN NOT NULL DEFAULT TRUE;
COMMENT ON COLUMN milestones.send_to_user IS 'Indicate if notifications are to be sent to the user.';

ALTER TABLE milestones
   ADD COLUMN admin_emails character varying NOT NULL DEFAULT '';
COMMENT ON COLUMN milestones.admin_emails IS 'Comma separated email address list, where additional messages are sent.';

