CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN

ALTER TABLE notifications ADD COLUMN linked_id integer;
CREATE INDEX ix_notifications_linked_id ON notifications(linked_id);
   
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON COLUMN notifications.linked_id IS 'ID of the record this notification is linked to, such as an occurrence record. The record''s table depends on the type of notification.';

UPDATE notifications 
SET linked_id=cast(substring(data from '"occurrence_id":"(\d+)"') as integer)
WHERE source='Verifications and comments';