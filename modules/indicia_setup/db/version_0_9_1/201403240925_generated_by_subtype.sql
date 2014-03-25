ALTER TABLE occurrence_comments ADD COLUMN generated_by_subtype character varying(100);
COMMENT ON COLUMN occurrence_comments.generated_by_subtype IS 'Allows a generator to subtype the output into different categories, e.g. ID difficulty levels.';
