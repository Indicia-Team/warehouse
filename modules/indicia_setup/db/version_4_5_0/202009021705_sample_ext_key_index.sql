CREATE INDEX ix_sample_external_key
    ON samples USING btree
    (external_key)
    TABLESPACE pg_default;