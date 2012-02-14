-- Table: cache_occurrences

-- DROP TABLE cache_occurrences;

CREATE TABLE cache_occurrences
(
  id integer NOT NULL,
  record_status character(1),
  downloaded_flag character(1),
  zero_abundance boolean,
  website_id integer,
  survey_id integer,
  survey_title character varying(100),
  date_start date,
  date_end date,
  date_type character varying(2),
  public_entered_sref character varying,
  entered_sref_system character varying(10),
  public_geom geometry,
  sample_method character varying,
  taxa_taxon_list_id integer,
  preferred_taxa_taxon_list_id integer,
  taxonomic_sort_order integer,
  taxon character varying,
  authority character varying(100),
  preferred_taxon character varying,
  preferred_authority character varying(100),
  default_common_name character varying,
  search_name character varying,
  taxa_taxon_list_external_key character varying(50),
  taxon_meaning_id integer,
  taxon_group_id integer,
  taxon_group character varying(100),
  created_by_id integer,
  cache_created_on timestamp without time zone NOT NULL,
  cache_updated_on timestamp without time zone NOT NULL,
  CONSTRAINT pk_cache_occurrences PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

-- Index: ix_cache_occurrences_created_by_id

-- DROP INDEX ix_cache_occurrences_created_by_id;

CREATE INDEX ix_cache_occurrences_created_by_id
  ON cache_occurrences
  USING btree
  (created_by_id);

-- Index: ix_cache_occurrences_date_start

-- DROP INDEX ix_cache_occurrences_date_start;

CREATE INDEX ix_cache_occurrences_date_start
  ON cache_occurrences
  USING btree
  (date_start);

-- Index: ix_cache_occurrences_geom

-- DROP INDEX ix_cache_occurrences_geom;

CREATE INDEX ix_cache_occurrences_geom
  ON cache_occurrences
  USING gist
  (public_geom);

-- Index: ix_cache_occurrences_record_status

-- DROP INDEX ix_cache_occurrences_record_status;

CREATE INDEX ix_cache_occurrences_record_status
  ON cache_occurrences
  USING btree
  (record_status);

-- Index: ix_cache_occurrences_survey_id

-- DROP INDEX ix_cache_occurrences_survey_id;

CREATE INDEX ix_cache_occurrences_survey_id
  ON cache_occurrences
  USING btree
  (survey_id);

-- Index: ix_cache_occurrences_website_id

-- DROP INDEX ix_cache_occurrences_website_id;

CREATE INDEX ix_cache_occurrences_website_id
  ON cache_occurrences
  USING btree
  (website_id);

-- Index: ix_occurrences_preferred_taxa_taxon_list_id

-- DROP INDEX ix_occurrences_preferred_taxa_taxon_list_id;

CREATE INDEX ix_occurrences_preferred_taxa_taxon_list_id
  ON cache_occurrences
  USING btree
  (preferred_taxa_taxon_list_id);

-- Index: ix_occurrences_search_name

-- DROP INDEX ix_occurrences_search_name;

CREATE INDEX ix_occurrences_search_name
  ON cache_occurrences
  USING btree
  (search_name);
  
-- Index: ix_occurrences_taxon_meaning_id

-- DROP INDEX ix_occurrences_taxon_meaning_id;

CREATE INDEX ix_occurrences_taxon_meaning_id
  ON cache_occurrences
  USING btree
  (taxon_meaning_id);

