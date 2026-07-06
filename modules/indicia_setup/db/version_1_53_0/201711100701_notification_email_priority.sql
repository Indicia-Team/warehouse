ALTER TABLE notifications
  ADD COLUMN escalate_email_priority integer default null,
  ADD CONSTRAINT notifications_escalate_email_priority_check
    CHECK (escalate_email_priority= ANY(ARRAY[1::integer, 2::integer]));