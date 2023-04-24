CREATE INDEX IF NOT EXISTS fki_trigger_actions_trigger_id
    ON trigger_actions USING btree
    (trigger_id ASC NULLS LAST)
    TABLESPACE pg_default;