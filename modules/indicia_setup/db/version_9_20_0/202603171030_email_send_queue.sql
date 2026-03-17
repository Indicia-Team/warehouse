CREATE TABLE IF NOT EXISTS email_send_queue (
  id serial NOT NULL,
  status character(1) NOT NULL DEFAULT 'Q',
  queued_on timestamp without time zone NOT NULL DEFAULT now(),
  sent_on timestamp without time zone,
  recipients json NOT NULL,
  cc json,
  subject text NOT NULL,
  body text NOT NULL,
  from_email text NOT NULL,
  from_name text,
  escalate_email_priority integer,
  attachment_info json,
  email_type text NOT NULL,
  email_subtype text,
  group_key text NOT NULL,
  attempts integer NOT NULL DEFAULT 0,
  error_message text,
  CONSTRAINT pk_email_send_queue PRIMARY KEY (id),
  CONSTRAINT chk_email_send_queue_status CHECK (status = ANY(ARRAY['Q'::bpchar, 'S'::bpchar, 'F'::bpchar])),
  CONSTRAINT chk_email_send_queue_escalate_email_priority CHECK (escalate_email_priority IS NULL OR escalate_email_priority = ANY(ARRAY[1, 2]))
);

COMMENT ON TABLE email_send_queue IS 'Deferred email payloads queued when hourly send cap is reached.';
COMMENT ON COLUMN email_send_queue.id IS 'Primary key and unique identifier for the queued email row.';
COMMENT ON COLUMN email_send_queue.status IS 'Q queued, S sent, F permanently failed.';
COMMENT ON COLUMN email_send_queue.queued_on IS 'Timestamp the email was queued.';
COMMENT ON COLUMN email_send_queue.sent_on IS 'Timestamp the queued email was sent.';
COMMENT ON COLUMN email_send_queue.recipients IS 'JSON array of recipients, each with email and optional name.';
COMMENT ON COLUMN email_send_queue.cc IS 'JSON array of CC recipients, each with email and optional name.';
COMMENT ON COLUMN email_send_queue.subject IS 'Email subject.';
COMMENT ON COLUMN email_send_queue.body IS 'Email body payload (typically HTML).';
COMMENT ON COLUMN email_send_queue.from_email IS 'Sender email address.';
COMMENT ON COLUMN email_send_queue.from_name IS 'Optional sender display name.';
COMMENT ON COLUMN email_send_queue.escalate_email_priority IS 'NULL normal, 1 urgent immediate send, 2 urgent immediate send and flag high priority.';
COMMENT ON COLUMN email_send_queue.attachment_info IS 'Optional JSON attachment payload with filename, mimeType and data.';
COMMENT ON COLUMN email_send_queue.email_type IS 'System component that caused the email, e.g. notifications.';
COMMENT ON COLUMN email_send_queue.email_subtype IS 'Optional additional source detail, e.g. notification type.';
COMMENT ON COLUMN email_send_queue.group_key IS 'Grouping key used to merge queued notification emails.';
COMMENT ON COLUMN email_send_queue.attempts IS 'Count of send attempts for this queued email.';
COMMENT ON COLUMN email_send_queue.error_message IS 'Last error message when a send attempt failed.';

CREATE INDEX IF NOT EXISTS idx_email_send_queue_status_escalate_time
  ON email_send_queue (status, escalate_email_priority DESC, queued_on ASC);

CREATE INDEX IF NOT EXISTS idx_email_send_queue_group_key
  ON email_send_queue (group_key)
  WHERE status = 'Q';
