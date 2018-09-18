-- #slow script#

-- Build a temp table with sample IDs mapped to their array of indexed locations.
SELECT sample_id, array_agg(distinct location_id) as location_ids
INTO temporary ils_temp
FROM index_locations_samples
GROUP BY sample_id;

CREATE INDEX ix_ils_temp ON ils_temp(sample_id);

-- Temporarily remove indexes and triggers for faster updates.
DROP INDEX ix_cache_occurrences_functional_created_by_id;
DROP INDEX ix_cache_occurrences_functional_date_end;
DROP INDEX ix_cache_occurrences_functional_date_start;
DROP INDEX ix_cache_occurrences_functional_family_taxa_taxon_list_id;
DROP INDEX ix_cache_occurrences_functional_group_id;
DROP INDEX ix_cache_occurrences_functional_location_id;
DROP INDEX ix_cache_occurrences_functional_location_ids;
DROP INDEX ix_cache_occurrences_functional_map_sq_10km_id;
DROP INDEX ix_cache_occurrences_functional_map_sq_1km_id;
DROP INDEX ix_cache_occurrences_functional_map_sq_2km_id;
DROP INDEX ix_cache_occurrences_functional_public_geom;
DROP INDEX ix_cache_occurrences_functional_status;
DROP INDEX ix_cache_occurrences_functional_submission;
DROP INDEX ix_cache_occurrences_functional_taxa_taxon_list_external_key;
DROP INDEX ix_cache_occurrences_functional_taxon_group_id;
DROP INDEX ix_cache_occurrences_functional_updated_on;
DROP INDEX ix_cache_occurrences_functional_verified_on;
DROP TRIGGER delete_quick_reply_auth_trigger ON cache_occurrences_functional;
DROP INDEX ix_cache_samples_functional_created_by_id;
DROP INDEX ix_cache_samples_functional_location_id;
DROP INDEX ix_cache_samples_functional_map_sq_10km_id;
DROP INDEX ix_cache_samples_functional_map_sq_1km_id;
DROP INDEX ix_cache_samples_functional_map_sq_2km_id;
DROP INDEX ix_cache_samples_functional_survey;

UPDATE cache_occurrences_functional o
SET location_ids=t.location_ids
FROM ils_temp t
WHERE t.sample_id=o.sample_id;

UPDATE cache_samples_functional s
SET location_ids=t.location_ids
FROM ils_temp t
WHERE t.sample_id=s.id;

-- Re-create the indexes.
CREATE INDEX ix_cache_occurrences_functional_created_by_id
  ON cache_occurrences_functional
  USING btree
  (created_by_id);

CREATE INDEX ix_cache_occurrences_functional_date_end
  ON cache_occurrences_functional
  USING btree
  (date_end);

CREATE INDEX ix_cache_occurrences_functional_date_start
  ON cache_occurrences_functional
  USING btree
  (date_start);

CREATE INDEX ix_cache_occurrences_functional_family_taxa_taxon_list_id
  ON cache_occurrences_functional
  USING btree
  (family_taxa_taxon_list_id);

CREATE INDEX ix_cache_occurrences_functional_group_id
  ON cache_occurrences_functional
  USING btree
  (group_id);

CREATE INDEX ix_cache_occurrences_functional_location_id
  ON cache_occurrences_functional
  USING btree
  (location_id);

CREATE INDEX ix_cache_occurrences_functional_location_ids
  ON cache_occurrences_functional
  USING gin
  (location_ids);

CREATE INDEX ix_cache_occurrences_functional_map_sq_10km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_10km_id);

CREATE INDEX ix_cache_occurrences_functional_map_sq_1km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_1km_id);

CREATE INDEX ix_cache_occurrences_functional_map_sq_2km_id
  ON cache_occurrences_functional
  USING btree
  (map_sq_2km_id);

CREATE INDEX ix_cache_occurrences_functional_public_geom
  ON cache_occurrences_functional
  USING gist
  (public_geom);

CREATE INDEX ix_cache_occurrences_functional_status
  ON cache_occurrences_functional
  USING btree
  (record_status COLLATE pg_catalog."default", record_substatus);

CREATE INDEX ix_cache_occurrences_functional_submission
  ON cache_occurrences_functional
  USING btree
  (website_id, survey_id, sample_id);

CREATE INDEX ix_cache_occurrences_functional_taxa_taxon_list_external_key
  ON cache_occurrences_functional
  USING btree
  (taxa_taxon_list_external_key COLLATE pg_catalog."default");

CREATE INDEX ix_cache_occurrences_functional_taxon_group_id
  ON cache_occurrences_functional
  USING btree
  (taxon_group_id);

CREATE INDEX ix_cache_occurrences_functional_updated_on
  ON cache_occurrences_functional
  USING btree
  (updated_on);

CREATE INDEX ix_cache_occurrences_functional_verified_on
  ON cache_occurrences_functional
  USING btree
  (verified_on);

CREATE TRIGGER delete_quick_reply_auth_trigger
  AFTER UPDATE
  ON cache_occurrences_functional
  FOR EACH ROW
  EXECUTE PROCEDURE delete_quick_reply_auth();

CREATE INDEX ix_cache_samples_functional_created_by_id
  ON cache_samples_functional
  USING btree
  (created_by_id);

CREATE INDEX ix_cache_samples_functional_location_id
  ON cache_samples_functional
  USING btree
  (location_id);

CREATE INDEX ix_cache_samples_functional_location_ids
  ON cache_samples_functional
  USING gin
  (location_ids);

CREATE INDEX ix_cache_samples_functional_map_sq_10km_id
  ON cache_samples_functional
  USING btree
  (map_sq_10km_id);

CREATE INDEX ix_cache_samples_functional_map_sq_1km_id
  ON cache_samples_functional
  USING btree
  (map_sq_1km_id);

CREATE INDEX ix_cache_samples_functional_map_sq_2km_id
  ON cache_samples_functional
  USING btree
  (map_sq_2km_id);

CREATE INDEX ix_cache_samples_functional_survey
  ON cache_samples_functional
  USING btree
  (website_id, survey_id);

-- Table no longer required.
DROP TABLE index_locations_samples;