CREATE TABLE rest_api_sync_taxon_mappings
(
  id serial NOT NULL,
  restrict_to_survey_id int,
  other_taxon_name character varying NOT NULL,
  mapped_taxon_list_id int NOT NULL,
  mapped_taxon_name character varying NOT NULL,
  mapped_search_code character varying,
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  CONSTRAINT pk_rest_api_sync_taxon_mappings PRIMARY KEY (id),
  CONSTRAINT fk_rest_api_sync_skipped_record_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE rest_api_sync_taxon_mappings
  IS 'List of mappings from taxon names used in other systems to the Indicia taxonomy.';