INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Media classifiers', 'List of media/image classification services used to identify photos.',
    now(), 1, now(), 1, 'indicia:classifiers');

SELECT insert_term('Unknown', 'eng', null, 'indicia:classifiers');

CREATE TABLE IF NOT EXISTS classification_events
(
  id serial NOT NULL,
  created_by_id integer,
  created_on timestamp without time zone NOT NULL,
  deleted boolean DEFAULT false NOT NULL,
  CONSTRAINT pk_classification_events PRIMARY KEY (id),
  CONSTRAINT fk_classification_events_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE classification_events
  IS 'A single classification event, which may involve requesting suggestions from one or more classifiers. Groups together the classification results that resulted in a single occurrence, or the redetermination of an occurrence.';
COMMENT ON COLUMN classification_events.created_by_id IS 'Foreign key to the users table (creator)';
COMMENT ON COLUMN classification_events.created_on IS 'Date and time this result was created.';
COMMENT ON COLUMN classification_events.deleted IS 'Has this record been deleted?';

ALTER TABLE occurrences
  ADD COLUMN classification_event_id integer,
  ADD COLUMN machine_involvement smallint,
  ADD CONSTRAINT fk_occurrences_classification_events FOREIGN KEY (classification_event_id) REFERENCES classification_events(id),
  ADD CONSTRAINT chk_occurrences_machine_involvement CHECK (machine_involvement BETWEEN 0 AND 5);

COMMENT ON COLUMN occurrences.classification_event_id IS 'Foreign key to the classification_events table. Identifies the usage of image classifiers in formulating the identification associated with this occurrence.';
COMMENT ON COLUMN occurrences.machine_involvement IS 'Identifies the involvement of machine image classifiers in the identification.
  Null = unknown;
  0 = no involvement;
  1 = human determined, machine suggestions were ignored;
  2 = human chose a machine suggestion given a very low probability;
  3 = human chose a machine suggestion that was less-preferred;
  4 = human chose a machine suggestion that was the preferred choice;
  5 = machine determined with no human involvement.';

ALTER TABLE determinations
  ADD COLUMN classification_event_id integer,
  ADD COLUMN machine_involvement smallint,
  ADD CONSTRAINT fk_determinations_classification_events FOREIGN KEY (classification_event_id) REFERENCES classification_events(id),
  ADD CONSTRAINT chk_determinations_machine_involvement CHECK (machine_involvement BETWEEN 0 AND 5);

COMMENT ON COLUMN determinations.classification_event_id IS 'Foreign key to the classification_events table. Identifies the usage of image classifiers in formulating this determination.';
COMMENT ON COLUMN determinations.machine_involvement IS 'Identifies the involvement of machine image classifiers in the identification.
  Null = no involvement;
  0 = no involvement;
  1 = human determined, machine suggestions were ignored;
  2 = human chose a machine suggestion given a very low probability;
  3 = human chose a machine suggestion that was less-preferred;
  4 = human chose a machine suggestion that was the preferred choice;
  5 = machine determined with no human involvement.';

CREATE TABLE IF NOT EXISTS classification_results
(
  id serial NOT NULL,
  classification_event_id integer NOT NULL,
  classifier_id integer NOT NULL,
  classifier_version varchar,
  additional_info_submitted json,
  results_raw json,
  created_by_id integer,
  created_on timestamp without time zone NOT NULL,
  deleted boolean DEFAULT false NOT NULL,
  CONSTRAINT pk_classification_results PRIMARY KEY (id),
  CONSTRAINT fk_classification_results_event FOREIGN KEY (classification_event_id)
      REFERENCES classification_events (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_results_classifier_term FOREIGN KEY (classifier_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_results_creator FOREIGN KEY (created_by_id)
    REFERENCES users (id) MATCH SIMPLE
    ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE classification_results
  IS 'Results of media/image classification services used to identify photos.';
COMMENT ON COLUMN classification_results.classification_event_id IS 'Foreign key to the classification_events table. Identifies the classification event the results were associated with.';
COMMENT ON COLUMN classification_results.classifier_id IS 'Foreign key to the termlists_terms table, identifies the name of the classification service.';
COMMENT ON COLUMN classification_results.classifier_version IS 'Optional version label of the service called.';
COMMENT ON COLUMN classification_results.additional_info_submitted IS 'JSON object defining any additional field values that were submitted with the photos to aid classification, e.g. latitude and longitude.';
COMMENT ON COLUMN classification_results.results_raw IS 'Optional raw response data.';
COMMENT ON COLUMN classification_results.created_by_id IS 'Foreign key to the users table (creator)';
COMMENT ON COLUMN classification_results.created_on IS 'Date and time this result was created.';
COMMENT ON COLUMN classification_results.deleted IS 'Has this record been deleted?';

CREATE TABLE IF NOT EXISTS classification_suggestions
(
  id serial NOT NULL,
  classification_result_id integer NOT NULL,
  taxon_name_given varchar NOT NULL,
  taxa_taxon_list_id integer,
  probability_given real,
  classifier_chosen boolean NOT NULL DEFAULT false,
  human_chosen boolean NOT NULL DEFAULT false,
  deleted boolean DEFAULT false NOT NULL,
  CONSTRAINT pk_classification_suggestions PRIMARY KEY (id),
  CONSTRAINT fk_classification_suggestions_result FOREIGN KEY (classification_result_id)
        REFERENCES classification_results (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_suggestions_taxon FOREIGN KEY (taxa_taxon_list_id)
        REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE classification_suggestions
  IS 'Individual suggested identifications as a result of a request sent to an image classification service';
COMMENT ON COLUMN classification_suggestions.classification_result_id IS 'Foreign key to the classification_results table, identifies the classification result this suggestion belongs to.';
COMMENT ON COLUMN classification_suggestions.taxon_name_given IS 'Name of the taxon suggested by the classifier.';
COMMENT ON COLUMN classification_suggestions.taxa_taxon_list_id IS 'Foreign key to the taxa_taxon_lists table. Identifies the taxon in Indicia''s taxonomy that this suggestion refers to. May be null if no match made.';
COMMENT ON COLUMN classification_suggestions.probability_given IS 'Probability between 0 and 1 assigned for this suggestion by the classifier.';
COMMENT ON COLUMN classification_suggestions.classifier_chosen IS 'True if this suggestion was given with confidence by the classifier.';
COMMENT ON COLUMN classification_suggestions.human_chosen IS 'True if a human accepted this suggestion in order to determine the occurrence.';
COMMENT ON COLUMN classification_suggestions.deleted IS 'Has this record been deleted?';

CREATE TABLE IF NOT EXISTS classification_results_occurrence_media
(
  id serial NOT NULL,
  classification_result_id integer NOT NULL,
  occurrence_media_id integer NOT NULL,
  CONSTRAINT classification_result_occurrence_result FOREIGN KEY (classification_result_id)
        REFERENCES classification_results (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_classification_results_occurrence_media_occurrence FOREIGN KEY (occurrence_media_id)
        REFERENCES occurrence_media (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT pk_classification_results_occurrence_media PRIMARY KEY (id)
);

COMMENT ON TABLE classification_results_occurrence_media
  IS 'Join table that links classification results to the media files that were used.';
COMMENT ON COLUMN classification_results_occurrence_media.classification_result_id IS 'Foreign key to the classification_result table. Identifies the set of classification results this file was submitted for.';
COMMENT ON COLUMN classification_results_occurrence_media.occurrence_media_id IS 'Foreign key to the occurrence_media table. Identifies the submitted media file.';