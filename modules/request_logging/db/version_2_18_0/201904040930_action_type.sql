ALTER TABLE request_log_entries DROP CONSTRAINT request_log_entries_io_check;

ALTER TABLE request_log_entries
  ADD CONSTRAINT request_log_entries_io_check CHECK (io = ANY (ARRAY['i'::bpchar, 'o'::bpchar, 'a'::bpchar]));

COMMENT ON COLUMN request_log_entries.io IS 'Is this request for data coming in (i) such as when posting records, out (o) such as when reporting, or another action (a).';