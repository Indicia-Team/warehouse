CREATE OR REPLACE VIEW list_sample_attributes AS
  SELECT * FROM (
    SELECT a.id,
      a.caption,
      a.caption_i18n,
      a.description,
      a.description_i18n,
      a.image_path,
      a.term_name,
      a.term_identifier,
      fsb2.name AS outer_structure_block,
      fsb.name AS inner_structure_block,
      a.data_type,
      ct.control AS control_type,
      a.termlist_id,
      a.multi_value,
      a.allow_ranges,
      aw.website_id,
      aw.restrict_to_survey_id,
      (((a.id || '|'::text) || a.data_type::text) || '|'::text) || COALESCE(a.termlist_id::text, ''::text) AS signature,
      aw.default_text_value,
      aw.default_int_value,
      aw.default_float_value,
      aw.default_upper_value,
      aw.default_date_start_value,
      aw.default_date_end_value,
      aw.default_date_type_value,
      COALESCE(aw.validation_rules::text || E'\n', '') || COALESCE(a.validation_rules::text, '') AS validation_rules,
      a.deleted,
      aw.deleted AS website_deleted,
      aw.restrict_to_sample_method_id,
      a.system_function,
      fsb2.weight as outer_block_weight,
      fsb.weight as inner_block_weight,
      aw.weight as weight,
      (
        SELECT string_agg(restrict_to_taxon_meaning_id::text || '|' || COALESCE(restrict_to_stage_term_meaning_id::text, ''), ';')
        FROM sample_attribute_taxon_restrictions tr
        WHERE tr.sample_attributes_website_id=aw.id
        AND tr.deleted=false
      ) as taxon_restrictions,
      rc.term as reporting_category,
      rc.id as reporting_category_id,
      a.unit as unit
    FROM sample_attributes a
    LEFT JOIN sample_attributes_websites aw ON a.id = aw.sample_attribute_id AND aw.deleted = false
    LEFT JOIN control_types ct ON ct.id = aw.control_type_id
    LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
    LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
    LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
    WHERE a.deleted = false
  ) as sub
  ORDER BY outer_block_weight, inner_block_weight, weight;

CREATE OR REPLACE VIEW list_occurrence_attributes AS
  SELECT * FROM (
    SELECT
      a.id,
      a.caption,
      a.caption_i18n,
      a.description,
      a.description_i18n,
      a.image_path,
      a.term_name,
      a.term_identifier,
      fsb2.name AS outer_structure_block,
      fsb.name AS inner_structure_block,
      a.data_type, ct.control AS control_type,
      a.termlist_id,
      a.multi_value,
      a.allow_ranges,
      aw.website_id,
      aw.restrict_to_survey_id,
      (((a.id || '|'::text) || a.data_type::text) || '|'::text) || COALESCE(a.termlist_id::text, ''::text) AS signature,
      aw.default_text_value,
      aw.default_int_value,
      aw.default_float_value,
      aw.default_upper_value,
      aw.default_date_start_value,
      aw.default_date_end_value,
      aw.default_date_type_value,
      COALESCE(aw.validation_rules::text || E'\n', '') || COALESCE(a.validation_rules::text, '') AS validation_rules,
      a.deleted,
      aw.deleted AS website_deleted,
      a.system_function,
      fsb2.weight as outer_block_weight,
      fsb.weight as inner_block_weight,
      aw.weight as weight,
      (
        SELECT string_agg(restrict_to_taxon_meaning_id::text || '|' || COALESCE(restrict_to_stage_term_meaning_id::text, ''), ';')
        FROM occurrence_attribute_taxon_restrictions tr
        WHERE tr.occurrence_attributes_website_id=aw.id
        AND tr.deleted=false
      ) as taxon_restrictions,
      rc.term as reporting_category,
      rc.id as reporting_category_id,
      a.unit as unit
    FROM occurrence_attributes a
    LEFT JOIN occurrence_attributes_websites aw ON a.id = aw.occurrence_attribute_id AND aw.deleted = false
    LEFT JOIN control_types ct ON ct.id = aw.control_type_id
    LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
    LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
    LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
    WHERE a.deleted = false
  ) as sub
  ORDER BY outer_block_weight, inner_block_weight, weight;

CREATE OR REPLACE VIEW list_taxa_taxon_list_attributes AS
  SELECT a.id,
    a.caption,
    a.caption_i18n,
    a.description,
    a.description_i18n,
    a.image_path,
    a.term_name,
    a.term_identifier,
    fsb2.name AS outer_structure_block,
    fsb.name AS inner_structure_block,
    a.data_type,
    ct.control AS control_type,
    a.termlist_id,
    a.multi_value,
    a.allow_ranges,
    tla.taxon_list_id,
    (((a.id || '|'::text) || a.data_type::text) || '|'::text) || COALESCE(a.termlist_id::text, ''::text) AS signature,
    tla.default_text_value,
    tla.default_int_value,
    tla.default_float_value,
    tla.default_upper_value,
    tla.default_date_start_value,
    tla.default_date_end_value,
    tla.default_date_type_value,
    COALESCE(tla.validation_rules::text || E'\n', '') || COALESCE(a.validation_rules::text, '') AS validation_rules,
    a.deleted,
    tla.deleted AS taxon_list_deleted,
    fsb2.weight as outer_block_weight,
    fsb.weight as inner_block_weight,
    tla.weight as weight,
    (
      SELECT string_agg(restrict_to_taxon_meaning_id::text || '|' || COALESCE(restrict_to_stage_term_meaning_id::text, ''), ';')
      FROM taxa_taxon_list_attribute_taxon_restrictions tr
      WHERE tr.taxon_lists_taxa_taxon_list_attribute_id=tla.id
      AND tr.deleted=false
    ) as taxon_restrictions,
    rc.term as reporting_category,
    rc.id as reporting_category_id,
    a.unit as unit
  FROM taxa_taxon_list_attributes a
    LEFT JOIN taxon_lists_taxa_taxon_list_attributes tla ON tla.taxa_taxon_list_attribute_id=a.id AND tla.deleted=false
    LEFT JOIN control_types ct ON ct.id = tla.control_type_id
    LEFT JOIN form_structure_blocks fsb ON fsb.id = tla.form_structure_block_id
    LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
    LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE a.deleted=false
  ORDER BY fsb2.weight, fsb.weight, tla.weight;