
-- Index: ix_cache_occurrences_functional_family_taxa_taxon_list_id

-- DROP INDEX ix_cache_occurrences_functional_family_taxa_taxon_list_id;

CREATE INDEX ix_cache_occurrences_functional_family_taxa_taxon_list_id
  ON cache_occurrences_functional
  USING btree
  (family_taxa_taxon_list_id);

-- Index: ix_cache_occurrences_functional_submission

-- DROP INDEX ix_cache_occurrences_functional_submission;

CREATE INDEX ix_cache_occurrences_functional_submission
  ON cache_occurrences_functional
  USING btree
  (website_id, survey_id, sample_id);
ALTER TABLE cache_occurrences_functional CLUSTER ON ix_cache_occurrences_functional_submission;

-- Index: ix_cache_occurrences_functional_date_start

-- DROP INDEX ix_cache_occurrences_functional_date_start;

CREATE INDEX ix_cache_occurrences_functional_date_start
  ON cache_occurrences_functional
  USING btree
  (date_start);

-- Index: ix_cache_samples_functional_survey

-- DROP INDEX ix_cache_samples_functional_survey;

CREATE INDEX ix_cache_samples_functional_survey
  ON cache_samples_functional
  USING btree
  (website_id, survey_id);
ALTER TABLE cache_samples_functional CLUSTER ON ix_cache_samples_functional_survey;

-- Index: ix_cache_occurrences_functional_taxon_group_id

-- DROP INDEX ix_cache_occurrences_functional_taxon_group_id;

CREATE INDEX ix_cache_occurrences_functional_taxon_group_id
  ON cache_occurrences_functional
  USING btree
  (taxon_group_id);