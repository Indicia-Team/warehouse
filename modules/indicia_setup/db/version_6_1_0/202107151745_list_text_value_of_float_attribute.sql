-- For each view, `list_*_attribute values`, alter the `value` field
-- for float data types to return the `text_value`, if there is one.
-- The text value is the entered value, including trailing zeroes,
-- which we need when a decimal validation rule is in place checking
-- the number of digits after the decimal place.
-- Decimal validation and ranges do not play nicely together.

-- I.e.
-- 
-- WHEN 'F'::bpchar THEN av.float_value::text ||
-- CASE
--     WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
--     ELSE ''::text
-- END
--
-- becomes
--
-- WHEN 'F'::bpchar THEN
-- CASE
--     WHEN av.text_value IS NOT NULL THEN av.text_value
--     ELSE av.float_value::text ||
--     CASE
--         WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
--         ELSE ''::text
--     END
-- END


CREATE OR REPLACE VIEW indicia.list_location_attribute_values
 AS
 SELECT av.id,
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
                WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                ELSE ''::text
            END
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN
            CASE
                WHEN av.text_value IS NOT NULL THEN av.text_value
                ELSE av.float_value::text ||
                CASE
                    WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                    ELSE ''::text
                END
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
            WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN av.upper_value
            ELSE NULL::double precision
        END AS upper_value,
    a.termlist_id,
    lg.iso,
    lw.website_id,
    l.location_type_id,
    rc.term AS reporting_category,
    rc.id AS reporting_category_id
   FROM indicia.locations l
     JOIN indicia.locations_websites lw ON lw.location_id = l.id AND lw.deleted = false
     JOIN indicia.location_attribute_values av ON av.location_id = l.id AND av.deleted = false
     JOIN indicia.location_attributes a ON a.id = av.location_attribute_id AND a.deleted = false
     LEFT JOIN (indicia.termlists_terms tt
     JOIN indicia.terms t ON t.id = tt.term_id
     JOIN indicia.languages lg ON lg.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
     LEFT JOIN indicia.cache_termlists_terms rc ON rc.id = a.reporting_category_id
  WHERE l.deleted = false
  ORDER BY a.id;

CREATE OR REPLACE VIEW indicia.list_occurrence_attribute_values
 AS
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
                WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                ELSE ''::text
            END
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN
            CASE
                WHEN av.text_value IS NOT NULL THEN av.text_value
                ELSE av.float_value::text ||
                CASE
                    WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                    ELSE ''::text
                END
            END
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS value,
        CASE a.data_type
            WHEN 'T'::bpchar THEN av.text_value
            WHEN 'L'::bpchar THEN av.int_value::text
            WHEN 'I'::bpchar THEN av.int_value::text
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN av.float_value::text
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS raw_value,
        CASE
            WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN av.upper_value
            ELSE NULL::double precision
        END AS upper_value,
    a.termlist_id,
    l.iso,
    aw.website_id,
    rc.term AS reporting_category,
    rc.id AS reporting_category_id
   FROM indicia.occurrences o
     JOIN indicia.samples s ON s.id = o.sample_id AND s.deleted = false
     JOIN indicia.surveys su ON su.id = s.survey_id AND su.deleted = false
     JOIN indicia.occurrence_attributes_websites aw ON aw.website_id = su.website_id AND (aw.restrict_to_survey_id = su.id OR aw.restrict_to_survey_id IS NULL) AND aw.deleted = false
     JOIN indicia.occurrence_attributes a ON a.id = aw.occurrence_attribute_id AND a.deleted = false
     LEFT JOIN indicia.occurrence_attribute_values av ON av.occurrence_attribute_id = a.id AND av.occurrence_id = o.id AND av.deleted = false
     LEFT JOIN (indicia.termlists_terms tt
     JOIN indicia.terms t ON t.id = tt.term_id
     JOIN indicia.languages l ON l.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
     LEFT JOIN indicia.cache_termlists_terms rc ON rc.id = a.reporting_category_id
  WHERE o.deleted = false
  ORDER BY a.id;

CREATE OR REPLACE VIEW indicia.list_person_attribute_values
 AS
 SELECT av.id,
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
                WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                ELSE ''::text
            END
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN
            CASE
                WHEN av.text_value IS NOT NULL THEN av.text_value
                ELSE av.float_value::text ||
                CASE
                    WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                    ELSE ''::text
                END
            END
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS value,
        CASE a.data_type
            WHEN 'T'::bpchar THEN av.text_value
            WHEN 'L'::bpchar THEN av.int_value::text
            WHEN 'I'::bpchar THEN av.int_value::text
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN av.float_value::text
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS raw_value,
        CASE
            WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN av.upper_value
            ELSE NULL::double precision
        END AS upper_value,
    a.termlist_id,
    l.iso,
    aw.website_id,
    rc.term AS reporting_category,
    rc.id AS reporting_category_id
   FROM indicia.people p
     LEFT JOIN indicia.person_attribute_values av ON av.person_id = p.id AND av.deleted = false
     LEFT JOIN (indicia.users u
     JOIN indicia.users_websites uw ON uw.user_id = u.id AND uw.site_role_id IS NOT NULL
     JOIN indicia.person_attributes_websites aw ON aw.website_id = uw.website_id AND aw.deleted = false) ON u.person_id = p.id
     JOIN indicia.person_attributes a ON (a.id = COALESCE(av.person_attribute_id, aw.person_attribute_id) OR a.public = true) AND (a.id = av.person_attribute_id OR av.id IS NULL) AND a.deleted = false
     LEFT JOIN (indicia.termlists_terms tt
     JOIN indicia.terms t ON t.id = tt.term_id
     JOIN indicia.languages l ON l.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
     LEFT JOIN indicia.cache_termlists_terms rc ON rc.id = a.reporting_category_id
  WHERE p.deleted = false
  ORDER BY a.id;

CREATE OR REPLACE VIEW indicia.list_sample_attribute_values
 AS
 SELECT av.id,
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
                WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                ELSE ''::text
            END
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN
            CASE
                WHEN av.text_value IS NOT NULL THEN av.text_value
                ELSE av.float_value::text ||
                CASE
                    WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                    ELSE ''::text
                END
            END
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS value,
        CASE a.data_type
            WHEN 'T'::bpchar THEN av.text_value
            WHEN 'L'::bpchar THEN av.int_value::text
            WHEN 'I'::bpchar THEN av.int_value::text
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN av.float_value::text
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS raw_value,
        CASE
            WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN av.upper_value
            ELSE NULL::double precision
        END AS upper_value,
    a.termlist_id,
    l.iso,
    aw.website_id,
    rc.term AS reporting_category,
    rc.id AS reporting_category_id
   FROM indicia.samples s
     JOIN indicia.surveys su ON su.id = s.survey_id AND su.deleted = false
     JOIN indicia.sample_attributes_websites aw ON aw.website_id = su.website_id AND (aw.restrict_to_survey_id = su.id OR aw.restrict_to_survey_id IS NULL) AND aw.deleted = false
     JOIN indicia.sample_attributes a ON a.id = aw.sample_attribute_id AND a.deleted = false
     LEFT JOIN indicia.sample_attribute_values av ON av.sample_attribute_id = a.id AND av.sample_id = s.id AND av.deleted = false
     LEFT JOIN (indicia.termlists_terms tt
     JOIN indicia.terms t ON t.id = tt.term_id
     JOIN indicia.languages l ON l.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
     LEFT JOIN indicia.cache_termlists_terms rc ON rc.id = a.reporting_category_id
  WHERE s.deleted = false
  ORDER BY a.id;

CREATE OR REPLACE VIEW indicia.list_survey_attribute_values
 AS
 SELECT av.id,
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
                WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                ELSE ''::text
            END
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN
            CASE
                WHEN av.text_value IS NOT NULL THEN av.text_value
                ELSE av.float_value::text ||
                CASE
                    WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                    ELSE ''::text
                END
            END
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS value,
        CASE a.data_type
            WHEN 'T'::bpchar THEN av.text_value
            WHEN 'L'::bpchar THEN av.int_value::text
            WHEN 'I'::bpchar THEN av.int_value::text
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN av.float_value::text
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS raw_value,
        CASE
            WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN av.upper_value
            ELSE NULL::double precision
        END AS upper_value,
    a.termlist_id,
    l.iso,
    s.website_id,
    rc.term AS reporting_category,
    rc.id AS reporting_category_id
   FROM indicia.surveys s
     JOIN indicia.survey_attribute_values av ON av.survey_id = s.id AND av.deleted = false
     JOIN indicia.survey_attributes a ON a.id = av.survey_attribute_id AND a.deleted = false
     LEFT JOIN (indicia.termlists_terms tt
     JOIN indicia.terms t ON t.id = tt.term_id
     JOIN indicia.languages l ON l.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
     LEFT JOIN indicia.cache_termlists_terms rc ON rc.id = a.reporting_category_id
  WHERE s.deleted = false
  ORDER BY a.id;

CREATE OR REPLACE VIEW indicia.list_taxa_taxon_list_attribute_values
 AS
 SELECT NULL::integer AS id,
    NULL::integer AS taxa_taxon_list_id,
    a.id AS taxa_taxon_list_attribute_id,
    a.caption,
    a.caption_i18n::text AS caption_i18n,
    a.description,
    a.description_i18n::text AS description_i18n,
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
    NULL::text AS value,
    NULL::text AS raw_value,
    NULL::double precision AS upper_value,
    a.termlist_id,
    NULL::bpchar AS iso,
    tlttla.taxon_list_id,
    tl.website_id,
    rc.term AS reporting_category,
    rc.id AS reporting_category_id
   FROM indicia.taxa_taxon_list_attributes a
     JOIN indicia.taxon_lists_taxa_taxon_list_attributes tlttla ON tlttla.taxa_taxon_list_attribute_id = a.id AND tlttla.deleted = false
     JOIN indicia.taxon_lists tl ON tl.id = tlttla.taxon_list_id AND tl.deleted = false
     LEFT JOIN indicia.cache_termlists_terms rc ON rc.id = a.reporting_category_id
  WHERE a.deleted = false
UNION
 SELECT av.id,
    ttl.id AS taxa_taxon_list_id,
    a.id AS taxa_taxon_list_attribute_id,
    a.caption,
    a.caption_i18n::text AS caption_i18n,
    a.description,
    a.description_i18n::text AS description_i18n,
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
                WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                ELSE ''::text
            END
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN
            CASE
                WHEN av.text_value IS NOT NULL THEN av.text_value
                ELSE av.float_value::text ||
                CASE
                    WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                    ELSE ''::text
                END
            END
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN (av.date_start_value::text || ' - '::text) || av.date_end_value::text
            WHEN 'G'::bpchar THEN st_astext(av.geom_value)
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
            WHEN 'G'::bpchar THEN st_astext(av.geom_value)
            ELSE NULL::text
        END AS raw_value,
        CASE
            WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN av.upper_value
            ELSE NULL::double precision
        END AS upper_value,
    a.termlist_id,
    l.iso,
    tlttla.taxon_list_id,
    tl.website_id,
    rc.term AS reporting_category,
    rc.id AS reporting_category_id
   FROM indicia.taxa_taxon_lists ttl
     JOIN indicia.taxon_lists_taxa_taxon_list_attributes tlttla ON tlttla.taxon_list_id = ttl.taxon_list_id AND tlttla.deleted = false
     JOIN indicia.taxon_lists tl ON tl.id = tlttla.taxon_list_id AND tl.deleted = false
     JOIN indicia.taxa_taxon_list_attributes a ON a.id = tlttla.taxa_taxon_list_attribute_id AND a.deleted = false
     LEFT JOIN indicia.taxa_taxon_list_attribute_values av ON av.taxa_taxon_list_attribute_id = a.id AND av.taxa_taxon_list_id = ttl.id AND av.deleted = false
     LEFT JOIN (indicia.termlists_terms tt
     JOIN indicia.terms t ON t.id = tt.term_id AND t.deleted = false
     JOIN indicia.languages l ON l.id = t.language_id AND l.deleted = false) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar AND tt.deleted = false
     LEFT JOIN indicia.cache_termlists_terms rc ON rc.id = a.reporting_category_id
  WHERE ttl.deleted = false;

CREATE OR REPLACE VIEW indicia.list_termlists_term_attribute_values
 AS
 SELECT av.id,
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
                WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                ELSE ''::text
            END
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN
            CASE
                WHEN av.text_value IS NOT NULL THEN av.text_value
                ELSE av.float_value::text ||
                CASE
                    WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN COALESCE(' - '::text || av.upper_value::text, ''::text)
                    ELSE ''::text
                END
            END
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS value,
        CASE a.data_type
            WHEN 'T'::bpchar THEN av.text_value
            WHEN 'L'::bpchar THEN av.int_value::text
            WHEN 'I'::bpchar THEN av.int_value::text
            WHEN 'B'::bpchar THEN av.int_value::text
            WHEN 'F'::bpchar THEN av.float_value::text
            WHEN 'D'::bpchar THEN av.date_start_value::text
            WHEN 'V'::bpchar THEN indicia.vague_date_to_raw_string(av.date_start_value, av.date_end_value, av.date_type_value)::text
            ELSE NULL::text
        END AS raw_value,
        CASE
            WHEN (a.data_type = ANY (ARRAY['I'::bpchar, 'F'::bpchar])) AND a.allow_ranges = true THEN av.upper_value
            ELSE NULL::double precision
        END AS upper_value,
    a.termlist_id AS lookup_termlist_id,
    l.iso,
    tl.website_id,
    rc.term AS reporting_category,
    rc.id AS reporting_category_id
   FROM indicia.termlists_terms tlt
     JOIN indicia.termlists_term_attribute_values av ON av.termlists_term_id = tlt.id AND av.deleted = false
     JOIN indicia.termlists_term_attributes a ON a.id = av.termlists_term_attribute_id AND a.deleted = false
     JOIN indicia.termlists tl ON tl.id = tlt.termlist_id AND tl.deleted = false
     LEFT JOIN (indicia.termlists_terms tt
     JOIN indicia.terms t ON t.id = tt.term_id
     JOIN indicia.languages l ON l.id = t.language_id) ON tt.id = av.int_value AND a.data_type = 'L'::bpchar
     LEFT JOIN indicia.cache_termlists_terms rc ON rc.id = a.reporting_category_id
  WHERE tlt.deleted = false
  ORDER BY a.id;
