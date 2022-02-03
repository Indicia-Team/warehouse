-- #slow script#
DROP INDEX IF EXISTS ix_sample_input_form;
CREATE INDEX ix_cache_samples_functional_input_form ON cache_samples_functional(website_id, survey_id, input_form);