CREATE INDEX IF NOT EXISTS fki_user_email_notification_settings_user_id
    ON user_email_notification_settings USING btree
    (user_id ASC NULLS LAST)
    TABLESPACE pg_default;

CREATE INDEX IF NOT EXISTS fki_trigger_actions_trigger_id
    ON trigger_actions USING btree
    (trigger_id ASC NULLS LAST)
    TABLESPACE pg_default;