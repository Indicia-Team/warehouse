ALTER TABLE occurrences
ADD COLUMN use_determination character(1) NOT NULL DEFAULT 'N'::bpchar, --Flag to indicate whether occurrence uses determinations table;
ADD CONSTRAINT occurrences_use_determination_check CHECK (use_determination = ANY (ARRAY['N'::bpchar, 'Y'::bpchar]));

COMMENT ON COLUMN occurrences.use_determination IS 'Flag to indicate whether occurrence uses determinations table';

