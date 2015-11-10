

CREATE INDEX ix_summary_occurrences_SL ON summary_occurrences USING btree (survey_id, location_id);

