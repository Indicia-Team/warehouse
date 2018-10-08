
CREATE OR REPLACE VIEW gv_survey_attributes AS
  SELECT a.id, aw.website_id, w.title AS website, a.caption,
    CASE a.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE a.data_type
    END AS data_type, a.public, a.created_by_id, a.deleted,
    CASE WHEN a.system_function IS NULL OR a.system_function='' THEN a.term_name ELSE a.system_function END AS function
  FROM survey_attributes a
  LEFT JOIN survey_attributes_websites aw ON a.id = aw.survey_attribute_id AND aw.deleted = false
  LEFT JOIN websites w ON w.id = aw.website_id AND w.deleted = false
  WHERE a.deleted = false;

CREATE OR REPLACE VIEW gv_sample_attributes AS
  SELECT sa.id, saw.website_id, saw.restrict_to_survey_id AS survey_id, w.title AS website, s.title AS survey, sa.caption,
    CASE sa.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE sa.data_type
    END AS data_type, sa.public, sa.created_by_id, sa.deleted,
    CASE WHEN sa.system_function IS NULL OR sa.system_function='' THEN sa.term_name ELSE sa.system_function END AS function
  FROM sample_attributes sa
  JOIN sample_attributes_websites saw ON sa.id = saw.sample_attribute_id AND saw.deleted = false
  LEFT JOIN websites w ON w.id = saw.website_id and w.deleted=false
  LEFT JOIN surveys s ON s.id = saw.restrict_to_survey_id and s.deleted=false
  WHERE sa.deleted=false
UNION
  SELECT sa.id, NULL::integer AS website_id, NULL::integer AS survey_id, NULL::text AS website, NULL::text AS survey, sa.caption,
    CASE sa.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE sa.data_type
    END AS data_type, sa.public, sa.created_by_id, sa.deleted,
    CASE WHEN sa.system_function IS NULL OR sa.system_function='' THEN sa.term_name ELSE sa.system_function END AS function
  FROM sample_attributes sa
  WHERE sa.deleted=false;

CREATE OR REPLACE VIEW gv_occurrence_attributes AS
  SELECT oa.id, oaw.website_id, oaw.restrict_to_survey_id AS survey_id, w.title AS website, s.title AS survey, oa.caption,
    CASE oa.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE oa.data_type
    END AS data_type, oa.public, oa.created_by_id, oa.deleted,
    CASE WHEN oa.system_function IS NULL OR oa.system_function='' THEN oa.term_name ELSE oa.system_function END AS function
  FROM occurrence_attributes oa
  JOIN occurrence_attributes_websites oaw ON oa.id = oaw.occurrence_attribute_id AND oaw.deleted = false
  LEFT JOIN websites w ON w.id = oaw.website_id and w.deleted=false
  LEFT JOIN surveys s ON s.id = oaw.restrict_to_survey_id and s.deleted=false
  WHERE oa.deleted=false
UNION
  SELECT oa.id, NULL::integer AS website_id, NULL::integer AS survey_id, NULL::text AS website, NULL::text AS survey, oa.caption,
    CASE oa.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE oa.data_type
    END AS data_type, oa.public, oa.created_by_id, oa.deleted,
    CASE WHEN oa.system_function IS NULL OR oa.system_function='' THEN oa.term_name ELSE oa.system_function END AS function
  FROM occurrence_attributes oa
  WHERE oa.deleted=false;

CREATE OR REPLACE VIEW gv_person_attributes AS
  SELECT pa.id, oaw.website_id, w.title AS website, pa.caption,
    CASE pa.data_type
      WHEN 'T'::bpchar THEN 'Text'::bpchar
      WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
      WHEN 'I'::bpchar THEN 'Integer'::bpchar
      WHEN 'B'::bpchar THEN 'Boolean'::bpchar
      WHEN 'F'::bpchar THEN 'Float'::bpchar
      WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
      WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
      ELSE pa.data_type
  END AS data_type, pa.public, pa.created_by_id, pa.deleted,
  CASE WHEN pa.system_function IS NULL OR pa.system_function='' THEN pa.term_name ELSE pa.system_function END AS function
  FROM person_attributes pa
  LEFT JOIN person_attributes_websites oaw ON pa.id = oaw.person_attribute_id AND oaw.deleted = false
  LEFT JOIN websites w ON w.id = oaw.website_id AND w.deleted = false
  WHERE pa.deleted = false;

CREATE OR REPLACE VIEW gv_taxa_taxon_list_attributes AS
  SELECT ta.id, tl.id as taxon_list_id, tl.title AS taxon_list, ta.caption,
  CASE ta.data_type
    WHEN 'T'::bpchar THEN 'Text'::bpchar
    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
    WHEN 'I'::bpchar THEN 'Integer'::bpchar
    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
    WHEN 'F'::bpchar THEN 'Float'::bpchar
    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
    ELSE ta.data_type
  END AS data_type, ta.public, ta.created_by_id, ta.deleted, tl.website_id,
  CASE WHEN ta.system_function IS NULL OR ta.system_function='' THEN ta.term_name ELSE ta.system_function END AS function
  FROM taxa_taxon_list_attributes ta
  JOIN taxon_lists_taxa_taxon_list_attributes tta ON tta.taxa_taxon_list_attribute_id=ta.id AND tta.deleted=false
  JOIN taxon_lists tl ON tl.id=tta.taxon_list_id AND tl.deleted=false
  WHERE ta.deleted=false
UNION
  SELECT ta.id, null as taxon_list_id, '' as taxon_list, ta.caption,
  CASE ta.data_type
    WHEN 'T'::bpchar THEN 'Text'::bpchar
    WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
    WHEN 'I'::bpchar THEN 'Integer'::bpchar
    WHEN 'B'::bpchar THEN 'Boolean'::bpchar
    WHEN 'F'::bpchar THEN 'Float'::bpchar
    WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
    WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
    ELSE ta.data_type
  END AS data_type, ta.public, ta.created_by_id, ta.deleted, null as website_id,
  CASE WHEN ta.system_function IS NULL OR ta.system_function='' THEN ta.term_name ELSE ta.system_function END AS function
  FROM taxa_taxon_list_attributes ta
  WHERE ta.deleted=false;

CREATE OR REPLACE VIEW gv_termlists_term_attributes AS
 SELECT a.id, tta.termlist_id, tl.title AS termlist, a.caption,
        CASE a.data_type
            WHEN 'T'::bpchar THEN 'Text'::bpchar
            WHEN 'L'::bpchar THEN 'Lookup List'::bpchar
            WHEN 'I'::bpchar THEN 'Integer'::bpchar
            WHEN 'B'::bpchar THEN 'Boolean'::bpchar
            WHEN 'F'::bpchar THEN 'Float'::bpchar
            WHEN 'D'::bpchar THEN 'Specific Date'::bpchar
            WHEN 'V'::bpchar THEN 'Vague Date'::bpchar
            ELSE a.data_type
        END AS data_type, a.public, a.created_by_id, a.deleted, tl.website_id,
        CASE WHEN a.system_function IS NULL OR a.system_function='' THEN a.term_name ELSE a.system_function END AS function
   FROM termlists_term_attributes a
   LEFT JOIN termlists_termlists_term_attributes tta ON tta.termlists_term_attribute_id = a.id AND tta.deleted = false
   LEFT JOIN termlists tl ON tl.id = tta.termlist_id AND tl.deleted = false
  WHERE a.deleted = false;