ALTER TABLE species_alerts
  ADD COLUMN survey_id integer,
  ADD CONSTRAINT fk_species_alerts_survey FOREIGN KEY (survey_id)
        REFERENCES surveys (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION;

COMMENT ON COLUMN species_alerts.survey_id IS 'Survey that this alert is limited to, if appropriate.';