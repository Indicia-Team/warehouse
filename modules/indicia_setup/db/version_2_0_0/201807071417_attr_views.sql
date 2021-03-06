-- list_survey_attributes
DROP VIEW list_survey_attributes;

CREATE OR REPLACE VIEW list_survey_attributes AS
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
    a.public,
    fsb2.weight as outer_block_weight,
    fsb.weight as inner_block_weight,
    aw.weight as weight,
    rc.term as reporting_category
  FROM survey_attributes a
  LEFT JOIN survey_attributes_websites aw ON a.id = aw.survey_attribute_id AND aw.deleted = false
  LEFT JOIN control_types ct ON ct.id = aw.control_type_id
  LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
  LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE a.deleted = false
  ORDER BY fsb2.weight, fsb.weight, aw.weight;

-- list_sample_attributes
DROP VIEW list_sample_attributes;

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
      ) as taxon_restrictions,
      rc.term as reporting_category
    FROM sample_attributes a
    LEFT JOIN sample_attributes_websites aw ON a.id = aw.sample_attribute_id AND aw.deleted = false
    LEFT JOIN control_types ct ON ct.id = aw.control_type_id
    LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
    LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
    LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
    WHERE a.deleted = false
  ) as sub
  ORDER BY outer_block_weight, inner_block_weight, weight;

-- list_occurrence_attributes
DROP VIEW list_occurrence_attributes;

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
      ) as taxon_restrictions,
      rc.term as reporting_category
    FROM occurrence_attributes a
    LEFT JOIN occurrence_attributes_websites aw ON a.id = aw.occurrence_attribute_id AND aw.deleted = false
    LEFT JOIN control_types ct ON ct.id = aw.control_type_id
    LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
    LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
    LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
    WHERE a.deleted = false
  ) as sub
  ORDER BY outer_block_weight, inner_block_weight, weight;

-- list_location_attributes
DROP VIEW list_location_attributes;

CREATE OR REPLACE VIEW list_location_attributes AS
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
    aw.restrict_to_location_type_id,
    a.system_function,
    fsb2.weight as outer_block_weight,
    fsb.weight as inner_block_weight,
    aw.weight as weight,
    rc.term as reporting_category
  FROM location_attributes a
  LEFT JOIN location_attributes_websites aw ON a.id = aw.location_attribute_id AND aw.deleted = false
  LEFT JOIN control_types ct ON ct.id = aw.control_type_id
  LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
  LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE a.deleted = false
  ORDER BY fsb2.weight, fsb.weight, aw.weight;

-- list_taxa_taxon_list_attributes
DROP VIEW list_taxa_taxon_list_attributes;

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
    ) as taxon_restrictions,
    rc.term as reporting_category
  FROM taxa_taxon_list_attributes a
    LEFT JOIN taxon_lists_taxa_taxon_list_attributes tla ON tla.taxa_taxon_list_attribute_id=a.id AND tla.deleted=false
    LEFT JOIN control_types ct ON ct.id = tla.control_type_id
    LEFT JOIN form_structure_blocks fsb ON fsb.id = tla.form_structure_block_id
    LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
    LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE a.deleted=false
  ORDER BY fsb2.weight, fsb.weight, tla.weight;

-- list_termlists_term_attributes
DROP VIEW list_termlists_term_attributes;

CREATE OR REPLACE VIEW list_termlists_term_attributes AS
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
    a.termlist_id as lookup_termlist_id,
    a.multi_value,
    a.allow_ranges,
    tta.termlist_id,
    (((a.id || '|'::text) || a.data_type::text) || '|'::text) || COALESCE(a.termlist_id::text, ''::text) AS signature,
    tta.default_text_value,
    tta.default_int_value,
    tta.default_float_value,
    tta.default_upper_value,
    tta.default_date_start_value,
    tta.default_date_end_value,
    tta.default_date_type_value,
    COALESCE(tta.validation_rules::text || E'\n', '') || COALESCE(a.validation_rules::text, '') AS validation_rules,
    a.deleted,
    tta.deleted AS termlist_deleted,
    a.public,
    tl.website_id,
    fsb2.weight as outer_block_weight,
    fsb.weight as inner_block_weight,
    tta.weight as weight,
    rc.term as reporting_category
  FROM termlists_term_attributes a
  LEFT JOIN termlists_termlists_term_attributes tta ON tta.termlists_term_attribute_id = a.id AND tta.deleted = false
  LEFT JOIN termlists tl ON tl.id = tta.termlist_id AND tl.deleted = false
  LEFT JOIN control_types ct ON ct.id = tta.control_type_id
  LEFT JOIN form_structure_blocks fsb ON fsb.id = tta.form_structure_block_id
  LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE a.deleted = false
  ORDER BY fsb2.weight, fsb.weight, tta.weight;

