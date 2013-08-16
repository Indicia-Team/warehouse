CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

ALTER TABLE notifications DROP CONSTRAINT chk_notification_source_type;

ALTER TABLE notifications
  ADD CONSTRAINT chk_notification_source_type CHECK (source_type::text = 'T'::bpchar::text OR source_type::text = 'V'::bpchar::text OR source_type::text = 'C'::bpchar::text OR source_type::text = 'A'::bpchar::text);

IF NOT EXISTS(SELECT 1 FROM information_schema.columns  
              WHERE table_name='notifications' and column_name='source_detail') THEN
  ALTER TABLE notifications ADD COLUMN source_detail character varying;

END IF;
  
COMMENT ON COLUMN notifications.source_type IS 'Defines the type of source of this notification, as described in the source. Value can be T (= trigger), C (= comment), V (= verification), A (= automated record check).';
COMMENT ON COLUMN notifications.source_detail IS 'Details on the source of the notification. Could be the rule that generated it, or the occurrence comment ID that caused it for example.';

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();