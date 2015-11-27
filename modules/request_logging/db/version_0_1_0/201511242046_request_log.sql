CREATE TABLE request_log_entries
(
id serial NOT NULL,
io character(1) NOT NULL,
service character varying NOT NULL,
resource character varying,
website_id integer,
user_id integer,
request_parameters_get json,
request_parameters_post json,
start_timestamp float,
duration float,
exception_msg character varying,
response_size integer,
CONSTRAINT pk_request_log_entries PRIMARY KEY (id),
CONSTRAINT request_log_entries_io_check CHECK (io = ANY (ARRAY['i'::bpchar, 'o'::bpchar]))
)
WITH (
OIDS = FALSE
)
;
COMMENT ON TABLE request_log_entries
IS 'List of web service requests logged by the request_log module.';
COMMENT ON COLUMN request_log_entries.io IS 'Is this request for data coming in (i) such as when posting records, or out (o) such as when reporting.';
COMMENT ON COLUMN request_log_entries.service IS 'Name of the web serive, e.g. data, report';
COMMENT ON COLUMN request_log_entries.resource IS 'Database entity, report name or other resource being accessed';
COMMENT ON COLUMN request_log_entries.website_id IS 'ID of the client website making the request if known';
COMMENT ON COLUMN request_log_entries.user_id IS 'ID of the warehouse user account making the request if known';
COMMENT ON COLUMN request_log_entries.request_parameters_get IS 'GET parameters in JSON notation';
COMMENT ON COLUMN request_log_entries.request_parameters_post IS 'POST parameters in JSON notation';
COMMENT ON COLUMN request_log_entries.start_timestamp IS 'Unix timestamp of the request';
COMMENT ON COLUMN request_log_entries.duration IS 'Duration in seconds';
COMMENT ON COLUMN request_log_entries.exception_msg IS 'If an exception occurred so the request failed, logs the message';
COMMENT ON COLUMN request_log_entries.response_size IS 'Length of the response in bytes';