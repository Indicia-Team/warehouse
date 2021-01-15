-- #slow script#
DROP INDEX CONCURRENTLY IF EXISTS ix_notifications_count_for_user;

CREATE INDEX ix_notifications_count_for_user
    ON notifications USING btree
    (user_id ASC NULLS LAST)
    TABLESPACE pg_default
    WHERE (source_type::text = ANY (ARRAY['VT'::character varying::text, 'PT'::character varying::text])) AND acknowledged = false;
