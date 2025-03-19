

DROP VIEW IF EXISTS gv_summariser_definitions;
DROP VIEW IF EXISTS list_summariser_definitions;

ALTER TABLE summariser_definitions
  DROP COLUMN check_for_missing,
  DROP COLUMN max_records_per_cycle;

CREATE VIEW gv_summariser_definitions AS
  SELECT s.id as survey_id, s.title, w.title AS website, s.website_id, sd.id
  FROM summariser_definitions sd 
  LEFT JOIN surveys s ON sd.survey_id = s.id AND s.deleted = FALSE
  LEFT JOIN websites w ON s.website_id = w.id AND w.deleted = FALSE
  WHERE sd.survey_id = s.id AND sd.deleted = false;

CREATE VIEW list_summariser_definitions AS
  SELECT s.website_id, sd.id, sd.survey_id, sd.period_type, sd.period_start, sd.period_one_contains, sd.calculate_estimates
  FROM summariser_definitions sd 
  JOIN surveys s ON sd.survey_id = s.id AND s.deleted = FALSE
  JOIN websites w ON s.website_id = w.id AND w.deleted = FALSE
  WHERE sd.survey_id = s.id AND sd.deleted = false;

DROP VIEW IF EXISTS list_summary_occurrences;

DROP TABLE summary_occurrences;

CREATE TABLE summary_occurrences
(
  website_id INTEGER NOT NULL,
  survey_id INTEGER NOT NULL,
  location_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  year INTEGER NOT NULL,
  type CHARACTER VARYING NOT NULL,
  taxon_list_id INTEGER NOT NULL,
  taxa_taxon_list_id INTEGER NOT NULL,
  preferred_taxa_taxon_list_id INTEGER NOT NULL,
  taxonomic_sort_order bigint,
  taxon CHARACTER VARYING,
  preferred_taxon CHARACTER VARYING,
  default_common_name CHARACTER VARYING,
  taxon_meaning_id INTEGER NOT NULL,
  summarised_data JSON,
  created_by_id INTEGER NOT NULL,
  summary_created_on timestamp without time zone NOT NULL
) WITH (
  OIDS=FALSE
);
CREATE INDEX ix_summary_occurrences_STLU ON summary_occurrences USING btree (survey_id, year, taxa_taxon_list_id, location_id, user_id);
CREATE INDEX ix_summary_occurrences_STU ON summary_occurrences USING btree (survey_id, taxa_taxon_list_id, user_id);

CREATE OR REPLACE VIEW list_summary_occurrences AS
  SELECT website_id, survey_id,
      year, location_id, user_id, type, 
      taxa_taxon_list_id, preferred_taxa_taxon_list_id, taxonomic_sort_order,
      taxon, preferred_taxon, default_common_name, taxon_meaning_id, taxon_list_id,
      created_by_id, summary_created_on, summarised_data
    FROM summary_occurrences;

COMMENT ON TABLE summary_occurrences IS 'Summary of occurrence data used for reporting.';
COMMENT ON COLUMN summary_occurrences.location_id IS 'Summary data location id, zero if for all locations.';
COMMENT ON COLUMN summary_occurrences.user_id IS 'ID of user who created the data summarised in this record, zero if for all users.';
COMMENT ON COLUMN summary_occurrences.type IS 'summariser_definition period type.';

