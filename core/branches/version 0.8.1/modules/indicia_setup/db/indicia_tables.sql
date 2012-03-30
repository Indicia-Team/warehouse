DROP INDEX IF EXISTS fki_locationattributes_websites_survey;
DROP INDEX IF EXISTS fki_sample_attributes_websites_survey;
DROP INDEX IF EXISTS fki_occurrence_attributes_websites_survey;
DROP INDEX IF EXISTS fki_occurrence_taxa_taxon_list;
DROP INDEX IF EXISTS fki_website;
DROP INDEX IF EXISTS fki_users_websites_site_roles;
DROP INDEX IF EXISTS fki_user_person;
DROP INDEX IF EXISTS fki_user_core_role;
DROP INDEX IF EXISTS fki_term_language;
DROP INDEX IF EXISTS fki_taxon_taxon_group;
DROP INDEX IF EXISTS fki_taxon_list_website;
DROP INDEX IF EXISTS fki_taxon_list_parent;
DROP INDEX IF EXISTS fki_taxon_language;
DROP INDEX IF EXISTS fki_taxa_taxon_lists_taxon_lists;
DROP INDEX IF EXISTS fki_taxa_taxon_lists_taxa;
DROP INDEX IF EXISTS fki_survey_website;
DROP INDEX IF EXISTS fki_samples_surveys;
DROP INDEX IF EXISTS fki_samples_locations;
DROP INDEX IF EXISTS fki_sample_attributes_websites_websites;
DROP INDEX IF EXISTS fki_sample_attributes_websites_sample_attributes;
DROP INDEX IF EXISTS fki_sample_attribute_values_sample;
DROP INDEX IF EXISTS fki_sample_attribute_value_sample_attribute;
DROP INDEX IF EXISTS fki_parent_termlist;
DROP INDEX IF EXISTS fki_occurrence_sample;
DROP INDEX IF EXISTS fki_occurrence_determiner;
DROP INDEX IF EXISTS fki_occurrence_attribute_values_occurrence;
DROP INDEX IF EXISTS fki_occurrence_attribute_value_occurrence_attribute;
DROP INDEX IF EXISTS fki_location_attribute_values_location;
DROP INDEX IF EXISTS fki_location_attribute_value_location_attribute;
DROP TABLE IF EXISTS titles;
DROP TABLE IF EXISTS user_tokens;
DROP TABLE IF EXISTS occurrence_comments;
DROP TABLE IF EXISTS system;
DROP TABLE IF EXISTS websites;
DROP TABLE IF EXISTS users_websites;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS terms;
DROP TABLE IF EXISTS termlists_terms;
DROP TABLE IF EXISTS termlists;
DROP TABLE IF EXISTS taxon_meanings;
DROP TABLE IF EXISTS taxon_lists;
DROP TABLE IF EXISTS taxon_groups;
DROP TABLE IF EXISTS taxa_taxon_lists;
DROP TABLE IF EXISTS taxa;
DROP TABLE IF EXISTS surveys;
DROP TABLE IF EXISTS site_roles;
DROP TABLE IF EXISTS samples;
DROP TABLE IF EXISTS sample_attributes_websites;
DROP TABLE IF EXISTS sample_attributes;
DROP TABLE IF EXISTS sample_attribute_values;
DROP TABLE IF EXISTS people;
DROP TABLE IF EXISTS occurrences;
DROP TABLE IF EXISTS occurrence_images;
DROP TABLE IF EXISTS occurrence_attributes_websites;
DROP TABLE IF EXISTS occurrence_attributes;
DROP TABLE IF EXISTS occurrence_attribute_values;
DROP TABLE IF EXISTS meanings;
DROP TABLE IF EXISTS locations_websites;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS location_attributes_websites;
DROP TABLE IF EXISTS location_attributes;
DROP TABLE IF EXISTS location_attribute_values;
DROP TABLE IF EXISTS languages;
DROP TABLE IF EXISTS core_roles;
SET check_function_bodies = false;
--
-- Structure for table core_roles (OID = 117396) :
--
CREATE TABLE core_roles (
    id integer DEFAULT nextval('roles_id_seq'::regclass) NOT NULL,
    title character varying(50),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table languages (OID = 117421) :
--
CREATE TABLE languages (
    id integer DEFAULT nextval('languages_id_seq'::regclass) NOT NULL,
    iso character(3),
    "language" character varying(50),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table location_attribute_values (OID = 117425) :
--
CREATE TABLE location_attribute_values (
    id integer DEFAULT nextval('location_attribute_values_id_seq'::regclass) NOT NULL,
    location_id integer,
    location_attribute_id integer,
    text_value text,
    float_value double precision,
    int_value integer,
    date_start_value date,
    date_end_value date,
    date_type_value character varying(2),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table location_attributes (OID = 117431) :
--
CREATE TABLE location_attributes (
    id integer DEFAULT nextval('location_attributes_id_seq'::regclass) NOT NULL,
    caption character varying(50),
    data_type character(1),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    validation_rules character varying,
    termlist_id integer,
    multi_value boolean DEFAULT false,
    public boolean DEFAULT false,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table location_attributes_websites (OID = 117434) :
--
CREATE TABLE location_attributes_websites (
    id integer DEFAULT nextval('location_attributes_websites_id_seq'::regclass) NOT NULL,
    website_id integer NOT NULL,
    location_attribute_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    restrict_to_survey_id integer,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table locations (OID = 117440) :
--
CREATE TABLE locations (
    id integer DEFAULT nextval('locations_id_seq'::regclass) NOT NULL,
    name character varying(100) NOT NULL,
    code character varying(20),
    parent_id integer,
    centroid_sref character varying(40) NOT NULL,
    centroid_sref_system character varying(10) NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    "comment" text,
    external_key character varying(50),
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;

SELECT AddGeometryColumn ('locations', 'centroid_geom', 900913, 'GEOMETRY', 2);
SELECT AddGeometryColumn ('locations', 'boundary_geom', 900913, 'GEOMETRY', 2);

--
-- Structure for table locations_websites (OID = 117452) :
--
CREATE TABLE locations_websites (
    id integer DEFAULT nextval('locations_websites_id_seq'::regclass) NOT NULL,
    location_id integer NOT NULL,
    website_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table meanings (OID = 117457) :
--
CREATE TABLE meanings (
    id integer DEFAULT nextval('meanings_id_seq'::regclass) NOT NULL
) WITHOUT OIDS;
--
-- Structure for table occurrence_attribute_values (OID = 117461) :
--
CREATE TABLE occurrence_attribute_values (
    id integer DEFAULT nextval('occurrence_attribute_values_id_seq'::regclass) NOT NULL,
    occurrence_id integer,
    occurrence_attribute_id integer,
    text_value text,
    float_value double precision,
    int_value integer,
    date_start_value date,
    date_end_value date,
    date_type_value character varying(2),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table occurrence_attributes (OID = 117467) :
--
CREATE TABLE occurrence_attributes (
    id integer DEFAULT nextval('occurrence_attributes_id_seq'::regclass) NOT NULL,
    caption character varying(50),
    data_type character(1),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    validation_rules character varying,
    termlist_id integer,
    multi_value boolean DEFAULT false,
    public boolean DEFAULT false,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table occurrence_attributes_websites (OID = 117470) :
--
CREATE TABLE occurrence_attributes_websites (
    id integer DEFAULT nextval('occurrence_attributes_websites_id_seq'::regclass) NOT NULL,
    website_id integer NOT NULL,
    occurrence_attribute_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    restrict_to_survey_id integer,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table occurrence_images (OID = 117473) :
--
CREATE TABLE occurrence_images (
    id integer DEFAULT nextval('occurrence_images_id_seq'::regclass) NOT NULL,
    occurrence_id integer NOT NULL,
    "path" character varying(200) NOT NULL,
    caption character varying(100),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table occurrences (OID = 117476) :
--
CREATE TABLE occurrences (
    id integer DEFAULT nextval('occurrences_id_seq'::regclass) NOT NULL,
    sample_id integer NOT NULL,
    determiner_id integer,
    confidential boolean DEFAULT false NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    website_id integer NOT NULL,
    external_key character varying(50),
    "comment" text,
    taxa_taxon_list_id integer,
    deleted boolean DEFAULT false NOT NULL,
    record_status character(1) DEFAULT 'I'::bpchar,
    verified_by_id integer,
    verified_on timestamp without time zone,
    CONSTRAINT occurrences_record_status_check CHECK ((record_status = ANY (ARRAY['I'::bpchar, 'C'::bpchar, 'V'::bpchar])))
) WITHOUT OIDS;
--
-- Structure for table people (OID = 117480) :
--
CREATE TABLE people (
    id integer DEFAULT nextval('people_id_seq'::regclass) NOT NULL,
    first_name character varying(30) NOT NULL,
    surname character varying(30) NOT NULL,
    initials character varying(6),
    email_address character varying(50),
    website_url character varying(1000),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    title_id integer,
    address character varying(200),
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table sample_attribute_values (OID = 117486) :
--
CREATE TABLE sample_attribute_values (
    id integer DEFAULT nextval('sample_attribute_values_id_seq'::regclass) NOT NULL,
    sample_id integer,
    sample_attribute_id integer,
    text_value text,
    float_value double precision,
    int_value integer,
    date_start_value date,
    date_end_value date,
    date_type_value character varying(2),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table sample_attributes (OID = 117492) :
--
CREATE TABLE sample_attributes (
    id integer DEFAULT nextval('sample_attributes_id_seq'::regclass) NOT NULL,
    caption character varying(50),
    data_type character(1),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    applies_to_location boolean DEFAULT false NOT NULL,
    validation_rules character varying,
    termlist_id integer,
    multi_value boolean DEFAULT false,
    public boolean DEFAULT false,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table sample_attributes_websites (OID = 117495) :
--
CREATE TABLE sample_attributes_websites (
    id integer DEFAULT nextval('sample_attributes_websites_id_seq'::regclass) NOT NULL,
    website_id integer NOT NULL,
    sample_attribute_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    restrict_to_survey_id integer,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table samples (OID = 117498) :
--
CREATE TABLE samples (
    id integer DEFAULT nextval('samples_id_seq'::regclass) NOT NULL,
    survey_id integer,
    location_id integer,
    date_start date,
    date_end date,
    date_type character varying(2),
    entered_sref character varying(40),
    entered_sref_system character varying(10),
    location_name character varying(200),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    "comment" text,
    external_key character varying(50),
    sample_method_id integer,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;

SELECT AddGeometryColumn ('samples', 'geom', 900913, 'GEOMETRY', 2);

--
-- Structure for table site_roles (OID = 117507) :
--
CREATE TABLE site_roles (
    id integer DEFAULT nextval('site_roles_id_seq'::regclass) NOT NULL,
    title character varying(50),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table surveys (OID = 117520) :
--
CREATE TABLE surveys (
    id integer DEFAULT nextval('surveys_id_seq'::regclass) NOT NULL,
    title character varying(100) NOT NULL,
    owner_id integer,
    description text,
    website_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table taxa (OID = 117528) :
--
CREATE TABLE taxa (
    id integer DEFAULT nextval('taxa_id_seq'::regclass) NOT NULL,
    taxon character varying(100),
    taxon_group_id integer NOT NULL,
    language_id integer,
    external_key character varying(50),
    authority character varying(50),
    search_code character varying(20),
    scientific boolean,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table taxa_taxon_lists (OID = 117533) :
--
CREATE TABLE taxa_taxon_lists (
    id integer DEFAULT nextval('taxa_taxon_lists_id_seq'::regclass) NOT NULL,
    taxon_list_id integer,
    taxon_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    parent_id integer,
    taxon_meaning_id integer,
    taxonomic_sort_order integer,
    preferred boolean DEFAULT false NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table taxon_groups (OID = 117536) :
--
CREATE TABLE taxon_groups (
    id integer DEFAULT nextval('taxon_groups_id_seq'::regclass) NOT NULL,
    title character varying(100),
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table taxon_lists (OID = 117541) :
--
CREATE TABLE taxon_lists (
    id integer DEFAULT nextval('taxon_lists_id_seq'::regclass) NOT NULL,
    title character varying(100),
    description text,
    website_id integer,
    parent_id integer,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table taxon_meanings (OID = 117548) :
--
CREATE TABLE taxon_meanings (
    id integer DEFAULT nextval('taxon_meanings_id_seq'::regclass) NOT NULL
) WITHOUT OIDS;
--
-- Structure for table termlists (OID = 117553) :
--
CREATE TABLE termlists (
    id integer DEFAULT nextval('termlists_id_seq'::regclass) NOT NULL,
    title character varying(100) NOT NULL,
    description text,
    website_id integer,
    parent_id integer,
    deleted boolean DEFAULT false NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL
) WITHOUT OIDS;
--
-- Structure for table termlists_terms (OID = 117563) :
--
CREATE TABLE termlists_terms (
    id integer DEFAULT nextval('termlists_terms_id_seq'::regclass) NOT NULL,
    termlist_id integer,
    term_id integer,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    parent_id integer,
    meaning_id integer,
    preferred boolean DEFAULT false NOT NULL,
    sort_order integer,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table terms (OID = 117569) :
--
CREATE TABLE terms (
    id integer DEFAULT nextval('terms_id_seq'::regclass) NOT NULL,
    term character varying(100),
    language_id integer,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table users (OID = 117574) :
--
CREATE TABLE users (
    id integer DEFAULT nextval('users_id_seq'::regclass) NOT NULL,
    openid_url character varying(1000),
    home_entered_sref character varying(30),
    home_entered_sref_system character varying(10),
    home_geom geometry,
    interests character varying,
    location_name character varying(200),
    person_id integer,
    email_visible boolean DEFAULT false NOT NULL,
    view_common_names boolean DEFAULT true NOT NULL,
    core_role_id integer,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    username character varying(30) NOT NULL,
    "password" character varying,
    forgotten_password_key character varying,
    deleted boolean DEFAULT false NOT NULL,
    CONSTRAINT enforce_dims_home_geom CHECK ((ndims(home_geom) = 2)),
    CONSTRAINT enforce_geotype_home_geom CHECK (((geometrytype(home_geom) = 'LINESTRING'::text) OR (home_geom IS NULL))),
    CONSTRAINT enforce_srid_home_geom CHECK ((srid(home_geom) = (-1)))
) WITHOUT OIDS;
--
-- Structure for table users_websites (OID = 117585) :
--
CREATE TABLE users_websites (
    id integer DEFAULT nextval('users_websites_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    website_id integer NOT NULL,
    deleted boolean DEFAULT false,
    activated boolean DEFAULT false NOT NULL,
    banned boolean DEFAULT false NOT NULL,
    activation_key character varying(128),
    site_role_id integer,
    registration_datetime timestamp without time zone,
    last_login_datetime timestamp without time zone,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    preferred_sref_system character varying(10)
) WITHOUT OIDS;
--
-- Structure for table websites (OID = 117593) :
--
CREATE TABLE websites (
    id integer DEFAULT nextval('websites_id_seq'::regclass) NOT NULL,
    title character varying(100) NOT NULL,
    description text,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    url character varying(500) NOT NULL,
    default_survey_id integer,
    "password" character varying(30) NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table system (OID = 119204) :
--
CREATE TABLE system (
    id integer DEFAULT nextval('system_id_seq'::regclass) NOT NULL,
    "version" character varying(10) DEFAULT ''::character varying NOT NULL,
    name character varying(30) DEFAULT ''::character varying NOT NULL,
    repository character varying(150) DEFAULT ''::character varying NOT NULL,
    release_date date
) WITHOUT OIDS;
--
-- Structure for table occurrence_comments (OID = 119215) :
--
CREATE TABLE occurrence_comments (
    id integer DEFAULT nextval('occurrence_comments_id_seq'::regclass) NOT NULL,
    "comment" text NOT NULL,
    created_by_id integer,
    created_on timestamp without time zone NOT NULL,
    updated_by_id integer,
    updated_on timestamp without time zone NOT NULL,
    occurrence_id integer,
    email_address character varying(50),
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Structure for table user_tokens (OID = 119247) :
--
CREATE TABLE user_tokens (
    id integer DEFAULT nextval('user_tokens_id_seq'::regclass) NOT NULL,
    user_id integer NOT NULL,
    expires timestamp without time zone NOT NULL,
    created timestamp without time zone NOT NULL,
    user_agent character varying NOT NULL,
    token character varying NOT NULL
) WITHOUT OIDS;
--
-- Structure for table titles (OID = 119311) :
--
CREATE TABLE titles (
    id integer NOT NULL,
    title character varying(10) NOT NULL,
    created_on timestamp without time zone NOT NULL,
    created_by_id integer NOT NULL,
    updated_on timestamp without time zone NOT NULL,
    updated_by_id integer NOT NULL,
    deleted boolean DEFAULT false NOT NULL
) WITHOUT OIDS;
--
-- Definition for index fki_location_attribute_value_location_attribute (OID = 118464) :
--
CREATE INDEX fki_location_attribute_value_location_attribute ON location_attribute_values USING btree (location_attribute_id);
--
-- Definition for index fki_location_attribute_values_location (OID = 118465) :
--
CREATE INDEX fki_location_attribute_values_location ON location_attribute_values USING btree (location_id);
--
-- Definition for index fki_occurrence_attribute_value_occurrence_attribute (OID = 118466) :
--
CREATE INDEX fki_occurrence_attribute_value_occurrence_attribute ON occurrence_attribute_values USING btree (occurrence_attribute_id);
--
-- Definition for index fki_occurrence_attribute_values_occurrence (OID = 118467) :
--
CREATE INDEX fki_occurrence_attribute_values_occurrence ON occurrence_attribute_values USING btree (occurrence_id);
--
-- Definition for index fki_occurrence_determiner (OID = 118468) :
--
CREATE INDEX fki_occurrence_determiner ON occurrences USING btree (determiner_id);
--
-- Definition for index fki_occurrence_sample (OID = 118469) :
--
CREATE INDEX fki_occurrence_sample ON occurrences USING btree (sample_id);
--
-- Definition for index fki_parent_termlist (OID = 118471) :
--
CREATE INDEX fki_parent_termlist ON termlists USING btree (parent_id);
--
-- Definition for index fki_sample_attribute_value_sample_attribute (OID = 118472) :
--
CREATE INDEX fki_sample_attribute_value_sample_attribute ON sample_attribute_values USING btree (sample_attribute_id);
--
-- Definition for index fki_sample_attribute_values_sample (OID = 118473) :
--
CREATE INDEX fki_sample_attribute_values_sample ON sample_attribute_values USING btree (sample_id);
--
-- Definition for index fki_sample_attributes_websites_sample_attributes (OID = 118474) :
--
CREATE INDEX fki_sample_attributes_websites_sample_attributes ON sample_attributes_websites USING btree (sample_attribute_id);
--
-- Definition for index fki_sample_attributes_websites_websites (OID = 118475) :
--
CREATE INDEX fki_sample_attributes_websites_websites ON sample_attributes_websites USING btree (website_id);
--
-- Definition for index fki_samples_locations (OID = 118476) :
--
CREATE INDEX fki_samples_locations ON samples USING btree (location_id);
--
-- Definition for index fki_samples_surveys (OID = 118477) :
--
CREATE INDEX fki_samples_surveys ON samples USING btree (survey_id);
--
-- Definition for index fki_survey_website (OID = 118478) :
--
CREATE INDEX fki_survey_website ON surveys USING btree (website_id);
--
-- Definition for index fki_taxa_taxon_lists_taxa (OID = 118479) :
--
CREATE INDEX fki_taxa_taxon_lists_taxa ON taxa_taxon_lists USING btree (taxon_id);
--
-- Definition for index fki_taxa_taxon_lists_taxon_lists (OID = 118480) :
--
CREATE INDEX fki_taxa_taxon_lists_taxon_lists ON taxa_taxon_lists USING btree (taxon_list_id);
--
-- Definition for index fki_taxon_language (OID = 118481) :
--
CREATE INDEX fki_taxon_language ON taxa USING btree (language_id);
--
-- Definition for index fki_taxon_list_parent (OID = 118482) :
--
CREATE INDEX fki_taxon_list_parent ON taxon_lists USING btree (parent_id);
--
-- Definition for index fki_taxon_list_website (OID = 118483) :
--
CREATE INDEX fki_taxon_list_website ON taxon_lists USING btree (website_id);
--
-- Definition for index fki_taxon_taxon_group (OID = 118485) :
--
CREATE INDEX fki_taxon_taxon_group ON taxa USING btree (taxon_group_id);
--
-- Definition for index fki_term_language (OID = 118487) :
--
CREATE INDEX fki_term_language ON terms USING btree (language_id);
--
-- Definition for index fki_user_core_role (OID = 118490) :
--
CREATE INDEX fki_user_core_role ON users USING btree (core_role_id);
--
-- Definition for index fki_user_person (OID = 118491) :
--
CREATE INDEX fki_user_person ON users USING btree (person_id);
--
-- Definition for index fki_users_websites_site_roles (OID = 118492) :
--
CREATE INDEX fki_users_websites_site_roles ON users_websites USING btree (site_role_id);
--
-- Definition for index fki_website (OID = 118493) :
--
CREATE INDEX fki_website ON termlists USING btree (website_id);
--
-- Definition for index fki_occurrence_taxa_taxon_list (OID = 119302) :
--
CREATE INDEX fki_occurrence_taxa_taxon_list ON occurrences USING btree (taxa_taxon_list_id);
--
-- Definition for index fki_occurrence_attributes_websites_survey (OID = 119380) :
--
CREATE INDEX fki_occurrence_attributes_websites_survey ON occurrence_attributes_websites USING btree (restrict_to_survey_id);
--
-- Definition for index fki_sample_attributes_websites_survey (OID = 119386) :
--
CREATE INDEX fki_sample_attributes_websites_survey ON sample_attributes_websites USING btree (restrict_to_survey_id);
--
-- Definition for index fki_locationattributes_websites_survey (OID = 119392) :
--
CREATE INDEX fki_locationattributes_websites_survey ON location_attributes_websites USING btree (restrict_to_survey_id);
--
-- Definition for index fk_languages (OID = 118398) :
--
ALTER TABLE ONLY languages
    ADD CONSTRAINT fk_languages PRIMARY KEY (id);
--
-- Definition for index fk_meanings (OID = 118400) :
--
ALTER TABLE ONLY meanings
    ADD CONSTRAINT fk_meanings PRIMARY KEY (id);
--
-- Definition for index pk_core_roles (OID = 118404) :
--
ALTER TABLE ONLY core_roles
    ADD CONSTRAINT pk_core_roles PRIMARY KEY (id);
--
-- Definition for index pk_location_attribute_values (OID = 118406) :
--
ALTER TABLE ONLY location_attribute_values
    ADD CONSTRAINT pk_location_attribute_values PRIMARY KEY (id);
--
-- Definition for index pk_location_attributes (OID = 118408) :
--
ALTER TABLE ONLY location_attributes
    ADD CONSTRAINT pk_location_attributes PRIMARY KEY (id);
--
-- Definition for index pk_location_attributes_websites (OID = 118410) :
--
ALTER TABLE ONLY location_attributes_websites
    ADD CONSTRAINT pk_location_attributes_websites PRIMARY KEY (id);
--
-- Definition for index pk_locations (OID = 118412) :
--
ALTER TABLE ONLY locations
    ADD CONSTRAINT pk_locations PRIMARY KEY (id);
--
-- Definition for index pk_locations_websites (OID = 118414) :
--
ALTER TABLE ONLY locations_websites
    ADD CONSTRAINT pk_locations_websites PRIMARY KEY (id);
--
-- Definition for index pk_occurrence_attribute_values (OID = 118416) :
--
ALTER TABLE ONLY occurrence_attribute_values
    ADD CONSTRAINT pk_occurrence_attribute_values PRIMARY KEY (id);
--
-- Definition for index pk_occurrence_attributes (OID = 118418) :
--
ALTER TABLE ONLY occurrence_attributes
    ADD CONSTRAINT pk_occurrence_attributes PRIMARY KEY (id);
--
-- Definition for index pk_occurrence_attributes_websites (OID = 118420) :
--
ALTER TABLE ONLY occurrence_attributes_websites
    ADD CONSTRAINT pk_occurrence_attributes_websites PRIMARY KEY (id);
--
-- Definition for index pk_occurrence_images (OID = 118422) :
--
ALTER TABLE ONLY occurrence_images
    ADD CONSTRAINT pk_occurrence_images PRIMARY KEY (id);
--
-- Definition for index pk_occurrences (OID = 118424) :
--
ALTER TABLE ONLY occurrences
    ADD CONSTRAINT pk_occurrences PRIMARY KEY (id);
--
-- Definition for index pk_people (OID = 118426) :
--
ALTER TABLE ONLY people
    ADD CONSTRAINT pk_people PRIMARY KEY (id);
--
-- Definition for index pk_sample_attribute_values (OID = 118428) :
--
ALTER TABLE ONLY sample_attribute_values
    ADD CONSTRAINT pk_sample_attribute_values PRIMARY KEY (id);
--
-- Definition for index pk_sample_attributes (OID = 118430) :
--
ALTER TABLE ONLY sample_attributes
    ADD CONSTRAINT pk_sample_attributes PRIMARY KEY (id);
--
-- Definition for index pk_sample_attributes_websites (OID = 118432) :
--
ALTER TABLE ONLY sample_attributes_websites
    ADD CONSTRAINT pk_sample_attributes_websites PRIMARY KEY (id);
--
-- Definition for index pk_samples (OID = 118434) :
--
ALTER TABLE ONLY samples
    ADD CONSTRAINT pk_samples PRIMARY KEY (id);
--
-- Definition for index pk_site_roles (OID = 118436) :
--
ALTER TABLE ONLY site_roles
    ADD CONSTRAINT pk_site_roles PRIMARY KEY (id);
--
-- Definition for index pk_surveys (OID = 118438) :
--
ALTER TABLE ONLY surveys
    ADD CONSTRAINT pk_surveys PRIMARY KEY (id);
--
-- Definition for index pk_taxa (OID = 118440) :
--
ALTER TABLE ONLY taxa
    ADD CONSTRAINT pk_taxa PRIMARY KEY (id);
--
-- Definition for index pk_taxa_taxon_lists (OID = 118442) :
--
ALTER TABLE ONLY taxa_taxon_lists
    ADD CONSTRAINT pk_taxa_taxon_lists PRIMARY KEY (id);
--
-- Definition for index pk_taxon_groups (OID = 118444) :
--
ALTER TABLE ONLY taxon_groups
    ADD CONSTRAINT pk_taxon_groups PRIMARY KEY (id);
--
-- Definition for index pk_taxon_lists (OID = 118446) :
--
ALTER TABLE ONLY taxon_lists
    ADD CONSTRAINT pk_taxon_lists PRIMARY KEY (id);
--
-- Definition for index pk_taxon_meanings (OID = 118448) :
--
ALTER TABLE ONLY taxon_meanings
    ADD CONSTRAINT pk_taxon_meanings PRIMARY KEY (id);
--
-- Definition for index pk_termlists (OID = 118450) :
--
ALTER TABLE ONLY termlists
    ADD CONSTRAINT pk_termlists PRIMARY KEY (id);
--
-- Definition for index pk_termlists_terms (OID = 118452) :
--
ALTER TABLE ONLY termlists_terms
    ADD CONSTRAINT pk_termlists_terms PRIMARY KEY (id);
--
-- Definition for index pk_terms (OID = 118454) :
--
ALTER TABLE ONLY terms
    ADD CONSTRAINT pk_terms PRIMARY KEY (id);
--
-- Definition for index pk_users (OID = 118456) :
--
ALTER TABLE ONLY users
    ADD CONSTRAINT pk_users PRIMARY KEY (id);
--
-- Definition for index pk_users_websites (OID = 118458) :
--
ALTER TABLE ONLY users_websites
    ADD CONSTRAINT pk_users_websites PRIMARY KEY (id);
--
-- Definition for index pk_websites (OID = 118460) :
--
ALTER TABLE ONLY websites
    ADD CONSTRAINT pk_websites PRIMARY KEY (id);
--
-- Definition for index fk_location_attribute_value_location_attribute (OID = 118494) :
--
ALTER TABLE ONLY location_attribute_values
    ADD CONSTRAINT fk_location_attribute_value_location_attribute FOREIGN KEY (location_attribute_id) REFERENCES location_attributes(id);
--
-- Definition for index fk_location_attribute_values_location (OID = 118499) :
--
ALTER TABLE ONLY location_attribute_values
    ADD CONSTRAINT fk_location_attribute_values_location FOREIGN KEY (location_id) REFERENCES locations(id);
--
-- Definition for index fk_location_attributes_websites_location_attributes (OID = 118504) :
--
ALTER TABLE ONLY location_attributes_websites
    ADD CONSTRAINT fk_location_attributes_websites_location_attributes FOREIGN KEY (location_attribute_id) REFERENCES location_attributes(id);
--
-- Definition for index fk_location_attributes_websites_websites (OID = 118509) :
--
ALTER TABLE ONLY location_attributes_websites
    ADD CONSTRAINT fk_location_attributes_websites_websites FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index fk_location_parent (OID = 118514) :
--
ALTER TABLE ONLY locations
    ADD CONSTRAINT fk_location_parent FOREIGN KEY (parent_id) REFERENCES locations(id);
--
-- Definition for index fk_locations_websites_locations (OID = 118519) :
--
ALTER TABLE ONLY locations_websites
    ADD CONSTRAINT fk_locations_websites_locations FOREIGN KEY (location_id) REFERENCES locations(id);
--
-- Definition for index fk_locations_websites_websites (OID = 118524) :
--
ALTER TABLE ONLY locations_websites
    ADD CONSTRAINT fk_locations_websites_websites FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index fk_occurrence_attribute_value_occurrence_attribute (OID = 118529) :
--
ALTER TABLE ONLY occurrence_attribute_values
    ADD CONSTRAINT fk_occurrence_attribute_value_occurrence_attribute FOREIGN KEY (occurrence_attribute_id) REFERENCES occurrence_attributes(id);
--
-- Definition for index fk_occurrence_attribute_values_occurrence (OID = 118534) :
--
ALTER TABLE ONLY occurrence_attribute_values
    ADD CONSTRAINT fk_occurrence_attribute_values_occurrence FOREIGN KEY (occurrence_id) REFERENCES occurrences(id);
--
-- Definition for index fk_occurrence_attributes_websites_occurrence_attributes (OID = 118539) :
--
ALTER TABLE ONLY occurrence_attributes_websites
    ADD CONSTRAINT fk_occurrence_attributes_websites_occurrence_attributes FOREIGN KEY (occurrence_attribute_id) REFERENCES occurrence_attributes(id);
--
-- Definition for index fk_occurrence_attributes_websites_websites (OID = 118544) :
--
ALTER TABLE ONLY occurrence_attributes_websites
    ADD CONSTRAINT fk_occurrence_attributes_websites_websites FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index fk_occurrence_determiner (OID = 118549) :
--
ALTER TABLE ONLY occurrences
    ADD CONSTRAINT fk_occurrence_determiner FOREIGN KEY (determiner_id) REFERENCES people(id);
--
-- Definition for index fk_occurrence_sample (OID = 118554) :
--
ALTER TABLE ONLY occurrences
    ADD CONSTRAINT fk_occurrence_sample FOREIGN KEY (sample_id) REFERENCES samples(id);
--
-- Definition for index fk_parent_termlist (OID = 118564) :
--
ALTER TABLE ONLY termlists
    ADD CONSTRAINT fk_parent_termlist FOREIGN KEY (parent_id) REFERENCES termlists(id);
--
-- Definition for index fk_sample_attribute_value_sample_attribute (OID = 118569) :
--
ALTER TABLE ONLY sample_attribute_values
    ADD CONSTRAINT fk_sample_attribute_value_sample_attribute FOREIGN KEY (sample_attribute_id) REFERENCES sample_attributes(id);
--
-- Definition for index fk_sample_attribute_values_sample (OID = 118574) :
--
ALTER TABLE ONLY sample_attribute_values
    ADD CONSTRAINT fk_sample_attribute_values_sample FOREIGN KEY (sample_id) REFERENCES samples(id);
--
-- Definition for index fk_sample_attributes_websites_sample_attributes (OID = 118579) :
--
ALTER TABLE ONLY sample_attributes_websites
    ADD CONSTRAINT fk_sample_attributes_websites_sample_attributes FOREIGN KEY (sample_attribute_id) REFERENCES sample_attributes(id);
--
-- Definition for index fk_sample_attributes_websites_websites (OID = 118584) :
--
ALTER TABLE ONLY sample_attributes_websites
    ADD CONSTRAINT fk_sample_attributes_websites_websites FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index fk_samples_locations (OID = 118589) :
--
ALTER TABLE ONLY samples
    ADD CONSTRAINT fk_samples_locations FOREIGN KEY (location_id) REFERENCES locations(id);
--
-- Definition for index fk_samples_surveys (OID = 118594) :
--
ALTER TABLE ONLY samples
    ADD CONSTRAINT fk_samples_surveys FOREIGN KEY (survey_id) REFERENCES surveys(id);
--
-- Definition for index fk_survey_website (OID = 118599) :
--
ALTER TABLE ONLY surveys
    ADD CONSTRAINT fk_survey_website FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index fk_taxa_taxon_lists_taxa (OID = 118604) :
--
ALTER TABLE ONLY taxa_taxon_lists
    ADD CONSTRAINT fk_taxa_taxon_lists_taxa FOREIGN KEY (taxon_id) REFERENCES taxa(id);
--
-- Definition for index fk_taxa_taxon_lists_taxon_lists (OID = 118609) :
--
ALTER TABLE ONLY taxa_taxon_lists
    ADD CONSTRAINT fk_taxa_taxon_lists_taxon_lists FOREIGN KEY (taxon_list_id) REFERENCES taxon_lists(id);
--
-- Definition for index fk_taxon_language (OID = 118614) :
--
ALTER TABLE ONLY taxa
    ADD CONSTRAINT fk_taxon_language FOREIGN KEY (language_id) REFERENCES languages(id);
--
-- Definition for index fk_taxon_list_parent (OID = 118619) :
--
ALTER TABLE ONLY taxon_lists
    ADD CONSTRAINT fk_taxon_list_parent FOREIGN KEY (parent_id) REFERENCES taxon_lists(id);
--
-- Definition for index fk_taxon_list_website (OID = 118624) :
--
ALTER TABLE ONLY taxon_lists
    ADD CONSTRAINT fk_taxon_list_website FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index fk_taxon_taxon_group (OID = 118634) :
--
ALTER TABLE ONLY taxa
    ADD CONSTRAINT fk_taxon_taxon_group FOREIGN KEY (taxon_group_id) REFERENCES taxon_groups(id);
--
-- Definition for index fk_term_language (OID = 118644) :
--
ALTER TABLE ONLY terms
    ADD CONSTRAINT fk_term_language FOREIGN KEY (language_id) REFERENCES languages(id);
--
-- Definition for index fk_termlists_terms_termlists (OID = 118659) :
--
ALTER TABLE ONLY termlists_terms
    ADD CONSTRAINT fk_termlists_terms_termlists FOREIGN KEY (termlist_id) REFERENCES termlists(id);
--
-- Definition for index fk_termlists_terms_terms (OID = 118664) :
--
ALTER TABLE ONLY termlists_terms
    ADD CONSTRAINT fk_termlists_terms_terms FOREIGN KEY (term_id) REFERENCES terms(id);
--
-- Definition for index fk_user_core_role (OID = 118669) :
--
ALTER TABLE ONLY users
    ADD CONSTRAINT fk_user_core_role FOREIGN KEY (core_role_id) REFERENCES core_roles(id);
--
-- Definition for index fk_users_websites_site_roles (OID = 118679) :
--
ALTER TABLE ONLY users_websites
    ADD CONSTRAINT fk_users_websites_site_roles FOREIGN KEY (site_role_id) REFERENCES site_roles(id);
--
-- Definition for index fk_users_websites_websites (OID = 118689) :
--
ALTER TABLE ONLY users_websites
    ADD CONSTRAINT fk_users_websites_websites FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index fk_website (OID = 118694) :
--
ALTER TABLE ONLY termlists
    ADD CONSTRAINT fk_website FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index pk_surveys_owner (OID = 118699) :
--
ALTER TABLE ONLY surveys
    ADD CONSTRAINT pk_surveys_owner FOREIGN KEY (owner_id) REFERENCES people(id);
--
-- Definition for index fk_core_role_creator (OID = 118704) :
--
ALTER TABLE ONLY core_roles
    ADD CONSTRAINT fk_core_role_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_core_role_updater (OID = 118709) :
--
ALTER TABLE ONLY core_roles
    ADD CONSTRAINT fk_core_role_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_language_creator (OID = 118714) :
--
ALTER TABLE ONLY languages
    ADD CONSTRAINT fk_language_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_language_updater (OID = 118719) :
--
ALTER TABLE ONLY languages
    ADD CONSTRAINT fk_language_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_location_attribute_value_creator (OID = 118724) :
--
ALTER TABLE ONLY location_attribute_values
    ADD CONSTRAINT fk_location_attribute_value_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_location_attribute_value_updater (OID = 118729) :
--
ALTER TABLE ONLY location_attribute_values
    ADD CONSTRAINT fk_location_attribute_value_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_location_attribute_creator (OID = 118734) :
--
ALTER TABLE ONLY location_attributes
    ADD CONSTRAINT fk_location_attribute_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_location_attribute_updater (OID = 118739) :
--
ALTER TABLE ONLY location_attributes
    ADD CONSTRAINT fk_location_attribute_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_location_attributes_website_creator (OID = 118744) :
--
ALTER TABLE ONLY location_attributes_websites
    ADD CONSTRAINT fk_location_attributes_website_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_location_creator (OID = 118749) :
--
ALTER TABLE ONLY locations
    ADD CONSTRAINT fk_location_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_location_updater (OID = 118754) :
--
ALTER TABLE ONLY locations
    ADD CONSTRAINT fk_location_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_locations_website_creator (OID = 118759) :
--
ALTER TABLE ONLY locations_websites
    ADD CONSTRAINT fk_locations_website_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_attribute_value_creator (OID = 118764) :
--
ALTER TABLE ONLY occurrence_attribute_values
    ADD CONSTRAINT fk_occurrence_attribute_value_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_attribute_value_updater (OID = 118769) :
--
ALTER TABLE ONLY occurrence_attribute_values
    ADD CONSTRAINT fk_occurrence_attribute_value_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_attribute_creator (OID = 118774) :
--
ALTER TABLE ONLY occurrence_attributes
    ADD CONSTRAINT fk_occurrence_attribute_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_attribute_updater (OID = 118779) :
--
ALTER TABLE ONLY occurrence_attributes
    ADD CONSTRAINT fk_occurrence_attribute_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_attributes_website_creator (OID = 118784) :
--
ALTER TABLE ONLY occurrence_attributes_websites
    ADD CONSTRAINT fk_occurrence_attributes_website_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_image_creator (OID = 118789) :
--
ALTER TABLE ONLY occurrence_images
    ADD CONSTRAINT fk_occurrence_image_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_image_updater (OID = 118794) :
--
ALTER TABLE ONLY occurrence_images
    ADD CONSTRAINT fk_occurrence_image_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_creator (OID = 118799) :
--
ALTER TABLE ONLY occurrences
    ADD CONSTRAINT fk_occurrence_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_updater (OID = 118804) :
--
ALTER TABLE ONLY occurrences
    ADD CONSTRAINT fk_occurrence_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_person_creator (OID = 118809) :
--
ALTER TABLE ONLY people
    ADD CONSTRAINT fk_person_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_person_updater (OID = 118814) :
--
ALTER TABLE ONLY people
    ADD CONSTRAINT fk_person_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_sample_attribute_value_creator (OID = 118819) :
--
ALTER TABLE ONLY sample_attribute_values
    ADD CONSTRAINT fk_sample_attribute_value_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_sample_attribute_value_updater (OID = 118824) :
--
ALTER TABLE ONLY sample_attribute_values
    ADD CONSTRAINT fk_sample_attribute_value_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_sample_attribute_creator (OID = 118829) :
--
ALTER TABLE ONLY sample_attributes
    ADD CONSTRAINT fk_sample_attribute_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_sample_attribute_updater (OID = 118834) :
--
ALTER TABLE ONLY sample_attributes
    ADD CONSTRAINT fk_sample_attribute_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_sample_attributes_website_creator (OID = 118839) :
--
ALTER TABLE ONLY sample_attributes_websites
    ADD CONSTRAINT fk_sample_attributes_website_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_sample_creator (OID = 118844) :
--
ALTER TABLE ONLY samples
    ADD CONSTRAINT fk_sample_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_sample_updater (OID = 118849) :
--
ALTER TABLE ONLY samples
    ADD CONSTRAINT fk_sample_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_site_role_creator (OID = 118854) :
--
ALTER TABLE ONLY site_roles
    ADD CONSTRAINT fk_site_role_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_site_role_updater (OID = 118859) :
--
ALTER TABLE ONLY site_roles
    ADD CONSTRAINT fk_site_role_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_survey_creator (OID = 118864) :
--
ALTER TABLE ONLY surveys
    ADD CONSTRAINT fk_survey_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_survey_updater (OID = 118869) :
--
ALTER TABLE ONLY surveys
    ADD CONSTRAINT fk_survey_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_taxon_creator (OID = 118874) :
--
ALTER TABLE ONLY taxa
    ADD CONSTRAINT fk_taxon_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_taxon_updater (OID = 118879) :
--
ALTER TABLE ONLY taxa
    ADD CONSTRAINT fk_taxon_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_taxa_taxon_list_creator (OID = 118884) :
--
ALTER TABLE ONLY taxa_taxon_lists
    ADD CONSTRAINT fk_taxa_taxon_list_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_taxon_group_creator (OID = 118889) :
--
ALTER TABLE ONLY taxon_groups
    ADD CONSTRAINT fk_taxon_group_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_taxon_group_updater (OID = 118894) :
--
ALTER TABLE ONLY taxon_groups
    ADD CONSTRAINT fk_taxon_group_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_taxon_list_creator (OID = 118899) :
--
ALTER TABLE ONLY taxon_lists
    ADD CONSTRAINT fk_taxon_list_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_taxon_list_updater (OID = 118904) :
--
ALTER TABLE ONLY taxon_lists
    ADD CONSTRAINT fk_taxon_list_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_termlist_creator (OID = 118909) :
--
ALTER TABLE ONLY termlists
    ADD CONSTRAINT fk_termlist_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_termlist_updater (OID = 118914) :
--
ALTER TABLE ONLY termlists
    ADD CONSTRAINT fk_termlist_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_termlists_term_creator (OID = 118919) :
--
ALTER TABLE ONLY termlists_terms
    ADD CONSTRAINT fk_termlists_term_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_termlists_term_updater (OID = 118924) :
--
ALTER TABLE ONLY termlists_terms
    ADD CONSTRAINT fk_termlists_term_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_term_creator (OID = 118929) :
--
ALTER TABLE ONLY terms
    ADD CONSTRAINT fk_term_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_term_updater (OID = 118934) :
--
ALTER TABLE ONLY terms
    ADD CONSTRAINT fk_term_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_user_creator (OID = 118939) :
--
ALTER TABLE ONLY users
    ADD CONSTRAINT fk_user_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_user_updater (OID = 118944) :
--
ALTER TABLE ONLY users
    ADD CONSTRAINT fk_user_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_users_website_creator (OID = 118949) :
--
ALTER TABLE ONLY users_websites
    ADD CONSTRAINT fk_users_website_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_users_website_updater (OID = 118954) :
--
ALTER TABLE ONLY users_websites
    ADD CONSTRAINT fk_users_website_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_website_creator (OID = 118959) :
--
ALTER TABLE ONLY websites
    ADD CONSTRAINT fk_website_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_website_updater (OID = 118964) :
--
ALTER TABLE ONLY websites
    ADD CONSTRAINT fk_website_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_user_person (OID = 118969) :
--
ALTER TABLE ONLY users
    ADD CONSTRAINT fk_user_person FOREIGN KEY (person_id) REFERENCES people(id);
--
-- Definition for index fk_termlists_term_parent (OID = 119011) :
--
ALTER TABLE ONLY termlists_terms
    ADD CONSTRAINT fk_termlists_term_parent FOREIGN KEY (parent_id) REFERENCES termlists_terms(id);
--
-- Definition for index fk_termlists_term_meaning (OID = 119016) :
--
ALTER TABLE ONLY termlists_terms
    ADD CONSTRAINT fk_termlists_term_meaning FOREIGN KEY (meaning_id) REFERENCES meanings(id);
--
-- Definition for index fk_taxon_taxon_meaning (OID = 119049) :
--
ALTER TABLE ONLY taxa_taxon_lists
    ADD CONSTRAINT fk_taxon_taxon_meaning FOREIGN KEY (taxon_meaning_id) REFERENCES taxon_meanings(id);
--
-- Definition for index pk_system (OID = 119211) :
--
ALTER TABLE ONLY system
    ADD CONSTRAINT pk_system PRIMARY KEY (id);
--
-- Definition for index pk_occurrence_comments (OID = 119222) :
--
ALTER TABLE ONLY occurrence_comments
    ADD CONSTRAINT pk_occurrence_comments PRIMARY KEY (id);
--
-- Definition for index fk_occurrence_comment_creator (OID = 119224) :
--
ALTER TABLE ONLY occurrence_comments
    ADD CONSTRAINT fk_occurrence_comment_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_comment_occurrence (OID = 119229) :
--
ALTER TABLE ONLY occurrence_comments
    ADD CONSTRAINT fk_occurrence_comment_occurrence FOREIGN KEY (occurrence_id) REFERENCES occurrences(id);
--
-- Definition for index fk_occurrence_comment_updater (OID = 119234) :
--
ALTER TABLE ONLY occurrence_comments
    ADD CONSTRAINT fk_occurrence_comment_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index pk_user_tokens (OID = 119254) :
--
ALTER TABLE ONLY user_tokens
    ADD CONSTRAINT pk_user_tokens PRIMARY KEY (id);
--
-- Definition for index fk_user_tokens_user (OID = 119256) :
--
ALTER TABLE ONLY user_tokens
    ADD CONSTRAINT fk_user_tokens_user FOREIGN KEY (user_id) REFERENCES users(id);
--
-- Definition for index fk_taxon_parent (OID = 119267) :
--
ALTER TABLE ONLY taxa_taxon_lists
    ADD CONSTRAINT fk_taxon_parent FOREIGN KEY (parent_id) REFERENCES taxa_taxon_lists(id);
--
-- Definition for index fk_occurrence_website (OID = 119275) :
--
ALTER TABLE ONLY occurrences
    ADD CONSTRAINT fk_occurrence_website FOREIGN KEY (website_id) REFERENCES websites(id);
--
-- Definition for index fk_website_default_survey (OID = 119287) :
--
ALTER TABLE ONLY websites
    ADD CONSTRAINT fk_website_default_survey FOREIGN KEY (default_survey_id) REFERENCES surveys(id);
--
-- Definition for index fk_sample_method (OID = 119292) :
--
ALTER TABLE ONLY samples
    ADD CONSTRAINT fk_sample_method FOREIGN KEY (sample_method_id) REFERENCES termlists_terms(id);
--
-- Definition for index fk_occurrence_taxa_taxon_list (OID = 119297) :
--
ALTER TABLE ONLY occurrences
    ADD CONSTRAINT fk_occurrence_taxa_taxon_list FOREIGN KEY (taxa_taxon_list_id) REFERENCES taxa_taxon_lists(id);
--
-- Definition for index unique_username (OID = 119307) :
--
ALTER TABLE ONLY users
    ADD CONSTRAINT unique_username UNIQUE (username);
--
-- Definition for index unique_email (OID = 119309) :
--
ALTER TABLE ONLY people
    ADD CONSTRAINT unique_email UNIQUE (email_address);
--
-- Definition for index pk_titles (OID = 119314) :
--
ALTER TABLE ONLY titles
    ADD CONSTRAINT pk_titles PRIMARY KEY (id);
--
-- Definition for index fk_title_creator (OID = 119316) :
--
ALTER TABLE ONLY titles
    ADD CONSTRAINT fk_title_creator FOREIGN KEY (created_by_id) REFERENCES users(id);
--
-- Definition for index fk_title_updater (OID = 119321) :
--
ALTER TABLE ONLY titles
    ADD CONSTRAINT fk_title_updater FOREIGN KEY (updated_by_id) REFERENCES users(id);
--
-- Definition for index fk_person_title (OID = 119326) :
--
ALTER TABLE ONLY people
    ADD CONSTRAINT fk_person_title FOREIGN KEY (title_id) REFERENCES titles(id);
--
-- Definition for index fk_occurrence_attributes_termlists (OID = 119360) :
--
ALTER TABLE ONLY occurrence_attributes
    ADD CONSTRAINT fk_occurrence_attributes_termlists FOREIGN KEY (termlist_id) REFERENCES termlists(id);
--
-- Definition for index fk_sample_attributes_termlists (OID = 119365) :
--
ALTER TABLE ONLY sample_attributes
    ADD CONSTRAINT fk_sample_attributes_termlists FOREIGN KEY (termlist_id) REFERENCES termlists(id);
--
-- Definition for index fk_location_attributes_termlists (OID = 119370) :
--
ALTER TABLE ONLY location_attributes
    ADD CONSTRAINT fk_location_attributes_termlists FOREIGN KEY (termlist_id) REFERENCES termlists(id);
--
-- Definition for index fk_occurrence_attributes_websites_survey (OID = 119375) :
--
ALTER TABLE ONLY occurrence_attributes_websites
    ADD CONSTRAINT fk_occurrence_attributes_websites_survey FOREIGN KEY (restrict_to_survey_id) REFERENCES surveys(id);
--
-- Definition for index fk_sample_attributes_websites_survey (OID = 119381) :
--
ALTER TABLE ONLY sample_attributes_websites
    ADD CONSTRAINT fk_sample_attributes_websites_survey FOREIGN KEY (restrict_to_survey_id) REFERENCES surveys(id);
--
-- Definition for index fk_location_attributes_websites_survey (OID = 119387) :
--
ALTER TABLE ONLY location_attributes_websites
    ADD CONSTRAINT fk_location_attributes_websites_survey FOREIGN KEY (restrict_to_survey_id) REFERENCES surveys(id);
--
-- Definition for index fk_users_websites_users (OID = 119505) :
--
ALTER TABLE ONLY users_websites
    ADD CONSTRAINT fk_users_websites_users FOREIGN KEY (user_id) REFERENCES users(id);
--
-- Definition for index fk_occurrence_verifier (OID = 119809) :
--
ALTER TABLE ONLY occurrences
    ADD CONSTRAINT fk_occurrence_verifier FOREIGN KEY (verified_by_id) REFERENCES users(id);
--
-- Comments
--
COMMENT ON TABLE core_roles IS 'List of user roles for the core site, including no access, site admin, core admin.';
COMMENT ON COLUMN core_roles.title IS 'Title of the role.';
COMMENT ON COLUMN core_roles.created_on IS 'Date this record was created.';
COMMENT ON COLUMN core_roles.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN core_roles.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN core_roles.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN core_roles.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE languages IS 'List of languages known to the system.';
COMMENT ON COLUMN languages.iso IS 'ISO 639-2 code for the language.';
COMMENT ON COLUMN languages."language" IS 'Term used to describe the language in the system.';
COMMENT ON COLUMN languages.created_on IS 'Date this record was created.';
COMMENT ON COLUMN languages.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN languages.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN languages.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN languages.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE location_attribute_values IS 'Contains values that have been stored for locations against custom attributes.';
COMMENT ON COLUMN location_attribute_values.location_id IS 'Foreign key to the locations table. Identifies the location that this value applies to.';
COMMENT ON COLUMN location_attribute_values.location_attribute_id IS 'Foreign key to the location_attributes table. Identifies the attribute that this value is for.';
COMMENT ON COLUMN location_attribute_values.text_value IS 'For text values, provides the value.';
COMMENT ON COLUMN location_attribute_values.float_value IS 'For float values, provides the value.';
COMMENT ON COLUMN location_attribute_values.int_value IS 'For integer values, provides the value. For lookup values, provides the term id. ';
COMMENT ON COLUMN location_attribute_values.date_start_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN location_attribute_values.date_end_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN location_attribute_values.date_type_value IS 'For vague date values, provides the date type identifier.';
COMMENT ON COLUMN location_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN location_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN location_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN location_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN location_attribute_values.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE location_attributes IS 'List of additional attributes that are defined for the location data.';
COMMENT ON COLUMN location_attributes.caption IS 'Display caption for the attribute.';
COMMENT ON COLUMN location_attributes.data_type IS 'Data type for the attribute. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist).';
COMMENT ON COLUMN location_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN location_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN location_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN location_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN location_attributes.validation_rules IS 'Validation rules defined for this attribute, for example: number, required,max[50].';
COMMENT ON COLUMN location_attributes.termlist_id IS 'For attributes which define a term from a termlist, provides the ID of the termlist the term can be selected from.';
COMMENT ON COLUMN location_attributes.multi_value IS 'Does this attribute allow multiple values? If set to true, then multiple values for this attribute can be stored against a single record.';
COMMENT ON COLUMN location_attributes.public IS 'Flag set to true if this attribute is available for selection and use by any website. If false the attribute is only available for use in the website which created it.';
COMMENT ON COLUMN location_attributes.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE location_attributes_websites IS 'Join table which identifies the websites that each location attribute is available for.';
COMMENT ON COLUMN location_attributes_websites.website_id IS 'Foreign key to the websites table. Identifies the website that the location attribute is available for.';
COMMENT ON COLUMN location_attributes_websites.location_attribute_id IS 'Foreign key to the location_attributes table. Identifies the location attribute that is available for the website.';
COMMENT ON COLUMN location_attributes_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN location_attributes_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN location_attributes_websites.restrict_to_survey_id IS 'Foreign key to the survey table. For attributes that are only applicable to a given survey, identifies the survey.';
COMMENT ON COLUMN location_attributes_websites.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE locations IS 'List of locations, including wildlife sites and other locations, known to the system.';
COMMENT ON COLUMN locations.name IS 'Name of the location.';
COMMENT ON COLUMN locations.code IS 'Location reference code.';
COMMENT ON COLUMN locations.parent_id IS 'Identifies the location''s parent location, if there is one.';
COMMENT ON COLUMN locations.centroid_sref IS 'Spatial reference at the centre of the location.';
COMMENT ON COLUMN locations.centroid_sref_system IS 'System used for the centroid_sref field.';
COMMENT ON COLUMN locations.centroid_geom IS 'Geometry of the spatial reference at the centre of the location. This is a point, or a polygon for grid references. Uses Latitude and Longitude on the WGS84 datum.';
COMMENT ON COLUMN locations.boundary_geom IS 'Polygon for the location''s boundary. Uses Latitude and Longitude on the WGS84 datum.';
COMMENT ON COLUMN locations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN locations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN locations.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN locations.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN locations."comment" IS 'Comment regarding the location.';
COMMENT ON COLUMN locations.external_key IS 'For locations imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';
COMMENT ON COLUMN locations.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE locations_websites IS 'Join table which identifies the locations that are available for data entry on each website.';
COMMENT ON COLUMN locations_websites.location_id IS 'Foreign key to the locations table. Identifies the location that is available for the website.';
COMMENT ON COLUMN locations_websites.website_id IS 'Foreign key to the websites table. Identifies the website that the location is available for.';
COMMENT ON COLUMN locations_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN locations_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN locations_websites.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE meanings IS 'List of unique term meanings. All terms that refer to a single meaning are considered synonymous.';
COMMENT ON TABLE occurrence_attribute_values IS 'Contains values that have been stored for occurrences against custom attributes.';
COMMENT ON COLUMN occurrence_attribute_values.occurrence_id IS 'Foreign key to the occurrences table. Identifies the occurrence that this value applies to.';
COMMENT ON COLUMN occurrence_attribute_values.occurrence_attribute_id IS 'Foreign key to the occurrence_attributes table. Identifies the attribute that this value is for.';
COMMENT ON COLUMN occurrence_attribute_values.text_value IS 'For text values, provides the value.';
COMMENT ON COLUMN occurrence_attribute_values.float_value IS 'For float values, provides the value.';
COMMENT ON COLUMN occurrence_attribute_values.int_value IS 'For integer values, provides the value. For lookup values, provides the term id. ';
COMMENT ON COLUMN occurrence_attribute_values.date_start_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN occurrence_attribute_values.date_end_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN occurrence_attribute_values.date_type_value IS 'For vague date values, provides the date type identifier.';
COMMENT ON COLUMN occurrence_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN occurrence_attribute_values.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE occurrence_attributes IS 'List of additional attributes that are defined for the occurrences data.';
COMMENT ON COLUMN occurrence_attributes.caption IS 'Display caption for the attribute.';
COMMENT ON COLUMN occurrence_attributes.data_type IS 'Data type for the attribute. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist).';
COMMENT ON COLUMN occurrence_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN occurrence_attributes.validation_rules IS 'Validation rules defined for this attribute, for example: number, required,max[50].';
COMMENT ON COLUMN occurrence_attributes.termlist_id IS 'For attributes which define a term from a termlist, provides the ID of the termlist the term can be selected from.';
COMMENT ON COLUMN occurrence_attributes.multi_value IS 'Does this attribute allow multiple values? If set to true, then multiple values for this attribute can be stored against a single record.';
COMMENT ON COLUMN occurrence_attributes.public IS 'Flag set to true if this attribute is available for selection and use by any website. If false the attribute is only available for use in the website which created it.';
COMMENT ON COLUMN occurrence_attributes.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE occurrence_attributes_websites IS 'Join table which identifies the occurrence attributes that are available when entering occurrence data on each website.';
COMMENT ON COLUMN occurrence_attributes_websites.website_id IS 'Foreign key to the websites table. Identifies the website that the occurrence attribute is available for.';
COMMENT ON COLUMN occurrence_attributes_websites.occurrence_attribute_id IS 'Foreign key to the occurrence_attributes table. Identifies the occurrence attribute that is available for the website.';
COMMENT ON COLUMN occurrence_attributes_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_attributes_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_attributes_websites.restrict_to_survey_id IS 'Foreign key to the survey table. For attributes that are only applicable to a given survey, identifies the survey.';
COMMENT ON COLUMN occurrence_attributes_websites.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE occurrence_images IS 'Lists images that are attached to occurrence records.';
COMMENT ON COLUMN occurrence_images.occurrence_id IS 'Foreign key to the occurrences table. Identifies the occurrence that the image is attached to.';
COMMENT ON COLUMN occurrence_images."path" IS 'Path to the image file, relative to the server''s image storage folder.';
COMMENT ON COLUMN occurrence_images.caption IS 'Caption for the image.';
COMMENT ON COLUMN occurrence_images.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_images.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_images.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_images.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN occurrence_images.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE occurrences IS 'List of occurrences of a taxon.';
COMMENT ON COLUMN occurrences.sample_id IS 'Foreign key to the samples table. Identifies the sample that this occurrence record is part of.';
COMMENT ON COLUMN occurrences.determiner_id IS 'Foreign key to the people table. Identifies the person who determined the record.';
COMMENT ON COLUMN occurrences.confidential IS 'Flag set to true if this record is confidential, for example if a user has elected not to allow their entered records to be indicialy visible.';
COMMENT ON COLUMN occurrences.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrences.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrences.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrences.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN occurrences.website_id IS 'Foreign key to the websites table. Website that the occurrence record is linked to.';
COMMENT ON COLUMN occurrences.external_key IS 'For occurrences imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';
COMMENT ON COLUMN occurrences."comment" IS 'User'' comment on data entry of the occurrence.';
COMMENT ON COLUMN occurrences.taxa_taxon_list_id IS 'Foreign key to the taxa_taxon_lists table. Identifies the taxa on a taxon list that this occurrence is a record of.';
COMMENT ON COLUMN occurrences.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN occurrences.record_status IS 'Progress of this record. I - in progress, C - completed, V - verified.';
COMMENT ON COLUMN occurrences.verified_by_id IS 'Foreign key to the users table (verifier).';
COMMENT ON COLUMN occurrences.verified_on IS 'Date this record was verified.';
COMMENT ON TABLE people IS 'List of all people known to the system.';
COMMENT ON COLUMN people.first_name IS 'First name of the person.';
COMMENT ON COLUMN people.surname IS 'Surname of the person.';
COMMENT ON COLUMN people.initials IS 'Initials of the person.';
COMMENT ON COLUMN people.email_address IS 'Email address of the person.';
COMMENT ON COLUMN people.website_url IS 'Website URL for the person.';
COMMENT ON COLUMN people.created_on IS 'Date this record was created.';
COMMENT ON COLUMN people.created_by_id IS 'Optional persons address.';
COMMENT ON COLUMN people.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN people.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN people.title_id IS 'Foreign key to the titles table.';
COMMENT ON COLUMN people.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE sample_attribute_values IS 'Contains values that have been stored for samples against custom attributes.';
COMMENT ON COLUMN sample_attribute_values.sample_id IS 'Foreign key to the samples table. Identifies the sample that this value applies to.';
COMMENT ON COLUMN sample_attribute_values.sample_attribute_id IS 'Foreign key to the sample_attributes table. Identifies the attribute that this value is for.';
COMMENT ON COLUMN sample_attribute_values.text_value IS 'For text values, provides the value.';
COMMENT ON COLUMN sample_attribute_values.float_value IS 'For float values, provides the value.';
COMMENT ON COLUMN sample_attribute_values.int_value IS 'For integer values, provides the value. For lookup values, provides the term id. ';
COMMENT ON COLUMN sample_attribute_values.date_start_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN sample_attribute_values.date_end_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN sample_attribute_values.date_type_value IS 'For vague date values, provides the date type identifier.';
COMMENT ON COLUMN sample_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN sample_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN sample_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN sample_attribute_values.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE sample_attributes IS 'List of additional attributes that are defined for the sample data.';
COMMENT ON COLUMN sample_attributes.caption IS 'Display caption for the attribute.';
COMMENT ON COLUMN sample_attributes.data_type IS 'Data type for the attribute. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist).';
COMMENT ON COLUMN sample_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN sample_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN sample_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN sample_attributes.applies_to_location IS 'For attributes that are gathered which pertain to the site or location rather than the specific sample, this flag is set to true.';
COMMENT ON COLUMN sample_attributes.validation_rules IS 'Validation rules defined for this attribute, for example: number, required,max[50].';
COMMENT ON COLUMN sample_attributes.termlist_id IS 'For attributes which define a term from a termlist, provides the ID of the termlist the term can be selected from.';
COMMENT ON COLUMN sample_attributes.multi_value IS 'Does this attribute allow multiple values? If set to true, then multiple values for this attribute can be stored against a single record.';
COMMENT ON COLUMN sample_attributes.public IS 'Flag set to true if this attribute is available for selection and use by any website. If false the attribute is only available for use in the website which created it.';
COMMENT ON COLUMN sample_attributes.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE sample_attributes_websites IS 'Join table that identifies which websites a sample attribute is defined for.';
COMMENT ON COLUMN sample_attributes_websites.website_id IS 'Foreign key to the websites table. Identifies the website that the sample attribute is available for.';
COMMENT ON COLUMN sample_attributes_websites.sample_attribute_id IS 'Foreign key to the sample attributes table. Identifies the sample attribute that is available for the website.';
COMMENT ON COLUMN sample_attributes_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_attributes_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN sample_attributes_websites.restrict_to_survey_id IS 'Foreign key to the survey table. For attributes that are only applicable to a given survey, identifies the survey.';
COMMENT ON COLUMN sample_attributes_websites.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE samples IS 'List of samples known to the system. ';
COMMENT ON COLUMN samples.survey_id IS 'Foreign key to the surveys table. Identifies the survey that this sample belongs to.';
COMMENT ON COLUMN samples.location_id IS 'Foreign key to the locations table. Identifies the location this sample is at, if known.';
COMMENT ON COLUMN samples.date_start IS 'Start of the range of dates that this sample could have been made on.';
COMMENT ON COLUMN samples.date_end IS 'End of the range of dates that this sample could have been made on.';
COMMENT ON COLUMN samples.date_type IS 'Vague date type code. ';
COMMENT ON COLUMN samples.entered_sref IS 'Spatial reference that was entered for the sample.';
COMMENT ON COLUMN samples.entered_sref_system IS 'System that was used for the spatial reference in entered_sref.';
COMMENT ON COLUMN samples.geom IS 'WGS84 geometry describing the spatial reference of the sample. This describes the full grid square as a polygon for grid references, or a point for other spatial references.';
COMMENT ON COLUMN samples.location_name IS 'Free text name of the location or other locality information given for the sample.';
COMMENT ON COLUMN samples.created_on IS 'Date this record was created.';
COMMENT ON COLUMN samples.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN samples.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN samples.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN samples."comment" IS 'Comment regarding the sample.';
COMMENT ON COLUMN samples.external_key IS 'For samples imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';
COMMENT ON COLUMN samples.sample_method_id IS 'Foreign key to the termlists_terms table. Identifies the term which describes the sampling method.';
COMMENT ON COLUMN samples.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE site_roles IS 'List of roles that exist at the online recording website level.';
COMMENT ON COLUMN site_roles.created_on IS 'Date this record was created.';
COMMENT ON COLUMN site_roles.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN site_roles.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN site_roles.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN site_roles.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE surveys IS 'List of surveys known to the system.';
COMMENT ON COLUMN surveys.title IS 'Title of the survey.';
COMMENT ON COLUMN surveys.owner_id IS 'Foreign key to the people table. Identifies the person responsible for the survey.';
COMMENT ON COLUMN surveys.description IS 'Description of the survey.';
COMMENT ON COLUMN surveys.website_id IS 'Foreign key to the websites table. Identifies the website that the survey is available for.';
COMMENT ON COLUMN surveys.created_on IS 'Date this record was created.';
COMMENT ON COLUMN surveys.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN surveys.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN surveys.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN surveys.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE taxa IS 'List of taxa known to the system.';
COMMENT ON COLUMN taxa.taxon IS 'Term used for the taxon, excluding the authority.';
COMMENT ON COLUMN taxa.taxon_group_id IS 'Foreign key to the taxon_groups table. Identifies a label that describes the taxon''s higher level grouping.';
COMMENT ON COLUMN taxa.language_id IS 'Foreign key to the languages table. Identifies the language used for this taxon name.';
COMMENT ON COLUMN taxa.external_key IS 'For taxa which are directly mappable onto taxon records in an external system, identifies the external record''s key. For example, this is used to store the taxon version key from the NBN Gateway.';
COMMENT ON COLUMN taxa.authority IS 'Authority label for the taxon name.';
COMMENT ON COLUMN taxa.search_code IS 'A search code that may be used for rapid lookup of the taxon name.';
COMMENT ON COLUMN taxa.scientific IS 'Flag set to true if the name is a scientific name rather than vernacular.';
COMMENT ON COLUMN taxa.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxa.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxa.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxa.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxa.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE taxa_taxon_lists IS 'Join table that defines which taxa belong to which taxon lists.';
COMMENT ON COLUMN taxa_taxon_lists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxa_taxon_lists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxa_taxon_lists.parent_id IS 'Foreign key to the taxa table. Identifies the taxonomic parent, for example the genus of a species.';
COMMENT ON COLUMN taxa_taxon_lists.taxon_meaning_id IS 'Foreign key to the taxon_meanings table. Identifies the meaning of this taxon record. Eacg group of taxa with the same meaning are considered synonymous.';
COMMENT ON COLUMN taxa_taxon_lists.taxonomic_sort_order IS 'Provides a sort order which allows the taxon hierarchy to be displayed in taxonomic rather than alphabetical order.';
COMMENT ON COLUMN taxa_taxon_lists.preferred IS 'Flag set to true if the name constitutes the preferred name when selected amongst all taxa that have the same meaning.';
COMMENT ON COLUMN taxa_taxon_lists.updated_on IS 'Date this record was updated.';
COMMENT ON COLUMN taxa_taxon_lists.updated_by_id IS 'Foreign key to the users table (updater).';
COMMENT ON COLUMN taxa_taxon_lists.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE taxon_groups IS 'List of higher level taxonomic groups, used to give a label that can quickly confirm that a selected name is in the right taxonomic area.';
COMMENT ON COLUMN taxon_groups.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_groups.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_groups.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_groups.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxon_groups.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE taxon_lists IS 'List of taxon lists known to the system, including the main species list and all subsets.';
COMMENT ON COLUMN taxon_lists.title IS 'Title of the taxon list.';
COMMENT ON COLUMN taxon_lists.description IS 'Description of the taxon list.';
COMMENT ON COLUMN taxon_lists.website_id IS 'Foreign key to the websites table. Identifies the website that this list is available for, or null for lists available across all websites.';
COMMENT ON COLUMN taxon_lists.parent_id IS 'Foreign key to the taxon_lists table. For lists that are subsets of other taxon lists, identifies the parent list.';
COMMENT ON COLUMN taxon_lists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_lists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_lists.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_lists.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxon_lists.deleted IS 'Has this list been deleted?';
COMMENT ON TABLE taxon_meanings IS 'List of distinct taxonomic meanings. Each meaning is associated with several taxa records, each of which are therefore considered to be synonymous with the same species or other taxon.';
COMMENT ON TABLE termlists IS 'List of all controlled terminology lists known to the system. Each termlist is used to store a list of known terms, which can provide a lookup for populating a field, or the values which may be selected when entering data into an auto-complete text box for example.';
COMMENT ON COLUMN termlists.title IS 'Title of the termlist.';
COMMENT ON COLUMN termlists.description IS 'Description of the termlist.';
COMMENT ON COLUMN termlists.website_id IS 'Foreign key to the websites table. Identifies the website that this termlist is owned by, or null if indicialy owned.';
COMMENT ON COLUMN termlists.parent_id IS 'Foreign key to the termlists table. Identifies the parent list when a list is a subset of another.';
COMMENT ON COLUMN termlists.deleted IS 'Identifies if the termlist has been marked as deleted.';
COMMENT ON COLUMN termlists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN termlists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN termlists.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN termlists.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON TABLE termlists_terms IS 'Join table that identifies the terms that belong to each termlist.';
COMMENT ON COLUMN termlists_terms.termlist_id IS 'Foreign key to the termlists table. Identifies the termlist that the term is listed within.';
COMMENT ON COLUMN termlists_terms.term_id IS 'Foreign key to the terms table. Identifies the term that is listed within the termlist.';
COMMENT ON COLUMN termlists_terms.created_on IS 'Date this record was created.';
COMMENT ON COLUMN termlists_terms.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN termlists_terms.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN termlists_terms.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN termlists_terms.parent_id IS 'Foreign key to the termlist_terms table. For heirarchical data, identifies the parent term.';
COMMENT ON COLUMN termlists_terms.meaning_id IS 'Foreign key to the meaning table - identifies synonymous terms within this list.';
COMMENT ON COLUMN termlists_terms.preferred IS 'Flag set to true if the term is the preferred term amongst the group of terms with the same meaning.';
COMMENT ON COLUMN termlists_terms.sort_order IS 'Used to control sort ordering';
COMMENT ON COLUMN termlists_terms.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE terms IS 'Distinct list of all terms which are included in termlists.';
COMMENT ON COLUMN terms.term IS 'Term text.';
COMMENT ON COLUMN terms.language_id IS 'Foreign key to the languages table. Identifies the language used for the term.';
COMMENT ON COLUMN terms.created_on IS 'Date this record was created.';
COMMENT ON COLUMN terms.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN terms.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN terms.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN terms.deleted IS 'Has this term been deleted?';
COMMENT ON TABLE users IS 'List of all users of the system. Contains login specific information only as each user is also identified as a record in the people table.';
COMMENT ON COLUMN users.openid_url IS 'For users with an OpenID login, identifies their OpenID URL.';
COMMENT ON COLUMN users.home_entered_sref IS 'Spatial reference of the user''s home, if specified. This can be used to provide shortcuts when entering records, for example an "At my home" checkbox.';
COMMENT ON COLUMN users.home_entered_sref_system IS 'Spatial reference system used for the home_entered_sref value.';
COMMENT ON COLUMN users.home_geom IS 'Geometry of the home spatial reference. This is a polygon representing the grid square, or a point for other spatial references. Uses Latitude and Longitude in the WGS84 datum.';
COMMENT ON COLUMN users.interests IS 'The user''s interests specified in their profile.';
COMMENT ON COLUMN users.location_name IS 'Free text description of the user''s location, from their profile.';
COMMENT ON COLUMN users.person_id IS 'Foreign key to the people table. Identifies the person record that this user is associated with.';
COMMENT ON COLUMN users.email_visible IS 'Flag set to true if the user allows their email to be visible to other users.';
COMMENT ON COLUMN users.view_common_names IS 'Flag set to true if the user prefers common names for taxa over scientific names.';
COMMENT ON COLUMN users.core_role_id IS 'Foreign key to the core_roles table. Identifies the user''s role within the core module.';
COMMENT ON COLUMN users.created_on IS 'Date this record was created.';
COMMENT ON COLUMN users.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN users.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN users.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN users.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE users_websites IS 'Join table that identifies the websites that a user has access to.';
COMMENT ON COLUMN users_websites.user_id IS 'Foreign key to the users table. Identifies the user with access to the website.';
COMMENT ON COLUMN users_websites.website_id IS 'Foreign key to the websites table. Identifies the website accessible by the user.';
COMMENT ON COLUMN users_websites.deleted IS 'Indicates if the user account has been logically deleted from the website.';
COMMENT ON COLUMN users_websites.activated IS 'Flag indicating if the user''s account has been activated.';
COMMENT ON COLUMN users_websites.banned IS 'Flag indicating if the user''s account has been banned from this site.';
COMMENT ON COLUMN users_websites.activation_key IS 'Unique key used by the activation process.';
COMMENT ON COLUMN users_websites.site_role_id IS 'Foreign key to the site_roles table. Identifies the role of the user on this specific site.';
COMMENT ON COLUMN users_websites.registration_datetime IS 'Date and time of registration on this website.';
COMMENT ON COLUMN users_websites.last_login_datetime IS 'Date and time of last login to this website.';
COMMENT ON COLUMN users_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN users_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN users_websites.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN users_websites.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN users_websites.preferred_sref_system IS 'Spatial reference system used for data entry and viewing of spatial data by this user of the website.';
COMMENT ON TABLE websites IS 'List of data entry websites using this instance of the core module.';
COMMENT ON COLUMN websites.title IS 'Website title.';
COMMENT ON COLUMN websites.description IS 'Website description.';
COMMENT ON COLUMN websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN websites.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN websites.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN websites.url IS 'URL of the website root.';
COMMENT ON COLUMN websites.default_survey_id IS 'Survey which records for this website are created under if not specified by the data entry form.';
COMMENT ON COLUMN websites."password" IS 'Encrypted password for the website. Enables secure access to services.';
COMMENT ON COLUMN websites.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE system IS 'Contains system versioning information.';
COMMENT ON COLUMN system."version" IS 'Version number.';
COMMENT ON COLUMN system.name IS 'Version name.';
COMMENT ON COLUMN system.repository IS 'SVN repository path.';
COMMENT ON COLUMN system.release_date IS 'Release date for version.';
COMMENT ON TABLE occurrence_comments IS 'List of comments regarding the occurrence posted by users viewing the occurrence subsequent to initial data entry.';
COMMENT ON COLUMN occurrence_comments.created_by_id IS 'Foreign key to the users table (creator), if user was logged in when comment created.';
COMMENT ON COLUMN occurrence_comments.created_on IS 'Date and time this comment was created.';
COMMENT ON COLUMN occurrence_comments.updated_by_id IS 'Foreign key to the users table (updater), if user was logged in when comment updated.';
COMMENT ON COLUMN occurrence_comments.updated_on IS 'Date and time this comment was updated.';
COMMENT ON COLUMN occurrence_comments.occurrence_id IS 'Foreign key to the occurrences table. Identifies the commented occurrence.';
COMMENT ON COLUMN occurrence_comments.email_address IS 'Email of user who created the comment, if the user was not logged in but supplied an email address.';
COMMENT ON COLUMN occurrence_comments.deleted IS 'Has this record been deleted?';
COMMENT ON TABLE user_tokens IS 'Contains tokens stored in cookies used to authenticate users on the core module.';
COMMENT ON COLUMN user_tokens.user_id IS 'User who to whom this token belongs. Foreign key to the users table';
COMMENT ON COLUMN user_tokens.expires IS 'Date and time this token was expires.';
COMMENT ON COLUMN user_tokens.created IS 'Date and time this token was created.';
COMMENT ON COLUMN user_tokens.user_agent IS 'Hash of User agent details';
COMMENT ON COLUMN user_tokens.token IS 'Value of token stored in cookie';
COMMENT ON COLUMN titles.title IS 'Persons title';
COMMENT ON COLUMN titles.created_on IS 'Date this record was created.';
COMMENT ON COLUMN titles.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN titles.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN titles.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN titles.deleted IS 'Has this record been deleted?';