-- list_person_attributes
DROP VIEW list_person_attributes;

CREATE OR REPLACE VIEW list_person_attributes AS
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
    a.public,
    fsb2.weight as outer_block_weight,
    fsb.weight as inner_block_weight,
    aw.weight as weight,
    rc.term as reporting_category
  FROM person_attributes a
  LEFT JOIN person_attributes_websites aw ON a.id = aw.person_attribute_id AND aw.deleted = false
  LEFT JOIN control_types ct ON ct.id = aw.control_type_id
  LEFT JOIN form_structure_blocks fsb ON fsb.id = aw.form_structure_block_id
  LEFT JOIN form_structure_blocks fsb2 ON fsb2.id = fsb.parent_id
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE a.deleted = false
  ORDER BY fsb2.weight, fsb.weight, aw.weight;

-- list_survey_attribute_values
DROP VIEW list_survey_attribute_values;

CREATE OR REPLACE VIEW list_survey_attribute_values AS
  SELECT
    av.id,
    s.id AS survey_id,
    a.id AS survey_attribute_id,
    a.caption,
    a.caption_i18n,
    a.description,
    a.description_i18n,
    a.image_path,
    a.term_name,
    a.term_identifier,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE a.data_type
    END AS data_type,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN t.term::text
      WHEN 'I'::bpchar THEN av.int_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
      ELSE NULL::text
    END AS value,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN av.int_value::text
      WHEN 'I'::bpchar THEN av.int_value::text
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
      ELSE NULL::text
    END AS raw_value,
    CASE
      WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN av.upper_value
      ELSE NULL
    END as upper_value,
    a.termlist_id,
    l.iso,
    s.website_id,
    rc.term as reporting_category
  FROM surveys s
  JOIN survey_attribute_values av ON av.survey_id = s.id AND av.deleted = false
  JOIN survey_attributes a ON a.id = av.survey_attribute_id AND a.deleted = false
  LEFT JOIN (termlists_terms tt
    JOIN terms t ON t.id = tt.term_id
    JOIN languages l ON l.id = t.language_id
  ) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE s.deleted = false
  ORDER BY a.id;

-- list_sample_attribute_values
DROP VIEW list_sample_attribute_values;

CREATE OR REPLACE VIEW list_sample_attribute_values AS
  SELECT
    av.id,
    s.id AS sample_id,
    a.id AS sample_attribute_id,
    a.caption,
    a.caption_i18n,
    a.description,
    a.description_i18n,
    a.image_path,
    a.term_name,
    a.term_identifier,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE a.data_type
    END AS data_type,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN t.term::text
      WHEN 'I'::bpchar THEN av.int_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)
      ELSE NULL::text
    END AS value,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN av.int_value::text
      WHEN 'I'::bpchar THEN av.int_value::text
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)
      ELSE NULL::text
    END AS raw_value,
    CASE
      WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN av.upper_value
      ELSE NULL
    END as upper_value,
    a.termlist_id,
    l.iso,
    aw.website_id,
    rc.term as reporting_category
  FROM samples s
  JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
  JOIN sample_attributes_websites aw ON aw.website_id = su.website_id AND (aw.restrict_to_survey_id = su.id OR aw.restrict_to_survey_id IS NULL) AND aw.deleted = false
  JOIN sample_attributes a ON a.id = aw.sample_attribute_id AND a.deleted = false
  LEFT JOIN sample_attribute_values av ON av.sample_attribute_id = a.id AND av.sample_id = s.id AND av.deleted = false
  LEFT JOIN (termlists_terms tt
    JOIN terms t ON t.id = tt.term_id
    JOIN languages l ON l.id = t.language_id
  ) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE s.deleted = false
  ORDER BY a.id;

-- list_occurrence_attribute_values
DROP VIEW list_occurrence_attribute_values;

CREATE OR REPLACE VIEW list_occurrence_attribute_values AS
  SELECT av.id,
    o.id AS occurrence_id,
    a.id AS occurrence_attribute_id,
    a.caption,
    a.caption_i18n,
    a.description,
    a.description_i18n,
    a.image_path,
    a.term_name,
    a.term_identifier,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE a.data_type
    END AS data_type,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN t.term::text
      WHEN 'I'::bpchar THEN av.int_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)
      ELSE NULL::text
    END AS value,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN av.int_value::text
      WHEN 'I'::bpchar THEN av.int_value::text
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)
      ELSE NULL::text
    END AS raw_value,
    CASE
      WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN av.upper_value
      ELSE NULL
    END as upper_value,
    a.termlist_id,
    l.iso,
    aw.website_id,
    rc.term as reporting_category
  FROM occurrences o
  JOIN samples s ON s.id = o.sample_id AND s.deleted = false
  JOIN surveys su ON su.id = s.survey_id AND su.deleted = false
  JOIN occurrence_attributes_websites aw ON aw.website_id = su.website_id AND (aw.restrict_to_survey_id = su.id OR aw.restrict_to_survey_id IS NULL) AND aw.deleted = false
  JOIN occurrence_attributes a ON a.id = aw.occurrence_attribute_id AND a.deleted = false
  LEFT JOIN occurrence_attribute_values av ON av.occurrence_attribute_id = a.id AND av.occurrence_id = o.id AND av.deleted = false
  LEFT JOIN (termlists_terms tt
    JOIN terms t ON t.id = tt.term_id
    JOIN languages l ON l.id = t.language_id
  ) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE o.deleted = false
  ORDER BY a.id;

