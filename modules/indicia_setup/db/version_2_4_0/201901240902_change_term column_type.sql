DROP VIEW IF EXISTS list_person_attribute_values;
DROP VIEW IF EXISTS list_termlists_term_attribute_values;
DROP VIEW IF EXISTS list_taxa_taxon_list_attribute_values;
DROP VIEW IF EXISTS list_location_attribute_values;
DROP VIEW IF EXISTS list_occurrence_attribute_values;
DROP VIEW IF EXISTS list_sample_attribute_values;
DROP VIEW IF EXISTS list_survey_attribute_values;
DROP VIEW IF EXISTS detail_termlists_terms;
DROP VIEW IF EXISTS lookup_terms;
DROP VIEW IF EXISTS list_terms;
DROP VIEW IF EXISTS list_termlists_terms;
DROP VIEW IF EXISTS list_taxon_codes;
DROP VIEW IF EXISTS gv_taxon_codes;
DROP VIEW IF EXISTS detail_taxon_codes;
DROP VIEW IF EXISTS gv_user_identifiers;
DROP VIEW IF EXISTS gv_termlists_terms;
DROP VIEW IF EXISTS gv_locations;
DROP VIEW IF EXISTS detail_terms;

--Note these are recreated using scripts inside their modules
DROP VIEW IF EXISTS gv_occurrence_associations;
DROP VIEW IF EXISTS gv_taxon_associations;
DROP VIEW IF EXISTS gv_taxon_designations;
DROP VIEW IF EXISTS gv_taxa_taxon_designations;
DROP VIEW IF EXISTS detail_taxon_designations;
DROP VIEW IF EXISTS detail_taxa_taxon_designations;

ALTER TABLE terms
  ALTER COLUMN term type varchar;

--Have to recreate all views that use term
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
    rc.term as reporting_category,
    rc.id as reporting_category_id
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
    rc.term as reporting_category,
    rc.id as reporting_category_id
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
    rc.term as reporting_category,
    rc.id as reporting_category_id
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
    rc.term as reporting_category,
    rc.id as reporting_category_id
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
    rc.term as reporting_category,
    rc.id as reporting_category_id
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
    rc.term as reporting_category,
    rc.id as reporting_category_id
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
    rc.term as reporting_category,
    rc.id as reporting_category_id
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
    rc.term as reporting_category,
    rc.id as reporting_category_id
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

CREATE OR REPLACE VIEW detail_termlists_terms AS
SELECT tlt.id,
    tlt.term_id,
    t.term,
    tlt.termlist_id,
    tl.title AS termlist,
    tlt.meaning_id,
    tlt.preferred,
    tltp.id as parent_id,
    tp.term AS parent,
    tlt.sort_order,
    tl.website_id,
    tlt.created_by_id,
    c.username AS created_by,
    tlt.updated_by_id,
    u.username AS updated_by,
    l.iso,
    tl.external_key AS termlist_external_key,
    tltpref.image_path AS preferred_image_path
  FROM termlists_terms tlt
     JOIN termlists tl ON tl.id = tlt.termlist_id AND tl.deleted = false
     JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
     JOIN languages l ON l.id = t.language_id AND l.deleted = false
     JOIN users c ON c.id = tlt.created_by_id
     JOIN users u ON u.id = tlt.updated_by_id
     JOIN termlists_terms tltpref on tltpref.meaning_id=tlt.meaning_id and tltpref.termlist_id=tlt.termlist_id and tltpref.preferred=true
     LEFT JOIN termlists_terms tltppref ON tltppref.id = tltpref.parent_id AND tltppref.deleted=false
     LEFT JOIN (termlists_terms tltp
       JOIN terms tp ON tp.id = tltp.term_id AND tp.deleted=false
     ) ON tltp.meaning_id=tltppref.meaning_id AND tltp.termlist_id=tltppref.termlist_id AND tltp.deleted=false
       AND tp.language_id=t.language_id
  WHERE tlt.deleted = false
  ORDER BY tlt.sort_order, t.term;

CREATE OR REPLACE VIEW lookup_terms AS 
  SELECT tt.id AS id, tt.meaning_id, tt.termlist_id, t.term
   FROM termlists_terms tt
   JOIN terms t ON tt.term_id = t.id AND t.deleted = false
  WHERE tt.deleted = false;

