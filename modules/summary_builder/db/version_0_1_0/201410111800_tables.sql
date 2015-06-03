

CREATE SEQUENCE summariser_definitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE summariser_definitions
(
  id                      integer           NOT NULL DEFAULT nextval('summariser_definitions_id_seq'::regclass),
  survey_id               integer           NOT NULL,
  period_type             character(1)      NOT NULL DEFAULT 'W'::bpchar,
  period_start            character varying NOT NULL,
  period_one_contains     character varying NOT NULL,
  occurrence_attribute_id integer,
  calculate_estimates     boolean           NOT NULL DEFAULT 'f',
  data_combination_method character(1)      NOT NULL DEFAULT 'A'::bpchar,
  data_rounding_method    character(1)      NOT NULL DEFAULT 'N'::bpchar,
  interpolation           character(1)      NOT NULL DEFAULT 'L'::bpchar,
  season_limits           character varying,
  first_value             character(1)      NOT NULL DEFAULT 'X'::bpchar,
  last_value              character(1)      NOT NULL DEFAULT 'X'::bpchar,
  created_on              timestamp without time zone NOT NULL,
  created_by_id           integer           NOT NULL,
  updated_on              timestamp without time zone NOT NULL,
  updated_by_id           integer           NOT NULL,
  deleted                 boolean           DEFAULT false NOT NULL,
  
  CONSTRAINT pk_summariser_definitions PRIMARY KEY (id),
  CONSTRAINT fk_summariser_definition_survey FOREIGN KEY (survey_id) REFERENCES surveys(id),
  CONSTRAINT summariser_definition_period_type_check CHECK (period_type = ANY (ARRAY['W'::bpchar, 'M'::bpchar])),
  CONSTRAINT summariser_definition_data_combination_method_check CHECK (data_combination_method = ANY (ARRAY['A'::bpchar, 'L'::bpchar, 'M'::bpchar])),
  CONSTRAINT summariser_definition_data_rounding_method_check CHECK (data_rounding_method = ANY (ARRAY['D'::bpchar, 'N'::bpchar, 'U'::bpchar, 'X'::bpchar])),
  CONSTRAINT summariser_definition_interpolation_check CHECK (interpolation = ANY (ARRAY['L'::bpchar])),
  CONSTRAINT summariser_definition_first_value_check CHECK (first_value = ANY (ARRAY['X'::bpchar,'H'::bpchar])),
  CONSTRAINT summariser_definition_last_value_check CHECK (last_value = ANY (ARRAY['X'::bpchar,'H'::bpchar])), 
  CONSTRAINT fk_summariser_definition_creator FOREIGN KEY (created_by_id) REFERENCES users(id),
  CONSTRAINT fk_summariser_definition_updater FOREIGN KEY (updated_by_id) REFERENCES users(id)
)
WITH (
  OIDS=FALSE
);

CREATE VIEW gv_summariser_definitions AS
 SELECT s.id as survey_id, s.title, w.title AS website, s.website_id, sd.id
   FROM surveys s
   LEFT JOIN websites w ON s.website_id = w.id AND w.deleted = false
   LEFT JOIN summariser_definitions sd ON sd.survey_id = s.id AND sd.deleted = false
  WHERE s.deleted = false;

CREATE TABLE summary_occurrences
(
  website_id integer,
  survey_id integer,
  location_id integer,
  user_id integer,
  date_start date,
  date_end date,
  date_type character varying(2),
  type character varying,
  taxa_taxon_list_id integer,
  preferred_taxa_taxon_list_id integer,
  taxonomic_sort_order bigint,
  taxon character varying,
  preferred_taxon character varying,
  default_common_name character varying,
  taxon_meaning_id integer,
  count double precision,
  estimate double precision,
  created_by_id integer,
  summary_created_on timestamp without time zone NOT NULL
) WITH (
  OIDS --- want oids as no pk.
);
CREATE INDEX ix_summary_occurrences_STLU ON summary_occurrences USING btree (survey_id, taxa_taxon_list_id, location_id, user_id);
CREATE INDEX ix_summary_occurrences_STU ON summary_occurrences USING btree (survey_id, taxa_taxon_list_id, user_id);