-- list_location_attribute_values
DROP VIEW list_location_attribute_values;

CREATE OR REPLACE VIEW list_location_attribute_values AS
  SELECT
    av.id,
    l.id AS location_id,
    a.id AS location_attribute_id,
    a.caption,
    a.caption_i18n,
    a.description,
    a.description_i18n,
    a.image_path,
    a.term_name,
    a.term_identifier,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE a.data_type
    END AS data_type,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN t.term::text
      WHEN 'I'::bpchar THEN av.int_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN (av.date_start_value::text || ' - '::text) || av.date_end_value::text
      ELSE NULL::text
    END AS value,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN av.int_value::text
      WHEN 'I'::bpchar THEN av.int_value::text
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN (av.date_start_value::text || ' - '::text) || av.date_end_value::text
      ELSE NULL::text
    END AS raw_value,
    CASE
      WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN av.upper_value
      ELSE NULL
    END as upper_value,
    a.termlist_id,
    lg.iso,
    lw.website_id,
    l.location_type_id,
    rc.term as reporting_category
  FROM locations l
  JOIN locations_websites lw ON lw.location_id = l.id AND lw.deleted = false
  JOIN location_attribute_values av ON av.location_id = l.id AND av.deleted = false
  JOIN location_attributes a ON a.id = av.location_attribute_id AND a.deleted = false
  LEFT JOIN (termlists_terms tt
    JOIN terms t ON t.id = tt.term_id
    JOIN languages lg ON lg.id = t.language_id
  ) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE l.deleted = false
  ORDER BY a.id;

-- list_taxa_taxon_list_attribute_values
DROP VIEW list_taxa_taxon_list_attribute_values;

CREATE OR REPLACE VIEW list_taxa_taxon_list_attribute_values AS
  SELECT
    NULL::unknown AS id,
    NULL::unknown AS taxa_taxon_list_id,
    a.id AS taxa_taxon_list_attribute_id,
    a.caption,
    a.caption_i18n::text,
    a.description,
    a.description_i18n::text,
    a.image_path,
    a.term_name,
    a.term_identifier,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      WHEN 'G'::bpchar THEN 'Geometry'::bpchar
      ELSE a.data_type
    END AS data_type,
    NULL::unknown AS value,
    NULL::unknown AS raw_value,
    NULL::unknown AS upper_value,
    a.termlist_id,
    NULL::unknown AS iso,
    tlttla.taxon_list_id,
    tl.website_id,
    rc.term as reporting_category
  FROM taxa_taxon_list_attributes a
  JOIN taxon_lists_taxa_taxon_list_attributes tlttla ON tlttla.taxa_taxon_list_attribute_id = a.id AND tlttla.deleted = false
  JOIN taxon_lists tl on tl.id=tlttla.taxon_list_id AND tl.deleted=false
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE a.deleted = false
UNION
  SELECT
    av.id,
    ttl.id AS taxa_taxon_list_id,
    a.id AS taxa_taxon_list_attribute_id,
    a.caption,
    a.caption_i18n::text,
    a.description,
    a.description_i18n::text,
    a.image_path,
    a.term_name,
    a.term_identifier,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      WHEN 'G'::bpchar THEN 'Geometry'::bpchar
      ELSE a.data_type
    END AS data_type,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN t.term::text
      WHEN 'I'::bpchar THEN av.int_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN (av.date_start_value::text || ' - '::text) || av.date_end_value::text
      WHEN 'G'::bpchar THEN st_astext(av.geom_value)::text
      ELSE NULL::text
    END AS value,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN av.int_value::text
      WHEN 'I'::bpchar THEN av.int_value::text
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN (av.date_start_value::text || ' - '::text) || av.date_end_value::text
      WHEN 'G'::bpchar THEN st_astext(av.geom_value)::text
      ELSE NULL::text
    END AS raw_value,
    CASE
      WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN av.upper_value
      ELSE NULL
    END as upper_value,
    a.termlist_id,
    l.iso,
    tlttla.taxon_list_id,
    tl.website_id,
    rc.term as reporting_category
  FROM taxa_taxon_lists ttl
  JOIN taxon_lists_taxa_taxon_list_attributes tlttla ON tlttla.taxon_list_id = ttl.taxon_list_id AND tlttla.deleted = false
  JOIN taxon_lists tl on tl.id=tlttla.taxon_list_id AND tl.deleted=false
  JOIN taxa_taxon_list_attributes a ON a.id = tlttla.taxa_taxon_list_attribute_id AND a.deleted = false
  LEFT JOIN taxa_taxon_list_attribute_values av ON av.taxa_taxon_list_attribute_id = a.id AND av.taxa_taxon_list_id = ttl.id AND av.deleted = false
  LEFT JOIN (termlists_terms tt
    JOIN terms t ON t.id = tt.term_id AND t.deleted = false
    JOIN languages l ON l.id = t.language_id AND l.deleted = false
    ) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar AND tt.deleted = false
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE ttl.deleted = false;

