ALTER TABLE occurrences
ADD COLUMN downloaded_flag character(1) NOT NULL DEFAULT 'N'::bpchar, --Date occurrence downloaded out of system;
ADD COLUMN downloaded_on timestamp, --Date occurrence downloaded out of system;
ADD CONSTRAINT occurrences_downloaded_flag_check CHECK ((downloaded_flag = ANY (ARRAY['N'::bpchar, 'I'::bpchar, 'F'::bpchar])));

COMMENT ON COLUMN occurrences.downloaded_on IS 'Date occurrence downloaded out of system'; 
COMMENT ON COLUMN occurrences.downloaded_flag IS 'Downloaded status flag: N - not downloaded, I - Initial download, F - Final download'; 