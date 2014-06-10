CREATE OR REPLACE function f_add_notifications_new_email_column (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

ALTER TABLE notifications ADD COLUMN email_sent boolean NOT NULL DEFAULT false;
  
COMMENT ON COLUMN notifications.email_sent IS 'Has an email been sent to the person receiving the notification? This ensures we do not send the email twice.';

END
$func$;

SELECT f_add_notifications_new_email_column();

DROP FUNCTION f_add_notifications_new_email_column();