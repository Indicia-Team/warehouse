CREATE TABLE IF NOT EXISTS classification_results_sample_media
(
  id serial NOT NULL,
  classification_result_id integer NOT NULL,
  sample_media_id integer NOT NULL,
  CONSTRAINT classification_results_sample_media_classification_result_id_fk FOREIGN KEY (classification_result_id)
        REFERENCES classification_results (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT classification_results_sample_media_sample_media_id_fk FOREIGN KEY (sample_media_id)
        REFERENCES sample_media (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT classification_results_sample_media_pkey PRIMARY KEY (id)
);

CREATE UNIQUE INDEX idx_classification_results_sample_media_unique
  ON classification_results_sample_media (classification_result_id, sample_media_id);

COMMENT ON TABLE classification_results_sample_media
  IS 'Join table that links classification results to the sample media files that were used.';
COMMENT ON COLUMN classification_results_sample_media.classification_result_id IS 'Foreign key to the classification_result table. Identifies the set of classification results this file was submitted for.';
COMMENT ON COLUMN classification_results_sample_media.sample_media_id IS 'Foreign key to the sample_media table. Identifies the submitted media file.';

CREATE TABLE IF NOT EXISTS classification_results_location_media
(
  id serial NOT NULL,
  classification_result_id integer NOT NULL,
  location_media_id integer NOT NULL,
  CONSTRAINT classification_results_location_media_classification_result_id_fk FOREIGN KEY (classification_result_id)
        REFERENCES classification_results (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT classification_results_location_media_location_media_id_fk FOREIGN KEY (location_media_id)
        REFERENCES location_media (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT classification_results_location_media_pkey PRIMARY KEY (id)
);

CREATE UNIQUE INDEX idx_classification_results_location_media_unique
  ON classification_results_location_media (classification_result_id, location_media_id);

COMMENT ON TABLE classification_results_location_media
  IS 'Join table that links classification results to the location media files that were used.';
COMMENT ON COLUMN classification_results_location_media.classification_result_id IS 'Foreign key to the classification_result table. Identifies the set of classification results this file was submitted for.';
COMMENT ON COLUMN classification_results_location_media.location_media_id IS 'Foreign key to the location_media table. Identifies the submitted media file.';

ALTER TABLE sample_attribute_values
  ADD COLUMN IF NOT EXISTS classification_event_id integer,
  ADD COLUMN IF NOT EXISTS machine_involvement smallint,
  ADD CONSTRAINT fk_sample_attribute_values_classification_events FOREIGN KEY (classification_event_id) REFERENCES classification_events(id),
  ADD CONSTRAINT chk_sample_attribute_values_machine_involvement CHECK (machine_involvement BETWEEN 0 AND 5);

CREATE INDEX IF NOT EXISTS fki_sample_attribute_values_classification_event ON sample_attribute_values USING btree (classification_event_id);

ALTER TABLE occurrence_attribute_values
  ADD COLUMN IF NOT EXISTS classification_event_id integer,
  ADD COLUMN IF NOT EXISTS machine_involvement smallint,
  ADD CONSTRAINT fk_occurrence_attribute_values_classification_events FOREIGN KEY (classification_event_id) REFERENCES classification_events(id),
  ADD CONSTRAINT chk_occurrence_attribute_values_machine_involvement CHECK (machine_involvement BETWEEN 0 AND 5);

CREATE INDEX IF NOT EXISTS fki_occurrence_attribute_values_classification_event ON occurrence_attribute_values USING btree (classification_event_id);

ALTER TABLE location_attribute_values
  ADD COLUMN IF NOT EXISTS classification_event_id integer,
  ADD COLUMN IF NOT EXISTS machine_involvement smallint,
  ADD CONSTRAINT fk_location_attribute_values_classification_events FOREIGN KEY (classification_event_id) REFERENCES classification_events(id),
  ADD CONSTRAINT chk_location_attribute_values_machine_involvement CHECK (machine_involvement BETWEEN 0 AND 5);

CREATE INDEX IF NOT EXISTS fki_location_attribute_values_classification_event ON location_attribute_values USING btree (classification_event_id);

CREATE TABLE IF NOT EXISTS classification_lookup_suggestions
(
  id serial NOT NULL,
  classification_result_id integer NOT NULL,
  sample_attribute_id integer,
  occurrence_attribute_id integer,
  location_attribute_id integer,
  term_given varchar NOT NULL,
  termlists_term_id integer,
  probability_given real,
  classifier_chosen boolean NOT NULL DEFAULT false,
  human_chosen boolean NOT NULL DEFAULT false,
  created_by_id integer,
  created_on timestamp without time zone NOT NULL,
  deleted boolean DEFAULT false NOT NULL,
  CONSTRAINT pk_classification_lookup_suggestions PRIMARY KEY (id),
  CONSTRAINT fk_classification_lookup_suggestions_result FOREIGN KEY (classification_result_id)
        REFERENCES classification_results (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_lookup_suggestions_sample_attribute FOREIGN KEY (sample_attribute_id)
        REFERENCES sample_attributes (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_lookup_suggestions_occurrence_attribute FOREIGN KEY (occurrence_attribute_id)
        REFERENCES occurrence_attributes (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_lookup_suggestions_location_attribute FOREIGN KEY (location_attribute_id)
        REFERENCES location_attributes (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_lookup_suggestions_term FOREIGN KEY (termlists_term_id)
        REFERENCES termlists_terms (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_lookup_suggestions_creator FOREIGN KEY (created_by_id)
    REFERENCES users (id) MATCH SIMPLE
    ON UPDATE NO ACTION ON DELETE NO ACTION
);

DO $check_classification_lookup_suggestions_target$
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM pg_constraint
    WHERE conname = 'chk_classification_lookup_suggestions_single_target'
  ) THEN
    ALTER TABLE classification_lookup_suggestions
      ADD CONSTRAINT chk_classification_lookup_suggestions_single_target
      CHECK (
        (
          CASE WHEN sample_attribute_id IS NULL THEN 0 ELSE 1 END +
          CASE WHEN occurrence_attribute_id IS NULL THEN 0 ELSE 1 END +
          CASE WHEN location_attribute_id IS NULL THEN 0 ELSE 1 END
        ) = 1
      );
  END IF;
END
$check_classification_lookup_suggestions_target$;

CREATE INDEX IF NOT EXISTS fki_classification_lookup_suggestions_result ON classification_lookup_suggestions USING btree (classification_result_id);
CREATE INDEX IF NOT EXISTS fki_classification_lookup_suggestions_sample_attribute ON classification_lookup_suggestions USING btree (sample_attribute_id);
CREATE INDEX IF NOT EXISTS fki_classification_lookup_suggestions_occurrence_attribute ON classification_lookup_suggestions USING btree (occurrence_attribute_id);
CREATE INDEX IF NOT EXISTS fki_classification_lookup_suggestions_location_attribute ON classification_lookup_suggestions USING btree (location_attribute_id);
CREATE INDEX IF NOT EXISTS fki_classification_lookup_suggestions_term ON classification_lookup_suggestions USING btree (termlists_term_id);

COMMENT ON TABLE classification_lookup_suggestions
  IS 'Individual suggested terms as a result of a request sent to an image classification service. This allows a classifier to suggest terms for attribute values such as habitat.';
COMMENT ON COLUMN classification_lookup_suggestions.classification_result_id IS 'Foreign key to the classification_results table, identifies the classification result this suggestion belongs to.';
COMMENT ON COLUMN classification_lookup_suggestions.sample_attribute_id IS 'Foreign key to the sample_attributes table, identifies the sample attribute that this suggestion is for.';
COMMENT ON COLUMN classification_lookup_suggestions.occurrence_attribute_id IS 'Foreign key to the occurrence_attributes table, identifies the occurrence attribute that this suggestion is for.';
COMMENT ON COLUMN classification_lookup_suggestions.location_attribute_id IS 'Foreign key to the location_attributes table, identifies the location attribute that this suggestion is for.';
COMMENT ON COLUMN classification_lookup_suggestions.term_given IS 'Term suggested by the classifier.';
COMMENT ON COLUMN classification_lookup_suggestions.termlists_term_id IS 'Foreign key to the termlists_terms table. Identifies the term in Indicia''s term list that this suggestion refers to. May be null if no match made.';
COMMENT ON COLUMN classification_lookup_suggestions.probability_given IS 'Probability between 0 and 1 assigned for this suggestion by the classifier.';
COMMENT ON COLUMN classification_lookup_suggestions.classifier_chosen IS 'True if this suggestion was given with confidence by the classifier.';
COMMENT ON COLUMN classification_lookup_suggestions.human_chosen IS 'True if a human accepted this suggestion in order to determine the occurrence.';
COMMENT ON COLUMN classification_lookup_suggestions.created_by_id IS 'Foreign key to the users table (creator)';
COMMENT ON COLUMN classification_lookup_suggestions.created_on IS 'Date and time this result was created.';
COMMENT ON COLUMN classification_lookup_suggestions.deleted IS 'Has this record been deleted?';
