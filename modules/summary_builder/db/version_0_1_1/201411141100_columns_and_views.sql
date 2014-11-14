

--- need to rebuild all the data to add in the new columns
DELETE FROM summary_occurrences;

ALTER TABLE summary_occurrences
  ADD COLUMN year INTEGER NOT NULL;
ALTER TABLE summary_occurrences
  ADD COLUMN period_number INTEGER NOT NULL;
ALTER TABLE summary_occurrences
  ADD COLUMN taxon_list_id INTEGER;

CREATE OR REPLACE VIEW list_summary_occurrences AS
  SELECT website_id, survey_id,
      year, location_id, user_id, date_start, date_end, date_type, type, period_number,
      taxa_taxon_list_id, preferred_taxa_taxon_list_id, taxonomic_sort_order, taxon, preferred_taxon, default_common_name, taxon_meaning_id, taxon_list_id,
      count, estimate, created_by_id, summary_created_on
    FROM summary_occurrences;

COMMENT ON TABLE summariser_definitions IS 'List of action definitions used by the summary_builder module to create summary data.';
COMMENT ON COLUMN summariser_definitions.survey_id IS 'Survey this action is associated with.';
COMMENT ON COLUMN summariser_definitions.period_type IS 'Period (weekly or monthly) over which thie data is summarised.';
COMMENT ON COLUMN summariser_definitions.period_start IS 'Definition of when a summary period begins.';
COMMENT ON COLUMN summariser_definitions.period_one_contains IS 'Definition of period one.';
COMMENT ON COLUMN summariser_definitions.occurrence_attribute_id IS 'Count attribute to summarise, null if occurrence counts as 1';
COMMENT ON COLUMN summariser_definitions.calculate_estimates IS 'Do we calculate estimates?';
COMMENT ON COLUMN summariser_definitions.data_combination_method IS 'How data is summarised.';
COMMENT ON COLUMN summariser_definitions.data_rounding_method IS 'How data values are rounded.';
COMMENT ON COLUMN summariser_definitions.interpolation IS 'How estimates are calculated in empty periods.';
COMMENT ON COLUMN summariser_definitions.season_limits IS 'First and last period numbers for the season limits: first and last value calculations only take place within these';
COMMENT ON COLUMN summariser_definitions.first_value IS 'Special processing of first value in year.';
COMMENT ON COLUMN summariser_definitions.last_value IS 'Special processing of last value in year.';

COMMENT ON TABLE summary_occurrences IS 'Summary of occurrence data used for reporting.';
COMMENT ON COLUMN summary_occurrences.location_id IS 'Summary data location id, null if for all locations.';
COMMENT ON COLUMN summary_occurrences.user_id IS 'ID of user who created the data summarised in this record, null if for all users.';
COMMENT ON COLUMN summary_occurrences.date_start IS 'Start of period covered by this summary record.';
COMMENT ON COLUMN summary_occurrences.date_end IS 'Start of period covered by this summary record.';
COMMENT ON COLUMN summary_occurrences.date_type IS 'Standard Indicia date type code for this period.';
COMMENT ON COLUMN summary_occurrences.type IS 'summariser_definition period type.';
COMMENT ON COLUMN summary_occurrences.count IS 'Actual Summary data, null if estimate created in period with no data.';
COMMENT ON COLUMN summary_occurrences.estimate IS 'Estimate for period.';
COMMENT ON COLUMN summary_occurrences.period_number IS 'Number of this period within year.';
COMMENT ON COLUMN summary_occurrences.estimate IS 'Estimate for period.';

