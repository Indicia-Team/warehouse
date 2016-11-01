-- #slow script#
CREATE INDEX ix_cache_occurrences_functional_family_taxa_taxon_list_id
  ON cache_occurrences_functional
  USING btree
  (family_taxa_taxon_list_id);

CREATE INDEX ix_cache_occurrences_functional_submission
  ON cache_occurrences_functional
  USING btree
  (website_id, survey_id, sample_id);
ALTER TABLE cache_occurrences_functional CLUSTER ON ix_cache_occurrences_functional_submission;

CREATE INDEX ix_cache_occurrences_functional_date_start
  ON cache_occurrences_functional
  USING btree
  (date_start);
  
CREATE INDEX ix_cache_occurrences_functional_date_end
  ON cache_occurrences_functional
  USING btree
  (date_end);
  
CREATE INDEX ix_cache_occurrences_functional_updated_on
  ON cache_occurrences_functional
  USING btree
  (updated_on);

CREATE INDEX ix_cache_samples_functional_survey
  ON cache_samples_functional
  USING btree
  (website_id, survey_id);
ALTER TABLE cache_samples_functional CLUSTER ON ix_cache_samples_functional_survey;

CREATE INDEX ix_cache_occurrences_functional_taxon_group_id
  ON cache_occurrences_functional
  USING btree
  (taxon_group_id);

CREATE INDEX ix_cache_occurrences_functional_group_id
  ON cache_occurrences_functional
  USING btree
  (group_id);

CREATE INDEX ix_cache_occurrences_functional_created_by_id
  ON cache_occurrences_functional
  USING btree
  (created_by_id);

CREATE INDEX ix_cache_occurrences_functional_map_sq_10km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_1km_id);
  
CREATE INDEX ix_cache_occurrences_functional_map_sq_2km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_1km_id);
  
CREATE INDEX ix_cache_occurrences_functional_map_sq_1km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_1km_id);
  
CREATE INDEX ix_cache_occurrences_functional_record_status
  ON cache_occurrences_functional
  USING btree
  (record_status);
  
CREATE INDEX ix_cache_occurrences_functional_public_geom
  ON cache_occurrences_functional
  USING gist
  (public_geom);
  
CREATE INDEX ix_cache_samples_functional_created_by_id
  ON cache_samples_functional
  USING btree
  (created_by_id);
  
CREATE INDEX ix_cache_samples_functional_map_sq_10km_id
  ON cache_samples_functional
  USING btree
  (map_sq_1km_id);
  
CREATE INDEX ix_cache_samples_functional_map_sq_2km_id
  ON cache_samples_functional
  USING btree
  (map_sq_1km_id);
  
CREATE INDEX ix_cache_samples_functional_map_sq_1km_id
  ON cache_samples_functional
  USING btree
  (map_sq_1km_id);