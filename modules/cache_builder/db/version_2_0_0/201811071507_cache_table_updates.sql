-- #slow script#

-- Create a temp table for prebuilt arrays of indexed location_ids. Empty table
-- build if the soatial index builder not installed.
DROP TABLE IF EXISTS loc_ids;

CREATE TEMPORARY TABLE loc_ids (
  sample_id integer,
  location_ids integer[]
  );

CREATE OR REPLACE FUNCTION temp_spatial_index()
RETURNS boolean AS
$BODY$
BEGIN
  BEGIN
    INSERT INTO loc_ids
    SELECT s.id as sample_id, array_agg(distinct ils.location_id) as location_ids
    FROM samples s
    LEFT JOIN index_locations_samples ils ON ils.sample_id=s.id
    WHERE s.deleted=false
    GROUP BY s.id;
  EXCEPTION
    WHEN undefined_table THEN
      BEGIN
        -- Handle case where beta v2 warehouse has already dropped location_ids.
        INSERT INTO loc_ids
        SELECT DISTINCT id, location_ids
        FROM cache_samples_functional;
      EXCEPTION
        WHEN undefined_column THEN
          -- Update from v1 warehouse without spatial index builder.
      END;
  END;
  RETURN true;
END;
$BODY$
  LANGUAGE plpgsql;

SELECT temp_spatial_index();

DROP FUNCTION temp_spatial_index();

CREATE INDEX ix_loc_ids ON loc_ids(sample_id);

-- Temporarily remove indexes and triggers for faster updates.
DROP INDEX ix_cache_occurrences_functional_created_by_id;
DROP INDEX ix_cache_occurrences_functional_date_end;
DROP INDEX ix_cache_occurrences_functional_date_start;
DROP INDEX ix_cache_occurrences_functional_family_taxa_taxon_list_id;
DROP INDEX ix_cache_occurrences_functional_group_id;
DROP INDEX ix_cache_occurrences_functional_location_id;
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
SET location_ids=CASE l.location_ids WHEN ARRAY[NULL::integer] THEN NULL ELSE l.location_ids END,
  taxon_path=CASE ctp.path WHEN ARRAY[NULL::integer] THEN NULL ELSE ctp.path END,
  blocked_sharing_tasks=
    CASE WHEN u.allow_share_for_reporting
      AND u.allow_share_for_peer_review AND u.allow_share_for_verification
      AND u.allow_share_for_data_flow AND u.allow_share_for_moderation
      AND u.allow_share_for_editing
    THEN null
    ELSE
      ARRAY_REMOVE(ARRAY[
        CASE WHEN u.allow_share_for_reporting=false THEN 'R' ELSE NULL END,
        CASE WHEN u.allow_share_for_peer_review=false THEN 'P' ELSE NULL END,
        CASE WHEN u.allow_share_for_verification=false THEN 'V' ELSE NULL END,
        CASE WHEN u.allow_share_for_data_flow=false THEN 'D' ELSE NULL END,
        CASE WHEN u.allow_share_for_moderation=false THEN 'M' ELSE NULL END,
        CASE WHEN u.allow_share_for_editing=false THEN 'E' ELSE NULL END
      ], NULL)
    END
FROM loc_ids l, users u, cache_taxa_taxon_lists cttl
LEFT JOIN cache_taxa_taxon_lists cttlm
  ON cttlm.external_key=cttl.external_key
  AND cttlm.taxon_list_id=COALESCE(#master_list_id#, cttl.taxon_list_id)
  AND cttlm.preferred=true
LEFT JOIN cache_taxon_paths ctp
  ON ctp.taxon_meaning_id=cttlm.taxon_meaning_id
  AND ctp.taxon_list_id=cttlm.taxon_list_id
WHERE l.sample_id=o.sample_id
AND u.id=o.created_by_id
AND cttl.id=o.taxa_taxon_list_id;

UPDATE cache_samples_functional s
SET location_ids=l.location_ids,
  blocked_sharing_tasks=
    CASE WHEN u.allow_share_for_reporting
      AND u.allow_share_for_peer_review AND u.allow_share_for_verification
      AND u.allow_share_for_data_flow AND u.allow_share_for_moderation
      AND u.allow_share_for_editing
    THEN null
    ELSE
      ARRAY_REMOVE(ARRAY[
        CASE WHEN u.allow_share_for_reporting=false THEN 'R' ELSE NULL END,
        CASE WHEN u.allow_share_for_peer_review=false THEN 'P' ELSE NULL END,
        CASE WHEN u.allow_share_for_verification=false THEN 'V' ELSE NULL END,
        CASE WHEN u.allow_share_for_data_flow=false THEN 'D' ELSE NULL END,
        CASE WHEN u.allow_share_for_moderation=false THEN 'M' ELSE NULL END,
        CASE WHEN u.allow_share_for_editing=false THEN 'E' ELSE NULL END
      ], NULL)
    END
FROM loc_ids l, users u
WHERE l.sample_id=s.id
AND u.id=s.created_by_id;

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

CREATE INDEX ix_cache_occurrences_functional_website_id
  ON cache_occurrences_functional
  USING btree
  (website_id);

CREATE INDEX ix_cache_occurrences_functional_survey_id
  ON cache_occurrences_functional
  USING btree
  (survey_id);

CREATE INDEX ix_cache_occurrences_functional_sample_id
  ON cache_occurrences_functional
  USING btree
  (sample_id);

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

CREATE INDEX ix_cache_samples_functional_website_id
  ON cache_samples_functional
  USING btree
  (website_id);

CREATE INDEX ix_cache_samples_functional_survey_id
  ON cache_samples_functional
  USING btree
  (survey_id);

DROP TABLE loc_ids;