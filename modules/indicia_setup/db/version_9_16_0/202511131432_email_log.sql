CREATE TABLE email_log_entries (
  id serial NOT NULL,
  from_email text NOT NULL,
  from_name text,
  recipients json NOT NULL,
  cc json,
  subject text NOT NULL,
  body text NOT NULL,
  email_type text NOT NULL,
  email_subtype text,
  sent_on timestamp without time zone NOT NULL,
  error_message text
);

COMMENT ON TABLE email_log_entries IS 'A log of emails sent, if email logging enabled.';
COMMENT ON COLUMN email_log_entries.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN email_log_entries.from_email IS 'Email sender.';
COMMENT ON COLUMN email_log_entries.recipients IS 'JSON array of recipients, each with an email address and optional name.';
COMMENT ON COLUMN email_log_entries.subject IS 'Email subject.';
COMMENT ON COLUMN email_log_entries.body IS 'Email body.';
COMMENT ON COLUMN email_log_entries.email_type IS 'System component that caused the email, e.g. notifications.';
COMMENT ON COLUMN email_log_entries.email_subtype IS 'Optional additional info to identify the source of the email, e.g. the notification type.';
COMMENT ON COLUMN email_log_entries.sent_on IS 'Date and time this email was sent.';
COMMENT ON COLUMN email_log_entries.error_message IS 'If the email failed to send and an exception caught, the message from the exception.';
