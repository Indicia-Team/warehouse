ALTER TABLE trigger_actions
  ADD COLUMN param3 character varying;

COMMENT ON COLUMN trigger_actions.param2 IS 'Third parameter for the action if required. For email actions, specifies a comma separated list of emails to cc to.';

ALTER TABLE notifications
  ADD COLUMN cc character varying;

COMMENT ON COLUMN notifications.cc IS 'Comma separated list of emails to cc to.';