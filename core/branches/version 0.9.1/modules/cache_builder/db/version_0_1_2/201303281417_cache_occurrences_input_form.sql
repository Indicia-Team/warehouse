ALTER TABLE cache_occurrences ADD COLUMN input_form character varying;

UPDATE cache_occurrences co
SET input_form=s.input_form
FROM samples s
WHERE s.id=co.sample_id AND s.deleted=false AND s.input_form IS NOT NULL;