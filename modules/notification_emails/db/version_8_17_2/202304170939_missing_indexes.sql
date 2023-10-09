CREATE INDEX IF NOT EXISTS fki_user_email_notification_settings_user_id
    ON user_email_notification_settings USING btree
    (user_id ASC NULLS LAST)
    TABLESPACE pg_default;