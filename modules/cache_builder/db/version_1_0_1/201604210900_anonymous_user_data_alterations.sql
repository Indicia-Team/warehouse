CREATE OR REPLACE function f_add_anon_col (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
	ALTER TABLE users_websites ADD COLUMN anonymous boolean default false NOT NULL;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE cache_occurrences_deprecated ADD COLUMN private_recorders varchar;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE cache_occurrences_deprecated ADD COLUMN anonymous boolean default false NOT NULL;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE cache_samples_nonfunctional ADD COLUMN private_recorders varchar;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE cache_samples_nonfunctional ADD COLUMN anonymous boolean default false NOT NULL;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

COMMENT ON COLUMN users_websites.anonymous IS
  'Should the user data be anonymous when their records are viewed.';

COMMENT ON COLUMN cache_occurrences_deprecated.private_recorders IS
  'Displays recorders even when the data is anonymised, for use with verification/administration reports.';

COMMENT ON COLUMN cache_occurrences_deprecated.anonymous IS
  'Indication that occurrence has been marked with an anonymous recorder.';

COMMENT ON COLUMN cache_samples_nonfunctional.private_recorders IS
  'Displays recorders even when the data is anonymised, for use with verification/administration reports.';

COMMENT ON COLUMN cache_samples_nonfunctional.anonymous IS
  'Indication that occurrence has been marked with an anonymous recorder.';

END
$func$;

SELECT f_add_anon_col();

DROP FUNCTION f_add_anon_col();

CREATE OR REPLACE FUNCTION update_cache_anon_data() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.anonymous = false AND NEW.anonymous = true) THEN
      UPDATE cache_occurrences_deprecated co 
      SET anonymous=true, recorders=null
      FROM users_websites uw
      WHERE uw.id=NEW.id
      AND uw.website_id=co.website_id
      AND uw.user_id=co.created_by_id;

      with update_data as (
        select csf.id as id
        from cache_samples_functional csf
        where csf.website_id=NEW.website_id AND csf.created_by_id=NEW.user_id
      )
      UPDATE cache_samples_nonfunctional csn 
      SET anonymous=true, recorders=null
      FROM users_websites uw
      WHERE uw.id=NEW.id
      AND csn.id in (select id from update_data);
    ELSEIF (OLD.anonymous = true AND NEW.anonymous = false) THEN
      UPDATE cache_occurrences_deprecated co SET anonymous=false, recorders=private_recorders
      FROM users_websites uw
      WHERE uw.id=NEW.id
      AND uw.website_id=co.website_id
      AND uw.user_id=co.created_by_id;

      with update_data as (
        select csf.id as id
        from cache_samples_functional csf
        where csf.website_id=NEW.website_id AND csf.created_by_id=NEW.user_id
      )
      UPDATE cache_samples_nonfunctional csn 
      SET anonymous=false, recorders=private_recorders
      FROM users_websites uw
      WHERE uw.id=NEW.id
      AND csn.id in (select id from update_data);
    END IF;
  RETURN OLD;
  END;
$$ LANGUAGE 'plpgsql';


DROP TRIGGER IF EXISTS update_cache_anon_data ON users_websites;
CREATE TRIGGER update_cache_anon_data AFTER UPDATE ON users_websites FOR EACH ROW EXECUTE PROCEDURE update_cache_anon_data();

--Add private recorders and anonymous to the view
CREATE OR REPLACE VIEW cache_occurrences AS
SELECT o.id,
  o.record_status,
  o.zero_abundance,
  o.website_id,
  o.survey_id,
  o.sample_id,
  snf.survey_title,
  snf.website_title,
  o.date_start,
  o.date_end,
  o.date_type,
  snf.public_entered_sref,
  snf.entered_sref_system,
  o.public_geom,
  --sample_method,
  o.taxa_taxon_list_id,
  cttl.preferred_taxa_taxon_list_id,
  cttl.taxonomic_sort_order,
  cttl.taxon,
  cttl.authority,
  cttl.preferred_taxon,
  cttl.preferred_authority,
  cttl.default_common_name,
  cttl.external_key as taxa_taxon_list_external_key,
  cttl.taxon_meaning_id,
  cttl.taxon_group_id,
  cttl.taxon_group,
  o.created_by_id,
  o.created_on as cache_created_on,
  o.updated_on as cache_updated_on,
  o.certainty,
  o.location_name,
  snf.recorders,
  onf.verifier,
  onf.media as images,
  o.training,
  o.location_id,
  o.input_form,
  o.data_cleaner_result,
  onf.data_cleaner_info,
  o.release_status,
  o.verified_on,
  onf.sensitivity_precision,
  o.map_sq_1km_id,
  o.map_sq_2km_id,
  o.map_sq_10km_id,
  o.group_id,
  onf.privacy_precision,
  onf.output_sref,
  o.record_substatus,
  o.query,
  o.licence_id,
  onf.licence_code,
  o.family_taxa_taxon_list_id,
  onf.attr_sex,
  onf.attr_stage,
  onf.attr_sex_stage,
  onf.attr_sex_stage_count,
  onf.attr_certainty,
  onf.attr_det_first_name,
  onf.attr_det_last_name,
  onf.attr_det_full_name,
  snf.attr_email,
  snf.attr_cms_user_id,
  snf.attr_cms_username,
  snf.attr_first_name,
  snf.attr_last_name,
  snf.attr_full_name,
  snf.attr_biotope,
  snf.attr_sref_precision,
  snf.private_recorders,
  snf.anonymous
  FROM cache_occurrences_functional o
  JOIN cache_occurrences_nonfunctional onf on onf.id=o.id
  JOIN cache_samples_nonfunctional snf on snf.id=o.sample_id
  JOIN cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id;