-- list_termlists_term_attribute_values
DROP VIEW list_termlists_term_attribute_values;

CREATE OR REPLACE VIEW list_termlists_term_attribute_values AS
  SELECT
    av.id,
    tlt.id AS termlists_term_id,
    a.id AS termlists_term_attribute_id,
    a.caption,
    a.caption_i18n,
    a.description,
    a.description_i18n,
    a.image_path,
    a.term_name,
    a.term_identifier,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE a.data_type
    END AS data_type,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN t.term::text
      WHEN 'I'::bpchar THEN av.int_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
      ELSE NULL::text
    END AS value,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN av.int_value::text
      WHEN 'I'::bpchar THEN av.int_value::text
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
      ELSE NULL::text
    END AS raw_value,
    CASE
      WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN av.upper_value
      ELSE NULL
    END as upper_value,
    a.termlist_id as lookup_termlist_id,
    l.iso,
    tl.website_id,
    rc.term as reporting_category
  FROM termlists_terms tlt
  JOIN termlists_term_attribute_values av ON av.termlists_term_id = tlt.id AND av.deleted = false
  JOIN termlists_term_attributes a ON a.id = av.termlists_term_attribute_id AND a.deleted = false
  JOIN termlists tl on tl.id=tlt.termlist_id AND tl.deleted=false
  LEFT JOIN (termlists_terms tt
    JOIN terms t ON t.id = tt.term_id
    JOIN languages l ON l.id = t.language_id
  ) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE tlt.deleted = false
  ORDER BY a.id;

-- list_person_attribute_values
DROP VIEW list_person_attribute_values;

CREATE OR REPLACE VIEW list_person_attribute_values AS
  SELECT
    av.id,
    p.id AS person_id,
    a.id AS person_attribute_id,
    a.caption,
    a.caption_i18n,
    a.description,
    a.description_i18n,
    a.image_path,
    a.term_name,
    a.term_identifier,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE a.data_type
    END AS data_type,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN t.term::text
      WHEN 'I'::bpchar THEN av.int_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text ||
        CASE
          WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN COALESCE(' - ' || av.upper_value::text, '')
          ELSE ''
        END
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)
      ELSE NULL::text
    END AS value,
    CASE a.data_type
      WHEN 'T'::bpchar THEN av.text_value
      WHEN 'L'::bpchar THEN av.int_value::text
      WHEN 'I'::bpchar THEN av.int_value::text
      WHEN 'B'::bpchar THEN av.int_value::text
      WHEN 'F'::bpchar THEN av.float_value::text
      WHEN 'D'::bpchar THEN av.date_start_value::text
      WHEN 'V'::bpchar THEN vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)
      ELSE NULL::text
    END AS raw_value,
    CASE
      WHEN a.data_type IN ('I', 'F') AND a.allow_ranges='t' THEN av.upper_value
      ELSE NULL
    END as upper_value,
    a.termlist_id,
    l.iso,
    aw.website_id,
    rc.term as reporting_category
  FROM people p
  LEFT JOIN person_attribute_values av ON av.person_id = p.id AND av.deleted = false
  LEFT JOIN (users u
  JOIN users_websites uw ON uw.user_id = u.id AND uw.site_role_id IS NOT NULL
  JOIN person_attributes_websites aw ON aw.website_id = uw.website_id AND aw.deleted = false) ON u.person_id = p.id
  JOIN person_attributes a ON (a.id = COALESCE(av.person_attribute_id, aw.person_attribute_id) OR a.public = true) AND (a.id = av.person_attribute_id OR av.id IS NULL) AND a.deleted = false
  LEFT JOIN (termlists_terms tt
  JOIN terms t ON t.id = tt.term_id
  JOIN languages l ON l.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
  LEFT JOIN cache_termlists_terms rc on rc.id=a.reporting_category_id
  WHERE p.deleted = false
  ORDER BY a.id;

