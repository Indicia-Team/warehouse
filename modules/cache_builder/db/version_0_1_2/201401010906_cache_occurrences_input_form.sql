UPDATE cache_occurrences co
SET input_form=s.input_form
FROM samples s
WHERE s.id=co.sample_id AND s.deleted=false AND s.input_form IS NOT NULL AND co.input_form<>s.input_form;