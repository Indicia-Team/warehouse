-- #slow script#
DROP TABLE cache_occurrences;

-- Create a view to ease the migration path to the new cache occurrences structure.
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
  o.confidential
  FROM cache_occurrences_functional o
  JOIN cache_occurrences_nonfunctional onf on onf.id=o.id
  JOIN cache_samples_nonfunctional snf on snf.id=o.sample_id
  JOIN cache_taxa_taxon_lists cttl on cttl.id=o.taxa_taxon_list_id;