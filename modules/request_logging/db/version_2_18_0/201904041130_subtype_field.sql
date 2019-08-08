ALTER TABLE request_log_entries ADD COLUMN subtype character varying;

COMMENT ON COLUMN request_log_entries.subtype IS 'Additional information about the subtype of request, e.g. count for reporting.';