-- #slow script#

-- Add missing covering indexes.

CREATE INDEX fki_occurrence_classification_event ON occurrences USING btree (classification_event_id);
CREATE INDEX fki_determination_classification_event ON determinations USING btree (classification_event_id);
CREATE INDEX fki_classification_results_event ON classification_results USING btree (classification_event_id);
CREATE INDEX fki_classification_results_classifier_term ON classification_results USING btree (classifier_id);
CREATE INDEX fki_classification_suggestions_result ON classification_suggestions USING btree (classification_result_id);
CREATE INDEX fki_classification_suggestions_taxon ON classification_suggestions USING btree (taxa_taxon_list_id);
CREATE INDEX fki_classification_results_occurrence_media_occurrence ON classification_results_occurrence_media USING btree (occurrence_media_id);
