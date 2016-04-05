-- Table: cache_occurrences_functional

-- DROP TABLE cache_occurrences_functional;

CREATE TABLE cache_occurrences_functional
(
  id integer NOT NULL,
  sample_id integer,
  website_id integer,
  survey_id integer,
  input_form character varying,
  location_id integer,
  location_name character varying,
  public_geom geometry(Geometry,900913),
  map_sq_1km_id integer,
  map_sq_2km_id integer,
  map_sq_10km_id integer,
  date_start date,
  date_end date,
  date_type character varying(2),
  created_on timestamp without time zone,
  updated_on timestamp without time zone,
  verified_on timestamp without time zone,
  created_by_id integer,
  group_id integer,
  taxa_taxon_list_id integer,
  preferred_taxa_taxon_list_id integer,
  taxon_meaning_id integer,
  taxa_taxon_list_external_key character varying(50),
  family_taxa_taxon_list_id integer,
  taxon_group_id integer,
  taxon_rank_sort_order integer,
  record_status character(1),
  record_substatus smallint,
  certainty character(1),
  query character(1),
  sensitive boolean,
  release_status character(1),
  marine_flag boolean,
  data_cleaner_result boolean,
  media_count integer default 0,
  training boolean,
  zero_abundance boolean,
  licence_id integer,
  CONSTRAINT pk_cache_occurrences_functional PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

-- Table: cache_occurrences_nonfunctional

-- DROP TABLE cache_occurrences_nonfunctional;

CREATE TABLE cache_occurrences_nonfunctional
(
  id integer NOT NULL,
  data_cleaner_info character varying,
  media character varying,
  comment text,
  sensitivity_precision integer,
  privacy_precision integer,
  output_sref character varying,
  verifier character varying,
  licence_code character varying,
  attr_sex_stage character varying,
  attr_sex character varying,
  attr_stage character varying,
  attr_sex_stage_count character varying,
  attr_certainty character varying,
  attr_det_first_name character varying,
  attr_det_last_name character varying,
  attr_det_full_name character varying,
  CONSTRAINT pk_cache_occurrences_nonfunctional PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

-- Table: cache_samples_functional

-- DROP TABLE cache_samples_functional;

CREATE TABLE cache_samples_functional
(
  id integer NOT NULL,
  website_id integer,
  survey_id integer,
  input_form character varying,
  location_id integer,
  location_name character varying,
  public_geom geometry(Geometry,900913),
  map_sq_1km_id integer,
  map_sq_2km_id integer,
  map_sq_10km_id integer,
  date_start date,
  date_end date,
  date_type character varying(2),
  created_on timestamp without time zone,
  updated_on timestamp without time zone,
  verified_on timestamp without time zone,
  created_by_id integer,
  group_id integer,
  record_status character(1),
  query character(1),
  media_count integer default 0,
  CONSTRAINT pk_cache_samples_functional PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

-- Table: cache_samples_nonfunctional

-- DROP TABLE cache_samples_nonfunctional;

CREATE TABLE cache_samples_nonfunctional
(
  id integer NOT NULL,
  website_title character varying,
  survey_title character varying,
  group_title character varying,
  public_entered_sref character varying,
  entered_sref_system character varying,
  recorders character varying,
  media character varying,
  comment text,
  privacy_precision integer,
  licence_code character varying,
  attr_email character varying,
  attr_cms_user_id integer,
  attr_cms_username character varying,
  attr_first_name character varying,
  attr_last_name character varying,
  attr_full_name character varying,
  attr_biotope character varying,
  attr_sref_precision double precision,
  CONSTRAINT pk_cache_samples_nonfunctional PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);