CREATE OR REPLACE VIEW list_terms AS 
  SELECT t.id, t.term, t.language_id, l.language, l.iso, NULL::integer AS website_id
   FROM terms t
   JOIN languages l ON l.id = t.language_id AND l.deleted=false
  WHERE t.deleted=false;

CREATE OR REPLACE VIEW list_termlists_terms AS 
  SELECT tlt.id, tlt.term_id, t.term, tlt.termlist_id, tl.title AS termlist, tl.website_id, tl.external_key AS termlist_external_key, l.iso, tlt.sort_order
   FROM termlists_terms tlt
   JOIN termlists tl ON tl.id = tlt.termlist_id AND tl.deleted = false
   JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
   JOIN languages l ON l.id = t.language_id AND l.deleted = false
  WHERE tlt.deleted = false
  ORDER BY tlt.sort_order, t.term;

CREATE OR REPLACE VIEW list_taxon_codes AS 
  select tc.id, tc.taxon_meaning_id, tc.code, t.term
  from taxon_codes tc
  join termlists_terms tlt on tlt.id=tc.code_type_id and tlt.deleted=false
  join terms t on t.id=tlt.term_id and t.deleted=false
  where tc.deleted=false;

CREATE OR REPLACE VIEW gv_taxon_codes AS 
  select tc.id, tc.taxon_meaning_id, tc.code, t.term
  from taxon_codes tc
  join termlists_terms tlt on tlt.id=tc.code_type_id and tlt.deleted=false
  join terms t on t.id=tlt.term_id and t.deleted=false
  where tc.deleted=false;

CREATE OR REPLACE VIEW detail_taxon_codes AS 
  select tc.id, tc.taxon_meaning_id, tc.code, t.term, tc.created_by_id, c.username AS created_by, tc.created_on, tc.updated_by_id, u.username AS updated_by, tc.updated_on
  from taxon_codes tc
  join termlists_terms tlt on tlt.id=tc.code_type_id and tlt.deleted=false
  join terms t on t.id=tlt.term_id and t.deleted=false
  join users c ON c.id = tc.created_by_id
  join users u ON u.id = tc.updated_by_id
  where tc.deleted=false;

CREATE OR REPLACE VIEW gv_user_identifiers AS 
 SELECT um.id, um.identifier, um.user_id, u.person_id, t.term as type
   FROM user_identifiers um
   JOIN users u ON u.id=um.user_id and u.deleted=false
   JOIN termlists_terms tlt on tlt.id=um.type_id and tlt.deleted=false
   JOIN terms t on t.id=tlt.term_id and t.deleted=false
  WHERE um.deleted = false;

CREATE OR REPLACE VIEW gv_termlists_terms AS 
 SELECT tt.id, tt.termlist_id, tt.term_id, tt.created_on, tt.created_by_id, tt.updated_on, tt.updated_by_id, tt.parent_id, tt.meaning_id, tt.preferred, tt.sort_order, tt.deleted, t.term, l.language
   FROM termlists_terms tt
   JOIN terms t ON tt.term_id = t.id AND t.deleted=false
   JOIN languages l ON t.language_id = l.id AND l.deleted=false
   WHERE tt.deleted=false;

CREATE VIEW gv_locations AS
 SELECT l.id,
    l.name,
    l.code,
    l.centroid_sref,
    lw.website_id,
    t.term AS type,
    CASE
      WHEN l.public = true THEN '&lt;public&gt;'::character varying
      ELSE COALESCE(w.title, '&lt;none&gt;'::character varying)
    END AS website,
    l.parent_id
   FROM locations l
     LEFT JOIN locations_websites lw ON l.id = lw.location_id and lw.deleted=false
     LEFT JOIN websites w ON w.id = lw.website_id AND w.deleted = false
     LEFT JOIN termlists_terms tlt ON tlt.id = l.location_type_id
     LEFT JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
  WHERE l.deleted = false;

CREATE VIEW detail_terms AS
  SELECT t.id, t.term, t.language_id, l.language, l.iso, t.created_by_id,
      c.username AS created_by, t.updated_by_id, u.username AS updated_by, cast (null as integer) as website_id
  FROM (((terms t JOIN languages l ON ((l.id = t.language_id))) JOIN users c
      ON ((c.id = t.created_by_id))) JOIN users u ON ((u.id = t.updated_by_id)));