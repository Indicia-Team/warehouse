-- #slow script#

CREATE INDEX IF NOT EXISTS idx_notifications_email_send_priority
  ON notifications (escalate_email_priority DESC, user_id, source_type, id)
  WHERE email_sent = 'f' AND acknowledged = 'f' AND source_type <> 'T';